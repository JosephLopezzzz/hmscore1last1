<?php
// includes/helpers.php
// Utility & billing helper functions — no duplicates from db.php

if (!function_exists('formatCurrencyPhpPeso')) {
  function formatCurrencyPhpPeso(float $amount, int $decimals = 2): string {
    if (class_exists('NumberFormatter')) {
      $formatter = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);
      $formatted = $formatter->formatCurrency($amount, 'PHP');
      if ($formatted !== false) return $formatted;
    }
    return '₱' . number_format($amount, $decimals);
  }
}

if (!function_exists('generateFolioId')) {
  function generateFolioId(): string {
    return 'FOL-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('generateTxnId')) {
  function generateTxnId(): string {
    return 'TXN-' . date('Ymd') . '-' . str_pad((string)mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
  }
}

if (!function_exists('validateAmount')) {
  function validateAmount($amount): bool {
    return is_numeric($amount) && $amount >= 0;
  }
}

if (!function_exists('calculateBalance')) {
  function calculateBalance(float $totalCharges, float $totalPaid): float {
    $balance = $totalCharges - $totalPaid;
    return $balance < 0 ? 0 : $balance;
  }
}

if (!function_exists('determineFolioStatus')) {
  function determineFolioStatus(float $balance): string {
    if ($balance <= 0) return 'paid';
    if ($balance > 0) return 'partial';
    return 'open';
  }
}

if (!function_exists('jsonResponse')) {
  function jsonResponse(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
  }
}
?>
