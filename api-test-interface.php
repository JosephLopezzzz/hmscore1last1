<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Channel Management API Test Interface</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .api-response {
            max-height: 400px;
            overflow-y: auto;
        }
        .endpoint-card {
            transition: all 0.3s ease;
        }
        .endpoint-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Channel Management API Test Interface</h1>
            <p class="text-gray-600 dark:text-gray-400">Test the API endpoints that simulate real OTA data for your channel management system</p>
        </div>

        <!-- API Endpoints -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="building-2" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Channel Data</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Basic channel information</p>
                    </div>
                </div>
                <button onclick="testAPI('get_channel_data')" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="calculator" class="w-5 h-5 text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Rates Data</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Room rates and pricing</p>
                    </div>
                </div>
                <button onclick="testAPI('get_rates_data')" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="calendar" class="w-5 h-5 text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Availability Data</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Room availability</p>
                    </div>
                </div>
                <button onclick="testAPI('get_availability_data')" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="users" class="w-5 h-5 text-orange-600 dark:text-orange-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Reservations Data</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Booking reservations</p>
                    </div>
                </div>
                <button onclick="testAPI('get_reservations_data')" class="w-full bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="home" class="w-5 h-5 text-pink-600 dark:text-pink-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Inventory Data</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Room inventory</p>
                    </div>
                </div>
                <button onclick="testAPI('get_inventory_data')" class="w-full bg-pink-600 hover:bg-pink-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 endpoint-card">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gray-100 dark:bg-gray-900 rounded-lg flex items-center justify-center mr-3">
                        <i data-lucide="activity" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Sync Status</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Synchronization status</p>
                    </div>
                </div>
                <button onclick="testAPI('get_sync_status')" class="w-full bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Endpoint
                </button>
            </div>
        </div>

        <!-- Response Display -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">API Response</h2>
                    <button id="clearResponse" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div id="responseContainer" class="hidden">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <span id="responseStatus" class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"></span>
                            <span id="responseTime" class="text-sm text-gray-600 dark:text-gray-400"></span>
                        </div>
                        <div class="flex gap-2">
                            <button id="formatResponse" class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-2 py-1 rounded transition-colors">
                                Format JSON
                            </button>
                            <button id="copyResponse" class="text-xs bg-blue-100 dark:bg-blue-900 hover:bg-blue-200 dark:hover:bg-blue-800 text-blue-700 dark:text-blue-300 px-2 py-1 rounded transition-colors">
                                Copy
                            </button>
                        </div>
                    </div>
                    <pre id="apiResponse" class="api-response bg-gray-50 dark:bg-gray-900 p-4 rounded-lg text-sm text-gray-800 dark:text-gray-200 overflow-x-auto"></pre>
                </div>
                <div id="noResponse" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <i data-lucide="terminal" class="w-12 h-12 mx-auto mb-4"></i>
                    <p>Click any endpoint button above to test the API and see the response here.</p>
                </div>
            </div>
        </div>

        <!-- API Information -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mt-8">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">API Information</h3>
            <div class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                <p><strong>Base URL:</strong> <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded"><?php echo htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI'])); ?>/api/channel-test-data.php</code></p>
                <p><strong>Content Type:</strong> application/json</p>
                <p><strong>CORS:</strong> Enabled for all origins</p>
                <p><strong>Data Format:</strong> Matches real OTA API responses (Booking.com, Expedia, Agoda style)</p>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Test API endpoint
        async function testAPI(action) {
            const startTime = Date.now();
            const noResponse = document.getElementById('noResponse');
            const responseContainer = document.getElementById('responseContainer');

            try {
                noResponse.classList.add('hidden');
                responseContainer.classList.remove('hidden');

                const response = await fetch(`api/channel-test-data.php?action=${action}`);
                const data = await response.json();
                const endTime = Date.now();

                // Update response display
                document.getElementById('responseStatus').textContent = data.success ? 'Success' : 'Error';
                document.getElementById('responseStatus').className = `px-2 py-1 text-xs rounded-full ${data.success ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}`;
                document.getElementById('responseTime').textContent = `${endTime - startTime}ms`;

                document.getElementById('apiResponse').textContent = JSON.stringify(data, null, 2);

            } catch (error) {
                const endTime = Date.now();
                document.getElementById('responseStatus').textContent = 'Network Error';
                document.getElementById('responseStatus').className = 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
                document.getElementById('responseTime').textContent = `${endTime - startTime}ms`;
                document.getElementById('apiResponse').textContent = JSON.stringify({
                    success: false,
                    error: error.message,
                    timestamp: new Date().toISOString()
                }, null, 2);
            }
        }

        // Format JSON response
        document.getElementById('formatResponse')?.addEventListener('click', function() {
            const responseElement = document.getElementById('apiResponse');
            try {
                const parsed = JSON.parse(responseElement.textContent);
                responseElement.textContent = JSON.stringify(parsed, null, 2);
            } catch (e) {
                alert('Invalid JSON format');
            }
        });

        // Copy response to clipboard
        document.getElementById('copyResponse')?.addEventListener('click', function() {
            const responseElement = document.getElementById('apiResponse');
            navigator.clipboard.writeText(responseElement.textContent).then(() => {
                // Show brief success feedback
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.textContent = originalText;
                }, 1000);
            }).catch(() => {
                alert('Failed to copy to clipboard');
            });
        });

        // Clear response
        document.getElementById('clearResponse')?.addEventListener('click', function() {
            document.getElementById('responseContainer').classList.add('hidden');
            document.getElementById('noResponse').classList.remove('hidden');
        });
    </script>
</body>
</html>
