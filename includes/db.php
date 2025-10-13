<?php
// Simple PDO MySQL connector for XAMPP
// Update credentials to match your local MySQL setup

declare(strict_types=1);

function getDbConfig(): array {
  // Defaults for XAMPP: user 'root' with empty password
  return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'inn_nexus',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => 'utf8mb4',
  ];
}

function getPdo(): ?PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }
  $cfg = getDbConfig();
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
  try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
  } catch (Throwable $e) {
    // Return null so pages can gracefully fallback to demo data
    return null;
  }
}

// --- Auth helpers ---
function initSession(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
}

function createUser(string $email, string $password, string $role = 'receptionist'): bool {
  $pdo = getPdo(); if (!$pdo) return false;
  try {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (:email,:hash,:role)');
    return $stmt->execute([':email' => strtolower(trim($email)), ':hash' => $hash, ':role' => $role]);
  } catch (Throwable $e) { return false; }
}

/**
 * Ensure a default admin exists for development environments.
 * Creates admin@example.com with password "password" if the users table is empty.
 */
function ensureDefaultAdmin(): void {
  $pdo = getPdo(); if (!$pdo) return;
  try {
    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count === 0) {
      $hash = password_hash('password', PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (:email,:hash,'admin')");
      $stmt->execute([':email' => 'admin@example.com', ':hash' => $hash]);
    }
  } catch (Throwable $e) {
    // ignore in case table doesn't exist yet
  }
}

function verifyLogin(string $email, string $password): bool {
  $pdo = getPdo(); if (!$pdo) return false;
  try {
    $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email=:email');
    $stmt->execute([':email' => strtolower(trim($email))]);
    $row = $stmt->fetch();
    if (!$row) return false;
    if (!password_verify($password, $row['password_hash'])) return false;
    initSession();
    $_SESSION['user_id'] = (int)$row['id'];
    $_SESSION['user_role'] = $row['role'];
    $_SESSION['user_email'] = strtolower(trim($email));
    return true;
  } catch (Throwable $e) { return false; }
}

function logout(): void { initSession(); session_unset(); session_destroy(); }

function currentUserRole(): ?string { initSession(); return $_SESSION['user_role'] ?? null; }
function currentUserEmail(): ?string { initSession(); return $_SESSION['user_email'] ?? null; }

function requireAuth(array $roles = []): void {
  initSession();
  $role = $_SESSION['user_role'] ?? null;
  if (!$role) {
    header('Location: login.php');
    exit;
  }
  if ($roles && !in_array($role, $roles, true)) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
  }
}

// --- 2FA helpers ---
function generateSecretKey(): string {
  $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $secret = '';
  for ($i = 0; $i < 32; $i++) {
    $secret .= $chars[random_int(0, strlen($chars) - 1)];
  }
  return $secret;
}

function generateQRCodeUrl(string $email, string $secret): string {
  $issuer = 'Core 1 PMS';
  $encodedIssuer = urlencode($issuer);
  $encodedEmail = urlencode($email);
  return "otpauth://totp/{$encodedIssuer}:{$encodedEmail}?secret={$secret}&issuer={$encodedIssuer}";
}

function verifyTOTPCode($userIdOrSecret, string $code): bool {
  // Handle both userId (int) and secret (string) for backward compatibility
  if (is_int($userIdOrSecret)) {
    $secret = get2FASecret($userIdOrSecret);
    if (!$secret) return false;
  } else {
    $secret = $userIdOrSecret;
  }
  
  $timeSlice = (int)floor(time() / 30);
  for ($i = -1; $i <= 1; $i++) {
    $calculatedCode = calculateTOTP($secret, (int)($timeSlice + $i));
    if (hash_equals($calculatedCode, $code)) {
      return true;
    }
  }
  return false;
}

function calculateTOTP(string $secret, int $timeSlice): string {
  $key = base32Decode($secret);
  $time = pack('N*', 0) . pack('N*', $timeSlice);
  $hm = hash_hmac('sha1', $time, $key, true);
  $offset = ord($hm[19]) & 0xf;
  $code = (
    ((ord($hm[$offset + 0]) & 0x7f) << 24) |
    ((ord($hm[$offset + 1]) & 0xff) << 16) |
    ((ord($hm[$offset + 2]) & 0xff) << 8) |
    (ord($hm[$offset + 3]) & 0xff)
  ) % 1000000;
  return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

function base32Decode(string $input): string {
  $map = [
    'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
    'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
    'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
    'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
  ];
  $input = strtoupper($input);
  $output = '';
  $v = 0;
  $vbits = 0;
  for ($i = 0; $i < strlen($input); $i++) {
    $v <<= 5;
    $v += $map[$input[$i]] ?? 0;
    $vbits += 5;
    if ($vbits >= 8) {
      $output .= chr(($v >> ($vbits - 8)) & 255);
      $vbits -= 8;
    }
  }
  return $output;
}

function setup2FA(int $userId, string $secret): bool {
  $pdo = getPdo();
  if (!$pdo) return false;
  try {
    $stmt = $pdo->prepare('UPDATE users SET totp_secret = :secret, totp_enabled = 1 WHERE id = :id');
    return $stmt->execute([':secret' => $secret, ':id' => $userId]);
  } catch (Throwable $e) {
    return false;
  }
}

function is2FAEnabled(int $userId): bool {
  $pdo = getPdo();
  if (!$pdo) return false;
  try {
    // Check both column names for compatibility
    $stmt = $pdo->prepare('SELECT totp_enabled, two_factor_enabled FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return (bool)($row['totp_enabled'] ?? false) || (bool)($row['two_factor_enabled'] ?? false);
  } catch (Throwable $e) {
    return false;
  }
}

function get2FASecret(int $userId): ?string {
  $pdo = getPdo();
  if (!$pdo) return null;
  try {
    // Check both column names for compatibility
    $stmt = $pdo->prepare('SELECT totp_secret, two_factor_secret FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();
    return $row['totp_secret'] ?? $row['two_factor_secret'] ?? null;
  } catch (Throwable $e) {
    return null;
  }
}

// --- Security helpers ---
function generateCSRFToken(): string {
  initSession();
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verifyCSRFToken(string $token): bool {
  initSession();
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput(string $input): string {
  return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validatePassword(string $password): array {
  $errors = [];
  if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
  return $errors;
}

function fetchAllGuests(): array {
  $pdo = getPdo();
  if (!$pdo) return [];
  try {
    $sql = 'SELECT id, name, email, phone, stays, tier, last_visit AS lastVisit FROM guests ORDER BY name';
    return $pdo->query($sql)->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}

function fetchAllReservations(): array {
  $pdo = getPdo();
  if (!$pdo) return [];
  try {
    $query = "
      SELECT 
        r.id,
        r.guest_name as guest,
        r.room_number as room,
        r.check_in_date as checkin,
        r.check_out_date as checkout,
        LOWER(r.status) as status,
        DATEDIFF(r.check_out_date, r.check_in_date) as nights,
        r.total_amount as rate,
        r.contact_number,
        r.special_requests,
        r.notes,
        g.email as guest_email,
        g.phone as guest_phone,
        rm.room_type
      FROM reservations r
      LEFT JOIN guests g ON r.guest_id = g.id
      LEFT JOIN rooms rm ON r.room_id = rm.id
      ORDER BY r.check_in_date DESC, r.created_at DESC
    ";
    
    $stmt = $pdo->query($query);
    $reservations = $stmt->fetchAll();
    
    // Format the data to match the expected structure
    return array_map(function($res) {
      return [
        'id' => $res['id'],
        'guest' => $res['guest'],
        'room' => $res['room'],
        'checkin' => $res['checkin'],
        'checkout' => $res['checkout'],
        'status' => strtolower($res['status']),
        'nights' => (int)$res['nights'],
        'rate' => (float)$res['rate'],
        'guest_email' => $res['guest_email'] ?? null,
        'guest_phone' => $res['guest_phone'] ?? null,
        'room_type' => $res['room_type'] ?? null,
        'contact_number' => $res['contact_number'],
        'special_requests' => $res['special_requests'],
        'notes' => $res['notes']
      ];
    }, $reservations);
  } catch (Throwable $e) {
    // Log the error for debugging
    error_log('Error fetching reservations: ' . $e->getMessage());
    return [];
  }
}

function fetchDashboardStats(): array {
  $pdo = getPdo();
  if (!$pdo) return [];
  try {
    // Guests in-house: any reservation spanning today
    $inHouse = (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE CURDATE() BETWEEN checkin AND checkout AND status IN ('checked-in','confirmed')")->fetchColumn();
    // ADR for current month
    $avgRate = (float)$pdo->query("SELECT AVG(rate) FROM reservations WHERE MONTH(checkin)=MONTH(CURDATE()) AND YEAR(checkin)=YEAR(CURDATE())")->fetchColumn();
    // Simple revenue proxy: sum of rate for stays that include today
    $todayRevenue = (float)$pdo->query("SELECT SUM(rate) FROM reservations WHERE CURDATE() BETWEEN checkin AND checkout")->fetchColumn();

    // Occupancy: use rooms inventory if available
    $totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name='rooms'")->fetchColumn() > 0
      ? (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn()
      : 0;
    $occRooms = 0;
    if ($totalRooms > 0) {
      $occRooms = (int)$pdo->query("SELECT COUNT(DISTINCT room) FROM reservations WHERE CURDATE() BETWEEN checkin AND checkout AND status IN ('checked-in','confirmed')")->fetchColumn();
    }
    $occupancy = $totalRooms > 0 ? max(0, min(100, (int)round(($occRooms / $totalRooms) * 100))) : 87;
    return [
      'occupancy' => $occupancy,
      'inHouse' => $inHouse,
      'todayRevenue' => $todayRevenue,
      'avgRate' => $avgRate,
    ];
  } catch (Throwable $e) {
    return [];
  }
}

function fetchArrivals(): array {
  $pdo = getPdo();
  if (!$pdo) return [];
  try {
    $stmt = $pdo->query("SELECT id, guest_name AS name, room, DATE_FORMAT(checkin, '%H:%i') AS time, 'pending' AS status FROM reservations WHERE checkin = CURDATE() ORDER BY time");
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}

function createGuest(array $data): bool {
  $pdo = getPdo();
  if (!$pdo) return false;
  try {
    $stmt = $pdo->prepare('INSERT INTO guests (name, email, phone, stays, tier, last_visit) VALUES (:name,:email,:phone,:stays,:tier,:last_visit)');
    return $stmt->execute([
      ':name' => trim($data['name'] ?? ''),
      ':email' => trim($data['email'] ?? ''),
      ':phone' => trim($data['phone'] ?? ''),
      ':stays' => (int)($data['stays'] ?? 0),
      ':tier' => $data['tier'] ?? 'member',
      ':last_visit' => $data['last_visit'] ?? null,
    ]);
  } catch (Throwable $e) {
    return false;
  }
}

function createReservation(array $data): bool {
  $pdo = getPdo();
  if (!$pdo) return false;
  try {
    $checkin = $data['checkin'] ?? null;
    $checkout = $data['checkout'] ?? null;
    $nights = 1;
    if ($checkin && $checkout) {
      $nights = max(1, (int)round((strtotime($checkout) - strtotime($checkin)) / 86400));
    }
    $stmt = $pdo->prepare('INSERT INTO reservations (id, guest_name, room, checkin, checkout, status, nights, rate) VALUES (:id,:guest_name,:room,:checkin,:checkout,:status,:nights,:rate)');
    $id = $data['id'] ?? ('RES-' . strtoupper(bin2hex(random_bytes(3))));
    return $stmt->execute([
      ':id' => $id,
      ':guest_name' => trim($data['guest_name'] ?? ''),
      ':room' => trim($data['room'] ?? ''),
      ':checkin' => $checkin,
      ':checkout' => $checkout,
      ':status' => $data['status'] ?? 'confirmed',
      ':nights' => $nights,
      ':rate' => (float)($data['rate'] ?? 0),
    ]);
  } catch (Throwable $e) {
    return false;
  }
}

function fetchDepartures(): array {
  $pdo = getPdo();
  if (!$pdo) return [];
  try {
    $stmt = $pdo->query("SELECT id, guest_name AS name, room, DATE_FORMAT(checkout, '%H:%i') AS time, 'pending' AS status FROM reservations WHERE checkout = CURDATE() ORDER BY time");
    return $stmt->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}


