<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class SequenceTrigger
{
    /**
     * Abunəçiyə tag əlavə olunanda çağırılır. Əgər həmin tag bir sequence-in
     * trigger_tag_id-sidirsə və abunəçi hələ o sequence-i işlətmirsə, subscriber_sequences-ə
     * sətir əlavə edir (ilk mesaj növbəyə qoyulur).
     */
    public static function onTagAdded(Database $db, int $subscriberId, int $tagId): void
    {
        $sequences = $db->fetchAll(
            "SELECT * FROM sequences WHERE trigger_tag_id = :tag_id AND status = 'active'",
            ['tag_id' => $tagId]
        );

        foreach ($sequences as $sequence) {
            $alreadyRunning = $db->fetch(
                "SELECT id FROM subscriber_sequences
                 WHERE subscriber_id = :sub_id AND sequence_id = :seq_id AND status = 'running'",
                ['sub_id' => $subscriberId, 'seq_id' => $sequence['id']]
            );

            if ($alreadyRunning !== null) {
                continue;
            }

            $firstMessage = $db->fetch(
                'SELECT * FROM sequence_messages WHERE sequence_id = :seq_id ORDER BY sort_order ASC, id ASC LIMIT 1',
                ['seq_id' => $sequence['id']]
            );

            if ($firstMessage === null) {
                continue;
            }

            $nextSendAt = date('Y-m-d H:i:s', strtotime('+' . (int) $firstMessage['delay_hours'] . ' hours'));

            $db->insert('subscriber_sequences', [
                'subscriber_id' => $subscriberId,
                'sequence_id' => $sequence['id'],
                'next_message_id' => $firstMessage['id'],
                'next_send_at' => $nextSendAt,
                'status' => 'running',
            ]);
        }
    }
}
