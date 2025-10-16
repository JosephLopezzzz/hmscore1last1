<?php
// Marketing and Promotion Management
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin', 'manager']);

$action = $_GET['action'] ?? 'dashboard';
$campaign_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getPdo();
    if (!$pdo) {
        $error = "Database connection failed.";
    } else {
        try {
            if ($action === 'create_campaign') {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $campaign_type = $_POST['campaign_type'];
                $target_audience = trim($_POST['target_audience']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'] ?: null;
                $budget = $_POST['budget'] ?: null;

                if (empty($name) || empty($campaign_type) || empty($start_date)) {
                    $error = "Please fill in all required fields.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO marketing_campaigns (name, description, campaign_type, target_audience, start_date, end_date, budget, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $campaign_type, $target_audience, $start_date, $end_date, $budget, $_SESSION['user_id']]);

                    $success = "Marketing campaign created successfully.";
                    header("Location: marketing.php?action=dashboard");
                    exit;
                }
            } elseif ($action === 'update_campaign_status' && $campaign_id) {
                $status = $_POST['status'];
                $stmt = $pdo->prepare("UPDATE marketing_campaigns SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $campaign_id]);
                $success = "Campaign status updated successfully.";
            } elseif ($action === 'create_promotion') {
                $code = strtoupper(trim($_POST['code']));
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $offer_type = $_POST['offer_type'];
                $discount_value = $_POST['discount_value'] ?: null;
                $discount_percentage = $_POST['discount_percentage'] ?: null;
                $valid_from = $_POST['valid_from'];
                $valid_until = $_POST['valid_until'];
                $usage_limit = $_POST['usage_limit'] ?: null;

                if (empty($code) || empty($name) || empty($offer_type) || empty($valid_from) || empty($valid_until)) {
                    $error = "Please fill in all required fields.";
                } elseif ($valid_from >= $valid_until) {
                    $error = "Valid until date must be after valid from date.";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO promotional_offers (code, name, description, offer_type, discount_value, discount_percentage, valid_from, valid_until, usage_limit, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$code, $name, $description, $offer_type, $discount_value, $discount_percentage, $valid_from, $valid_until, $usage_limit, $_SESSION['user_id']]);

                    $success = "Promotional offer created successfully.";
                    header("Location: marketing.php?action=promotions");
                    exit;
                }
            } elseif ($action === 'toggle_promotion' && isset($_POST['promotion_id'])) {
                $promotion_id = $_POST['promotion_id'];
                $stmt = $pdo->prepare("UPDATE promotional_offers SET is_active = !is_active, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$promotion_id]);
                $success = "Promotion status updated successfully.";
            }
        } catch (Exception $e) {
            $error = "An error occurred while processing your request: " . $e->getMessage();
        }
    }
}

// Get dashboard statistics
$stats = [];
$pdo = getPdo();
if ($pdo) {
    // Active campaigns
    $stmt = $pdo->query("SELECT COUNT(*) FROM marketing_campaigns WHERE status = 'active'");
    $stats['active_campaigns'] = $stmt->fetchColumn();

    // Total promotions
    $stmt = $pdo->query("SELECT COUNT(*) FROM promotional_offers WHERE is_active = 1");
    $stats['active_promotions'] = $stmt->fetchColumn();


    // Recent campaigns
    $stats['recent_campaigns'] = $pdo->query("
        SELECT name, campaign_type, status, start_date
        FROM marketing_campaigns
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Promotional offers for promotions page
    if ($action === 'promotions') {
        $stats['promotional_offers'] = $pdo->query("
            SELECT * FROM promotional_offers
            ORDER BY created_at DESC
        ")->fetchAll();
    }
}
?>
<!doctype html>
<html lang="en" class="">
<head>
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Marketing & Promotion - Inn Nexus Hotel Management System</title>
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
                <h1 class="text-3xl font-bold">Marketing & Promotion</h1>
                <p class="text-muted-foreground"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <?php if ($action === 'dashboard'): ?>
                    <button onclick="showCreateCampaignModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        Create Campaign
                    </button>
                    <a href="marketing.php?action=promotions" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90">
                        <i data-lucide="tag"></i>
                        Manage Promotions
                    </a>
                <?php else: ?>
                    <a href="marketing.php" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90">
                        <i data-lucide="arrow-left"></i>
                        Back to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'dashboard'): ?>
            <!-- Marketing Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-card rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Active Campaigns</p>
                            <p class="text-2xl font-bold"><?php echo $stats['active_campaigns'] ?? 0; ?></p>
                        </div>
                        <i data-lucide="megaphone" class="h-8 w-8 text-primary"></i>
                    </div>
                </div>

                <div class="bg-card rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Active Promotions</p>
                            <p class="text-2xl font-bold"><?php echo $stats['active_promotions'] ?? 0; ?></p>
                        </div>
                        <i data-lucide="tag" class="h-8 w-8 text-green-600"></i>
                    </div>
                </div>

                <div class="bg-card rounded-lg border p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">This Month</p>
                            <p class="text-2xl font-bold">$0</p>
                        </div>
                        <i data-lucide="trending-up" class="h-8 w-8 text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Campaigns -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Recent Campaigns</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($stats['recent_campaigns'])): ?>
                        <p class="text-muted-foreground text-center py-4">No campaigns created yet. <a href="#" onclick="showCreateCampaignModal()" class="text-primary hover:underline">Create your first campaign</a>.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($stats['recent_campaigns'] as $campaign): ?>
                                <div class="flex items-center justify-between p-4 border rounded-lg">
                                    <div class="flex-1">
                                        <h3 class="font-medium"><?php echo htmlspecialchars($campaign['name']); ?></h3>
                                        <div class="flex items-center gap-4 text-sm text-muted-foreground mt-1">
                                            <span class="px-2 py-1 bg-secondary text-secondary-foreground rounded text-xs">
                                                <?php echo ucfirst(str_replace('_', ' ', $campaign['campaign_type'])); ?>
                                            </span>
                                            <span>Starts: <?php echo date('M j, Y', strtotime($campaign['start_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php
                                        $status_colors = [
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'active' => 'bg-green-100 text-green-800',
                                            'paused' => 'bg-yellow-100 text-yellow-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $status_color = $status_colors[$campaign['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 <?php echo $status_color; ?> rounded text-xs">
                                            <?php echo ucfirst($campaign['status']); ?>
                                        </span>
                                        <div class="flex gap-1">
                                            <button onclick="editCampaign(<?php echo $campaign['id']; ?>)" class="px-2 py-1 text-blue-600 hover:bg-blue-50 rounded">
                                                <i data-lucide="edit"></i>
                                            </button>
                                            <?php if ($campaign['status'] === 'active'): ?>
                                                <button onclick="pauseCampaign(<?php echo $campaign['id']; ?>)" class="px-2 py-1 text-yellow-600 hover:bg-yellow-50 rounded">
                                                    <i data-lucide="pause"></i>
                                                </button>
                                            <?php elseif ($campaign['status'] === 'paused'): ?>
                                                <button onclick="activateCampaign(<?php echo $campaign['id']; ?>)" class="px-2 py-1 text-green-600 hover:bg-green-50 rounded">
                                                    <i data-lucide="play"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Campaign Modal -->
            <div id="create-campaign-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-card p-6 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_campaign">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Create Marketing Campaign</h2>
                            <button type="button" onclick="hideCreateCampaignModal()" class="text-muted-foreground hover:text-foreground">
                                <i data-lucide="x" class="h-6 w-6"></i>
                            </button>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Campaign Name *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Campaign Type *</label>
                            <select name="campaign_type" required class="w-full px-3 py-2 border rounded-md">
                                <option value="">Select campaign type</option>
                                <option value="email">Email Marketing</option>
                                <option value="social_media">Social Media</option>
                                <option value="advertising">Advertising</option>
                                <option value="promotion">Promotional Campaign</option>
                                <option value="seasonal">Seasonal Campaign</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Target Audience</label>
                            <textarea name="target_audience" rows="2" class="w-full px-3 py-2 border rounded-md" placeholder="Describe your target audience..."></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Start Date *</label>
                                <input type="date" name="start_date" required class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">End Date</label>
                                <input type="date" name="end_date" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Budget ($)</label>
                            <input type="number" name="budget" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="hideCreateCampaignModal()" class="px-4 py-2 border rounded-md hover:bg-muted">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">Create Campaign</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'promotions'): ?>
            <!-- Promotional Offers Management -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Promotional Offers</h2>
                        <button onclick="showCreatePromotionModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                            <i data-lucide="plus"></i>
                            Create Promotion
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <?php if (empty($stats['promotional_offers'])): ?>
                        <div class="text-center py-8">
                            <i data-lucide="tag" class="h-12 w-12 text-muted-foreground mx-auto mb-4"></i>
                            <p class="text-muted-foreground mb-4">No promotional offers created yet.</p>
                            <button onclick="showCreatePromotionModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                                <i data-lucide="plus"></i>
                                Create Your First Promotion
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-3">Code</th>
                                        <th class="text-left p-3">Name</th>
                                        <th class="text-left p-3">Type</th>
                                        <th class="text-left p-3">Discount</th>
                                        <th class="text-left p-3">Valid Dates</th>
                                        <th class="text-left p-3">Usage</th>
                                        <th class="text-center p-3">Status</th>
                                        <th class="text-center p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['promotional_offers'] as $offer): ?>
                                        <tr class="border-b hover:bg-muted/50">
                                            <td class="p-3">
                                                <div class="font-mono font-medium"><?php echo htmlspecialchars($offer['code']); ?></div>
                                            </td>
                                            <td class="p-3">
                                                <div class="font-medium"><?php echo htmlspecialchars($offer['name']); ?></div>
                                                <?php if ($offer['description']): ?>
                                                    <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars(substr($offer['description'], 0, 50)) . (strlen($offer['description']) > 50 ? '...' : ''); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-1 bg-secondary text-secondary-foreground rounded text-sm">
                                                    <?php echo ucfirst(str_replace('_', ' ', $offer['offer_type'])); ?>
                                                </span>
                                            </td>
                                            <td class="p-3">
                                                <?php if ($offer['offer_type'] === 'percentage_discount' && $offer['discount_percentage']): ?>
                                                    <span class="font-medium"><?php echo $offer['discount_percentage']; ?>%</span>
                                                <?php elseif ($offer['offer_type'] === 'fixed_amount_discount' && $offer['discount_value']): ?>
                                                    <span class="font-medium">$<?php echo number_format($offer['discount_value'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted-foreground">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <div class="text-sm">
                                                    <div><?php echo date('M j, Y', strtotime($offer['valid_from'])); ?></div>
                                                    <div class="text-muted-foreground">to <?php echo date('M j, Y', strtotime($offer['valid_until'])); ?></div>
                                                </div>
                                            </td>
                                            <td class="p-3">
                                                <div class="text-sm">
                                                    <div><?php echo $offer['usage_count']; ?> used</div>
                                                    <?php if ($offer['usage_limit']): ?>
                                                        <div class="text-muted-foreground">of <?php echo $offer['usage_limit']; ?> limit</div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="p-3 text-center">
                                                <?php if ($offer['is_active']): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Active</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <div class="flex justify-center gap-2">
                                                    <button onclick="togglePromotion(<?php echo $offer['id']; ?>)" class="px-2 py-1 text-blue-600 hover:bg-blue-50 rounded">
                                                        <i data-lucide="<?php echo $offer['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                    <button onclick="deletePromotion(<?php echo $offer['id']; ?>)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">
                                                        <i data-lucide="trash-2"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Promotion Modal -->
            <div id="create-promotion-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-card p-6 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_promotion">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Create Promotional Offer</h2>
                            <button type="button" onclick="hideCreatePromotionModal()" class="text-muted-foreground hover:text-foreground">
                                <i data-lucide="x" class="h-6 w-6"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Promo Code *</label>
                                <input type="text" name="code" required maxlength="50" class="w-full px-3 py-2 border rounded-md uppercase" placeholder="SUMMER2024">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Offer Name *</label>
                                <input type="text" name="name" required class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea name="description" rows="2" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Offer Type *</label>
                            <select name="offer_type" required class="w-full px-3 py-2 border rounded-md">
                                <option value="">Select offer type</option>
                                <option value="percentage_discount">Percentage Discount</option>
                                <option value="fixed_amount_discount">Fixed Amount Discount</option>
                                <option value="free_nights">Free Nights</option>
                                <option value="upgrade">Room Upgrade</option>
                                <option value="package_deal">Package Deal</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Discount Value ($)</label>
                                <input type="number" name="discount_value" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md">
                                <p class="text-xs text-muted-foreground mt-1">For fixed amount discounts</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Discount Percentage (%)</label>
                                <input type="number" name="discount_percentage" step="0.01" min="0" max="100" class="w-full px-3 py-2 border rounded-md">
                                <p class="text-xs text-muted-foreground mt-1">For percentage discounts</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Valid From *</label>
                                <input type="date" name="valid_from" required class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Valid Until *</label>
                                <input type="date" name="valid_until" required class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Usage Limit</label>
                            <input type="number" name="usage_limit" min="1" class="w-full px-3 py-2 border rounded-md" placeholder="Leave empty for unlimited">
                            <p class="text-xs text-muted-foreground mt-1">Maximum number of times this code can be used</p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="hideCreatePromotionModal()" class="px-4 py-2 border rounded-md hover:bg-muted">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">Create Promotion</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        window.lucide && window.lucide.createIcons();

        function showCreateCampaignModal() {
            document.getElementById('create-campaign-modal').classList.remove('hidden');
        }

        function hideCreateCampaignModal() {
            document.getElementById('create-campaign-modal').classList.add('hidden');
        }

        function editCampaign(id) {
            window.location.href = `marketing.php?action=edit_campaign&id=${id}`;
        }

        function pauseCampaign(id) {
            if (confirm('Are you sure you want to pause this campaign?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="update_campaign_status"><input type="hidden" name="id" value="${id}"><input type="hidden" name="status" value="paused">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function activateCampaign(id) {
            if (confirm('Are you sure you want to activate this campaign?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="update_campaign_status"><input type="hidden" name="id" value="${id}"><input type="hidden" name="status" value="active">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function showCreatePromotionModal() {
            document.getElementById('create-promotion-modal').classList.remove('hidden');
        }

        function hideCreatePromotionModal() {
            document.getElementById('create-promotion-modal').classList.add('hidden');
        }

        function togglePromotion(id) {
            if (confirm('Are you sure you want to toggle this promotion status?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="toggle_promotion"><input type="hidden" name="promotion_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deletePromotion(id) {
            if (confirm('Are you sure you want to delete this promotion? This action cannot be undone.')) {
                // Implementation for delete would go here
                alert('Delete functionality not yet implemented');
            }
        }

    </script>
</body>
</html>
