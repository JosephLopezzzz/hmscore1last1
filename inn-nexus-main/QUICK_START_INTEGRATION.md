# 🚀 Quick Start: Housekeeping Integration

## ⚡ 5-Minute Setup

### Step 1: Run Database Script (2 minutes)
```bash
# Open phpMyAdmin
# Select 'inn_nexus' database
# Go to SQL tab
# Copy and paste content from: database/01_housekeeping_integration.sql
# Click "Go"
```

### Step 2: Update Your Files (2 minutes)

#### Replace `rooms-overview.php`
Add this line after the existing `<script src="./public/css/tokens.css" />`:
```html
<script src="./public/js/hotel-sync.js"></script>
```

Then replace the JavaScript section with the code from `rooms-overview-updated.js`

#### Replace `housekeeping.php`
Simply copy the entire content from `housekeeping-updated.php`

### Step 3: Test (1 minute)
1. Open Rooms Overview page
2. Open Housekeeping page in another tab
3. Change a room status to "Cleaning"
4. Wait 5 seconds
5. Check Housekeeping page - you should see the new task!

---

## ✅ What You Get

### Real-Time Synchronization
- ✅ Room status changes appear in Housekeeping automatically
- ✅ Completed housekeeping tasks mark rooms as "Vacant"  
- ✅ Updates every 5 seconds
- ✅ Consistent UI colors across both pages

### Status Colors
- 🟢 **Green** = Vacant (ready)
- 🔴 **Red** = Occupied
- 🟠 **Orange** = Cleaning
- ⚫ **Gray** = Maintenance
- 🔵 **Blue** = Reserved

### Task Workflow
1. **Pending** → Click "Start Task"
2. **In Progress** → Click "Mark Complete"
3. **Completed** → Room automatically becomes "Vacant"

---

## 📦 Files Created

### Database
- `database/01_housekeeping_integration.sql` - Database schema

### Backend (PHP)
- `includes/housekeeping.php` - Housekeeping functions
- `api/index.php` - Updated with new endpoints

### Frontend (JavaScript)
- `public/js/hotel-sync.js` - Sync module
- `rooms-overview-updated.js` - Updated room page logic
- `housekeeping-updated.php` - Complete housekeeping page

### Documentation
- `HOUSEKEEPING_INTEGRATION.md` - Full documentation
- `QUICK_START_INTEGRATION.md` - This file!

---

## 🔌 API Endpoints Added

### For Rooms
- `GET /api/rooms` - Fetch all rooms
- `PATCH /api/rooms` - Update room status

### For Housekeeping
- `GET /api/housekeeping` - Fetch tasks + stats
- `PATCH /api/housekeeping/{id}` - Update task
- `POST /api/housekeeping` - Create task
- `GET /api/rooms/housekeeping` - Get rooms needing service

---

## 🎯 Usage Examples

### In JavaScript (Frontend)
```javascript
// Update room status
await hotelSync.updateRoom(1, 'Cleaning', 'John Doe', 'Deep clean');

// Complete housekeeping task
await hotelSync.updateTask(5, 'completed');

// Listen for updates
hotelSync.onRoomsUpdate((rooms) => {
  console.log('Rooms updated!', rooms);
});
```

### In PHP (Backend)
```php
// Fetch all tasks
$tasks = fetchHousekeepingTasks();

// Update task status
updateHousekeepingTask(5, 'completed', 'Maria Santos');

// Get statistics
$stats = getHousekeepingStats();
```

---

## 🐛 Troubleshooting

### Problem: "No tasks appear"
**Fix**: Make sure database script ran successfully. Check `housekeeping_tasks` table exists.

### Problem: "Updates don't appear"
**Fix**: Check browser console for errors. Verify `hotel-sync.js` is loaded.

### Problem: "Room doesn't change after completing task"
**Fix**: Check `updateHousekeepingTask()` function in `includes/housekeeping.php`

---

## 📚 Need More Help?

- **Full Documentation**: See `HOUSEKEEPING_INTEGRATION.md`
- **API Reference**: Check `api/index.php` for all endpoints
- **Database Functions**: Review `includes/housekeeping.php`

---

## 🎉 That's It!

You now have a fully integrated Rooms & Housekeeping system with real-time synchronization!

**Next Steps:**
1. Customize the UI to match your brand
2. Add more task types
3. Integrate with staff management
4. Add mobile app support

---

**Questions?** Check the full documentation in `HOUSEKEEPING_INTEGRATION.md`

