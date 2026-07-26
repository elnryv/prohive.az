CREATE TABLE offers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  driver_id BIGINT UNSIGNED NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  arrival_time VARCHAR(50) NOT NULL,
  note VARCHAR(200) NULL,
  status ENUM('pending','selected','withdrawn','archived') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_offers_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_offers_driver FOREIGN KEY (driver_id) REFERENCES drivers(user_id),
  -- qayda: bir sürücüdən bir elana yalnız bir pending təklif — tətbiq qatında yoxlanılır
  UNIQUE KEY uq_offers_order_driver_pending (order_id, driver_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_selected_offer FOREIGN KEY (selected_offer_id) REFERENCES offers(id);
