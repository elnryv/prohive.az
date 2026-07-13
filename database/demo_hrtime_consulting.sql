-- =====================================================================
-- DEMO BOT: HRtime Consulting
-- Bu skript mövcud bir botun üzərinə İK (HR) konsaltinq şirkəti üçün
-- tam işlək demo söhbət axını (menyu, FAQ, lead-toplama, sequence)
-- əlavə edir. Müştəriyə "canlı nümunə" göstərmək üçün nəzərdə tutulub.
--
-- İSTİFADƏ QAYDASI:
-- 1. Admin paneldə (Botlarım -> Yeni bot) real Telegram token ilə boş
--    bir bot yaradın (məs. adı "HRtime Consulting").
-- 2. Yaranan botun ID-sini URL-dən görün: /bots/{BOT_ID}/nodes
-- 3. Aşağıdakı sətirdə @bot_id dəyərini həmin ID ilə əvəz edin.
-- 4. mysql -u birlikde_user -p birlikde_bots < database/demo_hrtime_consulting.sql
-- 5. Telegram-da botunuza /start yazıb axını sınayın.
--
-- QEYD: telefon/email kimi əlaqə məlumatları yer tutucudur (placeholder) —
-- real sayt/brendinq hazır olanda mesajların içindəki mətni admin
-- paneldəki Node redaktoru vasitəsilə asanlıqla dəyişə bilərsiniz.
-- =====================================================================

SET NAMES utf8mb4;

SET @bot_id = 1; -- <<< BURAYA ÖZ BOT ID-NİZİ YAZIN

-- ---------------------------------------------------------------------
-- 1) NODE-LARI YARAT (əlaqələr olmadan, sonra bağlayacağıq)
-- ---------------------------------------------------------------------

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'start', 'Başlanğıc',
'buttons',
'Salam, {{first_name}}! 👋 <b>HRtime Consulting</b>-ə xoş gəldiniz.\n\nBiz bizneslərə insan resursları (İK) idarəetməsi və iş vaxtı/performans optimallaşdırılması üzrə peşəkar məsləhət xidmətləri göstəririk.\n\nSizə necə kömək edə bilərik?');
SET @start_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'services', 'Xidmətlər',
'buttons',
'📋 <b>Xidmətlərimiz:</b>\n\n🧩 İşə qəbul və seçim (Recruitment)\n⏱ İş vaxtı və davamiyyət idarəetməsi\n📊 Performans qiymətləndirmə sistemləri\n🎓 Korporativ təlim proqramları\n📑 İK sənədləşmə və outsourcing\n\nƏtraflı məlumat üçün "Məsləhət sifariş et" düyməsinə basın.');
SET @services_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'faq', 'FAQ menyu',
'buttons',
'💬 <b>Tez-tez verilən suallar</b>\n\nMövzu seçin, və ya sualınızı sərbəst mətn kimi yazın (məs. "qiymət", "müddət", "əlaqə") — bot açar sözlə də tanıyacaq.');
SET @faq_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'faq_pricing', 'FAQ - Qiymət',
'buttons',
'💰 <b>Qiymətlər</b>\n\nXidmət qiymətləri layihənin həcmi və komandanızın ölçüsündən asılı olaraq fərdi hesablanır.\n\nDəqiq təklif almaq üçün "Məsləhət sifariş et" bölməsindən bizə müraciət edin — 24 saat ərzində sizinlə əlaqə saxlayacağıq.');
SET @faq_pricing_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'faq_duration', 'FAQ - Müddət',
'buttons',
'⏱ <b>Müddət</b>\n\nAdi bir İK auditi 1–2 həftə, tam performans/vaxt idarəetmə sisteminin qurulması isə layihədən asılı olaraq 4–6 həftə çəkir.');
SET @faq_duration_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'faq_contact', 'FAQ - Əlaqə',
'buttons',
'📞 <b>Əlaqə məlumatları</b>\n\nTelefon: +994 XX XXX XX XX\nEmail: info@hrtime.az\nSayt: hrtime.az\nİş saatları: B.e – C.a, 09:00–18:00\n\n<i>(Bu məlumatlar nümunədir — real sayt/brendinq hazır olanda Node redaktorundan dəyişin.)</i>');
SET @faq_contact_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'about', 'Haqqımızda',
'buttons',
'🏢 <b>HRtime Consulting</b>\n\nTəcrübəli komandamızla bizneslərin insan resursları və vaxt idarəetməsi proseslərini optimallaşdırırıq.\n\nMəqsədimiz: komandanızın məhsuldarlığını artırmaq və İK proseslərini sadələşdirmək.');
SET @about_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'fallback', 'Anlaşılmayan mesaj',
'buttons',
'🤔 Sualınızı tam başa düşmədim. Aşağıdakı menyudan seçim edə, və ya birbaşa bizimlə əlaqə saxlaya bilərsiniz: 📞 +994 XX XXX XX XX');
SET @fallback_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text, input_field_key) VALUES
(@bot_id, 'lead_name', 'Sorğu - Ad', 'input', '✍️ Məsləhət sorğusu üçün adınızı və soyadınızı yazın:', 'full_name');
SET @lead_name_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text, input_field_key) VALUES
(@bot_id, 'lead_company', 'Sorğu - Şirkət', 'input', '🏢 Şirkətinizin adını yazın:', 'company_name');
SET @lead_company_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text, input_field_key) VALUES
(@bot_id, 'lead_phone', 'Sorğu - Telefon', 'input', '📱 Əlaqə üçün telefon nömrənizi yazın:', 'phone');
SET @lead_phone_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'lead_confirm', 'Sorğu - Təsdiq',
'buttons',
'✅ Məlumatlarınızı yoxlayın:\n\n👤 Ad: {{full_name}}\n🏢 Şirkət: {{company_name}}\n📱 Telefon: {{phone}}\n\nDüzgündürsə göndərin:');
SET @lead_confirm_id = LAST_INSERT_ID();

INSERT INTO bot_nodes (bot_id, node_key, title, node_type, message_text) VALUES
(@bot_id, 'lead_thanks', 'Sorğu - Təşəkkür',
'final',
'🙌 Təşəkkür edirik, {{full_name}}! Sorğunuz qeydə alındı. Mütəxəssisimiz 24 saat ərzində {{phone}} nömrəsi ilə sizinlə əlaqə saxlayacaq.');
SET @lead_thanks_id = LAST_INSERT_ID();

-- ---------------------------------------------------------------------
-- 2) NODE-LARI BAĞLA (input zənciri + botun başlanğıc node-u)
-- ---------------------------------------------------------------------

UPDATE bot_nodes SET input_next_node = @lead_company_id WHERE id = @lead_name_id;
UPDATE bot_nodes SET input_next_node = @lead_phone_id WHERE id = @lead_company_id;
UPDATE bot_nodes SET input_next_node = @lead_confirm_id WHERE id = @lead_phone_id;

UPDATE bots SET start_node_id = @start_id WHERE id = @bot_id;

-- ---------------------------------------------------------------------
-- 3) DÜYMƏLƏR
-- ---------------------------------------------------------------------

-- start menyusu
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@start_id, '🧩 Xidmətlərimiz', 'inline', @services_id, 1),
(@start_id, '💬 Sual-cavab (FAQ)', 'inline', @faq_id, 2),
(@start_id, '📞 Məsləhət sifariş et', 'inline', @lead_name_id, 3),
(@start_id, '🌐 Haqqımızda', 'inline', @about_id, 4);

-- services
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@services_id, '📞 Məsləhət sifariş et', 'inline', @lead_name_id, 1),
(@services_id, '⬅️ Geri', 'inline', @start_id, 2);

-- faq menyu
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@faq_id, '💰 Qiymət', 'inline', @faq_pricing_id, 1),
(@faq_id, '⏱ Müddət', 'inline', @faq_duration_id, 2),
(@faq_id, '📞 Əlaqə', 'inline', @faq_contact_id, 3),
(@faq_id, '⬅️ Geri', 'inline', @start_id, 4);

-- faq alt-səhifələri -> FAQ-a qayıt
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@faq_pricing_id, '⬅️ FAQ-a qayıt', 'inline', @faq_id, 1),
(@faq_duration_id, '⬅️ FAQ-a qayıt', 'inline', @faq_id, 1),
(@faq_contact_id, '⬅️ FAQ-a qayıt', 'inline', @faq_id, 1);

-- about
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@about_id, '⬅️ Geri', 'inline', @start_id, 1);

-- fallback
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order) VALUES
(@fallback_id, '🏠 Ana menyu', 'inline', @start_id, 1);

-- ---------------------------------------------------------------------
-- 4) AÇAR SÖZLƏR (sərbəst mətnlə də FAQ-a çıxış üçün)
-- ---------------------------------------------------------------------

INSERT INTO bot_keywords (bot_id, keyword, match_type, target_node_id) VALUES
(@bot_id, 'qiymət', 'contains', @faq_pricing_id),
(@bot_id, 'qiymet', 'contains', @faq_pricing_id),
(@bot_id, 'müddət', 'contains', @faq_duration_id),
(@bot_id, 'muddet', 'contains', @faq_duration_id),
(@bot_id, 'əlaqə', 'contains', @faq_contact_id),
(@bot_id, 'elaqe', 'contains', @faq_contact_id),
(@bot_id, 'xidmət', 'contains', @services_id),
(@bot_id, 'xidmet', 'contains', @services_id);

-- ---------------------------------------------------------------------
-- 5) TAG + "Göndər" düyməsi (lead_confirm) — göndərəndə tag təyin olunur
-- ---------------------------------------------------------------------

INSERT INTO tags (bot_id, name) VALUES (@bot_id, 'lead');
SET @lead_tag_id = LAST_INSERT_ID();

INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, action_tag_id, sort_order) VALUES
(@lead_confirm_id, '✅ Göndər', 'inline', @lead_thanks_id, @lead_tag_id, 1),
(@lead_confirm_id, '❌ Ləğv et', 'inline', @start_id, NULL, 2);

-- ---------------------------------------------------------------------
-- 6) SEQUENCE — "lead" tag-ı verilən kimi avtomatik izləmə mesajları
-- ---------------------------------------------------------------------

INSERT INTO sequences (bot_id, name, trigger_tag_id, status) VALUES
(@bot_id, 'Lead izləmə (follow-up)', @lead_tag_id, 'active');
SET @sequence_id = LAST_INSERT_ID();

INSERT INTO sequence_messages (sequence_id, delay_hours, message_text, sort_order) VALUES
(@sequence_id, 0, 'Salam {{full_name}}! Sorğunuzu aldıq, təşəkkür edirik. Komandamız tezliklə sizinlə əlaqə saxlayacaq. 🙌', 1),
(@sequence_id, 24, 'Salam yenidən, {{full_name}}! HRtime Consulting-dən yazırıq — məsləhət sessiyası üçün uyğun vaxtınız oldumu? Bizə buradan cavab yaza, və ya +994 XX XXX XX XX nömrəsi ilə əlaqə saxlaya bilərsiniz.', 2);

-- ---------------------------------------------------------------------
-- 7) YOXLAMA
-- ---------------------------------------------------------------------

SELECT id, node_key, node_type, title FROM bot_nodes WHERE bot_id = @bot_id ORDER BY id;
SELECT 'Demo bot hazırdır. start_node_id =' AS info, @start_id AS start_node_id;
