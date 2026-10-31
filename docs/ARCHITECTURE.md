# Arsitektur Sistem

```mermaid
flowchart LR
    U[Browser Pegawai/Admin] --> L[/login]
    L --> R{Role}
    R -->|employee| EP[Portal /pegawai]
    R -->|admin| AP[Panel /admin]
    EP --> EM[auth + role:employee]
    AP --> AM[auth + role:admin]
    EM --> EC[Employee Controllers]
    AM --> AC[Admin Controllers]
    EC --> AS[Attendance Service]
    AS --> GS[Geofence Service]
    AS --> PS[Private Storage]
    AS --> DB[(MySQL/MariaDB)]
    AC --> DB
    AC --> PS
    AS --> AU[Audit Service]
    AC --> AU
    AU --> DB
```

## ERD Ringkas

```mermaid
erDiagram
    USERS ||--o| EMPLOYEES : owns
    DEPARTMENTS ||--o{ EMPLOYEES : groups
    POSITIONS ||--o{ EMPLOYEES : assigns
    WORK_LOCATIONS ||--o{ EMPLOYEES : default_location
    EMPLOYEES ||--o{ EMPLOYEE_SCHEDULES : has
    SHIFTS ||--o{ EMPLOYEE_SCHEDULES : applies
    WORK_LOCATIONS ||--o{ EMPLOYEE_SCHEDULES : occurs_at
    EMPLOYEE_SCHEDULES ||--o| ATTENDANCE_RECORDS : produces
    EMPLOYEES ||--o{ ATTENDANCE_RECORDS : owns
    ATTENDANCE_RECORDS ||--o{ ATTENDANCE_EVENTS : contains
    EMPLOYEES ||--o{ LEAVE_REQUESTS : submits
    USERS ||--o{ LEAVE_REQUESTS : reviews
    USERS ||--o{ AUDIT_LOGS : performs
```

## Keputusan Desain

- Session authentication dipakai karena frontend dan backend berada pada aplikasi Laravel yang sama.
- Login digabung, tetapi route bisnis dipisahkan agar role tidak bergantung pada menu frontend.
- `attendance_records` menyimpan rekap per tanggal; `attendance_events` menyimpan bukti masuk/pulang.
- Foto dan lampiran memakai disk `local`, bukan `public`.
- Jarak dihitung dengan Haversine agar MySQL Laragon dapat digunakan tanpa PostGIS.
- Database transaction, row lock, dan unique index digunakan bersama untuk mencegah duplikasi.
- Audit log menyimpan perubahan penting tanpa menyimpan password.

## Boundary Keamanan

Frontend hanya mengumpulkan selfie, koordinat, akurasi, dan waktu perangkat. Backend selalu membaca jadwal, shift, lokasi, radius, status akun, dan waktu resmi dari server/database.
