<?php
// File: config/init_db.php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'sip_surat';

try {
    // 1. Buat koneksi tanpa dbname untuk membuat database jika belum ada
    $pdo_init = new PDO("mysql:host=$host", $user, $pass);
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    
    // 2. Koneksi ke database sip_surat
    $pdo_db = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Buat Tabel `users`
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nama` VARCHAR(150) NOT NULL,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(150) NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('masyarakat', 'desa', 'ptsp', 'kasi_pemerintahan', 'kasi_kesra', 'kasi_pmd', 'kasi_trantib', 'sekcam', 'camat', 'admin') NOT NULL,
            `instansi_desa` VARCHAR(150) NULL,
            `jabatan` VARCHAR(150) NULL,
            `nip` VARCHAR(50) NULL,
            `phone` VARCHAR(30) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Migrasi kolom pada `users` jika tabel sudah ada sebelumnya
    $colsUsers = $pdo_db->query("SHOW COLUMNS FROM `users`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $colsUsers)) {
        $pdo_db->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(150) NULL UNIQUE AFTER `username`;");
    }
    if (!in_array('jabatan', $colsUsers)) {
        $pdo_db->exec("ALTER TABLE `users` ADD COLUMN `jabatan` VARCHAR(150) NULL AFTER `instansi_desa`;");
    }
    if (!in_array('nip', $colsUsers)) {
        $pdo_db->exec("ALTER TABLE `users` ADD COLUMN `nip` VARCHAR(50) NULL AFTER `jabatan`;");
    }

    // 4. Buat Tabel `tanda_tangan` (Manajemen Tanda Tangan Kotak & Upload)
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS `tanda_tangan` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `judul_ttd` VARCHAR(150) NOT NULL,
            `tipe_ttd` ENUM('elektronik_kotak', 'gambar_upload') DEFAULT 'elektronik_kotak',
            `teks_atas` VARCHAR(255) DEFAULT 'Ditandatangani secara elektronik oleh',
            `jabatan` VARCHAR(150) NOT NULL,
            `nama_pejabat` VARCHAR(150) NOT NULL,
            `pangkat_golongan` VARCHAR(150) NOT NULL,
            `nip` VARCHAR(100) NOT NULL,
            `gambar_ttd` VARCHAR(255) NULL,
            `is_default` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Buat Tabel `templates` (No-Code Template System)
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS `templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `jenis_surat` VARCHAR(100) NOT NULL UNIQUE,
            `kategori_pemohon` ENUM('masyarakat', 'desa') DEFAULT 'masyarakat',
            `kasi_default` VARCHAR(50) DEFAULT 'kasi_kesra',
            `kode_klasifikasi` VARCHAR(50) NOT NULL,
            `format_nomor` VARCHAR(100) NOT NULL,
            `judul_surat` VARCHAR(255) NOT NULL,
            `sifat` VARCHAR(100) NULL,
            `lampiran` VARCHAR(100) NULL,
            `perihal` VARCHAR(255) NULL,
            `tujuan` VARCHAR(255) NULL,
            `pembuka` TEXT NOT NULL,
            `penutup` TEXT NOT NULL,
            `penandatangan_nama` VARCHAR(150) NOT NULL,
            `penandatangan_nip` VARCHAR(100) NOT NULL,
            `penandatangan_jabatan` VARCHAR(150) NOT NULL,
            `penandatangan_pangkat` VARCHAR(150) NOT NULL,
            `is_tte` TINYINT(1) DEFAULT 1,
            `form_fields` JSON NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colsTmpl = $pdo_db->query("SHOW COLUMNS FROM `templates`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kategori_pemohon', $colsTmpl)) {
        $pdo_db->exec("ALTER TABLE `templates` ADD COLUMN `kategori_pemohon` ENUM('masyarakat', 'desa') DEFAULT 'masyarakat' AFTER `jenis_surat`;");
    }
    if (!in_array('kasi_default', $colsTmpl)) {
        $pdo_db->exec("ALTER TABLE `templates` ADD COLUMN `kasi_default` VARCHAR(50) DEFAULT 'kasi_kesra' AFTER `kategori_pemohon`;");
    }

    // 6. Buat Tabel `pengajuan`
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS `pengajuan` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `jenis_surat` VARCHAR(100) NOT NULL,
            `nomor_surat_generated` VARCHAR(150) NULL,
            `nomor_urut` INT NULL,
            `data_form` JSON NOT NULL,
            `status` ENUM('menunggu', 'menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat', 'disetujui', 'ditolak') DEFAULT 'menunggu_ptsp',
            `is_ttd_dibubuhkan` TINYINT(1) DEFAULT 0,
            `ttd_id` INT NULL,
            `waktu_ttd_dibubuhkan` TIMESTAMP NULL,
            `ttd_dibubuhkan_oleh` VARCHAR(150) NULL,
            `kasi_tujuan` VARCHAR(50) NULL,
            `ptsp_oleh` VARCHAR(150) NULL,
            `waktu_validasi_ptsp` TIMESTAMP NULL,
            `catatan_ptsp` TEXT NULL,
            `paraf_kasi_oleh` VARCHAR(150) NULL,
            `paraf_kasi_nip` VARCHAR(50) NULL,
            `paraf_kasi_jabatan` VARCHAR(150) NULL,
            `waktu_paraf_kasi` TIMESTAMP NULL,
            `catatan_kasi` TEXT NULL,
            `redaksi_kasi_pembuka` TEXT NULL,
            `redaksi_kasi_penutup` TEXT NULL,
            `paraf_sekcam_oleh` VARCHAR(150) NULL,
            `paraf_sekcam_nip` VARCHAR(50) NULL,
            `waktu_paraf_sekcam` TIMESTAMP NULL,
            `catatan_sekcam` TEXT NULL,
            `tte_camat_oleh` VARCHAR(150) NULL,
            `tte_camat_nip` VARCHAR(50) NULL,
            `tte_kode` VARCHAR(100) NULL,
            `catatan_tolak` TEXT NULL,
            `ditolak_oleh` VARCHAR(150) NULL,
            `is_revisi` TINYINT(1) DEFAULT 0,
            `waktu_revisi` TIMESTAMP NULL,
            `waktu_pengajuan` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `waktu_verifikasi` TIMESTAMP NULL,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colsPengajuan = $pdo_db->query("SHOW COLUMNS FROM `pengajuan`")->fetchAll(PDO::FETCH_COLUMN);
    $alterPengajuanCols = [
        'is_ttd_dibubuhkan'    => "ADD COLUMN `is_ttd_dibubuhkan` TINYINT(1) DEFAULT 0 AFTER `status`",
        'ttd_id'               => "ADD COLUMN `ttd_id` INT NULL AFTER `is_ttd_dibubuhkan`",
        'waktu_ttd_dibubuhkan' => "ADD COLUMN `waktu_ttd_dibubuhkan` TIMESTAMP NULL AFTER `ttd_id`",
        'ttd_dibubuhkan_oleh'  => "ADD COLUMN `ttd_dibubuhkan_oleh` VARCHAR(150) NULL AFTER `waktu_ttd_dibubuhkan`",
        'kasi_tujuan'          => "ADD COLUMN `kasi_tujuan` VARCHAR(50) NULL AFTER `ttd_dibubuhkan_oleh`",
        'ptsp_oleh'            => "ADD COLUMN `ptsp_oleh` VARCHAR(150) NULL AFTER `kasi_tujuan`",
        'waktu_validasi_ptsp'  => "ADD COLUMN `waktu_validasi_ptsp` TIMESTAMP NULL AFTER `ptsp_oleh`",
        'catatan_ptsp'         => "ADD COLUMN `catatan_ptsp` TEXT NULL AFTER `waktu_validasi_ptsp`",
        'paraf_kasi_oleh'      => "ADD COLUMN `paraf_kasi_oleh` VARCHAR(150) NULL AFTER `catatan_ptsp`",
        'paraf_kasi_nip'       => "ADD COLUMN `paraf_kasi_nip` VARCHAR(50) NULL AFTER `paraf_kasi_oleh`",
        'paraf_kasi_jabatan'   => "ADD COLUMN `paraf_kasi_jabatan` VARCHAR(150) NULL AFTER `paraf_kasi_nip`",
        'waktu_paraf_kasi'     => "ADD COLUMN `waktu_paraf_kasi` TIMESTAMP NULL AFTER `paraf_kasi_jabatan`",
        'catatan_kasi'         => "ADD COLUMN `catatan_kasi` TEXT NULL AFTER `waktu_paraf_kasi`",
        'redaksi_kasi_pembuka' => "ADD COLUMN `redaksi_kasi_pembuka` TEXT NULL AFTER `catatan_kasi`",
        'redaksi_kasi_penutup' => "ADD COLUMN `redaksi_kasi_penutup` TEXT NULL AFTER `redaksi_kasi_pembuka`",
        'paraf_sekcam_oleh'    => "ADD COLUMN `paraf_sekcam_oleh` VARCHAR(150) NULL AFTER `redaksi_kasi_penutup`",
        'paraf_sekcam_nip'     => "ADD COLUMN `paraf_sekcam_nip` VARCHAR(50) NULL AFTER `paraf_sekcam_oleh`",
        'waktu_paraf_sekcam'   => "ADD COLUMN `waktu_paraf_sekcam` TIMESTAMP NULL AFTER `paraf_sekcam_nip`",
        'catatan_sekcam'       => "ADD COLUMN `catatan_sekcam` TEXT NULL AFTER `waktu_paraf_sekcam`",
        'tte_camat_oleh'       => "ADD COLUMN `tte_camat_oleh` VARCHAR(150) NULL AFTER `catatan_sekcam`",
        'tte_camat_nip'        => "ADD COLUMN `tte_camat_nip` VARCHAR(50) NULL AFTER `tte_camat_oleh`",
        'tte_kode'             => "ADD COLUMN `tte_kode` VARCHAR(100) NULL AFTER `tte_camat_nip`",
        'catatan_tolak'        => "ADD COLUMN `catatan_tolak` TEXT NULL AFTER `tte_kode`",
        'ditolak_oleh'         => "ADD COLUMN `ditolak_oleh` VARCHAR(150) NULL AFTER `catatan_tolak`",
        'is_revisi'            => "ADD COLUMN `is_revisi` TINYINT(1) DEFAULT 0 AFTER `ditolak_oleh`",
        'waktu_revisi'         => "ADD COLUMN `waktu_revisi` TIMESTAMP NULL AFTER `is_revisi`"
    ];
    foreach ($alterPengajuanCols as $colName => $sqlAlter) {
        if (!in_array($colName, $colsPengajuan)) {
            $pdo_db->exec("ALTER TABLE `pengajuan` $sqlAlter;");
        }
    }

    // 7. Buat Tabel `lampiran`
    $pdo_db->exec("
        CREATE TABLE IF NOT EXISTS `lampiran` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `pengajuan_id` INT NOT NULL,
            `nama_berkas` VARCHAR(100) NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            FOREIGN KEY (`pengajuan_id`) REFERENCES `pengajuan`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 8. Seeding / Upsert Tanda Tangan Default
    $stmtCountTTD = $pdo_db->query("SELECT COUNT(*) FROM `tanda_tangan`");
    if ($stmtCountTTD->fetchColumn() == 0) {
        $stmtInsTTD = $pdo_db->prepare("INSERT INTO `tanda_tangan` (`judul_ttd`, `tipe_ttd`, `teks_atas`, `jabatan`, `nama_pejabat`, `pangkat_golongan`, `nip`, `is_default`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtInsTTD->execute([
            'Tanda Tangan Elektronik Camat Cisayong (Utama)',
            'elektronik_kotak',
            'Ditandatangani secara elektronik oleh',
            'CAMAT CISAYONG',
            'AYI MULYANA HERNIWAN, SE., M.Si.',
            'Pembina : Tk. I IV/b',
            '19721003 199403 1 012',
            1
        ]);
        $stmtInsTTD->execute([
            'Tanda Tangan Elektronik a.n. Camat (Sekretaris Camat)',
            'elektronik_kotak',
            'Ditandatangani secara elektronik a.n. Camat Cisayong',
            'SEKRETARIS CAMAT CISAYONG',
            'Drs. H. UNDANG SUHENDAR, M.Si.',
            'Pembina Tk. I - IV/b',
            '19750610 199603 1 003',
            0
        ]);
        $stmtInsTTD->execute([
            'Tanda Tangan Manual & Stempel Kecamatan',
            'gambar_upload',
            'Ditandatangani & Distempel Resmi',
            'CAMAT CISAYONG',
            'AYI MULYANA HERNIWAN, SE., M.Si.',
            'Pembina : Tk. I IV/b',
            '19721003 199403 1 012',
            0
        ]);
    }

    // 9. Seeding / Upsert Akun Pengguna Sesuai Email & Alur Hierarki Kecamatan
    $passHash = password_hash('rahasia123', PASSWORD_BCRYPT);
    $defaultUsers = [
        // Warga
        ['Miftah Mulfarid', '3206082311050001', 'miftah@cisayong.desa.id', $passHash, 'masyarakat', null, 'Warga Kecamatan Cisayong', null, '081223344556'],
        ['Ahmad Warga', '3201928391820001', 'warga@cisayong.desa.id', $passHash, 'masyarakat', null, 'Warga Kecamatan Cisayong', null, '081298765432'],
        // Admin Desa
        ['Perangkat Desa Jatihurip', 'desa_jatihurip', 'desa.jatihurip@cisayong.desa.id', $passHash, 'desa', 'Desa Jatihurip', 'Kaur Keuangan Desa Jatihurip', null, '081311223344'],
        ['Budi Santoso (Admin Desa)', 'desa_karangtengah', 'desa.karangtengah@cisayong.desa.id', $passHash, 'desa', 'Desa Karangtengah', 'Sekretaris Desa Karangtengah', null, '081355667788'],
        // Front Office (PTSP) Kecamatan
        ['Rina Marlina, A.Md. (PTSP)', 'ptsp_kecamatan', 'ptsp@cisayong.desa.id', $passHash, 'ptsp', 'Kecamatan Cisayong', 'Petugas Front Office (PTSP)', '19920412 201903 2 008', '081210002000'],
        // Kasi Terkait (Spesifik per Bidang)
        ['Hj. Nenden Siti, S.Sos.', 'kasi_kesra', 'kasi.kesra@cisayong.desa.id', $passHash, 'kasi_kesra', 'Kecamatan Cisayong', 'Kasi Kesejahteraan Rakyat & Pelayanan', '19800514 200604 2 009', '081230004000'],
        ['H. Dedi Kurniawan, S.IP.', 'kasi_pemerintahan', 'kasi.pemerintahan@cisayong.desa.id', $passHash, 'kasi_pemerintahan', 'Kecamatan Cisayong', 'Kasi Pemerintahan', '19781120 200501 1 005', '081230004001'],
        ['Irfan Nugraha, S.E.', 'kasi_pmd', 'kasi.pmd@cisayong.desa.id', $passHash, 'kasi_pmd', 'Kecamatan Cisayong', 'Kasi Pemberdayaan Masyarakat & Desa (PMD)', '19820708 200801 1 004', '081230004002'],
        ['Agus Rahmat, S.H.', 'kasi_trantib', 'kasi.trantib@cisayong.desa.id', $passHash, 'kasi_trantib', 'Kecamatan Cisayong', 'Kasi Ketentraman & Ketertiban Umum', '19790315 200701 1 007', '081230004003'],
        // Sekretaris Camat (Sekcam)
        ['Drs. H. Undang Suhendar, M.Si.', 'sekcam_cisayong', 'sekcam@cisayong.desa.id', $passHash, 'sekcam', 'Kecamatan Cisayong', 'Sekretaris Camat Cisayong', '19750610 199603 1 003', '081250006000'],
        // Camat
        ['Ayi Mulyana Herniwan, SE., M.Si.', 'camat_cisayong', 'camat@cisayong.desa.id', $passHash, 'camat', 'Kecamatan Cisayong', 'Camat Cisayong', '19721003 199403 1 012', '081270008000'],
        // Superadmin
        ['Admin Sistem Kecamatan', 'admin_camat', 'admin@cisayong.desa.id', $passHash, 'admin', 'Kecamatan Cisayong', 'Superadmin Sistem Sip-Surat', '19880101 201503 1 001', '081299990000'],
    ];

    $stmtCheckUser = $pdo_db->prepare("SELECT id FROM `users` WHERE `username` = ?");
    $stmtInsertUser = $pdo_db->prepare("INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`, `instansi_desa`, `jabatan`, `nip`, `phone`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtUpdateUserMeta = $pdo_db->prepare("UPDATE `users` SET `email` = ?, `jabatan` = COALESCE(`jabatan`, ?), `nip` = COALESCE(`nip`, ?) WHERE `username` = ?");

    foreach ($defaultUsers as $u) {
        $stmtCheckUser->execute([$u[1]]);
        if (!$stmtCheckUser->fetchColumn()) {
            $stmtInsertUser->execute($u);
        } else {
            $stmtUpdateUserMeta->execute([$u[2], $u[6], $u[7], $u[1]]);
        }
    }

    // Pastikan user lama yang emailnya masih null diupdate otomatis
    $pdo_db->exec("UPDATE `users` SET `email` = CONCAT(LOWER(REPLACE(username, ' ', '')), '@cisayong.desa.id') WHERE `email` IS NULL OR `email` = '';");

    // 10. Seeding / Upsert Data Templates Default (5 Jenis Surat Umum Warga + 1 Surat ADD Desa)
    $defaultTemplates = [
        [
            'jenis_surat'           => 'SKTM (Surat Keterangan Tidak Mampu)',
            'kategori_pemohon'      => 'masyarakat',
            'kasi_default'          => 'kasi_kesra',
            'kode_klasifikasi'      => '460',
            'format_nomor'          => '[KODE]/[URUT]/Kec.Kesra/[TAHUN]',
            'judul_surat'           => 'SURAT KETERANGAN TIDAK MAMPU (SKTM)',
            'sifat'                 => null,
            'lampiran'              => null,
            'perihal'               => null,
            'tujuan'                => null,
            'pembuka'               => "Yang bertanda tangan di bawah ini Camat Cisayong Kabupaten Tasikmalaya, berdasarkan Surat Pengantar dari Pemerintah Desa serta hasil verifikasi berkas yang bersangkutan, dengan ini menerangkan bahwa:",
            'penutup'               => "Berdasarkan data dan keterangan dari Pemerintah Desa setempat, nama tersebut di atas adalah benar warga yang tergolong Keluarga Tidak Mampu / Pra-Sejahtera. Surat Keterangan ini diberikan untuk keperluan administrasi yang bersangkutan.\n\nDemikian Surat Keterangan Tidak Mampu ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => json_encode([
                ['label' => 'Tempat / Tanggal Lahir', 'name' => 'tempat_tanggal_lahir', 'type' => 'text', 'required' => true],
                ['label' => 'Jenis Kelamin', 'name' => 'jenis_kelamin', 'type' => 'text', 'required' => true],
                ['label' => 'Pekerjaan', 'name' => 'pekerjaan', 'type' => 'text', 'required' => true],
                ['label' => 'Penghasilan Rata-rata / Bulan (Rp)', 'name' => 'penghasilan_per_bulan', 'type' => 'text', 'required' => true],
                ['label' => 'Jumlah Tanggungan Keluarga', 'name' => 'jumlah_tanggungan', 'type' => 'number', 'required' => true],
                ['label' => 'Keperluan Pengajuan SKTM', 'name' => 'keperluan_sktm', 'type' => 'text', 'required' => true],
                ['label' => 'Alamat Lengkap Sesuai KTP', 'name' => 'alamat_lengkap', 'type' => 'textarea', 'required' => true],
                ['label' => 'KTP Pemohon', 'name' => 'file_ktp', 'type' => 'file', 'required' => true],
                ['label' => 'Kartu Keluarga (KK)', 'name' => 'file_kk', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Pengantar Desa / RT-RW', 'name' => 'file_pengantar_desa', 'type' => 'file', 'required' => true]
            ])
        ],
        [
            'jenis_surat'           => 'Surat Keterangan Ahli Waris',
            'kategori_pemohon'      => 'masyarakat',
            'kasi_default'          => 'kasi_pemerintahan',
            'kode_klasifikasi'      => '474.3',
            'format_nomor'          => '[KODE]/[URUT]/Kec.Pem/[TAHUN]',
            'judul_surat'           => 'SURAT KETERANGAN AHLI WARIS',
            'sifat'                 => null,
            'lampiran'              => null,
            'perihal'               => null,
            'tujuan'                => null,
            'pembuka'               => "Kami yang bertanda tangan di bawah ini, Camat Cisayong Kabupaten Tasikmalaya, berdasarkan Surat Pernyataan Ahli Waris yang diketahui oleh Kepala Desa serta hasil pemeriksaan administrasi pemerintahan, dengan ini menerangkan dengan sebenarnya bahwa:",
            'penutup'               => "Adalah benar para Ahli Waris yang sah dari Almarhum/Almarhumah tersebut di atas dan tidak ada ahli waris lainnya selain yang telah disebutkan.\n\nDemikian Surat Keterangan Ahli Waris ini dibuat dan disahkan di tingkat Kecamatan untuk dipergunakan sebagaimana mestinya.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => json_encode([
                ['label' => 'Nama Almarhum / Almarhumah (Pewaris)', 'name' => 'nama_pewaris', 'type' => 'text', 'required' => true],
                ['label' => 'Tanggal Meninggal Dunia', 'name' => 'tanggal_meninggal', 'type' => 'date', 'required' => true],
                ['label' => 'Tempat Meninggal & Nomor Akta/Surat Kematian', 'name' => 'nomor_surat_kematian', 'type' => 'text', 'required' => true],
                ['label' => 'Alamat Terakhir Pewaris', 'name' => 'alamat_terakhir_pewaris', 'type' => 'textarea', 'required' => true],
                ['label' => 'Hubungan Pemohon dengan Pewaris', 'name' => 'hubungan_waris', 'type' => 'text', 'required' => true],
                ['label' => 'Daftar Lengkap Nama Ahli Waris & Hubungan Keluarga', 'name' => 'daftar_ahli_waris', 'type' => 'textarea', 'required' => true],
                ['label' => 'Keperluan Administrasi Ahli Waris', 'name' => 'keperluan_waris', 'type' => 'text', 'required' => true],
                ['label' => 'KTP & KK Para Ahli Waris (PDF/JPG)', 'name' => 'file_ktp_kk_waris', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Kematian / Akta Kematian Pewaris', 'name' => 'file_akta_kematian', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Pernyataan Waris dari Desa', 'name' => 'file_pernyataan_desa', 'type' => 'file', 'required' => true]
            ])
        ],
        [
            'jenis_surat'           => 'Dispensasi Nikah',
            'kategori_pemohon'      => 'masyarakat',
            'kasi_default'          => 'kasi_kesra',
            'kode_klasifikasi'      => '400.8',
            'format_nomor'          => '[KODE]/[URUT]/Kec/[TAHUN]',
            'judul_surat'           => 'SURAT KETERANGAN DISPENSASI NIKAH',
            'sifat'                 => null,
            'lampiran'              => null,
            'perihal'               => null,
            'tujuan'                => null,
            'pembuka'               => "Sesuai dengan Peraturan Pemerintah Nomor 9 tahun 1975 Bab II Pasal 1, Camat Cisayong Atas Nama Bupati Tasikmalaya, dengan ini memberikan Dispensasi Nikah kepada:",
            'penutup'               => "Demikian agar menjadi maklum dan untuk digunakan sebagaimana mestinya.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => null
        ],
        [
            'jenis_surat'           => 'Surat Keterangan Usaha (SKU) / Domisili',
            'kategori_pemohon'      => 'masyarakat',
            'kasi_default'          => 'kasi_pmd',
            'kode_klasifikasi'      => '503',
            'format_nomor'          => '[KODE]/[URUT]/Kec.PMD/[TAHUN]',
            'judul_surat'           => 'SURAT KETERANGAN USAHA & DOMISILI',
            'sifat'                 => null,
            'lampiran'              => null,
            'perihal'               => null,
            'tujuan'                => null,
            'pembuka'               => "Yang bertanda tangan di bawah ini Camat Cisayong Kabupaten Tasikmalaya, berdasarkan Surat Keterangan dari Kepala Desa di lingkungan Kecamatan Cisayong, dengan ini menerangkan bahwa:",
            'penutup'               => "Nama tersebut di atas benar memiliki dan menjalankan kegiatan usaha yang berdomisili di wilayah Kecamatan Cisayong Kabupaten Tasikmalaya. Surat keterangan ini berlaku untuk keperluan administrasi perbankan/perizinan usaha mikro.\n\nDemikian Surat Keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => json_encode([
                ['label' => 'Tempat / Tanggal Lahir', 'name' => 'tempat_tanggal_lahir', 'type' => 'text', 'required' => true],
                ['label' => 'Pekerjaan Pokok', 'name' => 'pekerjaan', 'type' => 'text', 'required' => true],
                ['label' => 'Nama Usaha / Toko / UMKM', 'name' => 'nama_usaha', 'type' => 'text', 'required' => true],
                ['label' => 'Bidang / Jenis Usaha', 'name' => 'bidang_usaha', 'type' => 'text', 'required' => true],
                ['label' => 'Lama Usaha Berjalan', 'name' => 'lama_usaha', 'type' => 'text', 'required' => true],
                ['label' => 'Alamat Lengkap Tempat Usaha', 'name' => 'alamat_usaha', 'type' => 'textarea', 'required' => true],
                ['label' => 'Keperluan Pengajuan SKU / Domisili', 'name' => 'keperluan_surat', 'type' => 'text', 'required' => true],
                ['label' => 'KTP Pemohon', 'name' => 'file_ktp', 'type' => 'file', 'required' => true],
                ['label' => 'Kartu Keluarga (KK)', 'name' => 'file_kk', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Pengantar SKU Desa & Foto Usaha', 'name' => 'file_pengantar_sku', 'type' => 'file', 'required' => true]
            ])
        ],
        [
            'jenis_surat'           => 'Surat Rekomendasi Izin Keramaian / Acara',
            'kategori_pemohon'      => 'masyarakat',
            'kasi_default'          => 'kasi_trantib',
            'kode_klasifikasi'      => '300',
            'format_nomor'          => '[KODE]/[URUT]/Kec.Trantib/[TAHUN]',
            'judul_surat'           => 'SURAT REKOMENDASI IZIN KERAMAIAN',
            'sifat'                 => 'Biasa',
            'lampiran'              => '1 (satu) berkas',
            'perihal'               => 'Rekomendasi Izin Keramaian / Kegiatan Masyarakat',
            'tujuan'                => "Kapolsek Cisayong\ndi\nCISAYONG",
            'pembuka'               => "Berdasarkan permohonan rekomendasi izin keramaian dari warga/panitia penyelenggara serta surat pengantar dari Kepala Desa setempat, setelah dilakukan pemeriksaan ketentraman dan ketertiban umum (Trantibum), dengan ini Camat Cisayong memberikan rekomendasi kegiatan kepada:",
            'penutup'               => "Dengan ketentuan penanggung jawab wajib menjaga ketertiban umum, keamanan lingkungan, serta mematuhi peraturan perundang-undangan yang berlaku.\n\nDemikian surat rekomendasi ini diberikan untuk bahan proses lebih lanjut.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => json_encode([
                ['label' => 'Nama Kegiatan / Acara', 'name' => 'nama_acara', 'type' => 'text', 'required' => true],
                ['label' => 'Jabatan Pemohon dalam Acara/Kepanitiaan', 'name' => 'jabatan_kepanitiaan', 'type' => 'text', 'required' => true],
                ['label' => 'Tanggal Pelaksanaan Acara', 'name' => 'tanggal_acara', 'type' => 'date', 'required' => true],
                ['label' => 'Waktu / Jam Pelaksanaan', 'name' => 'waktu_acara', 'type' => 'text', 'required' => true],
                ['label' => 'Estimasi Jumlah Undangan / Peserta (Orang)', 'name' => 'estimasi_peserta', 'type' => 'number', 'required' => true],
                ['label' => 'Bentuk Hiburan / Rangkaian Kegiatan', 'name' => 'bentuk_hiburan', 'type' => 'text', 'required' => true],
                ['label' => 'Lokasi Lengkap Tempat Acara', 'name' => 'lokasi_acara', 'type' => 'textarea', 'required' => true],
                ['label' => 'KTP Penanggung Jawab Acara', 'name' => 'file_ktp', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Pengantar Desa', 'name' => 'file_pengantar_desa', 'type' => 'file', 'required' => true],
                ['label' => 'Surat Pernyataan Menjaga Ketertiban', 'name' => 'file_pernyataan_trantib', 'type' => 'file', 'required' => true]
            ])
        ],
        [
            'jenis_surat'           => 'Alokasi Dana Daerah (ADD)',
            'kategori_pemohon'      => 'desa',
            'kasi_default'          => 'kasi_pmd',
            'kode_klasifikasi'      => '900.10.2.4',
            'format_nomor'          => 'B/[KODE]/[URUT]/Kec.Pum/[TAHUN]',
            'judul_surat'           => 'Rekomendasi Pencairan ADD',
            'sifat'                 => 'Penting',
            'lampiran'              => '1 (satu) berkas',
            'perihal'               => 'Rekomendasi Pencairan ADD',
            'tujuan'                => "Bupati Tasikmalaya\nMelalui :\nKepala DPMD Kab. Tasikmalaya\ndi\nSINGAPARNA",
            'pembuka'               => "Berdasarkan permohonan pencairan ADD Desa Jatihurip Kecamatan Cisayong nomor B/86/KU.02.03/DS/2025 tanggal 22 Oktober 2025 perihal Permohonan Pencairan Alokasi Dana Desa (ADD), berdasarkan hasil verifikasi dengan ini kami merekomendasikan permohonan Pencairan Alokasi Dana Desa (ADD) dimaksud untuk Bulan November sebesar Rp. 50.950.000,00 (Lima Puluh Juta Sembilan Ratus Lima Puluh Ribu Rupiah).\n\nAdapun kelengkapan pengajuan pencairan sebagaimana terlampir, meliputi :\n1. Surat Permohonan Pencairan;\n2. Fakta Integritas;\n3. Bukti Pembayaran;\n4. Surat Pernyataan Tanggungjawab Mutlak (SPTJM);\n5. Foto copy KTP Kepala Desa dan Bendahara Desa;\n6. Foto copy Rekening Pemerintahan Desa;\n7. Foto Copy NPWP;\n8. Lembar Verifikasi Kecamatan;\n9. Data Jumlah Kaur, Kasi, Kadus, BPD, RT dan RW.",
            'penutup'               => "Demikian untuk maklum, atas terkabulnya kami ucapkan terimakasih.",
            'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
            'penandatangan_nip'     => '19721003 199403 1 012',
            'penandatangan_jabatan' => 'CAMAT CISAYONG',
            'penandatangan_pangkat' => 'Pembina Tk.I - IV/b',
            'is_tte'                => 1,
            'form_fields'           => null
        ]
    ];

    $stmtCheckTmpl = $pdo_db->prepare("SELECT id FROM `templates` WHERE `jenis_surat` = ?");
    $stmtInsertTmpl = $pdo_db->prepare("INSERT INTO `templates` (`jenis_surat`, `kategori_pemohon`, `kasi_default`, `kode_klasifikasi`, `format_nomor`, `judul_surat`, `sifat`, `lampiran`, `perihal`, `tujuan`, `pembuka`, `penutup`, `penandatangan_nama`, `penandatangan_nip`, `penandatangan_jabatan`, `penandatangan_pangkat`, `is_tte`, `form_fields`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtUpdateTmplMeta = $pdo_db->prepare("UPDATE `templates` SET `kategori_pemohon` = ?, `kasi_default` = ?, `is_tte` = 1 WHERE `jenis_surat` = ?");

    foreach ($defaultTemplates as $t) {
        $stmtCheckTmpl->execute([$t['jenis_surat']]);
        if (!$stmtCheckTmpl->fetchColumn()) {
            $stmtInsertTmpl->execute([
                $t['jenis_surat'],
                $t['kategori_pemohon'],
                $t['kasi_default'],
                $t['kode_klasifikasi'],
                $t['format_nomor'],
                $t['judul_surat'],
                $t['sifat'],
                $t['lampiran'],
                $t['perihal'],
                $t['tujuan'],
                $t['pembuka'],
                $t['penutup'],
                $t['penandatangan_nama'],
                $t['penandatangan_nip'],
                $t['penandatangan_jabatan'],
                $t['penandatangan_pangkat'],
                $t['is_tte'],
                $t['form_fields']
            ]);
        } else {
            $stmtUpdateTmplMeta->execute([$t['kategori_pemohon'], $t['kasi_default'], $t['jenis_surat']]);
        }
    }

    $pdo_db->exec("UPDATE `pengajuan` p JOIN `templates` t ON p.jenis_surat = t.jenis_surat SET p.kasi_tujuan = t.kasi_default WHERE p.kasi_tujuan IS NULL;");

} catch (\PDOException $e) {
    error_log("Database initialization error: " . $e->getMessage());
}
