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
    <title>Channel Management - Inn Nexus Hotel Management System</title>
    <link rel="icon" href="./public/favicon.svg" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Channel Management</h1>
          <p class="text-muted-foreground">Manage OTA connections and distribution channels</p>
        </div>
        <div class="flex gap-2">
          <button onclick="showAddChannelModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
            <i data-lucide="plus"></i>
            Add Channel
          </button>
        </div>
      </div>

      <!-- Overview Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-card rounded-lg border p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Channels</p>
              <p class="text-2xl font-bold" id="totalChannels">0</p>
            </div>
            <i data-lucide="globe" class="h-8 w-8 text-primary"></i>
          </div>
        </div>

        <div class="bg-card rounded-lg border p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Active Channels</p>
              <p class="text-2xl font-bold" id="activeChannels">0</p>
            </div>
            <i data-lucide="check-circle" class="h-8 w-8 text-green-600"></i>
          </div>
        </div>

        <div class="bg-card rounded-lg border p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Sync Errors</p>
              <p class="text-2xl font-bold" id="syncErrors">0</p>
            </div>
            <i data-lucide="alert-triangle" class="h-8 w-8 text-red-600"></i>
          </div>
        </div>

        <div class="bg-card rounded-lg border p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Last Sync</p>
              <p class="text-sm font-bold" id="lastSync">Never</p>
            </div>
            <i data-lucide="refresh-cw" class="h-8 w-8 text-blue-600"></i>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-card rounded-lg border p-4">
        <div class="flex flex-wrap gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select id="filterStatus" class="px-3 py-2 border rounded-md">
              <option value="all">All Status</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Error">Error</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select id="filterType" class="px-3 py-2 border rounded-md">
              <option value="all">All Types</option>
              <option value="OTA">OTA</option>
              <option value="GDS">GDS</option>
              <option value="Direct">Direct</option>
              <option value="Wholesale">Wholesale</option>
              <option value="Corporate">Corporate</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Search</label>
            <input type="text" id="searchInput" placeholder="Search channels..." class="px-3 py-2 border rounded-md">
          </div>
        </div>
      </div>

      <!-- Channels List -->
      <div class="bg-card rounded-lg border">
        <div class="p-6 border-b">
          <h2 class="text-lg font-semibold">Distribution Channels</h2>
        </div>
        <div class="p-6">
          <div id="channelsContainer" class="space-y-4">
            <!-- Channels will be loaded here -->
          </div>
        </div>
      </div>
    </main>

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
        const searchEl = document.getElementById('searchInput');

        statusEl.addEventListener('change', (e) => {
          filters.status = e.target.value;
          renderChannels();
        });

        typeEl.addEventListener('change', (e) => {
          filters.type = e.target.value;
          renderChannels();
        });

        searchEl.addEventListener('input', (e) => {
          filters.search = e.target.value.toLowerCase();
          renderChannels();
        });
      }

      // Setup event listeners
      function setupEventListeners() {
        // Add any global event listeners here
      }

      // Render channels
      function renderChannels() {
        const filteredChannels = channels.filter(channel => {
          const matchesStatus = filters.status === 'all' || channel.status === filters.status;
          const matchesType = filters.type === 'all' || channel.type === filters.type;
          const matchesSearch = filters.search === '' || 
            channel.display_name.toLowerCase().includes(filters.search) ||
            channel.name.toLowerCase().includes(filters.search);

          return matchesStatus && matchesType && matchesSearch;
        });

        if (filteredChannels.length === 0) {
          channelsContainer.innerHTML = `
            <div class="text-center py-8">
              <i data-lucide="globe" class="h-12 w-12 text-muted-foreground mx-auto mb-4"></i>
              <p class="text-muted-foreground">No channels found</p>
            </div>
          `;
          return;
        }

        channelsContainer.innerHTML = filteredChannels.map(channel => `
          <div class="border rounded-lg p-4 hover:bg-muted/50 transition-colors">
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                  <h3 class="font-semibold">${channel.display_name}</h3>
                  <span class="px-2 py-1 text-xs rounded-full ${statusClassMap[channel.status] || 'bg-gray-100 text-gray-800'}">
                    ${channel.status}
                  </span>
                  <span class="px-2 py-1 text-xs rounded-full bg-secondary text-secondary-foreground">
                    ${channel.type}
                  </span>
                </div>
                <div class="text-sm text-muted-foreground">
                  <p>Commission: ${channel.commission_rate}%</p>
                  <p>Last Sync: ${channel.last_sync ? new Date(channel.last_sync).toLocaleString() : 'Never'}</p>
                  <p>Room Mappings: ${channel.room_mappings || 0}</p>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button onclick="editChannel(${channel.id})" class="px-3 py-1 text-blue-600 hover:bg-blue-50 rounded">
                  <i data-lucide="edit"></i>
                </button>
                <button onclick="syncChannel(${channel.id})" class="px-3 py-1 text-green-600 hover:bg-green-50 rounded">
                  <i data-lucide="refresh-cw"></i>
                </button>
                <button onclick="deleteChannel(${channel.id})" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded">
                  <i data-lucide="trash-2"></i>
                </button>
              </div>
            </div>
          </div>
        `).join('');

        // Re-initialize icons
        window.lucide && window.lucide.createIcons();
      }

      // Update overview stats
      function updateOverviewStats() {
        document.getElementById('totalChannels').textContent = channels.length;
        document.getElementById('activeChannels').textContent = channels.filter(c => c.status === 'Active').length;
        document.getElementById('syncErrors').textContent = channels.filter(c => c.status === 'Error').length;
        
        const lastSync = channels.reduce((latest, channel) => {
          if (!channel.last_sync) return latest;
          const syncDate = new Date(channel.last_sync);
          return !latest || syncDate > latest ? syncDate : latest;
        }, null);
        
        document.getElementById('lastSync').textContent = lastSync ? 
          lastSync.toLocaleString() : 'Never';
      }

      // Show add channel modal
      function showAddChannelModal() {
        // Implementation for add channel modal
        alert('Add Channel functionality would be implemented here');
      }

      // Edit channel
      function editChannel(id) {
        // Implementation for edit channel
        alert(`Edit channel ${id} functionality would be implemented here`);
      }

      // Sync channel
      function syncChannel(id) {
        // Implementation for sync channel
        alert(`Sync channel ${id} functionality would be implemented here`);
      }

      // Delete channel
      function deleteChannel(id) {
        if (confirm('Are you sure you want to delete this channel?')) {
          // Implementation for delete channel
          alert(`Delete channel ${id} functionality would be implemented here`);
        }
      }

      // Initialize when ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }
    </script>
  </body>
</html>
