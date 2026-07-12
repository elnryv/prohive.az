<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Core\ValidationException;
use App\Models\DasiyiciOlcusu;
use App\Models\Kurye;
use App\Models\LegalLog;
use App\Models\Sessiya;
use App\Models\User;
use App\Models\YukdasimaOlcusu;

/**
 * Qeydiyyat/giriş/çıxış biznes məntiqi — bax CLAUDE.md bölmə 2 (Aktyorlar və Qeydiyyat)
 * və bölmə 7.3.1 (Uzunmüddətli Sessiya).
 */
final class AuthService
{
    private const NEQLIYYAT_NOVLERI = ['avtomobil', 'moto', 'skuter', 'velosiped', 'piyada'];
    private const ROLLAR = ['musteri', 'kurye', 'yukdasima'];
    private const DILLER = ['az', 'ru', 'en'];
    private const WHATSAPP_TIPLERI = ['sexsi', 'business'];
    // Mövcud olmayan telefon nömrəsi üçün password_verify() çağırılmasa,
    // bcrypt hesablama vaxtının olmaması ilə mövcud/mövcud olmayan hesab
    // arasında timing side-channel yaranır — bu saxta hash həmişə eyni
    // hesablama xərcini yaradır ki, cavab vaxtı fərqlənməsin.
    private const DUMMY_HASH = '$2y$12$RY.MHhCqtOeyxmLALCHwCug1aUvP9mE4dwnEJp/jlAGeVMc6nmeHO';

    private User $users;
    private Kurye $kuryeler;
    private DasiyiciOlcusu $dasiyiciOlculeri;
    private YukdasimaOlcusu $yukdasimaOlculeri;
    private Sessiya $sessiyalar;
    private LegalLog $legalLogs;

    public function __construct()
    {
        $this->users = new User();
        $this->kuryeler = new Kurye();
        $this->dasiyiciOlculeri = new DasiyiciOlcusu();
        $this->yukdasimaOlculeri = new YukdasimaOlcusu();
        $this->sessiyalar = new Sessiya();
        $this->legalLogs = new LegalLog();
    }

    /**
     * @return array{id:int, rol:string}
     * @throws ValidationException
     */
    public function qeydiyyat(array $input, string $ip): array
    {
        $rol = (string) ($input['rol'] ?? '');
        if (!in_array($rol, self::ROLLAR, true)) {
            throw new ValidationException('Rol düzgün seçilməyib.');
        }

        $ad = trim((string) ($input['ad'] ?? ''));
        $soyad = trim((string) ($input['soyad'] ?? ''));
        $telefon = self::normalisePhone((string) ($input['telefon'] ?? ''));
        $whatsapp = self::normalisePhone((string) ($input['whatsapp'] ?? ''));
        $parol = (string) ($input['parol'] ?? '');
        $sozlesmeQebul = filter_var($input['sozlesme_qebul'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($ad === '' || $soyad === '' || $telefon === '' || $whatsapp === '') {
            throw new ValidationException('Bütün məcburi sahələr doldurulmalıdır.');
        }
        if (strlen($parol) < 6) {
            throw new ValidationException('Parol ən azı 6 simvol olmalıdır.');
        }
        if (!$sozlesmeQebul) {
            throw new ValidationException('İstifadəçi Sözləşməsini qəbul etmədən qeydiyyat tamamlanmır.');
        }
        if ($this->users->existsByTelefon($telefon)) {
            throw new ValidationException('Bu telefon nömrəsi ilə artıq qeydiyyat mövcuddur.');
        }

        $neqliyyat = null;
        $olcuIds = [];

        if ($rol === 'kurye') {
            $neqliyyat = (string) ($input['neqliyyat'] ?? '');
            if (!in_array($neqliyyat, self::NEQLIYYAT_NOVLERI, true)) {
                throw new ValidationException('Nəqliyyat növü düzgün seçilməyib.');
            }
        }

        if ($rol === 'yukdasima') {
            $secilenler = $input['olculer'] ?? [];
            if (!is_array($secilenler) || $secilenler === []) {
                throw new ValidationException('Ən azı bir yükdaşıma ölçüsü seçilməlidir.');
            }

            $olcuIds = array_values(array_unique(array_map('intval', $secilenler)));
            $movcud = $this->yukdasimaOlculeri->existingIds($olcuIds);
            if (count($movcud) !== count($olcuIds)) {
                throw new ValidationException('Seçilmiş ölçülərdən bəziləri mövcud deyil.');
            }
        }

        $dil = (string) ($input['dil'] ?? 'az');
        if (!in_array($dil, self::DILLER, true)) {
            $dil = 'az';
        }

        $whatsappTip = (string) ($input['whatsapp_tip'] ?? 'sexsi');
        if (!in_array($whatsappTip, self::WHATSAPP_TIPLERI, true)) {
            $whatsappTip = 'sexsi';
        }

        $userId = $this->users->create([
            'ad' => $ad,
            'soyad' => $soyad,
            'telefon' => $telefon,
            'parol_hash' => password_hash($parol, PASSWORD_DEFAULT),
            'whatsapp' => $whatsapp,
            'whatsapp_tip' => $whatsappTip,
            'rol' => $rol,
            'dil' => $dil,
        ]);

        if ($rol === 'kurye' || $rol === 'yukdasima') {
            $kuryeId = $this->kuryeler->create($userId, $neqliyyat);

            if ($rol === 'yukdasima' && $olcuIds !== []) {
                $this->dasiyiciOlculeri->attachSizes($kuryeId, $olcuIds);
            }
        }

        $this->legalLogs->yaz($userId, null, 'qeydiyyat', ['rol' => $rol], $ip);

        return ['id' => $userId, 'rol' => $rol];
    }

    /**
     * @return array{user:array, remember_token:?string}
     * @throws ValidationException
     */
    public function giris(string $telefonRaw, string $parol, bool $rememberMe, string $ip, ?string $cihaz): array
    {
        $telefon = self::normalisePhone($telefonRaw);
        $user = $this->users->findByTelefon($telefon);
        $parolDogrudur = password_verify($parol, $user['parol_hash'] ?? self::DUMMY_HASH);

        if ($user === null || !$parolDogrudur) {
            throw new ValidationException('Telefon nömrəsi və ya parol yanlışdır.');
        }
        if ($user['status'] !== 'aktiv') {
            throw new ValidationException('Hesabınız bloklanıb.');
        }

        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('rol', $user['rol']);

        $rememberToken = null;
        if ($rememberMe) {
            $rememberToken = bin2hex(random_bytes(32));
            $this->sessiyalar->create((int) $user['id'], hash('sha256', $rememberToken), $cihaz);
        }

        $this->legalLogs->yaz((int) $user['id'], null, 'giris', [], $ip);

        return ['user' => $user, 'remember_token' => $rememberToken];
    }

    public function cixis(?string $rememberToken): void
    {
        if ($rememberToken !== null && $rememberToken !== '') {
            $this->sessiyalar->deleteByTokenHash(hash('sha256', $rememberToken));
        }

        Session::destroy();
    }

    private static function normalisePhone(string $phone): string
    {
        return (string) preg_replace('/\D/', '', $phone);
    }
}
