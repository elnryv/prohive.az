<?php
declare(strict_types=1);

// Vahid mətn kataloqu (Hissə 7.5 və ekran spesifikasiyalarındakı dırnaqlı mətnlər).
// Bütün istifadəçiyə görünən mətnlər buradan oxunur ki, tərcümə/dəyişiklik tək yerdən idarə olunsun.
function texts(): array
{
    return [
        'phone_incomplete' => 'Telefon nömrəsi natamamdır.',
        'registration_closed' => 'Hazırda yeni qeydiyyat müvəqqəti dayandırılıb.',
        'pin_mismatch' => 'PIN kodlar uyğun gəlmir.',
        'pin_wrong' => 'PIN kod yanlışdır.',
        'pin_locked' => 'Çox sayda yanlış cəhd. 15 dəqiqə sonra yenidən yoxlayın.',
        'name_required' => 'Adınızı daxil edin.',
        'surname_required' => 'Soyadınızı daxil edin.',
        'consent_required' => 'İstifadə Şərtləri və Məxfilik Siyasəti ilə razılaşmalısınız.',
        'phone_exists' => 'Bu nömrə ilə hesab artıq mövcuddur.',
        'phone_not_found' => 'Bu nömrə ilə hesab tapılmadı.',
        'invalid_credentials' => 'Telefon nömrəsi və ya PIN yanlışdır.',
        'account_blocked' => 'Hesabınız bloklanıb. Dəstəklə əlaqə saxlayın.',
    ];
}

function text(string $key): string
{
    return texts()[$key] ?? $key;
}
