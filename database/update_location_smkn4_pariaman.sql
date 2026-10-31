-- Gunakan file ini bila database sudah pernah di-seed dengan lokasi "Kantor Pusat".
USE absensi_db;

UPDATE work_locations
SET name = 'SMKN 4 Pariaman',
    address = 'Jalan Tuanku Naali, Desa Taluk, Kecamatan Pariaman Selatan, Kota Pariaman, Sumatera Barat',
    latitude = -0.6447000,
    longitude = 100.1401000,
    radius_meters = 100,
    maximum_accuracy_meters = 100,
    is_active = 1,
    updated_at = CURRENT_TIMESTAMP
WHERE name IN ('Kantor Pusat', 'SMKN 4 Pariaman');

-- Bila lokasi lama tidak ditemukan, buat lokasi SMKN 4 Pariaman.
INSERT INTO work_locations
    (name, address, latitude, longitude, radius_meters, maximum_accuracy_meters, is_active, created_at, updated_at)
SELECT
    'SMKN 4 Pariaman',
    'Jalan Tuanku Naali, Desa Taluk, Kecamatan Pariaman Selatan, Kota Pariaman, Sumatera Barat',
    -0.6447000,
    100.1401000,
    100,
    100,
    1,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM work_locations WHERE name = 'SMKN 4 Pariaman'
);
