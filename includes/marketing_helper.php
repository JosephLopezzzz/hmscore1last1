<?php
/**
 * Hotel Marketing and Promotion Helper Functions
 * Handles promotional codes, loyalty points, and marketing integrations
 */

class MarketingHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Validate a promotional code
     * @param string $code The promotional code to validate
     * @return array|false Returns offer details or false if invalid
     */
    public function validatePromotionCode($code) {
        if (empty($code)) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            SELECT * FROM promotional_offers
            WHERE code = ? AND is_active = 1
            AND valid_from <= CURDATE() AND valid_until >= CURDATE()
            AND (usage_limit IS NULL OR usage_count < usage_limit)
        ");
        $stmt->execute([strtoupper(trim($code))]);
        $offer = $stmt->fetch();

        return $offer ?: false;
    }

    /**
     * Apply promotional discount to amount
     * @param array $offer The promotional offer details
     * @param float $original_amount The original amount
     * @return array Returns discount details
     */
    public function calculatePromotionDiscount($offer, $original_amount) {
        $discount = 0;
        $discount_type = '';

        if ($offer['offer_type'] === 'percentage_discount' && $offer['discount_percentage']) {
            $discount = $original_amount * ($offer['discount_percentage'] / 100);
            $discount_type = 'percentage';

            // Apply maximum discount limit if set
            if ($offer['max_discount_amount'] && $discount > $offer['max_discount_amount']) {
                $discount = $offer['max_discount_amount'];
            }
        } elseif ($offer['offer_type'] === 'fixed_amount_discount' && $offer['discount_value']) {
            $discount = min($offer['discount_value'], $original_amount);
            $discount_type = 'fixed';
        }

        return [
            'discount_amount' => round($discount, 2),
            'discount_type' => $discount_type,
            'final_amount' => round($original_amount - $discount, 2)
        ];
    }

    /**
     * Record promotional offer usage
     * @param int $offer_id The promotional offer ID
     * @param int $guest_id The guest ID (optional)
     * @param int $reservation_id The reservation ID (optional)
     * @param float $discount_amount The discount amount applied
     * @return bool Success status
     */
    public function recordPromotionUsage($offer_id, $guest_id = null, $reservation_id = null, $discount_amount = 0) {
        try {
            // Increment usage count
            $stmt = $this->pdo->prepare("
                UPDATE promotional_offers
                SET usage_count = usage_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$offer_id]);

            // Record usage details
            $stmt = $this->pdo->prepare("
                INSERT INTO promotion_usage (promotional_offer_id, guest_id, reservation_id, usage_date, discount_amount)
                VALUES (?, ?, ?, CURDATE(), ?)
            ");
            $stmt->execute([$offer_id, $guest_id, $reservation_id, $discount_amount]);

            return true;
        } catch (Exception $e) {
            error_log("Error recording promotion usage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Award loyalty points for a stay
     * @param int $guest_id The guest ID
     * @param float $amount_spent The amount spent
     * @param int $nights_stayed Number of nights stayed
     * @return bool Success status
     */
    public function awardLoyaltyPoints($guest_id, $amount_spent = 0, $nights_stayed = 0) {
        try {
            // Get active loyalty program
            $stmt = $this->pdo->prepare("
                SELECT * FROM loyalty_programs WHERE is_active = 1 LIMIT 1
            ");
            $stmt->execute();
            $program = $stmt->fetch();

            if (!$program) {
                return false; // No active loyalty program
            }

            // Get or create guest loyalty membership
            $stmt = $this->pdo->prepare("
                SELECT id, points_balance FROM guest_loyalty_memberships
                WHERE guest_id = ? AND loyalty_program_id = ? AND is_active = 1
            ");
            $stmt->execute([$guest_id, $program['id']]);
            $membership = $stmt->fetch();

            if (!$membership) {
                // Auto-enroll guest if auto-enrollment is enabled
                if ($program['enrollment_auto']) {
                    $membership_number = 'LM' . str_pad($guest_id, 6, '0', STR_PAD_LEFT);
                    $stmt = $this->pdo->prepare("
                        INSERT INTO guest_loyalty_memberships (guest_id, loyalty_program_id, membership_number, enrolled_date)
                        VALUES (?, ?, ?, CURDATE())
                    ");
                    $stmt->execute([$guest_id, $program['id'], $membership_number]);
                    $membership_id = $this->pdo->getLastInsertId();
                    $current_points = 0;
                } else {
                    return false; // Guest not enrolled and auto-enrollment disabled
                }
            } else {
                $membership_id = $membership['id'];
                $current_points = $membership['points_balance'];
            }

            // Calculate points to award
            $points_to_award = 0;
            if ($nights_stayed > 0) {
                $points_to_award += $nights_stayed * $program['points_per_stay'];
            }
            if ($amount_spent > 0) {
                $points_to_award += $amount_spent * $program['points_per_dollar'];
            }

            if ($points_to_award > 0) {
                // Update points balance
                $stmt = $this->pdo->prepare("
                    UPDATE guest_loyalty_memberships
                    SET points_balance = points_balance + ?
                    WHERE id = ?
                ");
                $stmt->execute([$points_to_award, $membership_id]);

                // Record transaction
                $stmt = $this->pdo->prepare("
                    INSERT INTO loyalty_transactions (guest_loyalty_id, transaction_type, points_amount, description)
                    VALUES (?, 'earn', ?, ?)
                ");
                $stmt->execute([
                    $membership_id,
                    $points_to_award,
                    "Earned {$points_to_award} points for stay ({$nights_stayed} nights, $" . number_format($amount_spent, 2) . ")"
                ]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error awarding loyalty points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Redeem loyalty points for a discount
     * @param int $guest_id The guest ID
     * @param float $points_to_redeem Number of points to redeem
     * @param float $reservation_amount The reservation amount
     * @return array|false Returns redemption details or false if failed
     */
    public function redeemLoyaltyPoints($guest_id, $points_to_redeem, $reservation_amount) {
        try {
            // Get guest loyalty membership
            $stmt = $this->pdo->prepare("
                SELECT glm.*, lp.minimum_points_redeem FROM guest_loyalty_memberships glm
                JOIN loyalty_programs lp ON glm.loyalty_program_id = lp.id
                WHERE glm.guest_id = ? AND glm.is_active = 1 AND lp.is_active = 1
            ");
            $stmt->execute([$guest_id]);
            $membership = $stmt->fetch();

            if (!$membership || $membership['points_balance'] < $points_to_redeem) {
                return false; // Insufficient points or no membership
            }

            if ($points_to_redeem < $membership['minimum_points_redeem']) {
                return false; // Minimum redemption not met
            }

            // Calculate discount (assuming 100 points = $1)
            $discount_amount = min($points_to_redeem / 100, $reservation_amount);

            // Update points balance
            $stmt = $this->pdo->prepare("
                UPDATE guest_loyalty_memberships
                SET points_balance = points_balance - ?
                WHERE id = ?
            ");
            $stmt->execute([$points_to_redeem, $membership['id']]);

            // Record redemption transaction
            $stmt = $this->pdo->prepare("
                INSERT INTO loyalty_transactions (guest_loyalty_id, transaction_type, points_amount, description)
                VALUES (?, 'redeem', ?, ?)
            ");
            $stmt->execute([
                $membership['id'],
                $points_to_redeem,
                "Redeemed {$points_to_redeem} points for $" . number_format($discount_amount, 2) . " discount"
            ]);

            return [
                'points_redeemed' => $points_to_redeem,
                'discount_amount' => round($discount_amount, 2),
                'remaining_points' => $membership['points_balance'] - $points_to_redeem
            ];
        } catch (Exception $e) {
            error_log("Error redeeming loyalty points: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get guest loyalty information
     * @param int $guest_id The guest ID
     * @return array|false Returns loyalty information or false if not found
     */
    public function getGuestLoyaltyInfo($guest_id) {
        $stmt = $this->pdo->prepare("
            SELECT glm.*, lp.name as program_name, lp.points_per_stay, lp.points_per_dollar
            FROM guest_loyalty_memberships glm
            JOIN loyalty_programs lp ON glm.loyalty_program_id = lp.id
            WHERE glm.guest_id = ? AND glm.is_active = 1 AND lp.is_active = 1
        ");
        $stmt->execute([$guest_id]);
        return $stmt->fetch();
    }
}

/**
 * Helper function to get marketing helper instance
 * @return MarketingHelper
 */
function getMarketingHelper() {
    static $marketing_helper = null;
    if ($marketing_helper === null) {
        $pdo = getPdo();
        $marketing_helper = new MarketingHelper($pdo);
    }
    return $marketing_helper;
}
