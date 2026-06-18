-- Migration: add `size` to tblAorderItems and `status` to tblAorder to support tracking

ALTER TABLE tblAorderItems

ALTER TABLE tblAorder
ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'paid' COMMENT 'Order status for tracking';

CREATE INDEX idx_tblAorder_status ON tblAorder (status);
