<?php
/**
 * Validation and Sanitization Functions
 * Provides form data validation and sanitization utilities
 */

/**
 * Sanitize and validate form data based on rules
 * 
 * @param array $data The form data to validate
 * @param array $rules The validation rules
 * @return array Array with 'is_valid', 'data', and 'errors' keys
 */
function sanitizeFormData(array $data, array $rules): array {
    $sanitized = [];
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        // Handle required fields
        if (isset($rule['required']) && $rule['required'] && (is_null($value) || $value === '')) {
            $errors[] = $rule['error_message'] ?? "Field '{$field}' is required.";
            continue;
        }
        
        // Skip validation if field is empty and not required
        if (is_null($value) || $value === '') {
            $sanitized[$field] = $value;
            continue;
        }
        
        // Type validation and sanitization
        switch ($rule['type'] ?? 'string') {
            case 'string':
                $sanitized[$field] = trim((string)$value);
                if (isset($rule['max_length']) && strlen($sanitized[$field]) > $rule['max_length']) {
                    $errors[] = $rule['error_message'] ?? "Field '{$field}' exceeds maximum length of {$rule['max_length']} characters.";
                }
                break;
                
            case 'numeric':
                $sanitized[$field] = is_numeric($value) ? (float)$value : null;
                if (is_null($sanitized[$field])) {
                    $errors[] = $rule['error_message'] ?? "Field '{$field}' must be a valid number.";
                } else {
                    if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                        $errors[] = $rule['error_message'] ?? "Field '{$field}' must be at least {$rule['min']}.";
                    }
                    if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                        $errors[] = $rule['error_message'] ?? "Field '{$field}' must not exceed {$rule['max']}.";
                    }
                }
                break;
                
            case 'date':
                $sanitized[$field] = trim((string)$value);
                if (!empty($sanitized[$field]) && !strtotime($sanitized[$field])) {
                    $errors[] = $rule['error_message'] ?? "Field '{$field}' must be a valid date.";
                }
                break;
                
            case 'email':
                $sanitized[$field] = filter_var(trim($value), FILTER_SANITIZE_EMAIL);
                if (!empty($sanitized[$field]) && !filter_var($sanitized[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $rule['error_message'] ?? "Field '{$field}' must be a valid email address.";
                }
                break;
                
            case 'int':
                $sanitized[$field] = filter_var($value, FILTER_VALIDATE_INT);
                if ($sanitized[$field] === false) {
                    $errors[] = $rule['error_message'] ?? "Field '{$field}' must be a valid integer.";
                } else {
                    if (isset($rule['min']) && $sanitized[$field] < $rule['min']) {
                        $errors[] = $rule['error_message'] ?? "Field '{$field}' must be at least {$rule['min']}.";
                    }
                    if (isset($rule['max']) && $sanitized[$field] > $rule['max']) {
                        $errors[] = $rule['error_message'] ?? "Field '{$field}' must not exceed {$rule['max']}.";
                    }
                }
                break;
                
            default:
                $sanitized[$field] = trim((string)$value);
                break;
        }
    }
    
    return [
        'is_valid' => empty($errors),
        'data' => $sanitized,
        'errors' => $errors
    ];
}

/**
 * Validate email address
 * 
 * @param string $email
 * @return bool
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize string input
 * 
 * @param string $input
 * @return string
 */
function sanitizeString(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate date format
 * 
 * @param string $date
 * @param string $format
 * @return bool
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Check if date is in the future
 * 
 * @param string $date
 * @return bool
 */
function isFutureDate(string $date): bool {
    return strtotime($date) > time();
}

/**
 * Check if date is in the past
 * 
 * @param string $date
 * @return bool
 */
function isPastDate(string $date): bool {
    return strtotime($date) < time();
}
