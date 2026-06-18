-- Migration: add `size` to tblOrderItems and `status` to tblOrder to support tracking
ALTER TABLE tblOrderItems
ADD COLUMN `size` VARCHAR(50) NULL COMMENT 'Selected size for the ordered item';

ALTER TABLE tblOrder
ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'paid' COMMENT 'Order status for tracking';

CREATE INDEX idx_tblOrder_status ON tblOrder (status);
