# Hak Akses

## Login Bersama

```text
/login
```

Backend memeriksa kredensial, status aktif, dan role. Login tidak menampilkan pilihan role sehingga pengguna tidak dapat memilih hak akses sendiri.

## Pegawai

Prefix: `/pegawai`

Pegawai hanya dapat membaca dan mengubah data miliknya sendiri: dashboard, absensi, riwayat, izin, lampiran sendiri, profil, dan password.

## Administrator

Prefix: `/admin`

Admin mengelola seluruh data organisasi dan memeriksa bukti absensi, tetapi tidak memiliki endpoint check-in/check-out pada area admin.

## Proteksi

```mermaid
flowchart TD
    L[/login] --> C{Kredensial valid?}
    C -->|Tidak| X[Tolak]
    C -->|Ya| A{Akun aktif?}
    A -->|Tidak| X
    A -->|Ya| R{Role}
    R -->|employee| E[/pegawai/dashboard]
    R -->|admin| D[/admin/dashboard]
    E --> EM[role:employee]
    D --> AM[role:admin]
    EM -->|Membuka /admin| F[403]
    AM -->|Membuka /pegawai| F
```

Foto dan lampiran tidak memiliki URL public storage; file dibaca melalui controller yang berada di group middleware role.
