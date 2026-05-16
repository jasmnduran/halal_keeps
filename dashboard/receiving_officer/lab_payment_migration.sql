-- Add pending_payment status to laboratory_requests table
ALTER TABLE `laboratory_requests` 
MODIFY COLUMN `status` ENUM('submitted','pending_payment','received','testing','completed','rejected') DEFAULT 'submitted';

-- Add payment reference column for PayMongo
ALTER TABLE `laboratory_requests` 
ADD COLUMN `payment_reference` VARCHAR(255) DEFAULT NULL AFTER `payment_testing_fee`,
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT NULL AFTER `payment_reference`,
ADD COLUMN `paid_at` TIMESTAMP NULL DEFAULT NULL AFTER `payment_method`;
