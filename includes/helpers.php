<?php

// Currency formatting function
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

// Password strength validation
function validatePasswordStrength(string $password): array {
  $errors = [];
  $strength = 0;

  if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long';
  } else {
    $strength++;
  }

  if (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter';
  } else {
    $strength++;
  }

  if (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Password must contain at least one lowercase letter';
  } else {
    $strength++;
  }

  if (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one number';
  } else {
    $strength++;
  }

  if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $errors[] = 'Password must contain at least one special character';
  } else {
    $strength++;
  }

  return [
    'valid' => empty($errors),
    'errors' => $errors,
    'strength' => $strength
  ];
}
