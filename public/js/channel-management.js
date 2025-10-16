/**
 * Channel Management JavaScript
 * Handles all channel management interactions and real-time updates
 */

class ChannelManager {
    constructor() {
        this.channels = [];
        this.rates = [];
        this.availability = [];
        this.syncInProgress = new Set();

        this.init();
    }

    async init() {
        await this.loadChannels();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    async loadChannels() {
        try {
            const response = await fetch('./api/channel-actions.php?action=get_channels');
            const data = await response.json();

            if (data.success) {
                this.channels = data.channels || [];
                this.updateChannelUI();
            }
        } catch (error) {
            console.error('Error loading channels:', error);
        }
    }

    async loadRates(channelId = null) {
        try {
            const url = channelId
                ? `./api/channel-actions.php?action=get_rates&channel_id=${channelId}`
                : './api/channel-actions.php?action=get_rates';

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.rates = data.rates || [];
                this.updateRatesUI();
            }
        } catch (error) {
            console.error('Error loading rates:', error);
        }
    }

    async loadAvailability(channelId, roomType) {
        try {
            const response = await fetch(`./api/channel-actions.php?action=get_availability&channel_id=${channelId}&room_type=${roomType}`);
            const data = await response.json();

            if (data.success) {
                this.availability = data.availability || [];
                this.updateAvailabilityUI();
            }
        } catch (error) {
            console.error('Error loading availability:', error);
        }
    }

    async syncChannel(channelId) {
        if (this.syncInProgress.has(channelId)) {
            return;
        }

        this.syncInProgress.add(channelId);

        try {
            const response = await fetch('./api/channel-actions.php?action=sync_channel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `channel_id=${channelId}`
            });

            const data = await response.json();

            if (data.success) {
                await this.loadChannels(); // Refresh data
                this.showNotification('Channel synced successfully!', 'success');
            } else {
                throw new Error(data.error || 'Sync failed');
            }
        } catch (error) {
            console.error('Error syncing channel:', error);
            this.showNotification('Failed to sync channel. Please try again.', 'error');
        } finally {
            this.syncInProgress.delete(channelId);
        }
    }

    async syncAllChannels() {
        const activeChannels = this.channels.filter(c => c.status === 'Active');

        if (activeChannels.length === 0) {
            this.showNotification('No active channels to sync.', 'warning');
            return;
        }

        this.showNotification(`Syncing ${activeChannels.length} channels...`, 'info');

        try {
            const response = await fetch('./api/channel-actions.php?action=sync_all', {
                method: 'POST'
            });

            const data = await response.json();

            if (data.success) {
                await this.loadChannels(); // Refresh data
                this.showNotification(data.message, 'success');
            } else {
                throw new Error(data.error || 'Bulk sync failed');
            }
        } catch (error) {
            console.error('Error syncing all channels:', error);
            this.showNotification('Failed to sync channels. Please try again.', 'error');
        }
    }

    async testConnection(channelId) {
        try {
            const response = await fetch('./api/channel-actions.php?action=test_connection', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `channel_id=${channelId}`
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Connection test successful!', 'success');
                return data.details;
            } else {
                throw new Error(data.error || 'Connection test failed');
            }
        } catch (error) {
            console.error('Error testing connection:', error);
            this.showNotification('Connection test failed. Please check your settings.', 'error');
            return null;
        }
    }

    async addChannel(channelData) {
        try {
            const response = await fetch('./api/channel-actions.php?action=add_channel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(channelData)
            });

            const data = await response.json();

            if (data.success) {
                await this.loadChannels(); // Refresh data
                this.showNotification('Channel added successfully!', 'success');
                return data.channel_id;
            } else {
                throw new Error(data.error || 'Failed to add channel');
            }
        } catch (error) {
            console.error('Error adding channel:', error);
            this.showNotification('Failed to add channel. Please try again.', 'error');
            return null;
        }
    }

    async updateChannel(channelId, channelData) {
        try {
            const response = await fetch('./api/channel-actions.php?action=update_channel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ...channelData, id: channelId })
            });

            const data = await response.json();

            if (data.success) {
                await this.loadChannels(); // Refresh data
                this.showNotification('Channel updated successfully!', 'success');
                return true;
            } else {
                throw new Error(data.error || 'Failed to update channel');
            }
        } catch (error) {
            console.error('Error updating channel:', error);
            this.showNotification('Failed to update channel. Please try again.', 'error');
            return false;
        }
    }

    async deleteChannel(channelId) {
        if (!confirm('Are you sure you want to delete this channel? This action cannot be undone.')) {
            return false;
        }

        try {
            const response = await fetch(`./api/channel-actions.php?action=delete_channel&id=${channelId}`);
            const data = await response.json();

            if (data.success) {
                await this.loadChannels(); // Refresh data
                this.showNotification('Channel deleted successfully!', 'success');
                return true;
            } else {
                throw new Error(data.error || 'Failed to delete channel');
            }
        } catch (error) {
            console.error('Error deleting channel:', error);
            this.showNotification('Failed to delete channel. Please try again.', 'error');
            return false;
        }
    }

    setupEventListeners() {
        // Global event listeners for modal interactions
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="sync-channel"]')) {
                const channelId = e.target.getAttribute('data-channel-id');
                this.syncChannel(channelId);
            }

            if (e.target.matches('[data-action="edit-channel"]')) {
                const channelId = e.target.getAttribute('data-channel-id');
                this.editChannel(channelId);
            }

            if (e.target.matches('[data-action="delete-channel"]')) {
                const channelId = e.target.getAttribute('data-channel-id');
                this.deleteChannel(channelId);
            }

            if (e.target.matches('[data-action="test-connection"]')) {
                const channelId = e.target.getAttribute('data-channel-id');
                this.testConnection(channelId);
            }
        });

        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.loadChannels();
        }, 30000);
    }

    startAutoRefresh() {
        // Refresh data every 30 seconds
        setInterval(() => {
            this.loadChannels();
        }, 30000);
    }

    updateChannelUI() {
        // Update channel cards in the main dashboard
        const container = document.getElementById('channelsContainer');
        if (container) {
            this.renderChannelCards(container);
        }

        // Update overview statistics
        this.updateOverviewStats();

        // Trigger custom event for other components
        document.dispatchEvent(new CustomEvent('channelsUpdated', {
            detail: { channels: this.channels }
        }));
    }

    updateRatesUI() {
        // Trigger custom event for rates components
        document.dispatchEvent(new CustomEvent('ratesUpdated', {
            detail: { rates: this.rates }
        }));
    }

    updateAvailabilityUI() {
        // Trigger custom event for availability components
        document.dispatchEvent(new CustomEvent('availabilityUpdated', {
            detail: { availability: this.availability }
        }));
    }

    renderChannelCards(container) {
        if (!this.channels || this.channels.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="globe" class="w-16 h-16 text-muted-foreground mx-auto mb-4"></i>
                    <p class="text-muted-foreground">No channels configured yet.</p>
                    <a href="channels/ota" class="mt-2 text-primary hover:text-primary/80 text-sm underline inline-block">
                        Add your first channel
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = this.channels.map(channel => this.createChannelCard(channel)).join('');
    }

    createChannelCard(channel) {
        const statusClass = this.getStatusClass(channel.status);
        const syncStatusClass = this.getSyncStatusClass(channel.sync_status);
        const lastSync = channel.last_sync ? new Date(channel.last_sync).toLocaleString() : 'Never';

        return `
            <div class="channel-card ${statusClass} rounded-lg p-6 shadow-sm" data-channel-id="${channel.id}">
                <div class="flex flex-col justify-between h-full">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold text-lg">${this.escapeHtml(channel.display_name)}</h3>
                            <span class="text-xs opacity-90 px-2 py-1 rounded-full bg-black/20">
                                ${this.escapeHtml(channel.type)}
                            </span>
                        </div>
                        <p class="text-sm opacity-90 mb-3">${this.escapeHtml(channel.name)}</p>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-xs opacity-75">Commission:</span>
                            <span class="text-sm font-medium">${channel.commission_rate}%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs opacity-75">Last Sync:</span>
                            <span class="text-xs opacity-90">${lastSync}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs opacity-75">Sync Status:</span>
                            <span class="text-xs px-2 py-1 rounded-full ${syncStatusClass} text-white">
                                ${channel.sync_status}
                            </span>
                        </div>
                        ${channel.room_mappings ? `
                            <div class="flex justify-between items-center">
                                <span class="text-xs opacity-75">Room Mappings:</span>
                                <span class="text-sm font-medium">${channel.room_mappings}</span>
                            </div>
                        ` : ''}
                    </div>

                    <div class="flex gap-2 mt-4">
                        <button data-action="sync-channel" data-channel-id="${channel.id}"
                                class="flex-1 bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded text-sm transition-colors"
                                ${this.syncInProgress.has(channel.id) ? 'disabled' : ''}>
                            ${this.syncInProgress.has(channel.id) ? 'Syncing...' : 'Sync'}
                        </button>
                        <button data-action="edit-channel" data-channel-id="${channel.id}"
                                class="flex-1 bg-white/20 hover:bg-white/30 text-white px-3 py-2 rounded text-sm transition-colors">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    updateOverviewStats() {
        const totalChannelsEl = document.getElementById('totalChannels');
        const activeChannelsEl = document.getElementById('activeChannels');
        const lastSyncEl = document.getElementById('lastSync');

        if (totalChannelsEl) totalChannelsEl.textContent = this.channels.length;
        if (activeChannelsEl) activeChannelsEl.textContent = this.channels.filter(c => c.status === 'Active').length;

        // Find most recent sync
        const lastSyncChannel = this.channels
            .filter(c => c.last_sync)
            .sort((a, b) => new Date(b.last_sync) - new Date(a.last_sync))[0];

        if (lastSyncEl && lastSyncChannel) {
            const lastSyncTime = new Date(lastSyncChannel.last_sync).toLocaleString();
            lastSyncEl.textContent = lastSyncTime;
        }
    }

    editChannel(channelId) {
        window.location.href = `channels/ota?edit=${channelId}`;
    }

    getStatusClass(status) {
        const statusMap = {
            'Active': 'channel-active',
            'Inactive': 'channel-inactive',
            'Maintenance': 'channel-maintenance',
            'Error': 'channel-error'
        };
        return statusMap[status] || 'channel-inactive';
    }

    getSyncStatusClass(syncStatus) {
        const syncMap = {
            'Success': 'sync-success',
            'Failed': 'sync-failed',
            'Pending': 'sync-pending',
            'In Progress': 'sync-pending'
        };
        return syncMap[syncStatus] || 'sync-pending';
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;

        notification.innerHTML = `
            <div class="flex items-center">
                <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : type === 'warning' ? 'alert-triangle' : 'info'}" class="w-5 h-5 mr-2"></i>
                ${this.escapeHtml(message)}
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.channelManager = new ChannelManager();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ChannelManager;
}
