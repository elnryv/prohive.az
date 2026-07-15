<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ValidationException;
use App\Models\DasiyiciOlcusu;
use App\Models\LegalLog;
use App\Models\User;

/**
 * Admin panel — müştəri/kuryer siyahıları, pop-up detal, bloklama (bax bölmə 8.2).
 */
final class AdminUserService
{
    private const MUSTERI_ROLLAR = ['musteri'];
    private const KURYE_ROLLAR = ['kurye', 'yukdasima'];
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;

    private User $users;
    private DasiyiciOlcusu $dasiyiciOlculeri;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->users = new User();
        $this->dasiyiciOlculeri = new DasiyiciOlcusu();
        $this->legalLogs = new LegalLog();
    }

    public function musteriler(?string $axtar, int $page, int $limit): array
    {
        return $this->siyahi(self::MUSTERI_ROLLAR, $axtar, $page, $limit);
    }

    public function kuryerler(?string $axtar, int $page, int $limit): array
    {
        return $this->siyahi(self::KURYE_ROLLAR, $axtar, $page, $limit);
    }

    private function siyahi(array $rollar, ?string $axtar, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = min(self::MAX_LIMIT, max(1, $limit ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $data = $this->users->listByRoller($rollar, $axtar, $limit, $offset);
        $total = $this->users->countByRoller($rollar, $axtar);

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
        $user = $this->users->findDetail($id);
        if ($user === null) {
            throw new ValidationException('İstifadəçi tapılmadı.');
        }

        if ($user['rol'] === 'yukdasima' && $user['kurye_id'] !== null) {
            $user['olculer'] = $this->dasiyiciOlculeri->olcuKodlariByKurye((int) $user['kurye_id']);
        }

        unset($user['parol_hash']);

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function blokla(int $id, string $sebeb, int $adminId, string $ip): void
    {
        $this->deyisStatus($id, 'bloklu', 'istifadeci_bloklandi', $sebeb, $adminId, $ip);
    }

    /**
     * @throws ValidationException
     */
    public function blokdanCixar(int $id, string $sebeb, int $adminId, string $ip): void
    {
        $this->deyisStatus($id, 'aktiv', 'istifadeci_blokdan_cixarildi', $sebeb, $adminId, $ip);
    }

    /**
     * @throws ValidationException
     */
    private function deyisStatus(int $id, string $status, string $hadise, string $sebeb, int $adminId, string $ip): void
    {
        if (trim($sebeb) === '') {
            throw new ValidationException('Səbəb sahəsi mütləqdir.');
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new ValidationException('İstifadəçi tapılmadı.');
        }

        $this->users->setStatus($id, $status);
        $this->legalLogs->yaz($adminId, null, $hadise, ['istifadeci_id' => $id, 'sebeb' => $sebeb], $ip);
    }
}
