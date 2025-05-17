-- Update vendors table to ensure all vendor users have corresponding records
-- First, create records for all users with the 'vendor' role who don't have a record in the vendors table

-- Insert any missing vendor records
INSERT INTO vendors (user_id, vendor_name)
SELECT u.id, u.username
FROM users u
LEFT JOIN vendors v ON u.id = v.user_id
WHERE u.role = 'vendor' AND v.id IS NULL;

-- Set vendor_id for any products that might be missing it
-- (assuming products should belong to vendor with ID 1 if unspecified)
UPDATE products 
SET vendor_id = 1
WHERE vendor_id = 0 OR vendor_id IS NULL;

-- For testing: Show all vendors
SELECT * FROM vendors;

-- For testing: Show all products with their vendor information
SELECT p.id, p.name, p.vendor_id, v.user_id, u.username
FROM products p
JOIN vendors v ON p.vendor_id = v.id
JOIN users u ON v.user_id = u.id
LIMIT 10; 