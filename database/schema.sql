SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') DEFAULT 'active',
    is_super TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bots (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    telegram_token VARCHAR(255) NOT NULL UNIQUE,
    telegram_username VARCHAR(150) DEFAULT NULL,
    webhook_secret VARCHAR(64) NOT NULL UNIQUE,
    start_node_id BIGINT UNSIGNED DEFAULT NULL,
    status ENUM('active','paused','deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_owner (owner_id),
    INDEX idx_secret (webhook_secret)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bot_nodes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    node_key VARCHAR(100) NOT NULL,
    title VARCHAR(150) DEFAULT NULL,
    node_type ENUM('text','buttons','input','image','delay','goto','api','final') DEFAULT 'text',
    message_text TEXT DEFAULT NULL,
    media_url VARCHAR(500) DEFAULT NULL,
    input_field_key VARCHAR(100) DEFAULT NULL,
    input_next_node BIGINT UNSIGNED DEFAULT NULL,
    delay_seconds INT DEFAULT NULL,
    goto_node_id BIGINT UNSIGNED DEFAULT NULL,
    api_url VARCHAR(500) DEFAULT NULL,
    api_method ENUM('GET','POST') DEFAULT 'POST',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY uq_bot_nodekey (bot_id, node_key),
    INDEX idx_bot (bot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bot_node_buttons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    node_id BIGINT UNSIGNED NOT NULL,
    button_text VARCHAR(200) NOT NULL,
    button_type ENUM('inline','url') DEFAULT 'inline',
    target_node_id BIGINT UNSIGNED DEFAULT NULL,
    url VARCHAR(500) DEFAULT NULL,
    action_tag_id BIGINT UNSIGNED DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (node_id) REFERENCES bot_nodes(id) ON DELETE CASCADE,
    INDEX idx_node (node_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bot_subscribers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    telegram_chat_id BIGINT NOT NULL,
    first_name VARCHAR(150) DEFAULT NULL,
    last_name VARCHAR(150) DEFAULT NULL,
    username VARCHAR(150) DEFAULT NULL,
    current_node_id BIGINT UNSIGNED DEFAULT NULL,
    waiting_for VARCHAR(100) DEFAULT NULL,
    status ENUM('active','blocked','unsubscribed') DEFAULT 'active',
    last_seen_at TIMESTAMP NULL DEFAULT NULL,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY uq_bot_chat (bot_id, telegram_chat_id),
    INDEX idx_bot (bot_id),
    INDEX idx_waiting (bot_id, waiting_for)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriber_fields (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscriber_id BIGINT UNSIGNED NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    field_value TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subscriber_id) REFERENCES bot_subscribers(id) ON DELETE CASCADE,
    UNIQUE KEY uq_sub_field (subscriber_id, field_key),
    INDEX idx_sub (subscriber_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    UNIQUE KEY uq_bot_tag (bot_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriber_tags (
    subscriber_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (subscriber_id, tag_id),
    FOREIGN KEY (subscriber_id) REFERENCES bot_subscribers(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bot_keywords (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    keyword VARCHAR(200) NOT NULL,
    match_type ENUM('exact','contains','starts') DEFAULT 'contains',
    target_node_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    FOREIGN KEY (target_node_id) REFERENCES bot_nodes(id) ON DELETE CASCADE,
    INDEX idx_bot_kw (bot_id, keyword)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE broadcasts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    message_text TEXT NOT NULL,
    target_tag_id BIGINT UNSIGNED DEFAULT NULL,
    status ENUM('draft','queued','sending','done','failed') DEFAULT 'draft',
    total_count INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE broadcast_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broadcast_id BIGINT UNSIGNED NOT NULL,
    subscriber_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','sent','failed') DEFAULT 'pending',
    error VARCHAR(255) DEFAULT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (broadcast_id) REFERENCES broadcasts(id) ON DELETE CASCADE,
    FOREIGN KEY (subscriber_id) REFERENCES bot_subscribers(id) ON DELETE CASCADE,
    INDEX idx_bq_status (broadcast_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    trigger_tag_id BIGINT UNSIGNED DEFAULT NULL,
    status ENUM('active','paused') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sequence_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sequence_id BIGINT UNSIGNED NOT NULL,
    delay_hours INT NOT NULL DEFAULT 24,
    message_text TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subscriber_sequences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscriber_id BIGINT UNSIGNED NOT NULL,
    sequence_id BIGINT UNSIGNED NOT NULL,
    next_message_id BIGINT UNSIGNED DEFAULT NULL,
    next_send_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('running','completed') DEFAULT 'running',
    FOREIGN KEY (subscriber_id) REFERENCES bot_subscribers(id) ON DELETE CASCADE,
    FOREIGN KEY (sequence_id) REFERENCES sequences(id) ON DELETE CASCADE,
    INDEX idx_due (status, next_send_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bot_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bot_id BIGINT UNSIGNED NOT NULL,
    subscriber_id BIGINT UNSIGNED DEFAULT NULL,
    direction ENUM('in','out') NOT NULL,
    message_type VARCHAR(50) DEFAULT 'text',
    content TEXT DEFAULT NULL,
    raw_payload JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bot_id) REFERENCES bots(id) ON DELETE CASCADE,
    INDEX idx_bot_time (bot_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
