1. Informasi Dokumen & Kontrol Versi
Nama Proyek Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat)
Versi Dokumen v1.0
Tanggal Rilis PRD 27 Juni 2026
Status Dokumen APPROVED / READY FOR DEVELOPMENT
Target Platform Web-Based Application (Responsive Mobile & Desktop)
Stack Teknologi PHP Native, MySQL, Tailwind CSS, Vanilla JavaScript

2. Latar Belakang & Ringkasan Eksekutif
Pelayanan publik di tingkat kecamatan sering kali menghadapi kendala efisiensi waktu dan transparansi
akibat proses administrasi yang masih bersifat konvensional/manual. Masyarakat dan perangkat desa harus
datang langsung ke kantor kecamatan hanya untuk mengajukan permohonan surat, mengisi formulir fisik,
serta menyerahkan berkas fotokopi persyaratan. Proses ini rentan terhadap penumpukan berkas dan
keterlambatan verifikasi.
Sip-Surat (Sistem Pelayanan Surat Digital Kecamatan) hadir sebagai solusi digital untuk memodernisasi
tata kelola administrasi surat-menyurat di tingkat kecamatan. Melalui sistem berbasis web ini, pemohon dapat
mengajukan surat secara mandiri dari mana saja, memantau status validasi berkas secara real-time, dan
mengunduh atau mencetak langsung hasil surat resmi setelah disetujui oleh pihak admin kecamatan. Sistem
dirancang dengan pendekatan dominan putih bersih untuk mencerminkan formalitas layanan instansi
pemerintah, menggunakan teknologi yang efisien dan interaktif.
3. Tujuan Proyek (Project Objectives)
Digitalisasi Birokrasi: Mengubah formulir kertas menjadi input form digital interaktif dan mengganti
dokumen fisik menjadi berkas unggahan digital.
Efisiensi Waktu Operasional: Memangkas waktu tunggu pengajuan surat dari hitungan hari menjadi
hitungan jam melalui sistem otomatisasi cetak berbasis template.
Transparansi Layanan: Menyediakan visibilitas penuh bagi masyarakat dan perangkat desa terhadap
status peninjauan berkas yang diajukan.
•

•

•

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 1 dari 7

Kemudahan Pengelolaan: Memberikan alat bagi admin kecamatan untuk memperbarui template
dokumen secara fleksibel tanpa perlu mengubah struktur kode pemrograman.
4. Profil Pengguna & Manajemen Hak Akses (User Roles)
Sistem mengisolasi hak akses berdasarkan peran pengguna secara ketat dengan pembagian berikut:
Peran Pengguna Deskripsi Peran Aksesibilitas Surat & Fitur
Masyarakat Warga sipil umum yang berada di wilayah
administrasi kecamatan terkait.

Mengajukan Surat Dispensasi
Nikah.
Mengisi data form nikah & unggah
dokumen pendukung.
Melihat riwayat status dan
mengunduh suratnya sendiri.

Perangkat Desa Staf atau pejabat resmi dari pemerintah
desa di bawah naungan kecamatan.

Mengajukan Surat ADD (Alokasi
Dana Daerah).
Mengisi data form alokasi dana desa
& unggah dokumen rencana
anggaran/pengantar.
Melihat status pengajuan berkas desa
dan mengunduh hasilnya.

Admin
Kecamatan

Pegawai, staf administrasi, atau operator
sistem di Kantor Kecamatan.

Akses penuh ke dasbor monitoring
pengajuan masuk.
Melakukan verifikasi berkas (Terima /
Tolak dengan catatan).
Manajemen Template Surat dinamis
(Tambah/Edit placeholder variabel).

5. Kebutuhan Fungsional Utama (Functional Requirements)
5.1 Modul Autentikasi & Akun
Sistem harus menyediakan halaman Login tunggal yang dapat mendeteksi role pengguna berdasarkan
data tabel database.
Registrasi Akun terpisah: Akun Masyarakat mendaftar menggunakan NIK, sedangkan Akun Perangkat
Desa diverifikasi melalui kode desa atau NIP/ID khusus perangkat.
•

•

•

•

•

•

•

•

•

•

•

•

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 2 dari 7

Sistem menerapkan session handling berbasis PHP Native untuk mengunci halaman agar tidak dapat
diakses tanpa login (mencegah SQL Injection dan unauthorized access via URL).
5.2 Modul Pengajuan Surat (Sisi Pengguna)
Pengajuan Surat Dispensasi Nikah (Masyarakat): Pemohon wajib mengisi data formulir digital (Nama
Calon Pengantin, NIK, Tempat/Tanggal Lahir, Nama Orang Tua, Wali, Tanggal Pelaksanaan Akad). Sistem
menyediakan input file untuk mengunggah dokumen pendukung (KTP, Kartu Keluarga, dan Surat
Pengantar dari RT/RW) dalam format PDF/JPG/PNG maksimal 2MB.
Pengajuan Surat ADD (Perangkat Desa): Pemohon wajib mengisi data formulir digital (Nama Desa,
Nama Kepala Desa, Jumlah Dana yang Diajukan, Tahap Alokasi, Peruntukan Dana). Sistem menyediakan
input file untuk mengunggah dokumen persyaratan (Surat Pengantar Kepala Desa, Dokumen RAB, dan
Laporan Pertanggungjawaban Tahap Sebelumnya) dalam format PDF maksimal 5MB.
5.3 Modul Manajemen Template Surat (Sisi Admin)
Admin Kecamatan memiliki menu khusus untuk mengelola struktur template dokumen surat (dalam format
rich text / HTML).
Sistem menggunakan penanda variabel atau placeholder dinamis berbasis teks, seperti:
[NAMA_PEMOHON] , [NIK_USER] , [NAMA_DESA] , [NOMINAL_DANA] .
Saat template disimpan ke dalam MySQL, kode PHP akan bertindak sebagai pengganti string
(str_replace atau regex) untuk menukar variabel tersebut dengan nilai riil dari formulir saat admin
menyetujui pengajuan.
5.4 Modul Verifikasi & Generasi Otomatis (Sisi Admin)
Admin dapat melihat antrean pengajuan surat dengan status: MENUNGGU VERIFIKASI .
Halaman detail verifikasi menampilkan komparasi bersandingan (side-by-side) antara data teks form
pengajuan dengan tautan file dokumen pendukung yang diunggah.
Jika berkas tidak sesuai, Admin menekan tombol Tolak dan wajib memasukkan alasan penolakan (Status
berubah menjadi: DITOLAK ).
Jika berkas sesuai, Admin menekan tombol Verifikasi & Setujui. Sistem secara otomatis menjalankan
skrip penukaran variabel ke template surat, menyimpan payload surat final ke database, dan mengubah
status menjadi SELESAI / SIAP DIUNDUH .
5.5 Modul Cetak & Unduh Dokumen
Surat yang telah berstatus SELESAI akan memunculkan opsi tombol "Cetak Surat" dan "Unduh PDF"
pada dasbor user pemohon.
Fitur cetak menggunakan fungsi native JavaScript window.print() dengan CSS print media query
khusus yang menyembunyikan elemen UI web (seperti navigasi dan tombol) sehingga hanya dokumen
surat resmi saja yang tercetak ke kertas.
•

•

•

•

•

•

•
•

•

•

•

•

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 3 dari 7

6. Struktur Basis Data (Skema Relasional MySQL)
Untuk menunjang arsitektur PHP Native, skema tabel dirancang secara terelasi mengandalkan Foreign Key
demi integritas data:
6.1 Tabel: users
Nama Kolom Tipe Data Keterangan
id_user (PK) INT, AUTO_INCREMENT ID Utama Pengguna
username VARCHAR(50) Guna Autentikasi Login
password VARCHAR(255) Menggunakan enkripsi password_hash()
nama_lengkap VARCHAR(100) Nama Asli Pengguna
nik_nip VARCHAR(30) NIK untuk Masyarakat, NIP/ID untuk

Perangkat

role ENUM('masyarakat', 'perangkat_desa',

'admin')

Penentu Hak Akses Utama

6.2 Tabel: jenis_surat & template_surat
Nama Kolom Tipe Data Keterangan
id_jenis (PK) INT, AUTO_INCREMENT ID Jenis Surat (1: Dispensasi Nikah, 2: ADD)
nama_surat VARCHAR(100) Judul atau Nama Jenis Surat
kode_surat VARCHAR(20) Nomor Klasifikasi Arsip (misal: 400/Kec/)
template_html TEXT Struktur isi surat beserta placeholder variabel

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 4 dari 7

6.3 Tabel: pengajuan
Nama Kolom Tipe Data Keterangan
id_pengajuan (PK) INT, AUTO_INCREMENT ID Transaksi Pengajuan
id_user (FK) INT Relasi ke tabel users
id_jenis (FK) INT Relasi ke tabel jenis_surat
tanggal_pengajuan TIMESTAMP Waktu Pembuatan Pengajuan
status ENUM('menunggu', 'ditolak', 'selesai') Status Alur Proses Dokumen
catatan_admin TEXT Alasan penolakan berkas jika ditolak
surat_final_html TEXT Hasil kompilasi template surat siap cetak

6.4 Tabel: detail_pengajuan (Struktur Key-Value Dinamis)
Tabel ini digunakan untuk menampung isian formulir dinamis dari berbagai jenis surat tanpa perlu mengubah
struktur kolom database utama.
Nama Kolom Tipe Data Keterangan
id_detail (PK) INT, AUTO_INCREMENT ID Detail
id_pengajuan (FK) INT Relasi ke tabel pengajuan
meta_key VARCHAR(50) Kunci Field Form (misal: 'nama_calon', 'nominal_add')
meta_value TEXT Isi Jawaban dari Form User

6.5 Tabel: dokumen_pendukung
Nama Kolom Tipe Data Keterangan
id_dokumen (PK) INT, AUTO_INCREMENT ID File Berkas
id_pengajuan (FK) INT Relasi ke tabel pengajuan
nama_file VARCHAR(255) Path lokasi penyimpanan file berkas di server
jenis_berkas VARCHAR(50) Label Berkas (KTP, KK, RAB, dll)

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 5 dari 7

7. Kebutuhan Antarmuka (UI/UX Guidelines)

Dominasi Warna Putih Bersih: Tata letak aplikasi wajib menggunakan latar belakang putih dominan (bg-
white atau bg-slate-50) dikombinasikan dengan border tipis abu-abu terang (border-slate-200).

Warna aksen utama adalah Biru Navy Formal (text-blue-900, bg-blue-900) guna memunculkan
impresi kedinasan pemerintah yang kredibel dan rapi.
Tipografi Sistem:
Sisi Aplikasi Antarmuka (Aplikasi Web): Menggunakan font sans-serif modern yang bersih seperti
Inter atau Roboto untuk kemudahan keterbacaan data numerik, label form, dan teks navigasi.
Sisi Output Surat Resmi: Khusus untuk layout pencetakan dokumen surat hasil kompilasi, wajib
merender dengan font serif formal yaitu Times New Roman atau alternatif Arial dengan ukuran font
standar 12pt, mengikuti aturan tata naskah dinas resmi pemerintah.
Ikonografi Standar: Menggunakan library Google Material Icons untuk melambangkan aksi (misal: ikon
unduh, ikon verifikasi, ikon edit, ikon status check).
Sistem Navigasi Responsif & Hamburger Menu: Aplikasi harus responsif penuh menggunakan
framework utilitas Tailwind CSS. Pada view desktop, sidebar menu ditampilkan secara statis di sebelah kiri
layar. Pada view mobile (smartphone), sidebar disembunyikan sepenuhnya dan digantikan oleh bilah
navigasi atas (top bar) yang memuat tombol Hamburger Menu. Ketika tombol ini diklik oleh pengguna,
sebuah panel menu akan bergeser (drawer) atau muncul (dropdown) secara interaktif memanfaatkan skrip
JavaScript Vanilla.
8. Batasan Arsitektur & Kebutuhan Teknis (Technical Constraints)
Arsitektur Tanpa Framework (PHP Native): Struktur file kode program wajib disusun secara modular
menggunakan konsep pemisahan logika file (layouting layout header/footer di-include secara parsial).
Dilarang keras menggunakan framework seperti Laravel, Symfony, atau CodeIgniter demi kenyamanan
pemahaman basis kode dasar oleh tim internal.
Vanilla JavaScript Terisolasi: Interaktivitas halaman (efek hamburger menu, konfirmasi modal pop-up
saat menghapus template atau menyetujui dokumen, serta validasi form real-time sebelum submit) harus
ditulis dengan JavaScript murni tanpa ketergantungan pada library pihak ketiga seperti jQuery.
Pengendalian Layout Cetak (CSS Print): Wajib mengimplementasikan blok media cetak pada Tailwind/
CSS untuk memastikan hasil print-out bersih dari atribut web:

@media print {
body { background: white; color: black; }
.no-print { display: none !important; }
.print-area { width: 100%; margin: 0; padding: 0; box-shadow: none; }
}
•

•
◦

◦

•

•

•

•

•

PRD Sistem Pelayanan Surat Digital Kecamatan (Sip-Surat) Halaman 6 dari 7

9. Validasi Pengujian & Kriteria Penerimaan (Acceptance Criteria)
Skenario Pengujian Kriteria Keberhasilan Dokumen / Sistem
Pengajuan Berkas oleh
User

Masyarakat hanya dapat melihat opsi form Dispensasi Nikah, Perangkat
Desa hanya dapat melihat form ADD. Dokumen pendukung wajib diunggah
sebelum tombol submit aktif.

Komparasi Data Admin Admin mampu memeriksa kesesuaian berkas secara visual di satu layar,
dapat mengunduh dokumen pendukung, dan kolom catatan penolakan
wajib terisi jika memilih opsi Tolak.

Substitusi Variabel
Otomatis

Ketika status berubah menjadi 'Selesai', semua string placeholder seperti
[NAMA_PEMOHON] di dalam template harus sepenuhnya terganti dengan
data riil masukan pengguna tanpa menyisakan kode mentah HTML.
Pencetakan Fisik Saat tombol cetak diklik, dialog print bawaan browser muncul dan hanya
merender area lembar surat dengan font Times New Roman / Arial tanpa
mengikutsertakan header web, sidebar, maupun tombol aksi sistem.