<?php
// File: config/database.php

$host    = getenv('DB_HOST') ?: 'localhost';
$port    = getenv('DB_PORT') ?: '3306';
$db      = getenv('DB_NAME') ?: 'sip_surat';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = 'utf8mb4';

if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: '/surkec/');
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Cek apakah tabel tanda_tangan dan kolom alur berjenjang sudah ada
    $checkTbl = $pdo->query("SHOW TABLES LIKE 'tanda_tangan'")->fetch();
    $checkCol = $pdo->query("SHOW COLUMNS FROM `pengajuan` LIKE 'is_ttd_dibubuhkan'")->fetch();
    if (!$checkTbl || !$checkCol) {
        require_once __DIR__ . '/init_db.php';
    }
} catch (\PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown database') !== false || strpos($e->getMessage(), '1049') !== false || strpos($e->getMessage(), '42S02') !== false) {
        require_once __DIR__ . '/init_db.php';
        $pdo = new PDO($dsn, $user, $pass, $options);
    } else {
        error_log("Koneksi Database Gagal: " . $e->getMessage());
    }
}

// =============================================================================
// HELPER FUNGSI HAK AKSES & ALUR HIERARKI KECAMATAN
// (PTSP -> KASI TERKAIT -> SEKCAM -> CAMAT -> GENERATE PDF)
// =============================================================================

if (!function_exists('is_kecamatan_role')) {
    function is_kecamatan_role($role) {
        return in_array($role, [
            'admin',
            'ptsp',
            'kasi_pemerintahan',
            'kasi_kesra',
            'kasi_pmd',
            'kasi_trantib',
            'sekcam',
            'camat'
        ], true);
    }
}

if (!function_exists('is_kasi_role')) {
    function is_kasi_role($role) {
        return in_array($role, [
            'kasi_pemerintahan',
            'kasi_kesra',
            'kasi_pmd',
            'kasi_trantib'
        ], true);
    }
}

if (!function_exists('get_role_label')) {
    function get_role_label($role) {
        $labels = [
            'masyarakat'        => 'Warga / Masyarakat',
            'desa'              => 'Admin Desa',
            'ptsp'              => 'Front Office (PTSP)',
            'kasi_kesra'        => 'Kasi Kesra & Pelayanan',
            'kasi_pemerintahan' => 'Kasi Pemerintahan',
            'kasi_pmd'          => 'Kasi PMD (Pemberdayaan Masyarakat & Desa)',
            'kasi_trantib'      => 'Kasi Trantibum',
            'sekcam'            => 'Sekretaris Camat (Sekcam)',
            'camat'             => 'Camat Cisayong',
            'admin'             => 'Superadmin Kecamatan'
        ];
        return $labels[$role] ?? ucfirst((string)$role);
    }
}

if (!function_exists('get_kasi_list')) {
    function get_kasi_list() {
        return [
            'kasi_kesra'        => 'Kasi Kesra & Pelayanan (SKTM, Dispensasi Nikah, Layanan Sosial)',
            'kasi_pemerintahan' => 'Kasi Pemerintahan (Ahli Waris, Kependudukan, Pertanahan)',
            'kasi_pmd'          => 'Kasi PMD / Ekbang (Surat ADD Desa, SKU / Usaha)',
            'kasi_trantib'      => 'Kasi Trantibum (Rekomendasi Izin Keramaian / Acara)'
        ];
    }
}

if (!function_exists('get_default_kasi_for_surat')) {
    function get_default_kasi_for_surat($jenis_surat, $template_kasi = null) {
        if (!empty($template_kasi) && is_kasi_role($template_kasi)) {
            return $template_kasi;
        }
        $map = [
            'SKTM (Surat Keterangan Tidak Mampu)'      => 'kasi_kesra',
            'Dispensasi Nikah'                         => 'kasi_kesra',
            'Surat Keterangan Ahli Waris'              => 'kasi_pemerintahan',
            'Surat Keterangan Usaha (SKU) / Domisili'  => 'kasi_pmd',
            'Alokasi Dana Daerah (ADD)'                => 'kasi_pmd',
            'Surat Rekomendasi Izin Keramaian / Acara' => 'kasi_trantib',
        ];
        return $map[$jenis_surat] ?? 'kasi_kesra';
    }
}

if (!function_exists('get_status_info')) {
    function get_status_info($status, $kasi_tujuan = null) {
        $status = ($status === 'menunggu' || empty($status)) ? 'menunggu_ptsp' : $status;
        $kasi_label = $kasi_tujuan ? get_role_label($kasi_tujuan) : 'Kasi Terkait';

        switch ($status) {
            case 'menunggu_ptsp':
                return [
                    'code'        => 'menunggu_ptsp',
                    'step'        => 1,
                    'label'       => 'Verifikasi Front Office (PTSP)',
                    'short_label' => 'Di Meja PTSP',
                    'badge_class' => 'bg-amber-50 text-amber-800 border-amber-200',
                    'icon'        => 'support_agent',
                    'desc'        => 'Menunggu pemeriksaan kelengkapan berkas oleh petugas Front Office (PTSP) Kecamatan.'
                ];
            case 'menunggu_kasi':
                return [
                    'code'        => 'menunggu_kasi',
                    'step'        => 2,
                    'label'       => 'Periksa Substansi (' . $kasi_label . ')',
                    'short_label' => 'Di Meja ' . $kasi_label,
                    'badge_class' => 'bg-blue-50 text-blue-900 border-blue-200',
                    'icon'        => 'rate_review',
                    'desc'        => 'Berkas dinyatakan lengkap oleh PTSP dan sedang diperiksa substansi & redaksi oleh ' . $kasi_label . '.'
                ];
            case 'menunggu_sekcam':
                return [
                    'code'        => 'menunggu_sekcam',
                    'step'        => 3,
                    'label'       => 'Paraf Koordinasi Sekcam',
                    'short_label' => 'Di Meja Sekcam',
                    'badge_class' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                    'icon'        => 'fact_check',
                    'desc'        => 'Telah diparaf oleh Kasi Terkait, menunggu Paraf Koordinasi Sekretaris Camat (Sekcam).'
                ];
            case 'menunggu_camat':
                return [
                    'code'        => 'menunggu_camat',
                    'step'        => 4,
                    'label'       => 'Menunggu Approve & TTE Camat',
                    'short_label' => 'Di Meja Camat',
                    'badge_class' => 'bg-purple-50 text-purple-800 border-purple-200',
                    'icon'        => 'verified_user',
                    'desc'        => 'Telah diparaf koordinasi oleh Sekcam, menunggu Persetujuan Akhir & TTE (QR Code) Camat.'
                ];
            case 'disetujui':
                return [
                    'code'        => 'disetujui',
                    'step'        => 5,
                    'label'       => 'Selesai • TTE & PDF Siap Unduh',
                    'short_label' => 'Selesai (Siap Unduh)',
                    'badge_class' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                    'icon'        => 'qr_code_2',
                    'desc'        => 'Telah di-Approve & TTE (QR Code) oleh Camat. Dokumen PDF resmi siap diunduh dari akun.'
                ];
            case 'ditolak':
                return [
                    'code'        => 'ditolak',
                    'step'        => 0,
                    'label'       => 'Berkas Ditolak (Upload Ulang)',
                    'short_label' => 'Perlu Upload Ulang',
                    'badge_class' => 'bg-rose-50 text-rose-700 border-rose-200',
                    'icon'        => 'assignment_return',
                    'desc'        => 'Berkas dikembalikan. Silakan perbaiki dan upload ulang syarat sesuai catatan koreksi.'
                ];
            default:
                return [
                    'code'        => $status,
                    'step'        => 1,
                    'label'       => ucfirst($status),
                    'short_label' => ucfirst($status),
                    'badge_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                    'icon'        => 'schedule',
                    'desc'        => ''
                ];
        }
    }
}

if (!function_exists('can_role_verify_submission')) {
    function can_role_verify_submission($role, $pengajuan) {
        $status = ($pengajuan['status'] === 'menunggu' || empty($pengajuan['status'])) ? 'menunggu_ptsp' : $pengajuan['status'];
        if ($role === 'admin') {
            return in_array($status, ['menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat'], true);
        }
        if ($role === 'ptsp' && $status === 'menunggu_ptsp') {
            return true;
        }
        if (is_kasi_role($role) && $status === 'menunggu_kasi') {
            $kasi_tujuan = $pengajuan['kasi_tujuan'] ?: get_default_kasi_for_surat($pengajuan['jenis_surat']);
            return ($role === $kasi_tujuan);
        }
        if ($role === 'sekcam' && $status === 'menunggu_sekcam') {
            return true;
        }
        if ($role === 'camat' && $status === 'menunggu_camat') {
            return true;
        }
        return false;
    }
}

// =============================================================================
// HELPER NOTIFIKASI WHATSAPP UNTUK WARGA & ADMIN DESA
// =============================================================================

if (!function_exists('format_phone_whatsapp')) {
    function format_phone_whatsapp($phone) {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($clean)) return '';
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        return $clean;
    }
}

if (!function_exists('get_whatsapp_notification_url')) {
    function get_whatsapp_notification_url($phone, $nama, $jenis_surat, $nomor_surat, $pengajuan_id, $status = 'disetujui') {
        $wa_phone = format_phone_whatsapp($phone);
        if (empty($wa_phone)) return null;

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $download_url = $scheme . "://" . $host . BASE_URL . "surat/cetak_surat.php?id=" . $pengajuan_id;

        if ($status === 'disetujui') {
            $msg = "🏛️ *PEMBERITAHUAN PELAYANAN SURAT DIGITAL*\n"
                 . "*KECAMATAN CISAYONG - KABUPATEN TASIKMALAYA*\n\n"
                 . "Yth. Bpk/Ibu *{$nama}*,\n\n"
                 . "Permohonan surat Anda telah *SELESAI DIPROSES & DITANDATANGANI SECARA RESMI*:\n"
                 . "📄 *Jenis Surat:* {$jenis_surat}\n"
                 . "🔢 *Nomor Surat:* {$nomor_surat}\n"
                 . "✅ *Status:* Selesai (Approve & TTE Camat Cisayong)\n\n"
                 . "Dokumen resmi ber-TTE kini siap diunduh langsung dalam format PDF siap cetak melalui tautan portal berikut:\n"
                 . "👉 {$download_url}\n\n"
                 . "_Pesan otomatis dari Sistem Pelayanan Surat Digital Kecamatan Cisayong._";
        } elseif ($status === 'ditolak') {
            $msg = "🏛️ *PEMBERITAHUAN PELAYANAN SURAT DIGITAL*\n"
                 . "*KECAMATAN CISAYONG - KABUPATEN TASIKMALAYA*\n\n"
                 . "Yth. Bpk/Ibu *{$nama}*,\n\n"
                 . "Pengajuan surat *{$jenis_surat}* Anda memerlukan *UPLOAD ULANG / PERBAIKAN PERSYARATAN*.\n"
                 . "Silakan masuk ke akun Anda untuk melihat catatan perbaikan dan mengunggah ulang dokumen yang diminta:\n"
                 . "👉 {$scheme}://{$host}" . BASE_URL . "auth/login.php\n\n"
                 . "_Pesan resmi dari Sistem Pelayanan Surat Digital Kecamatan Cisayong._";
        } else {
            $msg = "🏛️ *PEMBERITAHUAN PELAYANAN SURAT DIGITAL*\n"
                 . "*KECAMATAN CISAYONG - KABUPATEN TASIKMALAYA*\n\n"
                 . "Yth. Bpk/Ibu *{$nama}*,\n\n"
                 . "Pengajuan surat *{$jenis_surat}* Anda telah diterima sistem dan sedang dalam antrean verifikasi berjenjang Kecamatan Cisayong.\n"
                 . "Pantau progres berkas Anda melalui dasbor pemohon di portal resmi:\n"
                 . "👉 {$scheme}://{$host}" . BASE_URL . "auth/login.php";
        }

        return "https://api.whatsapp.com/send?phone=" . $wa_phone . "&text=" . urlencode($msg);
    }
}
