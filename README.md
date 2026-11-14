# SIP-SURAT: Sistem Pelayanan Surat Digital Kecamatan Cisayong

Aplikasi Sistem Informasi Pelayanan Surat Digital berbasis Web (PHP Native + MySQL + Tailwind CSS + SweetAlert2) dengan alur verifikasi naskah dinas berjenjang, penandatanganan elektronik (TTE QR Code & Gambar Tanda Tangan), serta integrasi unduh dokumen resmi format A4 (PDF & Word).

---

## 🏛️ Alur Pelayanan Berjenjang (Workflow)

```
[ WARGA ]
   └──► Login ──► Pilih Jenis Surat Umum (SKTM, Waris, SKU, dll) ──► Upload Syarat ──► KIRIM
                                                                                       │
[ ADMIN DESA ]                                                                         │
   └──► Login ──► Pilih Surat Khusus Desa (Alokasi Dana Desa / ADD) ──► Upload Syarat ─► KIRIM
                                                                                       │
 ┌─────────────────────────────────────────────────────────────────────────────────────┘
 │
 ▼
[ FRONT OFFICE / PTSP KECAMATAN ]
   ├──► Tolak Berkas ──► (Warga/Admin Desa mengunggah revisi berkas)
   └──► Valid ──► Teruskan ke Kasi Terkait (Sesuai Bidang Surat)
                  │
                  ▼
         [ KASI TERKAIT (Pemerintahan / Kesra / PMD / Trantib) ]
                  ├──► Periksa Substansi & Redaksi Surat
                  └──► Setuju (Paraf Digital) ──► Teruskan ke Sekcam
                                                   │
                                                   ▼
                                          [ SEKRETARIS CAMAT (SEKCAM) ]
                                                   └──► Setuju (Paraf Koordinasi) ──► Teruskan ke Camat
                                                                                       │
                                                                                       ▼
                                                                                   [ CAMAT CISAYONG ]
                                                                                       └──► Approve & TTE (QR Code)
                                                                                            │
                                                                                            ▼
                                                                                   [ CETAK & UNDUH SURAT ]
                                                                                   (PDF A4 Resmi & Word .doc)
```

---

## ✨ Fitur Unggulan

- **Alur Verifikasi Hierarkis 4 Meja**: PTSP ➔ Kasi Terkait ➔ Sekcam ➔ Camat.
- **Form Builder Dinamis & Master Jenis Surat**: Penambahan dan pengaturan kolom formulir tanpa merombak struktur database.
- **Pengaturan Tanda Tangan Fleksibel**:
  - Format TTE Kotak Resmi (BSrE BSSN berlogo Kabupaten Tasikmalaya).
  - Format Scan / Gambar Tanda Tangan murni.
  - Tombol kontrol pembubuhan TTD oleh pejabat kecamatan.
- **Cetak Dokumen Resmi Format A4**:
  - Standar naskah dinas kedinasan (1 lembar penuh A4).
  - Ekspor langsung berkas PDF (`.pdf`) dan Word (`.doc`).
- **Notifikasi WhatsApp**: Tombol instan pengiriman notifikasi surat selesai langsung ke nomor WhatsApp pemohon.
- **Antarmuka Modern**: Tailwind CSS responsif (Mobile Friendly) & SweetAlert2 untuk dialog konfirmasi.

---

## 🚀 Panduan Instalasi Lokal

### 1. Prasyarat
- XAMPP (Apache + MySQL/MariaDB + PHP 8.x)
- Web Browser modern (Google Chrome, Microsoft Edge, Mozilla Firefox)

### 2. Langkah Pemasangan
1. Salin atau clone repositori ini ke dalam direktori web server lokal:
   ```bash
   cd c:/xampp/htdocs/
   git clone <URL_REPOSITORY_ANDA> surkec
   ```
2. Nyalakan service **Apache** dan **MySQL** melalui XAMPP Control Panel.
3. Buka browser dan akses **phpMyAdmin** di `http://localhost/phpmyadmin/`.
4. Buat database baru bernama `sip_surat`.
5. Import file berkas `database.sql` yang berada di dalam folder project ini ke database `sip_surat`.
6. Pastikan pengaturan koneksi di `config/database.php` sudah sesuai:
   ```php
   $host = 'localhost';
   $db   = 'sip_surat';
   $user = 'root';
   $pass = '';
   ```
7. Buka aplikasi di browser:
   ```
   http://localhost/surkec/
   ```

---

## 👤 Akun Pengujian Default

Semua akun menggunakan kata sandi (*password*): `password`

| Role / Jabatan | Alamat Email | Password |
| :--- | :--- | :--- |
| **Camat Cisayong** | `camat@cisayong.desa.id` | `password` |
| **Sekretaris Camat (Sekcam)** | `sekcam@cisayong.desa.id` | `password` |
| **Front Office (PTSP)** | `ptsp@cisayong.desa.id` | `password` |
| **Kasi Kesra & Pelayanan** | `kasi_kesra@cisayong.desa.id` | `password` |
| **Kasi Pemerintahan** | `kasi_pemerintahan@cisayong.desa.id` | `password` |
| **Admin Sistem** | `admin@cisayong.desa.id` | `password` |
| **Admin Desa (Jatihurip)** | `desa_jatihurip@cisayong.desa.id` | `password` |
| **Warga / Masyarakat** | `miftah@gmail.com` | `password` |

---

## 📄 Lisensi
Dikembangkan untuk pelayanan publik dan administrasi surat digital Kecamatan Cisayong, Kabupaten Tasikmalaya.
