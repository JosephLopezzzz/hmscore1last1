/**
 * Hotel Data Synchronization Module
 * Real-time synchronization between Rooms and Housekeeping sections
 */

class HotelDataSync {
    constructor() {
        this.rooms = [];
        this.housekeepingTasks = [];
        this.stats = {};
        this.listeners = {
            roomsUpdate: [],
            housekeepingUpdate: [],
            statsUpdate: []
        };
        this.pollInterval = 5000; // Poll every 5 seconds
        this.pollTimer = null;
    }

    /**
     * Initialize the sync system
     */
    async init() {
        await this.loadData();
        this.startPolling();
        return this;
    }

    /**
     * Load all data from API
     */
    async loadData() {
        try {
            await Promise.all([
                this.fetchRooms(),
                this.fetchHousekeepingTasks()
            ]);
        } catch (error) {
            console.error('Failed to load hotel data:', error);
        }
    }

    /**
     * Get base API URL
     */
    getApiBase() {
        // Robustly derive base: find '/inn-nexus-main' in path if present
        const path = window.location.pathname;
        const idx = path.indexOf('/inn-nexus-main');
        if (idx !== -1) {
            const root = path.substring(0, idx + '/inn-nexus-main'.length);
            return window.location.origin + root + '/api';
        }
        // Fallback to current directory api
        const basePath = path.substring(0, path.lastIndexOf('/'));
        return window.location.origin + basePath + '/api';
    }

    /**
     * Fetch rooms data
     */
    async fetchRooms() {
        try {
            const response = await fetch(this.getApiBase() + '/rooms');
            const data = await response.json();
            this.rooms = data.data || [];
            this.notifyListeners('roomsUpdate', this.rooms);
            return this.rooms;
        } catch (error) {
            console.error('Failed to fetch rooms:', error);
            return [];
        }
    }

    /**
     * Fetch housekeeping tasks
     */
    async fetchHousekeepingTasks(status = null) {
        try {
            const url = status ? `${this.getApiBase()}/housekeeping?status=${status}` : `${this.getApiBase()}/housekeeping`;
            const response = await fetch(url);
            const data = await response.json();
            this.housekeepingTasks = data.data || [];
            this.stats = data.stats || {};
            this.notifyListeners('housekeepingUpdate', this.housekeepingTasks);
            this.notifyListeners('statsUpdate', this.stats);
            return this.housekeepingTasks;
        } catch (error) {
            console.error('Failed to fetch housekeeping tasks:', error);
            return [];
        }
    }

    /**
     * Update room status
     */
    async updateRoomStatus(roomId, status, guestName = null, notes = null) {
        try {
            const response = await fetch(this.getApiBase() + '/rooms', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: roomId,
                    status: status,
                    guestName: guestName,
                    notes: notes
                })
            });

            const data = await response.json();
            
            if (data.ok) {
                // Refresh both rooms and housekeeping data
                await this.loadData();
                this.showToast('Room status updated successfully', 'success');
                return true;
            } else {
                this.showToast('Failed to update room status', 'error');
                return false;
            }
        } catch (error) {
            console.error('Failed to update room:', error);
            this.showToast('Failed to update room status', 'error');
            return false;
        }
    }

    /**
     * Update housekeeping task
     */
    async updateHousekeepingTask(taskId, status, assignedTo = null) {
        try {
            const response = await fetch(`${this.getApiBase()}/housekeeping/${taskId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    assignedTo: assignedTo
                })
            });

            const data = await response.json();
            
            if (data.ok) {
                // Refresh both rooms and housekeeping data
                await this.loadData();
                this.showToast(data.message || 'Task updated successfully', 'success');
                return true;
            } else {
                this.showToast('Failed to update task', 'error');
                return false;
            }
        } catch (error) {
            console.error('Failed to update task:', error);
            this.showToast('Failed to update task', 'error');
            return false;
        }
    }

    /**
     * Create housekeeping task
     */
    async createHousekeepingTask(taskData) {
        try {
            const response = await fetch(this.getApiBase() + '/housekeeping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(taskData)
            });

            const data = await response.json();
            
            if (data.ok) {
                await this.loadData();
                this.showToast('Task created successfully', 'success');
                return data.id;
            } else {
                this.showToast('Failed to create task', 'error');
                return null;
            }
        } catch (error) {
            console.error('Failed to create task:', error);
            this.showToast('Failed to create task', 'error');
            return null;
        }
    }

    /**
     * Add listener for data updates
     */
    on(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event].push(callback);
        }
    }

    /**
     * Remove listener
     */
    off(event, callback) {
        if (this.listeners[event]) {
            this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
        }
    }

    /**
     * Notify all listeners of an event
     */
    notifyListeners(event, data) {
        if (this.listeners[event]) {
            this.listeners[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`Error in ${event} listener:`, error);
                }
            });
        }
    }

    /**
     * Start polling for updates
     */
    startPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
        }
        
        this.pollTimer = setInterval(() => {
            this.loadData();
        }, this.pollInterval);
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    /**
     * Get room by ID
     */
    getRoom(roomId) {
        return this.rooms.find(room => room.id === roomId);
    }

    /**
     * Get room by number
     */
    getRoomByNumber(roomNumber) {
        return this.rooms.find(room => room.room_number === roomNumber);
    }

    /**
     * Get task by ID
     */
    getTask(taskId) {
        return this.housekeepingTasks.find(task => task.id === taskId);
    }

    /**
     * Get tasks for a specific room
     */
    getTasksForRoom(roomNumber) {
        return this.housekeepingTasks.filter(task => task.room_number === roomNumber);
    }

    /**
     * Get status color class
     */
    getStatusColor(status) {
        const colors = {
            'Vacant': 'green',
            'Occupied': 'red',
            'Cleaning': 'orange',
            'Maintenance': 'gray',
            'Reserved': 'blue'
        };
        return colors[status] || 'gray';
    }

    /**
     * Get status icon
     */
    getStatusIcon(status) {
        const icons = {
            'Vacant': 'check-circle',
            'Occupied': 'user',
            'Cleaning': 'loader',
            'Maintenance': 'wrench',
            'Reserved': 'clock'
        };
        return icons[status] || 'circle';
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Check if custom toast function exists
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
            return;
        }

        // Fallback to basic notification
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' :
            type === 'warning' ? 'bg-orange-500' :
            'bg-blue-500'
        } text-white`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Destroy the sync system
     */
    destroy() {
        this.stopPolling();
        this.listeners = {
            roomsUpdate: [],
            housekeepingUpdate: [],
            statsUpdate: []
        };
        this.rooms = [];
        this.housekeepingTasks = [];
        this.stats = {};
    }
}

// Create global instance
window.HotelSync = new HotelDataSync();

// Helper functions for easy access
window.hotelSync = {
    init: () => window.HotelSync.init(),
    updateRoom: (roomId, status, guestName, notes) => 
        window.HotelSync.updateRoomStatus(roomId, status, guestName, notes),
    updateTask: (taskId, status, assignedTo) => 
        window.HotelSync.updateHousekeepingTask(taskId, status, assignedTo),
    createTask: (taskData) => 
        window.HotelSync.createHousekeepingTask(taskData),
    onRoomsUpdate: (callback) => 
        window.HotelSync.on('roomsUpdate', callback),
    onHousekeepingUpdate: (callback) => 
        window.HotelSync.on('housekeepingUpdate', callback),
    onStatsUpdate: (callback) => 
        window.HotelSync.on('statsUpdate', callback),
    getRooms: () => window.HotelSync.rooms,
    getTasks: () => window.HotelSync.housekeepingTasks,
    getStats: () => window.HotelSync.stats
};

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Hotel Data Sync initialized');
    });
} else {
    console.log('Hotel Data Sync ready');
}

