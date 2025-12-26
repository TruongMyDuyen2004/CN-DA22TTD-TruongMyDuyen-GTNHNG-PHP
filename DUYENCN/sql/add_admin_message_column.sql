-- Add is_admin_message column to contacts table
ALTER TABLE contacts ADD COLUMN IF NOT EXISTS is_admin_message TINYINT(1) DEFAULT 0;
