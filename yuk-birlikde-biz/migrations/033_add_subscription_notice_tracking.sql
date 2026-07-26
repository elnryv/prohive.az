ALTER TABLE subscriptions
  ADD COLUMN notice_3d_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN notice_1d_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER notice_3d_sent;
