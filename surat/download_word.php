<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php" . "");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada ID, cari pengajuan terakhir yang disetujui sesuai role
if (!$id) {
    if (is_kecamatan_role($_SESSION['role'] ?? '')) {
        $stmtFirst = $pdo->query("SELECT id FROM `pengajuan` WHERE status='disetujui' ORDER BY waktu_verifikasi DESC LIMIT 1");
    } else {
        $stmtFirst = $pdo->prepare("SELECT id FROM `pengajuan` WHERE status='disetujui' AND user_id = ? ORDER BY waktu_verifikasi DESC LIMIT 1");
        $stmtFirst->execute([$_SESSION['user_id']]);
    }
    $id = (int)$stmtFirst->fetchColumn();
}

if (!$id) {
    die("Data pengajuan surat tidak ditemukan atau belum disetujui.");
}

// Fetch pengajuan
$stmtP = $pdo->prepare("SELECT p.*, u.nama, u.username, u.instansi_desa FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id WHERE p.id = ?");
$stmtP->execute([$id]);
$pengajuan = $stmtP->fetch();

if (!$pengajuan) {
    die("Data surat tidak ditemukan.");
}

// Validasi otorisasi kepemilikan surat (mencegah user melihat/mengunduh surat user lain)
if (!is_kecamatan_role($_SESSION['role'] ?? '') && $pengajuan['user_id'] != $_SESSION['user_id']) {
    die("Akses Ditolak: Anda tidak berhak mengunduh surat milik pengguna lain.");
}

$jenis_surat = $pengajuan['jenis_surat'];
$data_form = json_decode($pengajuan['data_form'], true) ?? [];
$waktu_verif = !empty($pengajuan['waktu_verifikasi']) ? strtotime($pengajuan['waktu_verifikasi']) : time();

// Fungsi format tanggal Indonesia
function tanggal_indonesia($waktu) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return date('d', $waktu) . ' ' . $bulan[(int)date('m', $waktu)] . ' ' . date('Y', $waktu);
}
$tanggal_surat_tampil = tanggal_indonesia($waktu_verif);
$nomor_surat = $pengajuan['nomor_surat_generated'] ?? ($jenis_surat === 'Dispensasi Nikah' ? '400.8/116/Kec/2026' : 'B/900.10.2.4/134/Kec.Pum/2025');

// Fetch Template
$stmtT = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
$stmtT->execute([$jenis_surat]);
$template = $stmtT->fetch();

if (!$template) {
    // Fallback default values matching Cisayong Tasikmalaya
    $template = [
        'judul_surat' => $jenis_surat === 'Dispensasi Nikah' ? 'SURAT KETERANGAN' : 'Rekomendasi Pencairan ADD',
        'sifat' => $jenis_surat === 'Dispensasi Nikah' ? '' : 'Penting',
        'lampiran' => $jenis_surat === 'Dispensasi Nikah' ? '' : '1 (satu) berkas',
        'perihal' => $jenis_surat === 'Dispensasi Nikah' ? '' : 'Rekomendasi Pencairan ADD',
        'tujuan' => $jenis_surat === 'Dispensasi Nikah' ? '' : "Bupati Tasikmalaya\nMelalui :\nKepala DPMD Kab. Tasikmalaya\ndi\nSINGAPARNA",
        'pembuka' => $jenis_surat === 'Dispensasi Nikah' ? 'Sesuai dengan Peraturan Pemerintah Nomor 9 tahun 1975 Bab II Pasal 1, Camat Cisayong Atas Nama Bupati Tasikmalaya, dengan ini memberikan Dispensasi Nikah kepada:' : "Berdasarkan permohonan pencairan ADD Desa Jatihurip Kecamatan Cisayong nomor B/86/KU.02.03/DS/2025 tanggal 22 Oktober 2025 perihal Permohonan Pencairan Alokasi Dana Desa (ADD), berdasarkan hasil verifikasi dengan ini kami merekomendasikan permohonan Pencairan Alokasi Dana Desa (ADD) dimaksud untuk Bulan November sebesar Rp. 50.950.000,00 (Lima Puluh Juta Sembilan Ratus Lima Puluh Ribu Rupiah).\n\nAdapun kelengkapan pengajuan pencairan sebagaimana terlampir, meliputi :\n1. Surat Permohonan Pencairan;\n2. Fakta Integritas;\n3. Bukti Pembayaran;\n4. Surat Pernyataan Tanggungjawab Mutlak (SPTJM);\n5. Foto copy KTP Kepala Desa dan Bendahara Desa;\n6. Foto copy Rekening Pemerintahan Desa;\n7. Foto Copy NPWP;\n8. Lembar Verifikasi Kecamatan;\n9. Data Jumlah Kaur, Kasi, Kadus, BPD, RT dan RW.",
        'penutup' => $jenis_surat === 'Dispensasi Nikah' ? 'Demikian agar menjadi maklum dan untuk digunakan sebagaimana mestinya.' : 'Demikian untuk maklum, atas terkabulnya kami ucapkan terimakasih.',
        'penandatangan_nama' => $jenis_surat === 'Dispensasi Nikah' ? 'SUTARYO, S.T.' : 'AYI MULYANA HERNIWAN, SE., M.Si.',
        'penandatangan_nip' => $jenis_surat === 'Dispensasi Nikah' ? '19740817 199803 1 004' : '19721003 199403 1 012',
        'penandatangan_jabatan' => 'CAMAT CISAYONG',
        'penandatangan_pangkat' => $jenis_surat === 'Dispensasi Nikah' ? 'Pembina' : 'Pembina : Tk.I IV/b',
        'is_tte' => $jenis_surat === 'Dispensasi Nikah' ? 1 : 0
    ];
}

// Status Tanda Tangan & Profil TTD
$is_ttd_dibubuhkan = (int)($pengajuan['is_ttd_dibubuhkan'] ?? 0);
$active_ttd_id = (int)($pengajuan['ttd_id'] ?? 0);
$current_ttd = null;

if ($active_ttd_id > 0) {
    $stmtTtd = $pdo->prepare("SELECT * FROM `tanda_tangan` WHERE id = ?");
    $stmtTtd->execute([$active_ttd_id]);
    $current_ttd = $stmtTtd->fetch();
}
if (!$current_ttd) {
    $current_ttd = $pdo->query("SELECT * FROM `tanda_tangan` WHERE is_default = 1 LIMIT 1")->fetch();
}

// Mengubah file lokal Tasikmalaya_Regency_Seal.png menjadi base64 agar tampil sempurna & offline-capable di MS Word
$logo_path = __DIR__ . '/../assets/logo/Tasikmalaya_Regency_Seal.png';
$logo_src = '../assets/logo/Tasikmalaya_Regency_Seal.png'; // fallback
if (file_exists($logo_path)) {
    $logo_base64 = base64_encode(file_get_contents($logo_path));
    $logo_src = 'data:image/png;base64,' . $logo_base64;
}

// Persiapkan base64 gambar TTD jika tipe gambar_upload
$ttd_img_src = '';
if (!empty($current_ttd['gambar_ttd'])) {
    $ttd_img_path = __DIR__ . '/../' . $current_ttd['gambar_ttd'];
    if (file_exists($ttd_img_path)) {
        $ext = strtolower(pathinfo($ttd_img_path, PATHINFO_EXTENSION));
        $ttd_base64 = base64_encode(file_get_contents($ttd_img_path));
        $ttd_img_src = 'data:image/' . ($ext === 'jpg' ? 'jpeg' : $ext) . ';base64,' . $ttd_base64;
    }
}

// Penamaan File Unik & Bebas Tabrakan
$nama_pemohon = $data_form['nama_pemohon'] ?? ($jenis_surat === 'Dispensasi Nikah' ? 'MIFTAH_MULFARID' : 'DESA_JATIHURIP');
$clean_nama = preg_replace('/[^A-Za-z0-9]/', '_', trim($nama_pemohon));
$clean_nomor = preg_replace('/[^A-Za-z0-9]/', '_', trim($nomor_surat));
$timestamp = date('Ymd_His');

$filename = "Surat_" . str_replace(' ', '_', $jenis_surat) . "_" . $clean_nama . "_" . $clean_nomor . "_" . $timestamp . ".doc";

// Mengatur header agar file diunduh sebagai dokumen Word
header("Content-Type: application/vnd.ms-word");
header("Content-Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment; filename=\"$filename\"");

?>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($jenis_surat); ?></title>
<style>
@page {
    size: 21cm 29.7cm; /* A4 */
    margin: 1.0cm 1.5cm 1.0cm 2.0cm; /* Margin atas/bawah 1.0cm agar dijamin pas 1 halaman Word */
    mso-page-orientation: portrait;
}
body {
    font-family: "Times New Roman", Times, serif;
    font-size: 11pt; /* Diperkecil sedikit menjadi 11pt agar muat 1 halaman */
    line-height: 1.2; /* Dibuat lebih padat di Word */
    color: #000000;
    margin: 0;
    padding: 0;
}
table {
    font-family: "Times New Roman", Times, serif;
    font-size: 11pt;
}
p {
    margin: 0 0 6px 0; /* Menghilangkan margin p bawaan Word yang membuat boros halaman */
}
</style>
</head>
<body>

<div class="Section1">

    <!-- KOP SURAT RESMI WORD (MENGGUNAKAN EMBEDDED BASE64 LOGO LOKAL) -->
    <table width="100%" style="border-bottom: 3px double #000000; margin-bottom: 12px;" cellpadding="0" cellspacing="0">
        <tr>
            <td width="15%" align="center" valign="middle" style="padding-bottom: 8px;">
                <img src="<?php echo $logo_src; ?>" width="65" height="70" alt="Logo Tasikmalaya">
            </td>
            <td width="85%" align="center" valign="middle" style="padding-bottom: 8px; padding-right: 65px;">
                <div style="font-size: 14pt; font-weight: bold; margin-bottom: 2px;">PEMERINTAH KABUPATEN TASIKMALAYA</div>
                <div style="font-size: 18pt; font-weight: bold; margin-bottom: 2px;">KECAMATAN CISAYONG</div>
                <div style="font-size: 10pt;">Jalan. Raya Cisayong Nomor. 20 Telepon. (0265) 420456</div>
                <div style="font-size: 10pt;">Faksimile 0265) 420456 (E-mail) cisayong@tasikmalayakab.go.id</div>
                <div style="font-size: 10pt;">Cisayong – 46153</div>
            </td>
        </tr>
    </table>

    <?php if ($jenis_surat === 'Alokasi Dana Daerah (ADD)' || !empty($template['tujuan'])): ?>
        <!-- MODEL SURAT DINAS ADD WORD SESUAI CONTOH CETAK RESMI -->
        <?php 
            $desa_nama_txt = $data_form['instansi_desa'] ?? ($desa_pemohon ?: 'Desa');
            $nomor_desa_val = !empty($data_form['nomor_surat_desa']) ? $data_form['nomor_surat_desa'] : '025/B.KU.03.02/DS/' . date('Y');
            $nominal_num = is_numeric($data_form['jumlah_dana'] ?? '') ? (float)$data_form['jumlah_dana'] : 0;
            $nominal_fmt = number_format($nominal_num, 0, ',', '.');
            $terbilang_txt = !empty($nominal_num) ? ucwords(terbilang_angka($nominal_num)) . ' Rupiah' : 'Nol Rupiah';
            $peruntukan_val = !empty($data_form['peruntukan_dana']) && $data_form['peruntukan_dana'] !== '-' ? $data_form['peruntukan_dana'] : ($data_form['tahap_alokasi'] ?? 'Bulan Berjalan');
            $tgl_surat_desa = !empty($pengajuan['waktu_pengajuan']) ? tanggal_indonesia(strtotime($pengajuan['waktu_pengajuan'])) : $tanggal_surat_tampil;
            $bulan_list_id = [
                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
            ];
            $cur_m = date('F', strtotime($pengajuan['waktu_pengajuan'] ?? 'now'));
            $bulan_absen_id = $bulan_list_id[$cur_m] ?? date('m');
        ?>
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 8px;">
            <tr>
                <td width="58%" valign="top">
                    <table width="100%" cellpadding="1" cellspacing="0" style="font-size: 11pt;">
                        <tr><td width="25%" valign="top">Nomor</td><td width="75%" valign="top">: <?php echo htmlspecialchars($nomor_surat); ?></td></tr>
                        <tr><td valign="top">Sifat</td><td valign="top">: <?php echo htmlspecialchars($template['sifat'] ?? 'Penting'); ?></td></tr>
                        <tr><td valign="top">Lampiran</td><td valign="top">: <?php echo htmlspecialchars($template['lampiran'] ?? '1 (satu) Berkas'); ?></td></tr>
                        <tr><td valign="top">Perihal</td><td valign="top">: <strong>Rekomendasi Pencairan ADD<br>Desa <?php echo htmlspecialchars($desa_nama_txt); ?></strong></td></tr>
                    </table>

                    <div style="margin-top: 10px; font-size: 11pt; line-height: 1.3;">
                        Kepada;<br>
                        Yth. <strong>Bupati Tasikmalaya</strong><br>
                        Melalui :<br>
                        <strong>Kepala DPMD Kabupaten Tasikmalaya</strong><br>
                        Di -<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;<strong>SINGAPARNA</strong>
                    </div>
                </td>
                <td width="42%" valign="top" align="left" style="padding-left: 20px; font-size: 11pt;">
                    <div>Cisayong, &nbsp;<?php echo $tanggal_surat_tampil; ?></div>
                </td>
            </tr>
        </table>

        <!-- Tulisan Arab Tengah atas Tubuh Surat -->
        <div style="text-align: center; margin: 10px 0 12px 0; font-size: 15pt; font-family: 'Traditional Arabic', 'Times New Roman', serif; font-weight: bold; line-height: 1.2;">
            بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ<br>
            السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ
        </div>

        <!-- Tubuh Surat Sesuai Contoh Nyata -->
        <div style="text-align: justify; margin-bottom: 8px; font-size: 10.5pt; line-height: 1.35;">
            <p style="text-indent: 40px; margin: 0 0 6px 0;">
                Berdasarkan permohonan pencairan ADD Desa <?php echo htmlspecialchars($desa_nama_txt); ?> Kecamatan Cisayong Nomor : <?php echo htmlspecialchars($nomor_desa_val); ?> tanggal <?php echo $tgl_surat_desa; ?> Perihal Permohonan Pencairan Alokasi Dana Desa (ADD), berdasarkan hasil verifikasi, dengan ini kami merekomendasikan permohonan Pencairan Alokasi Dana Desa (ADD) dimaksud untuk <?php echo htmlspecialchars($peruntukan_val); ?> sebesar <strong>Rp. <?php echo $nominal_fmt; ?>,- (<?php echo htmlspecialchars($terbilang_txt); ?>)</strong>
            </p>
            <p style="text-indent: 40px; margin: 0 0 4px 0;">
                Adapun kelengkapan pengajuan pencairan sebagaimana terlampir, meliputi :
            </p>
            <table width="100%" cellpadding="1" cellspacing="0" style="margin-left: 20px; font-size: 10.5pt; line-height: 1.25; margin-bottom: 6px;">
                <tr><td width="28" valign="top">1.</td><td>Surat Permohonan Pencairan;</td></tr>
                <tr><td valign="top">2.</td><td>Bukti Pembayaran;</td></tr>
                <tr><td valign="top">3.</td><td>Fakta integritas;</td></tr>
                <tr><td valign="top">4.</td><td>Surat Pernyataan Tanggungjawab Mutlak (SPTJM);</td></tr>
                <tr><td valign="top">5.</td><td>Foto Copy KTP Kepala Desa dan Bendahara Desa;</td></tr>
                <tr><td valign="top">6.</td><td>Foto Copy Rekening dan NPWP Pemerintahan Desa;</td></tr>
                <tr><td valign="top">7.</td><td>Laporan Realisasi APBDesa Bulanan;</td></tr>
                <tr><td valign="top">8.</td><td>Daftar Kehadiran (Absen) Bulan <?php echo htmlspecialchars($bulan_absen_id); ?>;</td></tr>
                <tr><td valign="top">9.</td><td>Print out Rekening koran Dana Cadangan Pilkades;</td></tr>
                <tr><td valign="top">10.</td><td>Lembar Verifikasi Kecamatan.</td></tr>
            </table>
            <p style="text-indent: 40px; margin: 0;">
                Demikian untuk maklum, atas terkabulnya kami ucapkan terimakasih.
            </p>
        </div>

        <!-- Salam Penutup Arab -->
        <div style="text-align: center; margin: 10px 0 16px 0; font-size: 15pt; font-family: 'Traditional Arabic', 'Times New Roman', serif; font-weight: bold;">
            وَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ
        </div>

        <!-- BAGIAN TANDA TANGAN MANUAL (KOSONGKAN RUANG UNTUK TTD & STEMPEL MANUAL BASAH) -->
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 15px;">
            <tr>
                <td width="55%"></td>
                <td width="45%" align="center">
                    <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 75px;">
                        <?php echo htmlspecialchars($template['penandatangan_jabatan'] ?? 'CAMAT CISAYONG'); ?>
                    </div>

                    <!-- Ruang kosong 75px di atas dikosongkan untuk tanda tangan basah & stempel manual -->

                    <div>
                        <strong style="text-decoration: underline; font-size: 11pt; text-transform: uppercase;">
                            <?php echo htmlspecialchars($template['penandatangan_nama'] ?? 'AYI MULYANA HERNIWAN, SE., M.Si.'); ?>
                        </strong><br>
                        <span style="font-size: 9.5pt;"><?php echo htmlspecialchars($template['penandatangan_pangkat'] ?? 'Pembina Tk. I, IV/b'); ?></span><br>
                        <span style="font-size: 9.5pt;">NIP. <?php echo htmlspecialchars($template['penandatangan_nip'] ?? '19721003 199403 1 012'); ?></span>
                    </div>
                </td>
            </tr>
        </table>

    <?php elseif ($jenis_surat === 'Dispensasi Nikah'): ?>
        <!-- MODEL SURAT KETERANGAN WORD (Khusus Dispensasi Nikah) -->
        <div style="text-align: center; margin-bottom: 16px;">
            <div style="font-size: 14pt; font-weight: normal;">
                <?php echo htmlspecialchars($template['judul_surat']); ?>
            </div>
            <div style="font-size: 11.5pt; margin-top: 3px;">NOMOR: <?php echo htmlspecialchars($nomor_surat); ?></div>
        </div>

        <div style="text-align: justify; margin-bottom: 10px;">
            <p style="text-indent: 40px; margin: 0;">
                <?php echo htmlspecialchars($template['pembuka']); ?>
            </p>
        </div>

        <div style="margin-left: 40px; margin-bottom: 12px;">
            <table width="100%" cellpadding="1" cellspacing="0">
                <?php 
                    $calon = $data_form['nama_pemohon'] ?? 'MIFTAH MULFARID';
                    $ttl   = ($data_form['tempat_lahir'] ?? 'Garut') . ', ' . tanggal_indonesia(strtotime($data_form['tanggal_lahir'] ?? '2005-11-23'));
                    $agama = $data_form['agama'] ?? 'Islam';
                    $pekerjaan = $data_form['pekerjaan'] ?? 'Buruh Harian Lepas';
                    $alamat = $data_form['alamat'] ?? 'Kp. Nagrog RT 007 RW 002 Desa Sarimukti Kecamatan Pasirwangi Kabupaten Garut';
                    
                    $pasangan = $data_form['nama_pasangan'] ?? 'EPA SOPIYAH';
                    $ttl_pasangan = ($data_form['tempat_lahir_pasangan'] ?? 'Tasikmalaya') . ', ' . tanggal_indonesia(strtotime($data_form['tanggal_lahir_pasangan'] ?? '2006-07-17'));
                    $agama_pasangan = $data_form['agama_pasangan'] ?? 'Islam';
                    $pekerjaan_pasangan = $data_form['pekerjaan_pasangan'] ?? 'Buruh';
                    $alamat_pasangan = $data_form['alamat_pasangan'] ?? 'Kp. Cidahu Tonggoh RT 001 RW 008 Desa Mekarwangi Kecamatan Cisayong';

                    $alasan = $data_form['alasan_dispensasi'] ?? 'kurang dari 10 hari, pendaftaran pada tanggal 21 Mei 2026 Nomor : 203/13';
                ?>
                <tr><td width="32%" valign="top">Nama</td><td width="3%" valign="top">:</td><td width="65%" valign="top"><strong><?php echo htmlspecialchars(strtoupper($calon)); ?></strong></td></tr>
                <tr><td valign="top">Tempat /Tanggal Lahir</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($ttl); ?></td></tr>
                <tr><td valign="top">Agama</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($agama); ?></td></tr>
                <tr><td valign="top">Pekerjaan</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($pekerjaan); ?></td></tr>
                <tr><td valign="top">Alamat</td><td valign="top">:</td><td valign="top" style="text-align: justify;"><?php echo htmlspecialchars($alamat); ?></td></tr>
            </table>
        </div>

        <div style="text-align: justify; margin-bottom: 10px;">
            <p style="text-indent: 40px; margin: 0;">Untuk melaksanakan nikah dengan seorang Perempuan:</p>
        </div>

        <div style="margin-left: 40px; margin-bottom: 12px;">
            <table width="100%" cellpadding="1" cellspacing="0">
                <tr><td width="32%" valign="top">Nama</td><td width="3%" valign="top">:</td><td width="65%" valign="top"><strong><?php echo htmlspecialchars(strtoupper($pasangan)); ?></strong></td></tr>
                <tr><td valign="top">Tempat/ Tanggal Lahir</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($ttl_pasangan); ?></td></tr>
                <tr><td valign="top">Agama</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($agama_pasangan); ?></td></tr>
                <tr><td valign="top">Pekerjaan</td><td valign="top">:</td><td valign="top"><?php echo htmlspecialchars($pekerjaan_pasangan); ?></td></tr>
                <tr><td valign="top">Alamat</td><td valign="top">:</td><td valign="top" style="text-align: justify;"><?php echo htmlspecialchars($alamat_pasangan); ?></td></tr>
            </table>
        </div>

        <div style="text-align: justify; margin-bottom: 14px;">
            <p style="text-indent: 40px; margin: 0;">
                Sesuai dengan Permohonan Akad Nikah akan di laksanakan pada tanggal <?php echo isset($data_form['tanggal_akad']) ? tanggal_indonesia(strtotime($data_form['tanggal_akad'])) : '07 Juni 2026'; ?> dengan alasan <?php echo htmlspecialchars($alasan); ?>
            </p>
        </div>

        <div style="text-align: justify; margin-bottom: 20px;">
            <p style="text-indent: 40px; margin: 0;">
                <?php echo htmlspecialchars($template['penutup']); ?>
            </p>
        </div>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td width="55%"></td>
                <td width="45%" align="left">
                    <p style="margin: 0 0 10px 0; text-align: center;">Cisayong, <?php echo $tanggal_surat_tampil; ?></p>
                    
                    <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #94a3b8; background-color: #f8fafc;">
                        <tr>
                            <td width="25%" align="center" valign="middle">
                                <img src="<?php echo $logo_src; ?>" width="42" height="46" alt="Logo Tasikmalaya">
                            </td>
                            <td width="75%" valign="middle" style="font-size: 9.5pt; color: #0f172a; line-height: 1.3;">
                                <span style="font-size: 8.5pt; color: #475569;">Ditandatangani secara elektronik oleh:</span><br>
                                <strong><?php echo htmlspecialchars($template['penandatangan_jabatan']); ?></strong><br><br>
                                <strong style="text-decoration: underline;"><?php echo htmlspecialchars($template['penandatangan_nama']); ?></strong><br>
                                <span><?php echo htmlspecialchars($template['penandatangan_pangkat']); ?></span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
    <?php else: ?>
        <!-- MODEL SURAT KETERANGAN DINAMIS WORD -->
        <div style="text-align: center; margin-bottom: 16px;">
            <div style="font-size: 14pt; font-weight: bold; text-decoration: underline;">
                <?php echo htmlspecialchars($template['judul_surat']); ?>
            </div>
            <div style="font-size: 11.5pt; margin-top: 3px;">NOMOR: <?php echo htmlspecialchars($nomor_surat); ?></div>
        </div>

        <div style="text-align: justify; margin-bottom: 10px;">
            <p style="text-indent: 40px; margin: 0;">
                <?php echo htmlspecialchars($template['pembuka']); ?>
            </p>
        </div>

        <div style="margin-left: 40px; margin-bottom: 12px;">
            <table width="100%" cellpadding="1" cellspacing="0">
                <?php foreach ($data_form as $key => $val): 
                    $label = ucwords(str_replace('_', ' ', $key));
                ?>
                    <tr>
                        <td width="32%" valign="top"><?php echo htmlspecialchars($label); ?></td>
                        <td width="3%" valign="top">:</td>
                        <td width="65%" valign="top" style="text-align: justify;"><?php echo nl2br(htmlspecialchars($val)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div style="text-align: justify; margin-bottom: 20px;">
            <p style="text-indent: 40px; margin: 0;">
                <?php echo htmlspecialchars($template['penutup']); ?>
            </p>
        </div>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">
            <tr>
                <td width="55%"></td>
                <td width="45%" align="left">
                    <p style="margin: 0 0 10px 0; text-align: center;">Cisayong, <?php echo $tanggal_surat_tampil; ?></p>
                    
                    <?php if ($is_ttd_dibubuhkan === 1 && $current_ttd && $current_ttd['tipe_ttd'] === 'gambar_upload' && !empty($ttd_img_src)): ?>
                        <!-- Tanda Tangan Bentuk Gambar Murni (Hanya Gambar Saja Sesuai Permintaan) -->
                        <div style="text-align: center; margin: 10px 0;">
                            <img src="<?php echo $ttd_img_src; ?>" style="max-height: 95px; max-width: 250px;" alt="Tanda Tangan Resmi">
                        </div>
                    <?php elseif ($is_ttd_dibubuhkan === 1): ?>
                        <table width="100%" cellpadding="6" cellspacing="0" style="border: 1px solid #475569; background-color: #ffffff;">
                            <tr>
                                <td width="25%" align="center" valign="middle">
                                    <img src="<?php echo $logo_src; ?>" width="42" height="46" alt="Logo Tasikmalaya">
                                </td>
                                <td width="75%" valign="middle" style="font-size: 9.5pt; color: #000000; line-height: 1.3;">
                                    <span style="font-size: 8.5pt; color: #475569;"><?php echo htmlspecialchars($current_ttd['teks_atas'] ?? 'Ditandatangani secara elektronik oleh:'); ?></span><br>
                                    <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($current_ttd['jabatan'] ?? $template['penandatangan_jabatan']); ?></strong><br><br>
                                    <strong style="text-decoration: underline;"><?php echo htmlspecialchars($current_ttd['nama_pejabat'] ?? $template['penandatangan_nama']); ?></strong><br>
                                    <span><?php echo htmlspecialchars($current_ttd['pangkat_golongan'] ?? $template['penandatangan_pangkat']); ?></span>
                                    <?php if (!empty($current_ttd['nip'])): ?>
                                        <br><span style="font-size: 8pt;">NIP: <?php echo htmlspecialchars($current_ttd['nip']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p style="font-weight: bold; margin: 0; text-align: center;"><?php echo htmlspecialchars($current_ttd['jabatan'] ?? $template['penandatangan_jabatan']); ?></p>
                        <table width="100%" cellpadding="0" cellspacing="0"><tr><td height="50" align="center" style="font-size: 9pt; color: #dc2626; font-style: italic;">[ Belum Dibubuhi Tanda Tangan ]</td></tr></table>
                        <p style="font-weight: bold; text-decoration: underline; margin: 0; text-align: center;">
                            <?php echo htmlspecialchars($current_ttd['nama_pejabat'] ?? $template['penandatangan_nama']); ?>
                        </p>
                        <p style="font-weight: bold; margin: 0; text-align: center;">
                            <?php echo htmlspecialchars($current_ttd['pangkat_golongan'] ?? $template['penandatangan_pangkat']); ?>
                        </p>
                        <?php if (!empty($current_ttd['nip'])): ?>
                            <p style="font-weight: bold; margin: 0; text-align: center;">
                                NIP: <?php echo htmlspecialchars($current_ttd['nip']); ?>
                            </p>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <?php if ($is_ttd_dibubuhkan === 1): ?>
        <!-- FOOTER BSrE RESMI WORD -->
        <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #cccccc; padding-top: 10px;">
            <tr>
                <td width="15%" align="center" valign="top">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=https://bsre.bssn.go.id/verifikasi/<?php echo urlencode($nomor_surat); ?>" width="55" height="55" alt="QR Code Verifikasi">
                </td>
                <td width="85%" align="center" valign="middle" style="font-size: 9.5pt; color: #334155; padding-top: 5px;">
                    Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik<br>
                    yang diterbitkan oleh Balai Besar Sertifikasi Elektronik (BSrE), Badan Siber dan Sandi Negara (BSSN).
                </td>
            </tr>
        </table>
    <?php endif; ?>

</div>

</body>
</html>
