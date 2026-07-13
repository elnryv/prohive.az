<?php

declare(strict_types=1);

/**
 * Qeydiyyat formundakı QISA sözləşmə xülasəsi (bax CLAUDE.md bölmə 1 "Hüquqi qeyd" və REG-2.2).
 * Tam hüquqi mətn (10 sənəd, Faza 7) `config/legal/` altındadır və /huquqi marşrutu ilə
 * görünür — bu fayl yalnız checkbox yanındakı qısa xülasədir.
 * Qəbul faktı və tarixi `users.sozlesme_qebul` / `users.sozlesme_tarix`-də saxlanılır.
 */

return [
    'az' => 'Qeydiyyatdan keçməklə İstifadəçi Sözləşməsini və Məxfilik Siyasətini, '
        . 'platformanın vasitəçi statusunu və istifadə qaydalarını qəbul edirsiniz.',
    'ru' => 'Регистрируясь, вы принимаете Пользовательское соглашение и Политику '
        . 'конфиденциальности, посреднический статус платформы и правила пользования.',
    'en' => 'By registering, you accept the User Agreement and Privacy Policy, '
        . 'the platform\'s intermediary role, and the terms of use.',
];
