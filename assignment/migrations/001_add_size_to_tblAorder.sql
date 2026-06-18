-- Migration: add size column to tblAorder to store selected size per cart item
ALTER TABLE tblAorder
ADD COLUMN `size` VARCHAR(50) NULL COMMENT 'Selected size for the cart item',
ADD INDEX idx_tblAorder_size (`size`);

-- If you also want to allow sizes for historical orders, consider adding to order tables as well.
