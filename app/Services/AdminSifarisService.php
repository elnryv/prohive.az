<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Models\LegalLog;
use App\Models\Sifaris;

/**
 * Admin panel — sifariş idarəsi, filtr, pagination (bax bölmə 8.3).
 */
final class AdminSifarisService
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;
    private const STATUSLAR = ['axtarisda', 'goturulub', 'tamamlandi', 'legv', 'passiv'];

    private Sifaris $sifarisler;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->sifarisler = new Sifaris();
        $this->legalLogs = new LegalLog();
    }

    /**
     * @throws ValidationException
     */
    public function siyahi(array $filtrler, int $page, int $limit): array
    {
        if (isset($filtrler['status']) && !in_array($filtrler['status'], self::STATUSLAR, true)) {
            throw new ValidationException('Status filtri düzgün deyil.');
        }

        $page = max(1, $page);
        $limit = min(self::MAX_LIMIT, max(1, $limit ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $data = $this->sifarisler->listForAdmin($filtrler, $limit, $offset);
        $total = $this->sifarisler->countForAdmin($filtrler);

        return [
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function detal(int $id): array
    {
        $sifaris = $this->sifarisler->findDetailForAdmin($id);
        if ($sifaris === null) {
            throw new ValidationException('Sifariş tapılmadı.');
        }

        $sifaris['tarixce'] = $this->legalLogs->bySifarisId($id);

        return $sifaris;
    }
}
