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

        // Hissə 7.5 — bildiriş mətnləri kataloqu (sprintf yer tutucuları ilə)
        'notify_new_offer' => 'Yeni təklif: %s — %s AZN (%s)',
        'notify_selected' => 'Siz seçildiniz! %s elanı üçün müştəri ilə əlaqə saxlayın.',
        'notify_selection_cancelled' => 'Müştəri seçimi ləğv etdi. Elan yenidən aktivdir.',
        'notify_driver_declined' => 'Sürücü imtina etdi. Elanınız yenidən aktivdir.',
        'notify_order_closed' => 'Sifariş bağlandı. Uğurlar!',
        'notify_order_expired' => 'Elanınızın müddəti bitdi. İstəsəniz yenidən dərc edin.',
        'notify_reminder' => 'Əgər sürücü ilə razılaşmısınızsa, zəhmət olmasa sifarişi bağlayın.',
        'notify_route_watch' => 'Yeni elan: %s → %s, %s',

        'order_not_found' => 'Elan tapılmadı.',
        'offer_not_found' => 'Təklif tapılmadı.',
        'forbidden' => 'Bu əməliyyata icazəniz yoxdur.',
        'order_not_active' => 'Bu elana artıq təklif göndərmək mümkün deyil.',
        'offer_duplicate' => 'Bu elana artıq aktiv təklifiniz var.',
        'order_close_invalid_status' => 'Sifariş yalnız danışıq gedən statusunda bağlana bilər.',
        'order_cancel_invalid_status' => 'Seçim yalnız danışıq gedən statusda ləğv edilə bilər.',
        'order_republish_invalid_status' => 'Yalnız müddəti bitmiş elan yenidən dərc edilə bilər.',
        'offer_select_invalid_status' => 'Bu elan üçün seçim artıq edilib və ya elan aktiv deyil.',
        'offer_decline_invalid_status' => 'Yalnız seçilmiş təklif imtina edilə bilər.',
        'rating_exists' => 'Bu sifariş üçün artıq qiymətləndirmə göndərilib.',
        'rating_invalid_status' => 'Yalnız bağlanmış sifarişlər qiymətləndirilə bilər.',
        'images_limit' => 'Maksimum 5 şəkil yükləyə bilərsiniz.',
        'past_date' => 'Keçmiş tarix seçilə bilməz.',
        'subscription_required' => 'Təklif göndərmək üçün abunə lazımdır.',
    ];
}

function text(string $key): string
{
    return texts()[$key] ?? $key;
}
