<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Env;
use App\Core\ValidationException;
use App\Models\Ayar;
use App\Models\Kurye;
use App\Models\LegalLog;
use App\Models\Odenis;
use App\Providers\PaymentProviderFactory;

/**
 * Abunə ödəniş axını — bax CLAUDE.md bölmə 9.1.1. Yarım-avtomatik: kuryer özü
 * "Ödə" basır, webhook idempotent şəkildə emal olunur (eyni order_id iki dəfə
 * emal edilmir).
 */
final class OdenisService
{
    private const ABUNE_GUN = 30;
    private const DEFAULT_QIYMET = '15.00';

    private Odenis $odenisler;
    private Ayar $ayarlar;
    private LegalLog $legalLogs;
    private AbunelikService $abunelikService;
    private Kurye $kuryeler;
    private PushService $pushService;

    public function __construct()
    {
        $this->odenisler = new Odenis();
        $this->ayarlar = new Ayar();
        $this->legalLogs = new LegalLog();
        $this->abunelikService = new AbunelikService();
        $this->kuryeler = new Kurye();
        $this->pushService = new PushService();
    }

    /**
     * @throws ValidationException
     */
    public function basla(int $kuryeId): array
    {
        if ($this->odenisler->findPendingByKurye($kuryeId) !== null) {
            throw new ValidationException('Artıq gözləyən ödənişiniz var, bir neçə dəqiqə gözləyin.');
        }

        $qiymet = (float) ($this->ayarlar->get('abune_qiymeti') ?? self::DEFAULT_QIYMET);
        $provayderAdi = Env::get('PAYMENT_PROVIDER', 'payriff');

        $provider = PaymentProviderFactory::current();
        // Sessiya provayderdən ƏVVƏL alınır — Payriff kimi provayderlər öz
        // order id-lərini (UUID) özləri yaradır və cavabda qaytarırlar,
        // ona görə yerli qeyd yalnız real order id məlumdur olandan sonra açılır.
        $sessiya = $provider->baslat($kuryeId, $qiymet, self::orderIdYarat());

        $this->odenisler->create($kuryeId, $sessiya->orderId, $qiymet, $provayderAdi);

        return ['order_id' => $sessiya->orderId, 'redirect_url' => $sessiya->redirectUrl];
    }

    /**
     * @throws ValidationException
     */
    public function sonHal(int $kuryeId): array
    {
        $odenis = $this->odenisler->findPendingByKurye($kuryeId) ?? $this->odenisler->findSonuncuByKurye($kuryeId);

        if ($odenis === null) {
            throw new ValidationException('Ödəniş qeydi tapılmadı.');
        }

        return [
            'order_id' => $odenis['order_id'],
            'status' => $odenis['status'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function webhookIsle(array $data, string $ip): void
    {
        $provider = PaymentProviderFactory::current();
        $neticeSonuc = $provider->callbackDogrula($data);

        if (!$neticeSonuc->gecerli) {
            throw new ValidationException('İmza doğrulanmadı.');
        }

        $odenis = $this->odenisler->findByOrderId($neticeSonuc->orderId);
        if ($odenis === null) {
            throw new ValidationException('Ödəniş qeydi tapılmadı.');
        }

        if ($odenis['status'] !== 'gozlemede') {
            // Artıq emal olunub — idempotent davranış, sakitcə çıxılır.
            return;
        }

        if ($neticeSonuc->ugurlu) {
            $this->odenisler->updateStatus((int) $odenis['id'], 'ugurlu', $neticeSonuc->ham);
            $this->abunelikService->odenisIleUzat((int) $odenis['kurye_id'], self::ABUNE_GUN, (int) $odenis['id'], $ip);
            $this->legalLogs->yaz(null, null, 'odenis_ugurlu', [
                'order_id' => $odenis['order_id'], 'kurye_id' => $odenis['kurye_id'], 'mebleg' => $odenis['mebleg'],
            ], $ip);

            $kurye = $this->kuryeler->findById((int) $odenis['kurye_id']);
            if ($kurye !== null) {
                $this->pushService->gonder(
                    (int) $kurye['user_id'],
                    'Abunəniz yeniləndi',
                    '+' . self::ABUNE_GUN . ' gün əlavə olundu'
                );
            }
        } else {
            $this->odenisler->updateStatus((int) $odenis['id'], 'ugursuz', $neticeSonuc->ham);
            $this->legalLogs->yaz(null, null, 'odenis_ugursuz', [
                'order_id' => $odenis['order_id'], 'kurye_id' => $odenis['kurye_id'],
            ], $ip);
        }
    }

    private static function orderIdYarat(): string
    {
        return 'OD' . date('YmdHis') . strtoupper(bin2hex(random_bytes(4)));
    }
}
