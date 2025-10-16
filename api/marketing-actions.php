<?php
// Simple Marketing API
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Only allow POST and GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method not allowed');
}

$pdo = getPdo();

// Handle GET requests for actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'pause_campaign':
            updateCampaignStatus($_GET['id'], 'paused');
            break;
        case 'activate_campaign':
            updateCampaignStatus($_GET['id'], 'active');
            break;
        case 'toggle_offer':
            toggleOfferStatus($_GET['id']);
            break;
        default:
            http_response_code(400);
            exit('Invalid action');
    }
    exit;
}

// Handle POST requests for creating
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_campaign':
        createCampaign($_POST);
        break;
    case 'create_offer':
        createOffer($_POST);
        break;
    default:
        http_response_code(400);
        exit('Invalid action');
}

function updateCampaignStatus($campaignId, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE marketing_campaigns SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $campaignId]);
        return true;
    } catch (Exception $e) {
        error_log("Error updating campaign status: " . $e->getMessage());
        return false;
    }
}

function toggleOfferStatus($offerId) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE promotional_offers SET is_active = !is_active, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$offerId]);
        return true;
    } catch (Exception $e) {
        error_log("Error toggling offer status: " . $e->getMessage());
        return false;
    }
}

function createCampaign($data) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO marketing_campaigns (
                name, description, campaign_type, budget, status, created_by
            ) VALUES (?, ?, ?, ?, 'draft', ?)
        ");

        $stmt->execute([
            $data['campaign_name'],
            $data['campaign_description'],
            $data['campaign_type'],
            $data['campaign_budget'] ?: null,
            $_SESSION['user_id'] ?? 1
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Error creating campaign: " . $e->getMessage());
        return false;
    }
}

function createOffer($data) {
    global $pdo;
    try {
        // Handle discount fields
        $discountValue = null;
        $discountPercentage = null;

        if ($data['offer_type'] === 'percentage_discount') {
            $discountPercentage = $data['offer_discount'];
        } else {
            $discountValue = $data['offer_discount'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO promotional_offers (
                code, name, offer_type, discount_value, discount_percentage,
                valid_from, valid_until, is_active, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");

        $stmt->execute([
            $data['offer_code'],
            $data['offer_name'],
            $data['offer_type'],
            $discountValue,
            $discountPercentage,
            date('Y-m-d'),
            $data['offer_valid_until'],
            $_SESSION['user_id'] ?? 1
        ]);

        return true;
    } catch (Exception $e) {
        error_log("Error creating offer: " . $e->getMessage());
        return false;
    }
}

// Return success
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
