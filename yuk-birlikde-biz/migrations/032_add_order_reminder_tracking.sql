-- Hissə 4.8 xatırlatma sistemi: hər danışıq dövrü üçün hansı intervalların
-- artıq göndərildiyini izləyir ki, eyni pillə iki dəfə göndərilməsin.
ALTER TABLE orders
  ADD COLUMN reminder_tier_sent INT NOT NULL DEFAULT 0 AFTER reopen_count;
