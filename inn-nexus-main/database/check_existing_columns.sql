-- Check what columns already exist in the users table
-- Run this first to see what we have

USE inn_nexus;

SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND TABLE_SCHEMA = 'inn_nexus'
ORDER BY ORDINAL_POSITION;
