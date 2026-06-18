-- Backfill existing orders to have a default status and normalize sizes

-- Set missing order statuses to 'paid'
UPDATE tblOrder SET status = 'paid' WHERE status IS NULL OR status = '';

-- Normalize empty size values to NULL in order items
UPDATE tblOrderItems SET size = NULL WHERE size = '';
