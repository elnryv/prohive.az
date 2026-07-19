<?php
declare(strict_types=1);

function normalize_phone(string $operatorPrefix, string $number): ?string
{
    $prefix = preg_replace('/\D/', '', $operatorPrefix);
    $digits = preg_replace('/\D/', '', $number);

    if (strlen($prefix) !== 3 || strlen($digits) !== 7) {
        return null;
    }

    return '994' . $prefix . $digits;
}

function is_valid_pin(string $pin, int $length): bool
{
    return preg_match('/^\d{' . $length . '}$/', $pin) === 1;
}

function require_fields(array $data, array $fields): ?string
{
    foreach ($fields as $field) {
        if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
            return $field;
        }
    }
    return null;
}
