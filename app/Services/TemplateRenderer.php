<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class TemplateRenderer
{
    /**
     * {{field_key}} dəyişənlərini abunəçinin subscriber_fields + əsas sahələri ilə əvəz edir.
     * Tapılmayan dəyişən boş sətir olur.
     */
    public static function render(Database $db, string $text, array $subscriber): string
    {
        $fields = $db->fetchAll(
            'SELECT field_key, field_value FROM subscriber_fields WHERE subscriber_id = :id',
            ['id' => $subscriber['id']]
        );

        $map = [
            'first_name' => $subscriber['first_name'] ?? '',
            'last_name' => $subscriber['last_name'] ?? '',
            'username' => $subscriber['username'] ?? '',
            'name' => trim(($subscriber['first_name'] ?? '') . ' ' . ($subscriber['last_name'] ?? '')),
        ];

        foreach ($fields as $field) {
            $map[$field['field_key']] = $field['field_value'] ?? '';
        }

        return preg_replace_callback('#\{\{\s*([a-zA-Z0-9_]+)\s*\}\}#', static function (array $m) use ($map): string {
            return (string) ($map[$m[1]] ?? '');
        }, $text) ?? $text;
    }
}
