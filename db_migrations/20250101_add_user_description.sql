-- Add description field to users table
ALTER TABLE users ADD COLUMN description TEXT DEFAULT NULL AFTER phone;
