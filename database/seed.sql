SET NAMES utf8mb4;

-- Super admin istifadəçi. Email: admin@birlikde.biz | Parol: Admin123!
INSERT INTO users (id, name, email, password_hash, status, is_super)
VALUES (1, 'Super Admin', 'admin@birlikde.biz', '$2y$12$DjWWCpsvVBU39d3AovcEq.ze2DGUtv8oD1VfGjIB1Xje9AXLZNmNS', 'active', 1);

-- Test bot (fake token, real Telegram-a bağlı deyil)
INSERT INTO bots (id, owner_id, name, telegram_token, telegram_username, webhook_secret, start_node_id, status)
VALUES (1, 1, 'Test Bot', '123456789:AAFakeTokenForLocalTestingOnly000000', 'birlikde_test_bot', 'test_secret_1234567890abcdef1234567890abcd', NULL, 'active');

-- Node-lar
INSERT INTO bot_nodes (id, bot_id, node_key, title, node_type, message_text)
VALUES
    (1, 1, 'start', 'Başlanğıc', 'buttons', 'Salam, {{first_name}}! Birlikdə Bot Builder-ə xoş gəldiniz. Nə etmək istəyirsiniz?'),
    (2, 1, 'menu', 'Menyu', 'text', 'Bu menyu bölməsidir. Burada botunuzun xidmətləri göstərilə bilər.'),
    (3, 1, 'about', 'Haqqımızda', 'text', 'Birlikdə — ManyChat səviyyəsində, AI-siz, qayda-əsaslı Telegram bot qurucu sistemdir.');

-- start node üçün düymələr
INSERT INTO bot_node_buttons (node_id, button_text, button_type, target_node_id, sort_order)
VALUES
    (1, 'Menyu', 'inline', 2, 1),
    (1, 'Haqqımızda', 'inline', 3, 2);

-- botun başlanğıc node-unu təyin et
UPDATE bots SET start_node_id = 1 WHERE id = 1;
