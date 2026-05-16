-- ============================================================
-- Separate Auditor Findings Migration
-- Run this in phpMyAdmin or MySQL CLI against halal_system
-- ============================================================

-- Add separate columns for each auditor's findings
ALTER TABLE `inspections`
    ADD COLUMN IF NOT EXISTS `tech_audit_findings` TEXT DEFAULT NULL AFTER `audit_findings`,
    ADD COLUMN IF NOT EXISTS `tech_conformity_status` ENUM('conforming','non_conforming','partial','pending') DEFAULT 'pending' AFTER `tech_audit_findings`,
    ADD COLUMN IF NOT EXISTS `tech_remarks` TEXT DEFAULT NULL AFTER `tech_conformity_status`,
    ADD COLUMN IF NOT EXISTS `tech_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `tech_remarks`,
    ADD COLUMN IF NOT EXISTS `shariah_audit_findings` TEXT DEFAULT NULL AFTER `tech_completed_at`,
    ADD COLUMN IF NOT EXISTS `shariah_conformity_status` ENUM('conforming','non_conforming','partial','pending') DEFAULT 'pending' AFTER `shariah_audit_findings`,
    ADD COLUMN IF NOT EXISTS `shariah_remarks` TEXT DEFAULT NULL AFTER `shariah_conformity_status`,
    ADD COLUMN IF NOT EXISTS `shariah_completed_at` TIMESTAMP NULL DEFAULT NULL AFTER `shariah_remarks`;

-- Migrate existing data from shared columns to technical auditor columns (if any)
-- This assumes existing findings were from the technical auditor
UPDATE `inspections`
SET
    `tech_audit_findings` = `audit_findings`,
    `tech_conformity_status` = `conformity_status`,
    `tech_remarks` = `remarks`,
    `tech_completed_at` = CASE WHEN `status` = 'completed' THEN `updated_at` ELSE NULL END
WHERE `audit_findings` IS NOT NULL
  AND `tech_audit_findings` IS NULL;

-- Note: The old columns (audit_findings, conformity_status, remarks) are kept for backward compatibility
-- They will now store the COMBINED/FINAL status after both auditors complete their findings
