<?php
// Marketing and Promotions Management - Simple Version
require_once 'includes/auth.php';
require_once 'includes/db.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <script>
      const theme = localStorage.getItem('theme') || 'light';
      document.documentElement.classList.toggle('dark', theme === 'dark');
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Marketing & Promotions - Inn Nexus</title>
    <link rel="icon" href="./public/favicon.svg" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
  </head>
  <body class="min-h-screen bg-background">
    <?php require_once 'includes/header.php'; ?>

    <div class="max-w-6xl mx-auto p-6">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-foreground mb-2">Marketing & Promotions</h1>
        <p class="text-muted-foreground">Manage your marketing campaigns and promotional offers</p>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
        $pdo = getPdo();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM marketing_campaigns WHERE status = 'active'");
        $active_campaigns = $stmt->fetch()['count'];
        ?>
        <div class="bg-card border rounded-lg p-6">
          <h3 class="text-sm font-medium text-muted-foreground mb-2">Active Campaigns</h3>
          <p class="text-2xl font-bold text-accent"><?php echo $active_campaigns; ?></p>
        </div>

        <?php
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM promotional_offers WHERE is_active = 1");
        $active_offers = $stmt->fetch()['count'];
        ?>
        <div class="bg-card border rounded-lg p-6">
          <h3 class="text-sm font-medium text-muted-foreground mb-2">Active Offers</h3>
          <p class="text-2xl font-bold text-accent"><?php echo $active_offers; ?></p>
        </div>

        <?php
        $stmt = $pdo->query("SELECT SUM(usage_count) as total FROM promotional_offers");
        $total_usage = $stmt->fetch()['total'] ?? 0;
        ?>
        <div class="bg-card border rounded-lg p-6">
          <h3 class="text-sm font-medium text-muted-foreground mb-2">Total Redemptions</h3>
          <p class="text-2xl font-bold text-accent"><?php echo $total_usage; ?></p>
        </div>
      </div>

      <!-- Marketing Campaigns -->
      <div class="bg-card border rounded-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">Marketing Campaigns</h2>
          <button onclick="showNewCampaignModal()" class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm">New Campaign</button>
        </div>

        <?php
        $stmt = $pdo->query("SELECT * FROM marketing_campaigns ORDER BY created_at DESC LIMIT 5");
        $campaigns = $stmt->fetchAll();
        if ($campaigns):
        ?>
          <div class="space-y-4">
            <?php foreach ($campaigns as $campaign): ?>
              <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="font-medium"><?php echo htmlspecialchars($campaign['name']); ?></h3>
                    <p class="text-sm text-muted-foreground"><?php echo htmlspecialchars($campaign['description'] ?? ''); ?></p>
                    <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
                      <span>Type: <?php echo ucfirst(str_replace('_', ' ', $campaign['campaign_type'])); ?></span>
                      <span>Status: <?php echo ucfirst($campaign['status']); ?></span>
                      <?php if ($campaign['budget']): ?>
                        <span>Budget: $<?php echo number_format($campaign['budget'], 2); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <?php if ($campaign['status'] === 'active'): ?>
                      <button onclick="pauseCampaign(<?php echo $campaign['id']; ?>)" class="px-3 py-1 bg-orange-500 text-white rounded text-xs">Pause</button>
                    <?php else: ?>
                      <button onclick="activateCampaign(<?php echo $campaign['id']; ?>)" class="px-3 py-1 bg-green-500 text-white rounded text-xs">Activate</button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted-foreground text-center py-8">No campaigns yet. Create your first campaign!</p>
        <?php endif; ?>
      </div>

      <!-- Promotional Offers -->
      <div class="bg-card border rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">Promotional Offers</h2>
          <button onclick="showNewOfferModal()" class="px-4 py-2 bg-primary text-primary-foreground rounded-md text-sm">New Offer</button>
        </div>

        <?php
        $stmt = $pdo->query("SELECT * FROM promotional_offers ORDER BY created_at DESC LIMIT 5");
        $offers = $stmt->fetchAll();
        if ($offers):
        ?>
          <div class="space-y-4">
            <?php foreach ($offers as $offer): ?>
              <div class="border rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="font-medium"><?php echo htmlspecialchars($offer['name']); ?></h3>
                    <p class="text-sm text-muted-foreground">Code: <?php echo htmlspecialchars($offer['code']); ?></p>
                    <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
                      <span>Type: <?php echo ucfirst(str_replace('_', ' ', $offer['offer_type'])); ?></span>
                      <?php if ($offer['discount_percentage']): ?>
                        <span>Discount: <?php echo $offer['discount_percentage']; ?>%</span>
                      <?php elseif ($offer['discount_value']): ?>
                        <span>Discount: $<?php echo number_format($offer['discount_value'], 2); ?></span>
                      <?php endif; ?>
                      <span>Used: <?php echo $offer['usage_count']; ?> times</span>
                      <span>Status: <?php echo $offer['is_active'] ? 'Active' : 'Inactive'; ?></span>
                    </div>
                  </div>
                  <div class="flex gap-2">
                    <button onclick="toggleOffer(<?php echo $offer['id']; ?>)" class="px-3 py-1 <?php echo $offer['is_active'] ? 'bg-red-500 text-white' : 'bg-green-500 text-white'; ?> rounded text-xs">
                      <?php echo $offer['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-muted-foreground text-center py-8">No offers yet. Create your first promotional offer!</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Campaign Modal -->
    <div id="campaign-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-card rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">New Campaign</h3>
        <form onsubmit="createCampaign(event)">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Campaign Name</label>
            <input type="text" id="campaign-name" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Description</label>
            <textarea id="campaign-description" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" rows="3"></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Type</label>
            <select id="campaign-type" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
              <option value="email">Email</option>
              <option value="social_media">Social Media</option>
              <option value="advertising">Advertising</option>
              <option value="promotion">Promotion</option>
              <option value="loyalty">Loyalty</option>
              <option value="seasonal">Seasonal</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Budget</label>
            <input type="number" step="0.01" id="campaign-budget" class="w-full px-3 py-2 border rounded-md bg-background text-foreground">
          </div>
          <div class="flex gap-2 justify-end">
            <button type="button" onclick="closeModal('campaign-modal')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md">Create</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Offer Modal -->
    <div id="offer-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
      <div class="bg-card rounded-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold mb-4">New Offer</h3>
        <form onsubmit="createOffer(event)">
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Offer Code</label>
            <input type="text" id="offer-code" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Offer Name</label>
            <input type="text" id="offer-name" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Discount Type</label>
            <select id="offer-type" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
              <option value="percentage_discount">Percentage Discount</option>
              <option value="fixed_amount_discount">Fixed Amount</option>
            </select>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Discount Value</label>
            <input type="number" step="0.01" id="offer-discount" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Valid Until</label>
            <input type="date" id="offer-valid-until" class="w-full px-3 py-2 border rounded-md bg-background text-foreground" required>
          </div>
          <div class="flex gap-2 justify-end">
            <button type="button" onclick="closeModal('offer-modal')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-md">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md">Create</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function showNewCampaignModal() {
        document.getElementById('campaign-modal').classList.remove('hidden');
      }

      function showNewOfferModal() {
        document.getElementById('offer-modal').classList.remove('hidden');
      }

      function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
      }

      async function createCampaign(event) {
        event.preventDefault();
        const formData = new FormData();
        formData.append('action', 'create_campaign');
        formData.append('campaign_name', document.getElementById('campaign-name').value);
        formData.append('campaign_description', document.getElementById('campaign-description').value);
        formData.append('campaign_type', document.getElementById('campaign-type').value);
        formData.append('campaign_budget', document.getElementById('campaign-budget').value);

        try {
          const response = await fetch('api/marketing-actions.php', {
            method: 'POST',
            body: formData
          });
          if (response.ok) {
            location.reload();
          }
        } catch (error) {
          console.error('Error:', error);
        }
      }

      async function createOffer(event) {
        event.preventDefault();
        const formData = new FormData();
        formData.append('action', 'create_offer');
        formData.append('offer_code', document.getElementById('offer-code').value.toUpperCase());
        formData.append('offer_name', document.getElementById('offer-name').value);
        formData.append('offer_type', document.getElementById('offer-type').value);
        formData.append('offer_discount', document.getElementById('offer-discount').value);
        formData.append('offer_valid_until', document.getElementById('offer-valid-until').value);

        try {
          const response = await fetch('api/marketing-actions.php', {
            method: 'POST',
            body: formData
          });
          if (response.ok) {
            location.reload();
          }
        } catch (error) {
          console.error('Error:', error);
        }
      }

      async function pauseCampaign(id) {
        if (confirm('Pause this campaign?')) {
          try {
            const response = await fetch(`api/marketing-actions.php?action=pause_campaign&id=${id}`);
            if (response.ok) location.reload();
          } catch (error) {
            console.error('Error:', error);
          }
        }
      }

      async function activateCampaign(id) {
        try {
          const response = await fetch(`api/marketing-actions.php?action=activate_campaign&id=${id}`);
          if (response.ok) location.reload();
        } catch (error) {
          console.error('Error:', error);
        }
      }

      async function toggleOffer(id) {
        try {
          const response = await fetch(`api/marketing-actions.php?action=toggle_offer&id=${id}`);
          if (response.ok) location.reload();
        } catch (error) {
          console.error('Error:', error);
        }
      }
    </script>
  </body>
</html>
