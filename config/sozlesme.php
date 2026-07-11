<?php

declare(strict_types=1);

/**
 * Qeydiyyat formundakı QISA sözləşmə xülasəsi (bax CLAUDE.md bölmə 1 "Hüquqi qeyd" və REG-2.2).
 * Tam hüquqi mətn (10 sənəd, QARALAMA statusunda, Faza 7) `config/legal/` altındadır və
 * /huquqi marşrutu ilə görünür — bu fayl yalnız checkbox yanındakı qısa xülasədir.
 * Qəbul faktı və tarixi `users.sozlesme_qebul` / `users.sozlesme_tarix`-də saxlanılır.
 */

return [
    'az' => 'İstifadəçi Sözləşməsi mətni hazırlanmaqdadır (hüquqşünas təsdiqi gözlənilir). '
        . 'Qeydiyyatdan keçməklə platformanın vasitəçi statusunu və istifadə qaydalarını qəbul edirsiniz.',
    'ru' => 'Текст Пользовательского соглашения находится в разработке (ожидается подтверждение юриста). '
        . 'Регистрируясь, вы принимаете посреднический статус платформы и правила пользования.',
    'en' => 'The User Agreement text is being finalised (pending legal review). '
        . 'By registering, you accept the platform\'s intermediary role and terms of use.',
];
