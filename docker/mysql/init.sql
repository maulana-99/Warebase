-- Separate database for automated tests (php artisan test).
CREATE DATABASE IF NOT EXISTS warebase_test;
GRANT ALL PRIVILEGES ON warebase_test.* TO 'app'@'%';
