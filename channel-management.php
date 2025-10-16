<?php
  // PHP session and auth MUST be at the very top before any HTML
  require_once __DIR__ . '/includes/db.php';
  requireAuth(['admin','receptionist']);
?>
<!doctype html>
<html lang="en" class="">
  <head>
    <!-- Theme initialization (must be first to prevent flash) -->
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Primary Meta Tags -->
    <title>Channel Management - Inn Nexus Hotel Management System</title>
    <meta name="title" content="Channel Management - Inn Nexus Hotel Management System" />
    <meta name="description" content="Manage OTA connections, rates, and availability across all distribution channels for Inn Nexus hotel." />
    <meta name="keywords" content="channel management, OTA, booking.com, expedia, agoda, rate management, availability, hotel distribution" />
    <meta name="author" content="Inn Nexus Team" />
    <meta name="robots" content="noindex, nofollow" />

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="./public/favicon.svg" />
    <link rel="icon" type="image/png" href="./public/favicon.ico" />
    <link rel="apple-touch-icon" href="./public/favicon.svg" />

    <!-- Theme Color -->
    <meta name="theme-color" content="#3b82f6" />

    <!-- Stylesheets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <script src="./public/js/hotel-sync.js"></script>

    <!-- Security -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
    <style>
      .channel-card {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
      }
      .channel-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      }
      .channel-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
      }
      .channel-inactive {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
      }
      .channel-maintenance {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
      }
      .channel-error {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
      }
      .sync-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
      }
      .sync-failed {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
      }
      .sync-pending {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
      }
      .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
      }
      .dot-active { background: #10b981; }
      .dot-inactive { background: #ef4444; }
      .dot-maintenance { background: #f59e0b; }
      .dot-error { background: #8b5cf6; }
      .dot-success { background: #10b981; }
      .dot-failed { background: #ef4444; }
      .dot-pending { background: #f59e0b; }
    </style>
  </head>
  <body class="min-h-screen bg-background">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Channel Management</h1>
        <p class="text-muted-foreground mb-6">Manage OTA connections, rates, and availability across all distribution channels</p>

        <!-- Quick Actions -->
        <div class="flex flex-wrap gap-3 mb-6">
          <button id="syncAllBtn" class="bg-primary text-primary-foreground px-4 py-2 rounded-md hover:bg-primary/90 transition-colors">
            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2 inline"></i>
            Sync All Channels
          </button>
          <button id="addChannelBtn" class="bg-secondary text-secondary-foreground px-4 py-2 rounded-md hover:bg-secondary/80 transition-colors">
            <i data-lucide="plus" class="w-4 h-4 mr-2 inline"></i>
            Add Channel
          </button>
          <button id="viewRatesBtn" class="bg-outline text-foreground px-4 py-2 rounded-md border hover:bg-accent transition-colors">
            <i data-lucide="tag" class="w-4 h-4 mr-2 inline"></i>
            Manage Rates
          </button>
          <button id="viewAvailabilityBtn" class="bg-outline text-foreground px-4 py-2 rounded-md border hover:bg-accent transition-colors">
            <i data-lucide="calendar" class="w-4 h-4 mr-2 inline"></i>
            Availability
          </button>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-6 mb-6">
          <div class="flex items-center">
            <span class="legend-dot dot-active"></span>
            <span class="text-sm font-medium">Active</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-inactive"></span>
            <span class="text-sm font-medium">Inactive</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-maintenance"></span>
            <span class="text-sm font-medium">Maintenance</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-error"></span>
            <span class="text-sm font-medium">Error</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-success"></span>
            <span class="text-sm font-medium">Sync Success</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-failed"></span>
            <span class="text-sm font-medium">Sync Failed</span>
          </div>
          <div class="flex items-center">
            <span class="legend-dot dot-pending"></span>
            <span class="text-sm font-medium">Sync Pending</span>
          </div>
        </div>

        <!-- Filters -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 md:gap-4 mb-2">
          <div>
            <label for="filterStatus" class="block text-xs text-muted-foreground mb-1">Status</label>
            <select id="filterStatus" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Error">Error</option>
            </select>
          </div>
          <div>
            <label for="filterType" class="block text-xs text-muted-foreground mb-1">Type</label>
            <select id="filterType" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All types</option>
              <option value="OTA">OTA</option>
              <option value="GDS">GDS</option>
              <option value="Direct">Direct</option>
              <option value="Wholesale">Wholesale</option>
              <option value="Corporate">Corporate</option>
            </select>
          </div>
          <div>
            <label for="filterSyncStatus" class="block text-xs text-muted-foreground mb-1">Sync Status</label>
            <select id="filterSyncStatus" class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground text-sm">
              <option value="all">All sync statuses</option>
              <option value="Success">Success</option>
              <option value="Failed">Failed</option>
              <option value="Pending">Pending</option>
              <option value="In Progress">In Progress</option>
            </select>
          </div>
          <div>
            <label for="filterSearch" class="block text-xs text-muted-foreground mb-1">Search</label>
            <input id="filterSearch" type="text" placeholder="Channel name..." class="w-full px-3 py-2 rounded-md border border-border bg-background text-foreground placeholder:text-muted-foreground text-sm" />
          </div>
        </div>
      </div>

      <!-- Channel Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Channels</p>
              <p id="totalChannels" class="text-3xl font-bold text-foreground">0</p>
            </div>
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
              <i data-lucide="globe" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
            </div>
          </div>
        </div>

        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Active Channels</p>
              <p id="activeChannels" class="text-3xl font-bold text-green-600">0</p>
            </div>
            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
              <i data-lucide="check-circle" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
            </div>
          </div>
        </div>

        <div class="bg-card rounded-lg border p-6 shadow-sm">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Last Sync</p>
              <p id="lastSync" class="text-lg font-bold text-foreground">Never</p>
            </div>
            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
              <i data-lucide="clock" class="w-5 h-5 text-orange-600 dark:text-orange-400"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Channels Grid -->
      <div id="channelsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Channels will be dynamically generated here -->
      </div>
    </main>

    <!-- Channel Detail Modal -->
    <div id="channelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-card text-card-foreground rounded-lg p-6 w-full max-w-md mx-4 shadow-xl border border-border">
        <div class="flex items-center justify-between mb-4">
          <h2 id="modalChannelName" class="text-xl font-bold text-foreground">Channel Name</h2>
          <button id="closeChannelModal" class="text-muted-foreground hover:text-foreground">
            <i data-lucide="x" class="h-5 w-5"></i>
          </button>
        </div>

        <div class="space-y-3">
          <p id="modalChannelType" class="text-sm text-muted-foreground">Type: OTA</p>
          <p id="modalChannelStatus" class="text-sm text-muted-foreground">Status: Active</p>
          <p id="modalChannelCommission" class="text-sm text-muted-foreground">Commission: 15%</p>
          <p id="modalLastSync" class="text-sm text-muted-foreground">Last Sync: Never</p>
          <p id="modalSyncStatus" class="text-sm text-muted-foreground">Sync Status: Pending</p>
        </div>

        <div class="flex gap-3 pt-6">
          <button id="modalSyncChannel" class="flex-1 rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground hover:bg-primary/90">
            Sync Now
          </button>
          <button id="modalEditChannel" class="flex-1 rounded-md border border-border px-4 py-2 text-sm hover:bg-muted bg-background text-foreground">
            Edit
          </button>
          <button id="modalCloseChannel" class="flex-1 rounded-md bg-secondary text-secondary-foreground px-4 py-2 text-sm hover:bg-secondary/80">
            Close
          </button>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>window.lucide && window.lucide.createIcons();</script>

    <script>
      // Channel data
      let channels = [];

      // UI filters
      const filters = {
        status: 'all',
        type: 'all',
        syncStatus: 'all',
        search: ''
      };

      // Status class mapping
      const statusClassMap = {
        "Active": "channel-active",
        "Inactive": "channel-inactive",
        "Maintenance": "channel-maintenance",
        "Error": "channel-error"
      };

      const syncStatusClassMap = {
        "Success": "sync-success",
        "Failed": "sync-failed",
        "Pending": "sync-pending",
        "In Progress": "sync-pending"
      };

      const channelsContainer = document.getElementById("channelsContainer");
      let currentChannelModal = null;

      // Initialize
      async function init() {
        await loadChannels();
        setupFilters();
        setupEventListeners();
        renderChannels();
        updateOverviewStats();
      }

      // Load channels from API
      async function loadChannels() {
        try {
          const response = await fetch('./api/channel-actions.php?action=get_channels');
          const data = await response.json();
          if (data.success) {
            channels = data.channels || [];
          } else {
            console.error('Failed to load channels:', data.error);
            channels = [];
          }
        } catch (error) {
          console.error('Error loading channels:', error);
          channels = [];
        }
      }

      // Setup filters
      function setupFilters() {
        const statusEl = document.getElementById('filterStatus');
        const typeEl = document.getElementById('filterType');
        const syncStatusEl = document.getElementById('filterSyncStatus');
        const searchEl = document.getElementById('filterSearch');

        if (statusEl) statusEl.addEventListener('change', () => { filters.status = statusEl.value; renderChannels(); });
        if (typeEl) typeEl.addEventListener('change', () => { filters.type = typeEl.value; renderChannels(); });
        if (syncStatusEl) syncStatusEl.addEventListener('change', () => { filters.syncStatus = syncStatusEl.value; renderChannels(); });
        if (searchEl) searchEl.addEventListener('input', () => { filters.search = searchEl.value; renderChannels(); });
      }

      // Setup event listeners
      function setupEventListeners() {
        // Sync all channels
        const syncAllBtn = document.getElementById('syncAllBtn');
        if (syncAllBtn) {
          syncAllBtn.addEventListener('click', syncAllChannels);
        }

        // Add new channel
        const addChannelBtn = document.getElementById('addChannelBtn');
        if (addChannelBtn) {
          addChannelBtn.addEventListener('click', () => {
            window.location.href = '/channels/ota';
          });
        }

        // Quick access buttons
        const viewRatesBtn = document.getElementById('viewRatesBtn');
        if (viewRatesBtn) {
          viewRatesBtn.addEventListener('click', () => {
            window.location.href = '/channels/rates';
          });
        }

        const viewAvailabilityBtn = document.getElementById('viewAvailabilityBtn');
        if (viewAvailabilityBtn) {
          viewAvailabilityBtn.addEventListener('click', () => {
            window.location.href = '/channels/availability';
          });
        }
      }

      // Render channels
      function renderChannels() {
        if (!channelsContainer) return;

        const normalizedSearch = (filters.search || '').toString().trim().toLowerCase();

        // Apply filters
        const visibleChannels = channels.filter(channel => {
          const statusOk = filters.status === 'all' || channel.status === filters.status;
          const typeOk = filters.type === 'all' || channel.type === filters.type;
          const syncStatusOk = filters.syncStatus === 'all' || channel.sync_status === filters.syncStatus;
          if (!normalizedSearch) return statusOk && typeOk && syncStatusOk;
          const hay = `${channel.name || ''} ${channel.display_name || ''}`.toLowerCase();
          return statusOk && typeOk && syncStatusOk && hay.includes(normalizedSearch);
        });

        if (visibleChannels.length === 0) {
          channelsContainer.innerHTML = `
            <div class="col-span-full text-center py-12">
              <i data-lucide="globe" class="w-16 h-16 text-muted-foreground mx-auto mb-4"></i>
              <p class="text-muted-foreground">No channels found matching the current filters.</p>
              <button id="clearFiltersBtn" class="mt-2 text-primary hover:text-primary/80 text-sm underline">
                Clear filters
              </button>
            </div>
          `;

          // Clear filters functionality
          const clearFiltersBtn = document.getElementById('clearFiltersBtn');
          if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => {
              filters.status = 'all';
              filters.type = 'all';
              filters.syncStatus = 'all';
              filters.search = '';
              document.getElementById('filterStatus').value = 'all';
              document.getElementById('filterType').value = 'all';
              document.getElementById('filterSyncStatus').value = 'all';
              document.getElementById('filterSearch').value = '';
              renderChannels();
            });
          }
          return;
        }

        channelsContainer.innerHTML = visibleChannels.map(channel => createChannelCard(channel)).join('');
      }

      // Create channel card
      function createChannelCard(channel) {
        const statusClass = statusClassMap[channel.status] || "channel-inactive";
        const syncStatusClass = syncStatusClassMap[channel.sync_status] || "sync-pending";
        const lastSync = channel.last_sync ? new Date(channel.last_sync).toLocaleString() : 'Never';

        return `
          <div class="channel-card ${statusClass} rounded-lg p-6 shadow-sm" data-channel-id="${channel.id}">
            <div class="flex flex-col justify-between h-full">
              <div>
                <div class="flex items-center justify-between mb-2">
                  <h3 class="font-bold text-lg">${channel.display_name}</h3>
                  <span class="text-xs opacity-90 px-2 py-1 rounded-full bg-black/20">
                    ${channel.type}
                  </span>
                </div>
                <p class="text-sm opacity-90 mb-3">${channel.name}</p>
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
              </div>
            </div>
          </div>
        `;
      }

      // Update overview statistics
      function updateOverviewStats() {
        const totalChannelsEl = document.getElementById('totalChannels');
        const activeChannelsEl = document.getElementById('activeChannels');
        const lastSyncEl = document.getElementById('lastSync');

        if (totalChannelsEl) totalChannelsEl.textContent = channels.length;
        if (activeChannelsEl) activeChannelsEl.textContent = channels.filter(c => c.status === 'Active').length;

        // Find most recent sync
        const lastSyncChannel = channels
          .filter(c => c.last_sync)
          .sort((a, b) => new Date(b.last_sync) - new Date(a.last_sync))[0];

        if (lastSyncEl && lastSyncChannel) {
          const lastSyncTime = new Date(lastSyncChannel.last_sync).toLocaleString();
          lastSyncEl.textContent = lastSyncTime;
        }
      }

      // Sync all channels
      async function syncAllChannels() {
        const syncBtn = document.getElementById('syncAllBtn');
        const originalText = syncBtn.innerHTML;
        syncBtn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 mr-2 inline animate-spin"></i> Syncing...';
        syncBtn.disabled = true;

        try {
          const response = await fetch('./api/channel-actions.php?action=sync_all');
          const data = await response.json();

          if (data.success) {
            // Refresh channels data
            await loadChannels();
            renderChannels();
            updateOverviewStats();

            // Show success message
            showNotification('All channels synced successfully!', 'success');
          } else {
            showNotification('Failed to sync some channels. Check individual channel status.', 'error');
          }
        } catch (error) {
          console.error('Error syncing channels:', error);
          showNotification('Error syncing channels. Please try again.', 'error');
        }

        // Restore button
        syncBtn.innerHTML = originalText;
        syncBtn.disabled = false;
      }

      // Show notification
      function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
          type === 'success' ? 'bg-green-500 text-white' :
          type === 'error' ? 'bg-red-500 text-white' :
          'bg-blue-500 text-white'
        }`;

        notification.innerHTML = `
          <div class="flex items-center">
            <i data-lucide="${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info'}" class="w-5 h-5 mr-2"></i>
            ${message}
          </div>
        `;

        document.body.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
          notification.remove();
        }, 5000);
      }

      // Add click handlers to channel cards after rendering
      function addChannelCardListeners() {
        const channelCards = document.querySelectorAll('.channel-card');
        channelCards.forEach(card => {
          card.addEventListener('click', () => {
            const channelId = card.getAttribute('data-channel-id');
            const channel = channels.find(c => c.id.toString() === channelId);
            if (channel) {
              openChannelModal(channel);
            }
          });
        });
      }

      // Open channel modal
      function openChannelModal(channel) {
        currentChannelModal = channel;

        document.getElementById("modalChannelName").textContent = channel.display_name;
        document.getElementById("modalChannelType").textContent = `Type: ${channel.type}`;
        document.getElementById("modalChannelStatus").textContent = `Status: ${channel.status}`;
        document.getElementById("modalChannelCommission").textContent = `Commission: ${channel.commission_rate}%`;

        const lastSync = channel.last_sync ? new Date(channel.last_sync).toLocaleString() : 'Never';
        document.getElementById("modalLastSync").textContent = `Last Sync: ${lastSync}`;
        document.getElementById("modalSyncStatus").textContent = `Sync Status: ${channel.sync_status}`;

        // Setup modal buttons
        const modalSyncBtn = document.getElementById("modalSyncChannel");
        const modalEditBtn = document.getElementById("modalEditChannel");
        const modalCloseBtn = document.getElementById("modalCloseChannel");

        if (modalSyncBtn) {
          modalSyncBtn.onclick = () => syncSingleChannel(channel.id);
        }

        if (modalEditBtn) {
          modalEditBtn.onclick = () => {
            window.location.href = `/channels/ota?edit=${channel.id}`;
          };
        }

        if (modalCloseBtn) {
          modalCloseBtn.onclick = closeChannelModal;
        }

        const modal = document.getElementById("channelModal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");
      }

      // Sync single channel
      async function syncSingleChannel(channelId) {
        const syncBtn = document.getElementById("modalSyncChannel");
        const originalText = syncBtn.textContent;
        syncBtn.textContent = 'Syncing...';
        syncBtn.disabled = true;

        try {
          const response = await fetch('./api/channel-actions.php?action=sync_channel', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ channel_id: channelId })
          });

          const data = await response.json();

          if (data.success) {
            // Refresh channels data
            await loadChannels();
            renderChannels();
            updateOverviewStats();
            closeChannelModal();
            showNotification(`Channel synced successfully!`, 'success');
          } else {
            showNotification('Failed to sync channel. Please try again.', 'error');
          }
        } catch (error) {
          console.error('Error syncing channel:', error);
          showNotification('Error syncing channel. Please try again.', 'error');
        }

        // Restore button
        syncBtn.textContent = originalText;
        syncBtn.disabled = false;
      }

      // Close channel modal
      function closeChannelModal() {
        const modal = document.getElementById("channelModal");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        currentChannelModal = null;
      }

      // Close modal on outside click
      document.getElementById("channelModal").addEventListener("click", (e) => {
        if (e.target === document.getElementById("channelModal")) {
          closeChannelModal();
        }
      });

      // Close modal on Escape
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !document.getElementById("channelModal").classList.contains("hidden")) {
          closeChannelModal();
        }
      });

      // Start when DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
    </script>
  </body>
</html>
