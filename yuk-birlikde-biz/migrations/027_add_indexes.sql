CREATE INDEX idx_orders_status_city_created ON orders (status, from_city, created_at);
CREATE INDEX idx_orders_expires_at ON orders (expires_at);
CREATE INDEX idx_offers_order_status ON offers (order_id, status);
CREATE INDEX idx_offers_driver_status ON offers (driver_id, status);
CREATE INDEX idx_events_channel_id ON events (channel, id);
CREATE INDEX idx_notifications_user_read ON notifications (user_id, is_read);
CREATE INDEX idx_subscriptions_driver_ends ON subscriptions (driver_id, ends_at);
CREATE INDEX idx_push_subscriptions_user ON push_subscriptions (user_id);
