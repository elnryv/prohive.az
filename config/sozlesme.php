<?php

declare(strict_types=1);

/**
 * İstifadəçi Sözləşməsi mətni (bax CLAUDE.md bölmə 1 "Hüquqi hissə" qeydi və REG-2.2).
 * Hüquqi mətn hüquqşünasla dəqiqləşdirilənə qədər (Faza 7) bu, YER TUTUCUdur.
 * Qəbul faktı və tarixi `users.sozlesme_qebul` / `users.sozlesme_tarix`-də saxlanılır;
 * mətnin özü yalnız burada — dəyişdirilməsi asan olsun deyə ayrıca fayldadır.
 */

return [
    'az' => 'İstifadəçi Sözləşməsi mətni hazırlanmaqdadır (hüquqşünas təsdiqi gözlənilir). '
        . 'Qeydiyyatdan keçməklə platformanın vasitəçi statusunu və istifadə qaydalarını qəbul edirsiniz.',
    'ru' => 'Текст Пользовательского соглашения находится в разработке (ожидается подтверждение юриста). '
        . 'Регистрируясь, вы принимаете посреднический статус платформы и правила пользования.',
    'en' => 'The User Agreement text is being finalised (pending legal review). '
        . 'By registering, you accept the platform\'s intermediary role and terms of use.',
];
