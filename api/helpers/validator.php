<?php
// api/helpers/validator.php

function validate_required($data, $fields) {
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        error_response('Missing required fields: ' . implode(', ', $missing), 400);
    }
}

function validate_email($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Invalid email format', 400);
    }
}

function validate_enum($value, $allowed, $field_name) {
    if (!in_array($value, $allowed, true)) {
        error_response("Invalid {$field_name}. Allowed: " . implode(', ', $allowed), 400);
    }
}

function validate_date($date, $field_name = 'date') {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        error_response("Invalid {$field_name} format. Use YYYY-MM-DD", 400);
    }
}

function validate_positive_number($value, $field_name) {
    if (!is_numeric($value) || $value < 0) {
        error_response("{$field_name} must be a positive number", 400);
    }
}

function sanitize_string($str) {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
