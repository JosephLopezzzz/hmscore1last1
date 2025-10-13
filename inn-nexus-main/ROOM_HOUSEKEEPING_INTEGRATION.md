# Rooms & Housekeeping Integration

## Overview
The Rooms Overview and Housekeeping Dashboard are fully synchronized in real-time. Changes in one section automatically reflect in the other within 5 seconds.

---

## ✅ Integration Features

### 1. **Real-Time Synchronization**
- **Polling Interval**: Every 5 seconds
- **Auto-Refresh**: Both pages update automatically
- **Shared Data**: Both sections share the same database tables

### 2. **Automatic Status Updates**

#### When Housekeeping Task is Started (`in-progress`):
- ✅ Room status changes to **"Cleaning"**
- ✅ `housekeeping_status` set to `"cleaning"`
- ✅ Update timestamp recorded

#### When Housekeeping Task is Completed (`completed`):
- ✅ Room status changes to **"Vacant"**
- ✅ `housekeeping_status` set to `"clean"`
- ✅ Guest name cleared (if any)
- ✅ `completed_at` timestamp recorded
- ✅ Status change logged in audit trail

#### When Room Status is Changed:
- ✅ If set to "Cleaning" → May trigger housekeeping task creation
- ✅ If set to "Maintenance" → Housekeeping notified
- ✅ All changes logged for audit

### 3. **Data Flow**

```
┌─────────────────────┐         ┌──────────────────┐
│  Rooms Overview     │◄───────►│  hotel-sync.js   │
│  (rooms-overview.php)│         │  (Sync Module)   │
└─────────────────────┘         └──────────────────┘
                                         ▲
                                         │
                                         ▼
┌─────────────────────┐         ┌──────────────────┐
│  Housekeeping       │◄───────►│   REST API       │
│  (housekeeping.php) │         │  (api/index.php) │
└─────────────────────┘         └──────────────────┘
                                         ▲
                                         │
                                         ▼
                                ┌──────────────────┐
                                │   Database       │
                                │  - rooms         │
                                │  - housekeeping  │
                                │  - status_logs   │
                                └──────────────────┘
```

---

## 🔧 Technical Implementation

### **Frontend Sync Module**
**File**: `public/js/hotel-sync.js`

```javascript
// Initialize sync
await hotelSync.init();

// Listen for updates
hotelSync.onRoomsUpdate(handleRoomsUpdate);
hotelSync.onHousekeepingUpdate(handleTasksUpdate);

// Update room status
await hotelSync.updateRoom(roomId, 'Cleaning', guestName, notes);

// Update task status
await hotelSync.updateTask(taskId, 'completed');
```

### **Backend Integration**
**File**: `includes/housekeeping.php`

**Key Functions:**
- `updateHousekeepingTask()` - Updates task and syncs room status
- `fetchHousekeepingTasks()` - Gets all tasks with room info
- `logRoomStatusChange()` - Audit trail for all changes

### **API Endpoints**

#### **GET /api/rooms**
- Returns all rooms with current status
- Includes housekeeping status
- Updated every 5 seconds by both pages

#### **PATCH /api/rooms**
- Update room status
- Accepts: `id`, `status`, `guestName`, `notes`
- Returns: `{ok: true, roomNumber: "101"}`

#### **GET /api/housekeeping**
- Returns all housekeeping tasks
- Includes room information
- Returns stats (pending, in-progress, completed)

#### **PATCH /api/housekeeping/:id**
- Update task status
- **Auto-updates room status** when completed
- Accepts: `status`, `assignedTo`
- Returns: `{ok: true, message: "Task updated successfully"}`

---

## 📊 Database Schema

### **rooms** table
```sql
- id (INT, PRIMARY KEY)
- room_number (VARCHAR) - "101", "201", etc.
- room_type (VARCHAR) - "Single", "Double", "Suite", etc.
- floor_number (INT)
- status (ENUM) - "Vacant", "Occupied", "Cleaning", "Maintenance"
- housekeeping_status (ENUM) - "clean", "dirty", "cleaning", "inspected"
- guest_name (VARCHAR, nullable)
- maintenance_notes (TEXT, nullable)
- updated_at (TIMESTAMP)
```

### **housekeeping_tasks** table
```sql
- id (INT, PRIMARY KEY)
- room_id (INT, FOREIGN KEY → rooms.id)
- room_number (VARCHAR)
- task_type (ENUM) - "cleaning", "maintenance", "inspection"
- status (ENUM) - "pending", "in-progress", "completed", "maintenance"
- priority (ENUM) - "urgent", "high", "normal"
- assigned_to (VARCHAR, nullable)
- guest_name (VARCHAR, nullable)
- notes (TEXT, nullable)
- started_at (TIMESTAMP, nullable)
- completed_at (TIMESTAMP, nullable)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### **room_status_logs** table
```sql
- id (INT, PRIMARY KEY)
- room_number (VARCHAR)
- previous_status (VARCHAR)
- new_status (VARCHAR)
- changed_by (VARCHAR)
- change_reason (TEXT)
- created_at (TIMESTAMP)
```

---

## 🎯 User Workflow

### **Receptionist View (Rooms Overview)**
1. View all rooms by floor
2. Click a room to see details
3. Change status (Vacant → Cleaning)
4. Housekeeping dashboard updates automatically

### **Housekeeper View (Housekeeping Dashboard)**
1. See all pending tasks
2. Click "Start Task" → Room status changes to "Cleaning"
3. After cleaning, click "Mark Complete" → Room status changes to "Vacant"
4. Rooms Overview updates automatically

---

## 🔄 Status Mapping

| Room Status | Housekeeping Status | Task Status |
|------------|-------------------|-------------|
| Vacant | clean | N/A |
| Occupied | N/A | N/A |
| Cleaning | cleaning | in-progress |
| Maintenance | N/A | maintenance |

---

## 🎨 Visual Indicators

### **Status Colors (Both Themes)**

| Status | Light Mode | Dark Mode |
|--------|-----------|-----------|
| Vacant | Green (#10b981) | Green (#10b981) |
| Occupied | Red (#ef4444) | Red (#ef4444) |
| Cleaning | Orange (#f59e0b) | Orange (#f59e0b) |
| Maintenance | Gray (#6b7280) | Gray (#6b7280) |

---

## 🔒 Security & Audit

### **Audit Trail**
- All status changes logged in `room_status_logs`
- Includes: who, when, why, old status, new status
- Viewable by admin for compliance

### **Access Control**
- Rooms Overview: `admin`, `receptionist`
- Housekeeping: `admin`, `receptionist`
- API: Session-based authentication required

---

## 🚀 Performance

- **Polling**: 5-second intervals (configurable)
- **Batch Updates**: Multiple changes queued and processed together
- **Optimized Queries**: Indexed on `room_number`, `status`, `floor_number`
- **Real-Time**: Average sync delay < 5 seconds

---

## 📝 Future Enhancements

### Planned Features:
- [ ] WebSocket support for instant updates (0-delay)
- [ ] Push notifications for urgent tasks
- [ ] Task assignment by staff member
- [ ] Estimated completion times
- [ ] Task history and analytics
- [ ] Mobile app integration

---

## 🛠️ Troubleshooting

### **Rooms not updating?**
1. Check browser console for errors
2. Verify API is accessible: `/api/rooms`, `/api/housekeeping`
3. Check database connection
4. Ensure polling is active (check Network tab)

### **Task completion not updating room?**
1. Check `housekeeping_tasks` table for task record
2. Verify `room_id` foreign key is correct
3. Check `room_status_logs` for audit trail
4. Ensure database transaction completed successfully

---

## 📞 Support

For issues or questions:
- Check the browser console for errors
- Review the database logs
- Verify all SQL scripts have been run
- Contact: admin@inn-nexus.com

---

**Last Updated**: 2025-10-12  
**Version**: 1.0.0  
**Status**: ✅ Fully Operational

