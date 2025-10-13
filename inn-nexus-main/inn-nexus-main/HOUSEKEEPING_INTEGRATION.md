# Housekeeping & Rooms Integration

## 📋 Overview

This integration synchronizes the **Rooms Overview** and **Housekeeping** sections in real-time, ensuring data consistency across both modules. When a room's status changes, it automatically updates in both sections, and when housekeeping tasks are completed, rooms are automatically marked as ready.

---

## 🎯 Features

### ✅ Real-Time Synchronization
- **Automatic Updates**: Changes in one section immediately reflect in the other
- **Polling System**: Updates every 5 seconds to keep data fresh
- **Event-Driven**: Uses listeners to notify components of changes

### ✅ Room Status Management
- **Color-Coded Status**: Consistent visual indicators across both pages
  - 🟢 **Green**: Vacant (ready for guests)
  - 🔴 **Red**: Occupied
  - 🟠 **Orange**: Cleaning/For Cleaning
  - ⚫ **Gray**: Maintenance
  - 🔵 **Blue**: Reserved

### ✅ Housekeeping Task Workflow
- **Pending → In Progress → Completed**: Clear task progression
- **Priority Levels**: Normal, High, Urgent
- **Automatic Room Updates**: Completing a task marks the room as "Vacant"

### ✅ Audit Trail
- All status changes are logged in `room_status_logs` table
- Tracks who made the change and when
- Includes reason for the change

---

## 🗄️ Database Schema

### Tables Created

#### 1. `housekeeping_tasks`
```sql
- id: INT (Primary Key)
- room_id: INT (Foreign Key → rooms.id)
- room_number: VARCHAR(10)
- task_type: ENUM('cleaning', 'deep_clean', 'maintenance', 'inspection')
- status: ENUM('pending', 'in-progress', 'completed', 'maintenance')
- priority: ENUM('normal', 'high', 'urgent')
- assigned_to: VARCHAR(100) NULL
- guest_name: VARCHAR(200) NULL
- notes: TEXT NULL
- started_at: TIMESTAMP NULL
- completed_at: TIMESTAMP NULL
- created_at, updated_at: TIMESTAMP
```

#### 2. `rooms` (Enhanced)
```sql
- guest_name: VARCHAR(200) NULL [NEW]
- housekeeping_status: ENUM('clean', 'dirty', 'cleaning', 'inspected') [NEW]
```

#### 3. `room_status_logs` (Enhanced)
```sql
- Logs all status changes for audit trail
- Includes change reason and who made the change
```

---

## 🔌 API Endpoints

### Rooms API

#### `GET /api/rooms`
Fetch all rooms with status information
```json
{
  "data": [
    {
      "id": 1,
      "room_number": "101",
      "room_type": "Single",
      "floor_number": 1,
      "status": "Vacant",
      "guest_name": null,
      "housekeeping_status": "clean"
    }
  ]
}
```

#### `PATCH /api/rooms`
Update room status
```json
// Request
{
  "id": 1,
  "status": "Cleaning",
  "guestName": "John Doe",
  "notes": "AC repair needed"
}

// Response
{
  "ok": true,
  "roomNumber": "101"
}
```

### Housekeeping API

#### `GET /api/housekeeping`
Fetch all housekeeping tasks
```json
{
  "data": [
    {
      "id": 1,
      "room_id": 1,
      "room_number": "101",
      "task_type": "cleaning",
      "status": "pending",
      "priority": "normal",
      "guest_name": null,
      "notes": "Extra towels requested"
    }
  ],
  "stats": {
    "pending": 5,
    "in_progress": 2,
    "completed": 10,
    "maintenance": 1,
    "efficiency": 85
  }
}
```

#### `PATCH /api/housekeeping/{taskId}`
Update task status
```json
// Request
{
  "status": "completed",
  "assignedTo": "Maria Santos"
}

// Response
{
  "ok": true,
  "message": "Task updated successfully"
}
```

#### `POST /api/housekeeping`
Create new task
```json
// Request
{
  "room_id": 1,
  "room_number": "101",
  "task_type": "cleaning",
  "priority": "high",
  "notes": "VIP guest arriving"
}

// Response
{
  "ok": true,
  "id": 15,
  "message": "Task created successfully"
}
```

#### `GET /api/rooms/housekeeping`
Get rooms needing housekeeping
```json
{
  "data": [
    {
      "id": 1,
      "room_number": "101",
      "status": "Cleaning",
      "housekeeping_status": "dirty",
      "has_task": 1,
      "task_status": "pending"
    }
  ]
}
```

---

## 🚀 Setup Instructions

### Step 1: Run Database Migration
```bash
# In phpMyAdmin or MySQL CLI
mysql -u root -p inn_nexus < database/01_housekeeping_integration.sql
```

This will:
- Create the `housekeeping_tasks` table
- Add new columns to `rooms` table
- Set up database triggers
- Insert sample data

### Step 2: Include Required Files

In your PHP pages, include:
```php
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/housekeeping.php';
?>
```

### Step 3: Add JavaScript Sync Library

In your HTML `<head>` section:
```html
<script src="./public/js/hotel-sync.js"></script>
```

### Step 4: Update Existing Pages

#### Option A: Replace Existing Files
```bash
# Backup originals first
cp rooms-overview.php rooms-overview.php.backup
cp housekeeping.php housekeeping.php.backup

# Use updated versions
cp rooms-overview-updated.js rooms-overview.php
cp housekeeping-updated.php housekeeping.php
```

#### Option B: Manual Integration
See the code examples in the updated files and integrate into your existing pages.

---

## 💻 Frontend Integration

### Initialize Sync System
```javascript
// Auto-initializes on page load
// Or manually:
await hotelSync.init();
```

### Listen for Updates
```javascript
// Listen for room updates
hotelSync.onRoomsUpdate((rooms) => {
  console.log('Rooms updated:', rooms);
  renderRooms(rooms);
});

// Listen for housekeeping updates
hotelSync.onHousekeepingUpdate((tasks) => {
  console.log('Tasks updated:', tasks);
  renderTasks(tasks);
});

// Listen for stats updates
hotelSync.onStatsUpdate((stats) => {
  console.log('Stats updated:', stats);
  updateDashboard(stats);
});
```

### Update Room Status
```javascript
// Update room status
await hotelSync.updateRoom(
  roomId,           // Room ID
  'Cleaning',       // New status
  'John Doe',       // Guest name (optional)
  'AC repair'       // Notes (optional)
);
```

### Update Housekeeping Task
```javascript
// Start a task
await hotelSync.updateTask(taskId, 'in-progress');

// Complete a task
await hotelSync.updateTask(taskId, 'completed');

// Assign task to someone
await hotelSync.updateTask(taskId, 'in-progress', 'Maria Santos');
```

### Create New Task
```javascript
await hotelSync.createTask({
  room_id: 1,
  room_number: '101',
  task_type: 'cleaning',
  priority: 'high',
  notes: 'VIP guest'
});
```

---

## 🔄 Data Flow

### When Room Status Changes:

1. **User Action**: Staff member changes room status in Rooms Overview
2. **API Call**: `PATCH /api/rooms` updates the database
3. **Database Trigger**: Automatically creates/updates housekeeping task
4. **Audit Log**: Records the change in `room_status_logs`
5. **Sync Update**: Next polling cycle fetches updated data
6. **UI Update**: Both Rooms and Housekeeping pages reflect the change

### When Housekeeping Task Completes:

1. **User Action**: Housekeeping staff marks task as "Completed"
2. **API Call**: `PATCH /api/housekeeping/{id}` updates task
3. **Room Update**: Automatically sets room status to "Vacant"
4. **Housekeeping Status**: Sets `housekeeping_status` to "clean"
5. **Audit Log**: Records the completion
6. **Sync Update**: Next polling cycle fetches updated data
7. **UI Update**: Room appears as "Vacant" in Rooms Overview

---

## 🎨 UI Consistency

### Status Colors (CSS Classes)
```css
.room-vacant {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.room-occupied {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.room-cleaning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.room-maintenance {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
  color: white;
}
```

### Icons (using Lucide)
- Vacant: `check-circle`
- Occupied: `user`
- Cleaning: `loader`
- Maintenance: `wrench`
- Reserved: `clock`

---

## 🔧 Troubleshooting

### Issue: Updates not appearing in real-time
**Solution**: 
- Check browser console for errors
- Verify `/api/rooms` and `/api/housekeeping` endpoints are accessible
- Ensure `hotel-sync.js` is loaded before page-specific scripts

### Issue: "No database connection" error
**Solution**:
- Verify database credentials in `includes/db.php`
- Ensure `inn_nexus` database exists
- Check if tables were created successfully

### Issue: Room status not updating after task completion
**Solution**:
- Check `housekeeping_tasks` table has correct `room_id`
- Verify `updateHousekeepingTask()` function in `includes/housekeeping.php`
- Check database trigger is active: `SHOW TRIGGERS;`

### Issue: Duplicate tasks being created
**Solution**:
- Check if database trigger is firing multiple times
- Ensure only one instance of the page is updating the same room

---

## 📊 Performance Considerations

### Polling Interval
Default: 5 seconds
```javascript
// Adjust in hotel-sync.js
this.pollInterval = 10000; // 10 seconds
```

### Database Indexes
Ensure these indexes exist:
- `rooms.id` (Primary Key)
- `rooms.room_number`
- `rooms.status`
- `housekeeping_tasks.room_id`
- `housekeeping_tasks.status`
- `housekeeping_tasks.created_at`

### Optimization Tips
1. Use database connection pooling
2. Cache frequently accessed data
3. Consider WebSockets for large-scale deployments
4. Add pagination for large task lists

---

## 🔐 Security

### API Protection
- All endpoints check user authentication
- CSRF protection on state-changing operations
- Input sanitization on all user inputs
- SQL injection prevention via prepared statements

### Role-Based Access
```php
requireAuth(['admin', 'receptionist']); // In both pages
```

### Audit Trail
All changes are logged with:
- User who made the change
- Timestamp
- Old and new status
- Reason for change

---

## 🧪 Testing

### Test Workflow 1: Room to Housekeeping
1. Open **Rooms Overview** page
2. Change a room to "Cleaning" status
3. Open **Housekeeping** page (or refresh if already open)
4. Verify a new "Pending" task appears

### Test Workflow 2: Housekeeping to Room
1. Open **Housekeeping** page
2. Click "Start Task" on a pending task
3. Click "Mark Complete"
4. Open **Rooms Overview** page (or wait for refresh)
5. Verify the room status is now "Vacant"

### Test Workflow 3: Real-Time Sync
1. Open both pages side-by-side
2. Make a change in one page
3. Wait 5-10 seconds
4. Verify the change appears in the other page

---

## 📈 Future Enhancements

### Planned Features
- [ ] WebSocket support for instant updates
- [ ] Staff assignment and notifications
- [ ] Task scheduling and recurring tasks
- [ ] Mobile app integration
- [ ] Advanced analytics and reporting
- [ ] Integration with PMS/POS systems

### Suggested Improvements
- Add task time tracking
- Implement task templates
- Add photo attachments for maintenance
- Create housekeeper performance reports
- Add guest preferences tracking

---

## 📞 Support

For issues or questions:
1. Check this documentation first
2. Review the API endpoints in `api/index.php`
3. Check database functions in `includes/housekeeping.php`
4. Review the sync module in `public/js/hotel-sync.js`

---

**Last Updated**: October 2025  
**Version**: 1.0.0  
**Compatibility**: PHP 7.4+, MySQL 5.7+

