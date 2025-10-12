<?php
function formatCurrencyPhpPeso(float $amount, int $decimals = 2): string {
  // Prefer Intl NumberFormatter if available for proper locale formatting
  if (class_exists('NumberFormatter')) {
    $formatter = new NumberFormatter('en_PH', NumberFormatter::CURRENCY);
    $formatted = $formatter->formatCurrency($amount, 'PHP');
    if ($formatted !== false) {
      return $formatted;
    }
  }
  // Fallback: manual formatting with peso sign
  return '₱' . number_format($amount, $decimals);
}


