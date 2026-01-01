-- Add owner and borrower profile enabled flags
ALTER TABLE users 
  ADD COLUMN owner_profile_enabled TINYINT NOT NULL DEFAULT 0 AFTER description,
  ADD COLUMN borrower_profile_enabled TINYINT NOT NULL DEFAULT 0 AFTER owner_profile_enabled;
