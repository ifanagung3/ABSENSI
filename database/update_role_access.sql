-- Jalankan hanya pada database lama yang sebelumnya memakai role supervisor.
-- Untuk instalasi Laravel normal, migration 2026_07_12_000300 sudah menjalankan perubahan ini.

START TRANSACTION;

ALTER TABLE users
    MODIFY role ENUM('admin', 'supervisor', 'employee')
    NOT NULL DEFAULT 'employee';

UPDATE users
SET role = 'employee'
WHERE role = 'supervisor';

ALTER TABLE users
    MODIFY role ENUM('admin', 'employee')
    NOT NULL DEFAULT 'employee';

COMMIT;
