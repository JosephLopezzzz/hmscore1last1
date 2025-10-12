<?php
/**
 * Housekeeping Data Functions
 * Handles all housekeeping-related database operations
 */

declare(strict_types=1);

/**
 * Fetch all housekeeping tasks with room information
 */
function fetchHousekeepingTasks(?string $status = null): array {
    $pdo = getPdo();
    if (!$pdo) return [];
    
    try {
        $sql = "
            SELECT 
                ht.id,
                ht.room_id,
                ht.room_number,
                ht.task_type,
                ht.status,
                ht.priority,
                ht.assigned_to,
                ht.guest_name,
                ht.notes,
                ht.started_at,
                ht.completed_at,
                ht.created_at,
                ht.updated_at,
                r.room_type,
                r.floor_number,
                r.status as room_status
            FROM housekeeping_tasks ht
            LEFT JOIN rooms r ON ht.room_id = r.id
        ";
        
        if ($status !== null) {
            $sql .= " WHERE ht.status = :status";
        }
        
        $sql .= " ORDER BY 
            CASE ht.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
            END,
            ht.created_at DESC";
        
        if ($status !== null) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } else {
            return $pdo->query($sql)->fetchAll();
        }
    } catch (Throwable $e) {
        error_log("fetchHousekeepingTasks error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update housekeeping task status
 */
function updateHousekeepingTask(int $taskId, string $status, ?string $assignedTo = null): bool {
    $pdo = getPdo();
    if (!$pdo) return false;
    
    try {
        $pdo->beginTransaction();
        
        // Fetch task info
        $stmt = $pdo->prepare("SELECT room_id, room_number, status as old_status FROM housekeeping_tasks WHERE id = :id");
        $stmt->execute([':id' => $taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            $pdo->rollBack();
            return false;
        }
        
        // Update task
        $sql = "UPDATE housekeeping_tasks SET status = :status";
        $params = [':status' => $status, ':id' => $taskId];
        
        if ($status === 'in-progress' && $task['old_status'] !== 'in-progress') {
            $sql .= ", started_at = NOW()";
        }
        
        if ($status === 'completed' && $task['old_status'] !== 'completed') {
            $sql .= ", completed_at = NOW()";
        }
        
        if ($assignedTo !== null) {
            $sql .= ", assigned_to = :assigned_to";
            $params[':assigned_to'] = $assignedTo;
        }
        
        $sql .= ", updated_at = NOW() WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Update room status based on task completion
        if ($status === 'completed') {
            $roomStmt = $pdo->prepare("
                UPDATE rooms 
                SET status = 'Vacant', 
                    housekeeping_status = 'clean',
                    guest_name = NULL
                WHERE id = :room_id
            ");
            $roomStmt->execute([':room_id' => $task['room_id']]);
            
            // Log the status change
            logRoomStatusChange($task['room_number'], 'Cleaning/Maintenance', 'Vacant', 'Housekeeping completed', 'System');
        } elseif ($status === 'in-progress') {
            $roomStmt = $pdo->prepare("
                UPDATE rooms 
                SET status = 'Cleaning',
                    housekeeping_status = 'cleaning'
                WHERE id = :room_id
            ");
            $roomStmt->execute([':room_id' => $task['room_id']]);
        }
        
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log("updateHousekeepingTask error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new housekeeping task
 */
function createHousekeepingTask(array $data): ?int {
    $pdo = getPdo();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO housekeeping_tasks (
                room_id, room_number, task_type, status, priority, 
                assigned_to, guest_name, notes
            ) VALUES (
                :room_id, :room_number, :task_type, :status, :priority,
                :assigned_to, :guest_name, :notes
            )
        ");
        
        $result = $stmt->execute([
            ':room_id' => $data['room_id'] ?? null,
            ':room_number' => $data['room_number'] ?? '',
            ':task_type' => $data['task_type'] ?? 'cleaning',
            ':status' => $data['status'] ?? 'pending',
            ':priority' => $data['priority'] ?? 'normal',
            ':assigned_to' => $data['assigned_to'] ?? null,
            ':guest_name' => $data['guest_name'] ?? null,
            ':notes' => $data['notes'] ?? null,
        ]);
        
        return $result ? (int)$pdo->lastInsertId() : null;
    } catch (Throwable $e) {
        error_log("createHousekeepingTask error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get housekeeping statistics
 */
function getHousekeepingStats(): array {
    $pdo = getPdo();
    if (!$pdo) return [];
    
    try {
        $stats = [
            'pending' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'maintenance' => 0,
            'total' => 0,
            'efficiency' => 0
        ];
        
        $stmt = $pdo->query("
            SELECT 
                status,
                COUNT(*) as count
            FROM housekeeping_tasks
            WHERE DATE(created_at) = CURDATE()
            GROUP BY status
        ");
        
        while ($row = $stmt->fetch()) {
            $status = str_replace('-', '_', $row['status']);
            $stats[$status] = (int)$row['count'];
            $stats['total'] += (int)$row['count'];
        }
        
        // Calculate efficiency (completed / total tasks today)
        if ($stats['total'] > 0) {
            $stats['efficiency'] = round(($stats['completed'] / $stats['total']) * 100);
        } else {
            $stats['efficiency'] = 100;
        }
        
        return $stats;
    } catch (Throwable $e) {
        error_log("getHousekeepingStats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Log room status changes for audit trail
 */
function logRoomStatusChange(string $roomNumber, string $oldStatus, string $newStatus, string $reason = '', string $changedBy = ''): bool {
    $pdo = getPdo();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO room_status_logs (
                room_number, previous_status, new_status, changed_by, change_reason
            ) VALUES (
                :room_number, :previous_status, :new_status, :changed_by, :change_reason
            )
        ");
        
        return $stmt->execute([
            ':room_number' => $roomNumber,
            ':previous_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':changed_by' => $changedBy ?: ($_SESSION['user_email'] ?? 'System'),
            ':change_reason' => $reason
        ]);
    } catch (Throwable $e) {
        error_log("logRoomStatusChange error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch rooms that need housekeeping
 */
function fetchRoomsNeedingHousekeeping(): array {
    $pdo = getPdo();
    if (!$pdo) return [];
    
    try {
        return $pdo->query("
            SELECT 
                r.id,
                r.room_number,
                r.room_type,
                r.floor_number,
                r.status,
                r.housekeeping_status,
                r.guest_name,
                r.last_cleaned,
                COALESCE(ht.id, 0) as has_task,
                COALESCE(ht.status, 'none') as task_status
            FROM rooms r
            LEFT JOIN housekeeping_tasks ht ON r.id = ht.room_id AND ht.status != 'completed'
            WHERE r.status IN ('Cleaning', 'Maintenance') 
               OR r.housekeeping_status = 'dirty'
               OR r.last_cleaned < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY 
                CASE r.status
                    WHEN 'Maintenance' THEN 1
                    WHEN 'Cleaning' THEN 2
                    ELSE 3
                END,
                r.floor_number,
                r.room_number
        ")->fetchAll();
    } catch (Throwable $e) {
        error_log("fetchRoomsNeedingHousekeeping error: " . $e->getMessage());
        return [];
    }
}

