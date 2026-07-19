<?php
declare(strict_types=1);

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
