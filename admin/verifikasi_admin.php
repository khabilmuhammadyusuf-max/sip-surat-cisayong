<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_role  = $_SESSION['role'];
$user_nama  = $_SESSION['nama'] ?? 'Pejabat Kecamatan';
$user_nip   = $_SESSION['nip'] ?? '-';
$role_label = get_role_label($user_role);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada ID di GET, cari 1 berkas yang sedang menunggu tindakan meja role ini
if (!$id) {
    $stmtAllActive = $pdo->query("SELECT * FROM `pengajuan` WHERE status IN ('menunggu', 'menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat') ORDER BY waktu_pengajuan ASC");
    while ($cand = $stmtAllActive->fetch()) {
        if (can_role_verify_submission($user_role, $cand)) {
            $id = (int)$cand['id'];
            break;
        }
    }
}

// -----------------------------------------------------------------------------
// PROSES AKSI POST BERJENJANG (PTSP -> KASI -> SEKCAM -> CAMAT / TOLAK)
// -----------------------------------------------------------------------------
if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmtP = $pdo->prepare("SELECT * FROM `pengajuan` WHERE id = ?");
    $stmtP->execute([$id]);
    $pengajuan = $stmtP->fetch();

    if ($pengajuan) {
        $action = $_POST['action'] ?? '';
        $reg_number = 'REG-' . date('Ymd', strtotime($pengajuan['waktu_pengajuan'])) . '-' . str_pad($pengajuan['id'], 3, '0', STR_PAD_LEFT);

        if ($action === 'tolak') {
            $alasan = trim($_POST['alasan_tolak'] ?? 'Mohon lengkapi dan upload ulang dokumen persyaratan.');
            $ditolak_oleh = $user_nama . ' (' . $role_label . ')';
            $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET status = 'ditolak', catatan_tolak = ?, ditolak_oleh = ?, waktu_verifikasi = NOW() WHERE id = ?");
            $stmtUpd->execute([$alasan, $ditolak_oleh, $id]);
            header("Location: " . BASE_URL . "admin/dashboard_admin.php?msg=" . urlencode("Berkas $reg_number ditolak. Sistem telah mengirim notifikasi ke Pemohon untuk Upload Ulang."));
            exit;

        } elseif ($action === 'validasi_ptsp') {
            // Tahap 1: Front Office (PTSP) -> Teruskan ke Kasi Terkait
            $kasi_tujuan = trim($_POST['kasi_tujuan'] ?? get_default_kasi_for_surat($pengajuan['jenis_surat']));
            $catatan_ptsp = trim($_POST['catatan_ptsp'] ?? 'Berkas persyaratan lengkap dan valid.');
            $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET status = 'menunggu_kasi', kasi_tujuan = ?, ptsp_oleh = ?, waktu_validasi_ptsp = NOW(), catatan_ptsp = ?, catatan_tolak = NULL WHERE id = ?");
            $stmtUpd->execute([$kasi_tujuan, $user_nama, $catatan_ptsp, $id]);
            header("Location: " . BASE_URL . "admin/dashboard_admin.php?msg=" . urlencode("Berkas $reg_number dinyatakan VALID oleh PTSP dan diteruskan ke " . get_role_label($kasi_tujuan) . "!"));
            exit;

        } elseif ($action === 'paraf_kasi' || $action === 'approve_kasi') {
            // Tahap 2: Kasi Terkait -> Periksa Substansi & Redaksi + Persetujuan Akhir -> Langsung Terbitkan Surat (Status Disetujui)
            $existing_form = json_decode($pengajuan['data_form'], true) ?? [];
            if (isset($_POST['substansi']) && is_array($_POST['substansi'])) {
                foreach ($_POST['substansi'] as $k => $v) {
                    $existing_form[$k] = trim((string)$v);
                }
            }
            $new_data_form = json_encode($existing_form);
            $redaksi_pembuka = trim($_POST['redaksi_kasi_pembuka'] ?? '');
            $redaksi_penutup = trim($_POST['redaksi_kasi_penutup'] ?? '');
            $catatan_kasi    = trim($_POST['catatan_kasi'] ?? 'Substansi dan redaksi surat telah diperiksa, disetujui, dan diterbitkan.');
            $jabatan_kasi    = get_role_label($pengajuan['kasi_tujuan'] ?: $user_role);

            // Generate nomor surat otomatis
            $stmtTmpl = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
            $stmtTmpl->execute([$pengajuan['jenis_surat']]);
            $template = $stmtTmpl->fetch();

            $tahun = date('Y');
            $stmtMax = $pdo->prepare("SELECT MAX(nomor_urut) FROM `pengajuan` WHERE jenis_surat = ? AND YEAR(waktu_verifikasi) = ?");
            $stmtMax->execute([$pengajuan['jenis_surat'], $tahun]);
            $max_urut = (int)$stmtMax->fetchColumn();
            $next_urut = $max_urut + 1;

            $kode_klas = $template['kode_klasifikasi'] ?? '400';
            $fmt_nomor = $template['format_nomor'] ?? '[KODE]/[URUT]/Kec/[TAHUN]';
            $nomor_surat = str_replace(
                ['[KODE]', '[URUT]', '[TAHUN]'],
                [$kode_klas, $next_urut, $tahun],
                $fmt_nomor
            );

            $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET status = 'disetujui', nomor_surat_generated = ?, nomor_urut = ?, data_form = ?, redaksi_kasi_pembuka = ?, redaksi_kasi_penutup = ?, paraf_kasi_oleh = ?, paraf_kasi_nip = ?, paraf_kasi_jabatan = ?, waktu_paraf_kasi = NOW(), waktu_verifikasi = NOW(), catatan_kasi = ? WHERE id = ?");
            $stmtUpd->execute([
                $nomor_surat,
                $next_urut,
                $new_data_form,
                $redaksi_pembuka ?: null,
                $redaksi_penutup ?: null,
                $user_nama,
                $user_nip,
                $jabatan_kasi,
                $catatan_kasi,
                $id
            ]);
            header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&generated=1");
            exit;

        } elseif ($action === 'paraf_sekcam') {
            // Tahap 3: Sekretaris Camat -> Paraf Koordinasi -> Teruskan ke Camat
            $catatan_sekcam = trim($_POST['catatan_sekcam'] ?? 'Telah dikoordinasikan dan siap untuk TTE Camat.');
            $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET status = 'menunggu_camat', paraf_sekcam_oleh = ?, paraf_sekcam_nip = ?, waktu_paraf_sekcam = NOW(), catatan_sekcam = ? WHERE id = ?");
            $stmtUpd->execute([$user_nama, $user_nip, $catatan_sekcam, $id]);
            header("Location: " . BASE_URL . "admin/dashboard_admin.php?msg=" . urlencode("Paraf Koordinasi Sekcam pada berkas $reg_number berhasil dibubuhkan dan diteruskan ke Camat!"));
            exit;

        } elseif ($action === 'approve_tte_camat') {
            // Tahap 4: Camat -> Approve & TTE (QR Code) -> Sistem Generate PDF
            $stmtTmpl = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
            $stmtTmpl->execute([$pengajuan['jenis_surat']]);
            $template = $stmtTmpl->fetch();

            $tahun = date('Y');
            $stmtMax = $pdo->prepare("SELECT MAX(nomor_urut) FROM `pengajuan` WHERE jenis_surat = ? AND YEAR(waktu_verifikasi) = ?");
            $stmtMax->execute([$pengajuan['jenis_surat'], $tahun]);
            $max_urut = (int)$stmtMax->fetchColumn();
            $next_urut = $max_urut + 1;

            $kode_klas = $template['kode_klasifikasi'] ?? '400';
            $fmt_nomor = $template['format_nomor'] ?? '[KODE]/[URUT]/Kec/[TAHUN]';
            $nomor_surat = str_replace(
                ['[KODE]', '[URUT]', '[TAHUN]'],
                [$kode_klas, $next_urut, $tahun],
                $fmt_nomor
            );

            $tte_kode = 'TTE-CSY-' . date('YmdHis') . '-' . strtoupper(substr(md5($id . $nomor_surat . time()), 0, 8));
            $camat_nama = ($user_role === 'camat') ? $user_nama : ($template['penandatangan_nama'] ?? 'AYI MULYANA HERNIWAN, SE., M.Si.');
            $camat_nip  = ($user_role === 'camat' && !empty($user_nip)) ? $user_nip : ($template['penandatangan_nip'] ?? '19721003 199403 1 012');

            $stmtSetujui = $pdo->prepare("UPDATE `pengajuan` SET status = 'disetujui', nomor_surat_generated = ?, nomor_urut = ?, tte_camat_oleh = ?, tte_camat_nip = ?, tte_kode = ?, waktu_verifikasi = NOW(), is_ttd_dibubuhkan = 0 WHERE id = ?");
            $stmtSetujui->execute([$nomor_surat, $next_urut, $camat_nama, $camat_nip, $tte_kode, $id]);

            header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&generated=1");
            exit;

        } elseif ($action === 'bubuhkan_ttd') {
            $ttd_id = (int)($_POST['ttd_id'] ?? 0);
            if ($ttd_id <= 0) {
                $ttd_id = (int)$pdo->query("SELECT id FROM `tanda_tangan` WHERE is_default = 1 LIMIT 1")->fetchColumn();
            }
            $stmtTtd = $pdo->prepare("UPDATE `pengajuan` SET is_ttd_dibubuhkan = 1, ttd_id = ?, waktu_ttd_dibubuhkan = NOW(), ttd_dibubuhkan_oleh = ? WHERE id = ?");
            $stmtTtd->execute([$ttd_id, $user_nama . ' (' . $role_label . ')', $id]);
            header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&signed=1");
            exit;

        } elseif ($action === 'tarik_ttd') {
            $stmtTtd = $pdo->prepare("UPDATE `pengajuan` SET is_ttd_dibubuhkan = 0, waktu_ttd_dibubuhkan = NULL, ttd_dibubuhkan_oleh = NULL WHERE id = ?");
            $stmtTtd->execute([$id]);
            header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&unsigned=1");
            exit;
        }
    }
}

$page_title = "Meja Verifikasi Berjenjang Kecamatan";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!$id) {
    // Tampilkan daftar antrean dan dokumen siap verifikasi agar Camat/pejabat tidak jalan buntu
    $stmtRecent = $pdo->query("SELECT p.*, u.nama, u.username, u.instansi_desa, u.phone FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id ORDER BY p.waktu_pengajuan DESC LIMIT 15");
    $recent_submissions = $stmtRecent->fetchAll();
    ?>
    <main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-6 overflow-y-auto">
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-700 rounded-2xl flex items-center justify-center shrink-0 border border-emerald-100">
                    <span class="material-icons-outlined text-3xl">task_alt</span>
                </div>
                <div>
                    <h2 class="font-extrabold text-2xl text-slate-900 tracking-tight">Meja Kerja <?php echo htmlspecialchars($role_label); ?></h2>
                    <p class="text-xs text-slate-500 mt-1">Saat ini tidak ada berkas yang menunggu tindakan khusus di meja Anda. Seluruh antrean permohonan terkini dapat dipantau di bawah ini.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?php echo BASE_URL; ?>admin/dashboard_admin.php" class="px-5 py-3 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center space-x-2 transition-all">
                    <span class="material-icons-outlined text-base">toc</span>
                    <span>Buka Dasbor Antrean</span>
                </a>
                <a href="<?php echo BASE_URL; ?>admin/admin_riwayat.php" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all">
                    <span>Arsip Surat</span>
                </a>
                <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all">
                    <span>Pengaturan TTD</span>
                </a>
            </div>
        </div>

        <!-- Tabel Monitoring Permohonan Terkini -->
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="material-icons-outlined text-blue-900 text-xl">folder_shared</span>
                    <h3 class="font-extrabold text-sm text-slate-900 uppercase tracking-wider">Antrean Permohonan Terkini di Sistem</h3>
                </div>
                <span class="text-xs text-slate-400 font-bold"><?php echo count($recent_submissions); ?> Dokumen</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[850px] text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-4 whitespace-nowrap">Nomor / Jenis Surat</th>
                            <th class="px-6 py-4 whitespace-nowrap">Pemohon</th>
                            <th class="px-6 py-4 whitespace-nowrap">Tahapan Saat Ini</th>
                            <th class="px-6 py-4 text-right whitespace-nowrap">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        <?php if (empty($recent_submissions)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-xs font-bold">Belum ada pengajuan surat dalam sistem.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_submissions as $r): 
                                $r_status = ($r['status'] === 'menunggu' || empty($r['status'])) ? 'menunggu_ptsp' : $r['status'];
                                $r_info = get_status_info($r_status, $r['kasi_tujuan'] ?? '');
                                $r_can = can_role_verify_submission($user_role, $r);
                                $r_reg = $r['nomor_surat_generated'] ?? ('REG-' . date('Ymd', strtotime($r['waktu_pengajuan'])) . '-' . str_pad($r['id'], 3, '0', STR_PAD_LEFT));
                            ?>
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($r_reg); ?></p>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 bg-blue-50 text-blue-900 border border-blue-200 rounded text-[11px] font-bold">
                                            <?php echo htmlspecialchars($r['jenis_surat']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-bold text-slate-800"><?php echo htmlspecialchars($r['nama']); ?></p>
                                        <p class="text-[11px] text-slate-400"><?php echo date('d M Y, H:i', strtotime($r['waktu_pengajuan'])); ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center space-x-1 px-3 py-1 rounded-full text-xs font-bold border <?php echo $r_info['badge_class']; ?>">
                                            <span class="material-icons-outlined text-xs"><?php echo $r_info['icon']; ?></span>
                                            <span><?php echo htmlspecialchars($r_info['label']); ?></span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <?php if ($r_can): ?>
                                            <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php?id=<?php echo $r['id']; ?>" class="inline-flex items-center space-x-1 px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-extrabold shadow-sm transition-all">
                                                <span class="material-icons-outlined text-xs">fact_check</span>
                                                <span>Proses di Meja Anda</span>
                                            </a>
                                        <?php elseif ($r_status === 'disetujui'): ?>
                                            <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $r['id']; ?>" class="inline-flex items-center space-x-1 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all">
                                                <span class="material-icons-outlined text-xs">picture_as_pdf</span>
                                                <span>Lihat PDF & TTE</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php?id=<?php echo $r['id']; ?>" class="inline-flex items-center space-x-1 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all">
                                                <span class="material-icons-outlined text-xs">visibility</span>
                                                <span>Lihat Detail Alur</span>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <?php
    include __DIR__ . '/../components/footer.php';
    exit;
}

// Fetch pengajuan & pemohon
$stmtP = $pdo->prepare("SELECT p.*, u.nama, u.username, u.instansi_desa, u.role as pemohon_role FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id WHERE p.id = ?");
$stmtP->execute([$id]);
$pengajuan = $stmtP->fetch();

if (!$pengajuan) {
    echo '<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8"><div class="bg-white p-8 rounded-3xl border border-slate-200 text-center"><h3 class="font-bold text-xl text-slate-800">Data pengajuan tidak ditemukan.</h3><a href="' . BASE_URL . 'admin/dashboard_admin.php" class="inline-block mt-4 px-6 py-3 bg-blue-900 text-white rounded-xl text-xs font-bold">Kembali ke Dasbor</a></div></main>';
    include __DIR__ . '/../components/footer.php';
    exit;
}

$status_norm = ($pengajuan['status'] === 'menunggu' || empty($pengajuan['status'])) ? 'menunggu_ptsp' : $pengajuan['status'];
$data_form = json_decode($pengajuan['data_form'], true) ?? [];
$reg_number = 'REG-' . date('Ymd', strtotime($pengajuan['waktu_pengajuan'])) . '-' . str_pad($pengajuan['id'], 3, '0', STR_PAD_LEFT);
$kasi_tujuan = $pengajuan['kasi_tujuan'] ?: get_default_kasi_for_surat($pengajuan['jenis_surat']);
$sInfo = get_status_info($status_norm, $kasi_tujuan);
$can_verify = can_role_verify_submission($user_role, $pengajuan);

// Fetch template untuk redaksi default
$stmtTmpl = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
$stmtTmpl->execute([$pengajuan['jenis_surat']]);
$template = $stmtTmpl->fetch();

$redaksi_pembuka_aktif = $pengajuan['redaksi_kasi_pembuka'] ?? ($template['pembuka'] ?? '');
$redaksi_penutup_aktif = $pengajuan['redaksi_kasi_penutup'] ?? ($template['penutup'] ?? '');

// Fetch Lampiran
$stmtL = $pdo->prepare("SELECT * FROM `lampiran` WHERE pengajuan_id = ?");
$stmtL->execute([$id]);
$lampiran_list = $stmtL->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">

    <!-- Visual Stepper Alur Hierarki Kecamatan -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-900 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                    Alur Berjenjang Kecamatan Cisayong
                </span>
                <h2 class="text-xl font-extrabold text-slate-900 mt-2">
                    <?php echo htmlspecialchars($pengajuan['jenis_surat']); ?> — <?php echo htmlspecialchars($pengajuan['nama']); ?>
                </h2>
                <p class="text-xs text-slate-500">
                    <?php echo $reg_number; ?> • Diajukan <?php echo date('d M Y, H:i', strtotime($pengajuan['waktu_pengajuan'])); ?>
                    <?php echo !empty($pengajuan['is_revisi']) ? ' • <span class="text-amber-700 font-bold">Hasil Upload Ulang Pemohon</span>' : ''; ?>
                </p>
            </div>
            <span class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-2xl text-xs font-extrabold border <?php echo $sInfo['badge_class']; ?>">
                <span class="material-icons-outlined text-sm"><?php echo $sInfo['icon']; ?></span>
                <span><?php echo htmlspecialchars($sInfo['label']); ?></span>
            </span>
        </div>

        <!-- 3 Stage Stepper Grid (Alur Terpadu: Layanan PTSP -> Kasi Terkait -> Selesai) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
            <!-- Step 1: PTSP -->
            <div class="p-3.5 rounded-2xl border <?php echo $status_norm === 'menunggu_ptsp' ? 'border-amber-400 bg-amber-50/60 ring-2 ring-amber-200' : (!empty($pengajuan['waktu_validasi_ptsp']) ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50'); ?>">
                <div class="flex items-center justify-between text-xs font-extrabold">
                    <span class="text-slate-800">1. Front Office (Layanan / PTSP)</span>
                    <?php if (!empty($pengajuan['waktu_validasi_ptsp'])): ?>
                        <span class="material-icons-outlined text-emerald-600 text-base">check_circle</span>
                    <?php elseif ($status_norm === 'menunggu_ptsp'): ?>
                        <span class="material-icons-outlined text-amber-600 text-base">pending</span>
                    <?php endif; ?>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                    <?php echo !empty($pengajuan['waktu_validasi_ptsp']) ? 'Valid oleh ' . htmlspecialchars($pengajuan['ptsp_oleh']) : 'Periksa Kelengkapan Syarat'; ?>
                </p>
            </div>

            <!-- Step 2: Kasi Terkait -->
            <div class="p-3.5 rounded-2xl border <?php echo $status_norm === 'menunggu_kasi' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-200' : (!empty($pengajuan['waktu_paraf_kasi']) ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-slate-50'); ?>">
                <div class="flex items-center justify-between text-xs font-extrabold">
                    <span class="text-slate-800">2. <?php echo htmlspecialchars(get_role_label($kasi_tujuan)); ?></span>
                    <?php if (!empty($pengajuan['waktu_paraf_kasi'])): ?>
                        <span class="material-icons-outlined text-emerald-600 text-base">check_circle</span>
                    <?php elseif ($status_norm === 'menunggu_kasi'): ?>
                        <span class="material-icons-outlined text-blue-700 text-base">rate_review</span>
                    <?php endif; ?>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                    <?php echo !empty($pengajuan['waktu_paraf_kasi']) ? 'Disetujui: ' . htmlspecialchars($pengajuan['paraf_kasi_oleh']) : 'Pemeriksaan & Persetujuan Akhir'; ?>
                </p>
            </div>

            <!-- Step 3: Selesai -->
            <div class="p-3.5 rounded-2xl border <?php echo $status_norm === 'disetujui' ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200' : 'border-slate-200 bg-slate-50'; ?>">
                <div class="flex items-center justify-between text-xs font-extrabold">
                    <span class="text-slate-800">3. Selesai (Surat Siap Unduh)</span>
                    <?php if ($status_norm === 'disetujui'): ?>
                        <span class="material-icons-outlined text-emerald-600 text-base">task_alt</span>
                    <?php endif; ?>
                </div>
                <p class="text-[11px] text-slate-500 mt-1">
                    <?php echo $status_norm === 'disetujui' ? 'Surat Telah Diterbitkan' : 'Menunggu Persetujuan Kasi'; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- PANEL TINDAKAN SESUAI TAHAPAN & ROLE PEJABAT -->
    <?php if ($can_verify): ?>
        <?php if ($status_norm === 'menunggu_ptsp'): ?>
            <!-- MEJA 1: FRONT OFFICE (PTSP) KECAMATAN -->
            <form method="POST" action="verifikasi_admin.php?id=<?php echo $id; ?>" class="bg-amber-50/70 p-6 rounded-3xl border-2 border-amber-300 shadow-lg space-y-5">
                <input type="hidden" name="action" value="validasi_ptsp">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold uppercase tracking-wider bg-amber-200 text-amber-950 px-3 py-1 rounded-full">
                            <span class="material-icons-outlined text-xs">support_agent</span>
                            <span>Meja 1 • Front Office (PTSP) Kecamatan</span>
                        </span>
                        <h3 class="text-lg font-extrabold text-slate-900 mt-1">Pemeriksaan Kelengkapan Persyaratan & Disposisi Kasi Terkait</h3>
                        <p class="text-xs text-slate-600">Periksa dokumen yang diunggah pemohon di bawah. Jika tidak sesuai, klik <strong>Tolak Berkas</strong> (pemohon otomatis mendapat notifikasi upload ulang). Jika lengkap, teruskan ke <strong>Kasi Terkait</strong>.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white p-5 rounded-2xl border border-amber-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Teruskan ke Kasi Terkait</label>
                        <select name="kasi_tujuan" class="w-full px-4 py-3 rounded-xl border border-slate-300 font-bold text-sm text-blue-900 bg-slate-50 focus:outline-none focus:border-blue-900">
                            <?php foreach (get_kasi_list() as $k_role => $k_desc): ?>
                                <option value="<?php echo htmlspecialchars($k_role); ?>" <?php echo $kasi_tujuan === $k_role ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($k_desc); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Catatan Pengantar PTSP ke Kasi</label>
                        <input type="text" name="catatan_ptsp" value="Berkas persyaratan administrasi telah diperiksa lengkap dan valid." class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-blue-900">
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" id="btn-tolak" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-6 py-3.5 rounded-2xl shadow-lg shadow-rose-600/20 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">assignment_return</span>
                        <span>Tolak Berkas (Minta Upload Ulang)</span>
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-8 py-3.5 rounded-2xl shadow-lg shadow-emerald-600/20 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">forward_to_inbox</span>
                        <span>Valid → Teruskan ke Kasi Terkait</span>
                    </button>
                </div>
            </form>

        <?php elseif ($status_norm === 'menunggu_kasi'): ?>
            <!-- MEJA 2: KASI TERKAIT (PERIKSA SUBSTANSI & REDAKSI + PARAF DIGITAL) -->
            <form method="POST" action="verifikasi_admin.php?id=<?php echo $id; ?>" class="bg-blue-50/70 p-6 rounded-3xl border-2 border-blue-300 shadow-lg space-y-6">
                <input type="hidden" name="action" value="paraf_kasi">
                <div>
                    <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold uppercase tracking-wider bg-blue-200 text-blue-950 px-3 py-1 rounded-full">
                        <span class="material-icons-outlined text-xs">rate_review</span>
                        <span>Meja 2 • <?php echo htmlspecialchars(get_role_label($kasi_tujuan)); ?></span>
                    </span>
                    <h3 class="text-lg font-extrabold text-slate-900 mt-1">Pemeriksaan Substansi, Redaksi Surat & Paraf Digital Kasi</h3>
                    <p class="text-xs text-slate-600">Anda dapat mengoreksi data substansi pemohon atau menyesuaikan kalimat redaksi pembuka/penutup surat di bawah ini sebelum membubuhkan <strong>Paraf Digital Kasi</strong> untuk diteruskan ke <strong>Sekretaris Camat (Sekcam)</strong>.</p>
                </div>

                <!-- Editor Substansi & Redaksi Surat oleh Kasi -->
                <div class="bg-white p-6 rounded-2xl border border-blue-200 space-y-5">
                    <h4 class="font-extrabold text-sm text-blue-900 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-base">edit_note</span>
                        <span>A. Koreksi Substansi Isian Pemohon (Jika Diperlukan)</span>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($data_form as $key => $val): 
                            $label = ucwords(str_replace('_', ' ', $key));
                        ?>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1"><?php echo htmlspecialchars($label); ?></label>
                                <input type="text" name="substansi[<?php echo htmlspecialchars($key); ?>]" value="<?php echo htmlspecialchars((string)$val); ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-slate-50 focus:bg-white focus:border-blue-900 focus:outline-none">
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h4 class="font-extrabold text-sm text-blue-900 uppercase tracking-wider flex items-center space-x-2 border-b border-slate-100 pb-2 pt-2">
                        <span class="material-icons-outlined text-base">article</span>
                        <span>B. Pemeriksaan Redaksi Surat (Pembuka & Penutup)</span>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Redaksi Kalimat Pembuka Surat</label>
                            <textarea name="redaksi_kasi_pembuka" rows="4" class="w-full p-3.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:border-blue-900 focus:outline-none"><?php echo htmlspecialchars($redaksi_pembuka_aktif); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Redaksi Kalimat Penutup Surat</label>
                            <textarea name="redaksi_kasi_penutup" rows="4" class="w-full p-3.5 rounded-xl border border-slate-200 text-xs text-slate-800 bg-slate-50 focus:bg-white focus:border-blue-900 focus:outline-none"><?php echo htmlspecialchars($redaksi_penutup_aktif); ?></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Catatan Persetujuan Kasi / Keterangan Penerbitan</label>
                        <input type="text" name="catatan_kasi" value="Substansi dan redaksi surat telah diperiksa, memenuhi ketentuan, dan disetujui untuk diterbitkan." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-800 bg-slate-50">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" id="btn-tolak" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-6 py-3.5 rounded-2xl shadow-lg shadow-rose-600/20 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">cancel</span>
                        <span>Tolak Berkas</span>
                    </button>
                    <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold px-8 py-3.5 rounded-2xl shadow-lg shadow-emerald-700/25 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">task_alt</span>
                        <span>Setujui & Terbitkan Surat Resmi</span>
                    </button>
                </div>
            </form>

        <?php elseif ($status_norm === 'menunggu_sekcam'): ?>
            <!-- MEJA 3: SEKRETARIS CAMAT (PARAF KOORDINASI) -->
            <form method="POST" action="verifikasi_admin.php?id=<?php echo $id; ?>" class="bg-indigo-50/70 p-6 rounded-3xl border-2 border-indigo-300 shadow-lg space-y-5">
                <input type="hidden" name="action" value="paraf_sekcam">
                <div>
                    <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold uppercase tracking-wider bg-indigo-200 text-indigo-950 px-3 py-1 rounded-full">
                        <span class="material-icons-outlined text-xs">fact_check</span>
                        <span>Meja 3 • Sekretaris Camat (Sekcam)</span>
                    </span>
                    <h3 class="text-lg font-extrabold text-slate-900 mt-1">Pemeriksaan Koordinasi & Paraf Koordinasi Sekcam</h3>
                    <p class="text-xs text-slate-600">Berkas ini telah divalidasi oleh PTSP (<strong><?php echo htmlspecialchars($pengajuan['ptsp_oleh'] ?? '-'); ?></strong>) dan telah diberikan Paraf Substansi oleh <strong><?php echo htmlspecialchars($pengajuan['paraf_kasi_oleh'] ?? get_role_label($kasi_tujuan)); ?></strong>.</p>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-indigo-200 space-y-3 text-xs">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="font-bold text-slate-500 block uppercase text-[10px]">Redaksi Pembuka (Hasil Pemeriksaan Kasi):</span>
                            <p class="text-slate-800 mt-1"><?php echo nl2br(htmlspecialchars($redaksi_pembuka_aktif)); ?></p>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                            <span class="font-bold text-slate-500 block uppercase text-[10px]">Redaksi Penutup (Hasil Pemeriksaan Kasi):</span>
                            <p class="text-slate-800 mt-1"><?php echo nl2br(htmlspecialchars($redaksi_penutup_aktif)); ?></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Catatan Paraf Koordinasi Sekcam ke Camat</label>
                        <input type="text" name="catatan_sekcam" value="Telah dilakukan paraf koordinasi administrasi dan siap diajukan untuk TTE Camat." class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm text-slate-800 bg-slate-50">
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="button" id="btn-tolak" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-6 py-3.5 rounded-2xl shadow-lg shadow-rose-600/20 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">cancel</span>
                        <span>Tolak / Kembalikan</span>
                    </button>
                    <button type="submit" class="bg-indigo-800 hover:bg-indigo-900 text-white font-extrabold px-8 py-3.5 rounded-2xl shadow-lg shadow-indigo-800/20 flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-lg">how_to_reg</span>
                        <span>Setuju (Paraf Koordinasi) → Teruskan ke Camat</span>
                    </button>
                </div>
            </form>

        <?php elseif ($status_norm === 'menunggu_camat'): ?>
            <!-- MEJA 4: CAMAT (APPROVE & TTE QR CODE -> SISTEM GENERATE PDF) -->
            <form id="form-approve-camat" method="POST" action="verifikasi_admin.php?id=<?php echo $id; ?>" class="bg-emerald-50/80 p-6 rounded-3xl border-2 border-emerald-400 shadow-lg space-y-5">
                <input type="hidden" name="action" value="approve_tte_camat">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center space-x-1 text-[11px] font-extrabold uppercase tracking-wider bg-emerald-200 text-emerald-950 px-3 py-1 rounded-full">
                            <span class="material-icons-outlined text-xs">qr_code_2</span>
                            <span>Meja 4 • Otorisasi Akhir Camat Cisayong</span>
                        </span>
                        <h3 class="text-lg font-extrabold text-slate-900 mt-1">Approve & Tanda Tangan Elektronik (TTE QR Code)</h3>
                        <p class="text-xs text-slate-600">Seluruh paraf berjenjang telah lengkap: Validasi PTSP (<strong><?php echo htmlspecialchars($pengajuan['ptsp_oleh'] ?? 'Valid'); ?></strong>) → Paraf Kasi (<strong><?php echo htmlspecialchars($pengajuan['paraf_kasi_oleh'] ?? 'Disetujui'); ?></strong>) → Paraf Koordinasi Sekcam (<strong><?php echo htmlspecialchars($pengajuan['paraf_sekcam_oleh'] ?? 'Disetujui'); ?></strong>).</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <button type="button" id="btn-tolak" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-5 py-3.5 rounded-2xl shadow-lg shadow-rose-600/20 flex items-center space-x-2 transition-all">
                            <span class="material-icons-outlined text-lg">cancel</span>
                            <span>Tolak</span>
                        </button>
                        <button type="submit" class="bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold px-8 py-4 rounded-2xl shadow-xl shadow-emerald-700/25 hover:scale-105 flex items-center space-x-2 transition-all">
                            <span class="material-icons-outlined text-xl">qr_code_2</span>
                            <span>Approve & TTE (QR Code) → Generate PDF</span>
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($status_norm === 'disetujui'): ?>
        <?php
            $user_phone = $pengajuan['phone'] ?? '';
            if (empty($user_phone) && !empty($data_form)) {
                $user_phone = $data_form['no_hp'] ?? ($data_form['telepon'] ?? ($data_form['nomor_hp'] ?? ($data_form['kontak'] ?? ($data_form['wa'] ?? ''))));
            }
            $wa_url = get_whatsapp_notification_url($user_phone, $pengajuan['nama'], $pengajuan['jenis_surat'], $pengajuan['nomor_surat_generated'] ?? $reg_number, $id, 'disetujui');
        ?>
        <div class="bg-emerald-50 border-2 border-emerald-300 p-6 rounded-3xl shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-1">
                <span class="inline-flex items-center space-x-1.5 px-3 py-1 bg-emerald-200 text-emerald-950 rounded-full text-xs font-bold uppercase tracking-wider">
                    <span class="material-icons-outlined text-sm">verified</span>
                    <span>Surat Telah Selesai Diproses & Diterbitkan</span>
                </span>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1">Naskah Dinas Ber-TTE (QR Code BSrE)</h3>
                <p class="text-xs text-slate-600">Surat ini telah disetujui & ditandatangani secara elektronik. Anda dapat melihat lembar cetak atau mengirimkan notifikasi WhatsApp ke pemohon.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <?php if (!empty($wa_url)): ?>
                    <a href="<?php echo htmlspecialchars($wa_url); ?>" target="_blank" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center space-x-2 transition-all">
                        <span class="material-icons-outlined text-base">chat</span>
                        <span>Kirim Notifikasi WhatsApp</span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $id; ?>" class="px-6 py-3 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-extrabold shadow-md flex items-center space-x-2 transition-all">
                    <span class="material-icons-outlined text-base">picture_as_pdf</span>
                    <span>Buka Lembar Cetak & Pengaturan TTD</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Side-by-Side Layout Grid (Data Formulir & Dokumen Lampiran) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Left Column: Form User Data & Riwayat Catatan Paraf -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center space-x-3">
                    <span class="material-icons-outlined text-blue-900 text-2xl">speaker_notes</span>
                    <h3 class="font-extrabold text-lg text-slate-900">Data Isian Formulir Pemohon</h3>
                </div>
                <div class="p-6 space-y-4 divide-y divide-slate-100">
                    <?php if (!empty($data_form)): ?>
                        <?php foreach ($data_form as $key => $value): 
                            $label = ucwords(str_replace('_', ' ', $key));
                        ?>
                            <div class="pt-3 first:pt-0">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block"><?php echo htmlspecialchars($label); ?></label>
                                <p class="font-bold text-slate-800 text-sm mt-0.5"><?php echo nl2br(htmlspecialchars((string)$value)); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-slate-500 text-sm">Tidak ada rincian form khusus.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Log Catatan Berjenjang -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 bg-slate-50 border-b border-slate-100 flex items-center space-x-2">
                    <span class="material-icons-outlined text-blue-900 text-xl">history_edu</span>
                    <h3 class="font-extrabold text-sm text-slate-900 uppercase tracking-wider">Log Paraf & Catatan Berjenjang</h3>
                </div>
                <div class="p-5 space-y-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="font-bold text-slate-800">1. Front Office (PTSP):</p>
                        <p class="text-slate-600 mt-0.5"><?php echo !empty($pengajuan['waktu_validasi_ptsp']) ? '✅ Valid oleh ' . htmlspecialchars($pengajuan['ptsp_oleh']) . ' (' . date('d M Y H:i', strtotime($pengajuan['waktu_validasi_ptsp'])) . ') — "' . htmlspecialchars($pengajuan['catatan_ptsp'] ?? '') . '"' : '⏳ Menunggu validasi PTSP'; ?></p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="font-bold text-slate-800">2. <?php echo htmlspecialchars(get_role_label($kasi_tujuan)); ?>:</p>
                        <p class="text-slate-600 mt-0.5"><?php echo !empty($pengajuan['waktu_paraf_kasi']) ? '✅ Paraf Digital oleh ' . htmlspecialchars($pengajuan['paraf_kasi_oleh']) . ' (' . date('d M Y H:i', strtotime($pengajuan['waktu_paraf_kasi'])) . ') — "' . htmlspecialchars($pengajuan['catatan_kasi'] ?? '') . '"' : '⏳ Menunggu pemeriksaan substansi & paraf Kasi'; ?></p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="font-bold text-slate-800">3. Sekretaris Camat (Sekcam):</p>
                        <p class="text-slate-600 mt-0.5"><?php echo !empty($pengajuan['waktu_paraf_sekcam']) ? '✅ Paraf Koordinasi oleh ' . htmlspecialchars($pengajuan['paraf_sekcam_oleh']) . ' (' . date('d M Y H:i', strtotime($pengajuan['waktu_paraf_sekcam'])) . ') — "' . htmlspecialchars($pengajuan['catatan_sekcam'] ?? '') . '"' : '⏳ Menunggu paraf koordinasi Sekcam'; ?></p>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="font-bold text-slate-800">4. Camat Cisayong (TTE QR Code):</p>
                        <p class="text-slate-600 mt-0.5"><?php echo $status_norm === 'disetujui' ? '✅ Approved & TTE oleh ' . htmlspecialchars($pengajuan['tte_camat_oleh']) . ' • Kode QR: ' . htmlspecialchars($pengajuan['tte_kode'] ?? '-') : '⏳ Menunggu Approve & TTE Camat'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Document Verification Viewer -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="material-icons-outlined text-blue-900 text-2xl">file_present</span>
                    <h3 class="font-extrabold text-lg text-slate-900">Berkas Persyaratan yang Diunggah</h3>
                </div>
                <span class="text-xs bg-slate-200 text-slate-700 px-3 py-1 rounded-full font-semibold"><?php echo count($lampiran_list); ?> Dokumen</span>
            </div>
            <div class="p-6 space-y-6">
                <!-- Document Switcher Nav -->
                <div class="flex space-x-2 border-b border-slate-200 pb-4 overflow-x-auto">
                    <?php foreach ($lampiran_list as $index => $lamp): ?>
                        <button type="button" onclick="changePreview('<?php echo htmlspecialchars($lamp['file_path']); ?>', '<?php echo htmlspecialchars($lamp['nama_berkas']); ?>')" class="tab-berkas px-4 py-2 <?php echo $index === 0 ? 'bg-blue-900 text-white shadow-md shadow-blue-900/10' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'; ?> rounded-xl font-bold text-xs shrink-0 transition-colors">
                            📄 <?php echo htmlspecialchars($lamp['nama_berkas']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Document Preview Area -->
                <div class="border-2 border-slate-200 rounded-2xl bg-slate-100 p-4 flex flex-col items-center justify-center min-h-[420px] relative overflow-hidden">
                    <?php if (!empty($lampiran_list)): 
                        $first_lamp = $lampiran_list[0];
                    ?>
                        <div id="preview-box" class="w-full max-w-md bg-white p-6 rounded-2xl shadow-xl border border-slate-200 space-y-4 text-center">
                            <div class="w-16 h-16 bg-blue-50 text-blue-900 rounded-2xl mx-auto flex items-center justify-center border border-blue-100">
                                <span class="material-icons-outlined text-4xl">contact_page</span>
                            </div>
                            <h4 id="preview-title" class="font-extrabold text-slate-800 text-lg"><?php echo htmlspecialchars($first_lamp['nama_berkas']); ?></h4>
                            <p id="preview-path" class="text-xs text-slate-500 max-w-sm mx-auto">Lokasi file: <?php echo htmlspecialchars($first_lamp['file_path']); ?></p>
                            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-left text-xs space-y-1 text-emerald-900">
                                <p class="font-bold flex items-center space-x-1">
                                    <span class="material-icons-outlined text-sm">verified</span>
                                    <span>Status Unggahan Dokumen</span>
                                </p>
                                <p>Tersimpan di Server Kecamatan • Siap diperiksa oleh PTSP & Kasi Terkait</p>
                            </div>
                            <a id="download-link" href="<?php echo BASE_URL; ?><?php echo htmlspecialchars($first_lamp['file_path']); ?>" target="_blank" class="inline-flex items-center space-x-2 px-6 py-3 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl text-xs shadow-md transition-all">
                                <span class="material-icons-outlined text-sm">open_in_new</span>
                                <span>Buka / Periksa File Asli</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-500 font-medium text-sm">Tidak ada dokumen terlampir.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function changePreview(path, title) {
    document.getElementById('preview-title').innerText = title;
    document.getElementById('preview-path').innerText = 'Lokasi file: ' + path;
    document.getElementById('download-link').href = '<?php echo BASE_URL; ?>' + path;
    
    const tabs = document.querySelectorAll('.tab-berkas');
    tabs.forEach(tab => {
        if (tab.innerText.includes(title)) {
            tab.className = "tab-berkas px-4 py-2 bg-blue-900 text-white shadow-md shadow-blue-900/10 rounded-xl font-bold text-xs shrink-0 transition-colors";
        } else {
            tab.className = "tab-berkas px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold text-xs shrink-0 transition-colors";
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const btnTolak = document.getElementById('btn-tolak');
    if (btnTolak) {
        btnTolak.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Tolak Berkas & Kirim Notifikasi Upload Ulang',
                input: 'textarea',
                inputLabel: 'Catatan Alasan Penolakan (Akan tampil di akun Warga / Admin Desa)',
                inputPlaceholder: 'Contoh: Foto KTP buram / Surat Pengantar RT-RW belum dilampirkan, silakan upload ulang...',
                showCancelButton: true,
                confirmButtonText: 'Konfirmasi Tolak Berkas',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                    input: 'w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-slate-50 text-slate-800 font-medium text-sm mt-3',
                    confirmButton: 'px-7 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
                    cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
                },
                buttonsStyling: false,
                inputValidator: (value) => {
                    if (!value) {
                        return 'Harap mengisi alasan penolakan agar pemohon tahu berkas mana yang harus diupload ulang!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = 'verifikasi_admin.php?id=<?php echo $id; ?>';
                    const i1 = document.createElement('input');
                    i1.type = 'hidden';
                    i1.name = 'action';
                    i1.value = 'tolak';
                    f.appendChild(i1);
                    const i2 = document.createElement('input');
                    i2.type = 'hidden';
                    i2.name = 'alasan_tolak';
                    i2.value = result.value;
                    f.appendChild(i2);
                    document.body.appendChild(f);
                    f.submit();
                }
            });
        });
    }

    const formApproveCamat = document.getElementById('form-approve-camat');
    if (formApproveCamat) {
        formApproveCamat.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Approve & Terbitkan TTE Camat?',
                text: 'Apakah Anda yakin ingin menyetujui permohonan ini dan menerbitkan TTE resmi Camat Cisayong? Dokumen PDF resmi siap cetak akan diterbitkan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Approve & Terbitkan TTE',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                    confirmButton: 'px-7 py-3 bg-emerald-700 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
                    cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    formApproveCamat.submit();
                }
            });
        });
    }
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
