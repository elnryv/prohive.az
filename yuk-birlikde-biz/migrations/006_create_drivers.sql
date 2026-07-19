CREATE TABLE drivers (
  user_id BIGINT UNSIGNED PRIMARY KEY,
  vehicle_id BIGINT UNSIGNED NOT NULL,
  vehicle_other VARCHAR(60) NULL,
  vehicle_size_id BIGINT UNSIGNED NOT NULL,
  rating_avg DECIMAL(2,1) NULL,
  rating_count INT NOT NULL DEFAULT 0,
  completed_count INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_drivers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_drivers_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
  CONSTRAINT fk_drivers_vehicle_size FOREIGN KEY (vehicle_size_id) REFERENCES vehicle_sizes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
