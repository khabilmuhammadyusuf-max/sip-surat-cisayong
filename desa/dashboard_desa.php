<?php
$page_title = "Dasbor Admin Desa - Khusus Permohonan Surat ADD";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_desa.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'desa') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_id       = $_SESSION['user_id'];
$user_nama     = $_SESSION['nama'] ?? 'Perangkat Desa';
$instansi_desa = $_SESSION['instansi_desa'] ?? 'Desa Jatihurip';

$success_msg = '';
$error_msg   = '';

// Cek apakah sedang dalam Mode Upload Ulang (Revisi berkas ADD yang ditolak PTSP/Kecamatan)
$revisi_id = isset($_GET['revisi_id']) ? (int)$_GET['revisi_id'] : (isset($_POST['revisi_id']) ? (int)$_POST['revisi_id'] : 0);
$revisi_row = null;
$revisi_form_data = [];

if ($revisi_id > 0) {
    $stmtRev = $pdo->prepare("SELECT * FROM `pengajuan` WHERE id = ? AND user_id = ? AND status = 'ditolak'");
    $stmtRev->execute([$revisi_id, $user_id]);
    $revisi_row = $stmtRev->fetch();
    if ($revisi_row) {
        $revisi_form_data = json_decode($revisi_row['data_form'], true) ?? [];
    } else {
        $revisi_id = 0;
    }
}

// Handle Form Submit (Pengajuan Baru atau Upload Ulang Surat ADD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_surat_desa = trim($_POST['nomor_surat_desa'] ?? '');
    $nama_kades       = trim($_POST['nama_kades'] ?? '');
    $jumlah_dana      = trim($_POST['jumlah_dana'] ?? '');
    $tahap_alokasi    = trim($_POST['tahap_alokasi'] ?? '');
    $peruntukan_dana  = trim($_POST['peruntukan_dana'] ?? '');

    if (!empty($nama_kades) && !empty($jumlah_dana) && !empty($tahap_alokasi)) {
        $data_form = json_encode([
            'nama_pemohon'     => $instansi_desa . ' (' . $nama_kades . ')',
            'nomor_surat_desa' => $nomor_surat_desa,
            'nama_kades'       => $nama_kades,
            'instansi_desa'    => $instansi_desa,
            'jumlah_dana'      => $jumlah_dana,
            'tahap_alokasi'    => $tahap_alokasi,
            'peruntukan_dana'  => $peruntukan_dana
        ]);

        try {
            if ($revisi_id > 0 && $revisi_row) {
                $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET `data_form` = ?, `status` = 'menunggu_ptsp', `kasi_tujuan` = 'kasi_pmd', `catatan_tolak` = NULL, `ditolak_oleh` = NULL, `is_revisi` = 1, `waktu_revisi` = NOW() WHERE `id` = ? AND `user_id` = ?");
                $stmtUpd->execute([$data_form, $revisi_id, $user_id]);
                $pengajuan_id = $revisi_id;
            } else {
                $stmtInsert = $pdo->prepare("INSERT INTO `pengajuan` (`user_id`, `jenis_surat`, `data_form`, `status`, `kasi_tujuan`) VALUES (?, 'Alokasi Dana Daerah (ADD)', ?, 'menunggu_ptsp', 'kasi_pmd')");
                $stmtInsert->execute([$user_id, $data_form]);
                $pengajuan_id = $pdo->lastInsertId();
            }

            // Handle uploads
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $files = [
                'file_pengantar_kades' => 'Surat Pengantar Kepala Desa',
                'file_rab'             => 'Dokumen RAB (Anggaran)',
                'file_lpj'             => 'LPJ Tahap Sebelumnya'
            ];

            foreach ($files as $input_name => $berkas_title) {
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES[$input_name]['tmp_name'];
                    $name     = basename($_FILES[$input_name]['name']);
                    $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_name = "berkas_{$pengajuan_id}_{$input_name}_" . time() . ".$ext";
                    $dest     = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $dest)) {
                        if ($revisi_id > 0) {
                            $stmtDelOld = $pdo->prepare("DELETE FROM `lampiran` WHERE `pengajuan_id` = ? AND `nama_berkas` = ?");
                            $stmtDelOld->execute([$pengajuan_id, $berkas_title]);
                        }
                        $stmtLampiran = $pdo->prepare("INSERT INTO `lampiran` (`pengajuan_id`, `nama_berkas`, `file_path`) VALUES (?, ?, ?)");
                        $stmtLampiran->execute([$pengajuan_id, $berkas_title, 'uploads/' . $new_name]);
                    }
                }
            }

            if ($revisi_id > 0) {
                $success_msg = "Perbaikan & Upload Ulang Berkas ADD berhasil dikirim kembali ke Front Office (PTSP) Kecamatan!";
                $revisi_id = 0;
                $revisi_row = null;
                $revisi_form_data = [];
            } else {
                $success_msg = "Pengajuan Surat Alokasi Dana Daerah (ADD) berhasil dikirim ke Front Office (PTSP) Kecamatan!";
            }
        } catch (\Exception $e) {
            $error_msg = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error_msg = "Harap mengisi seluruh data form ADD yang diwajibkan.";
    }
}

// Fetch history
$stmtHist = $pdo->prepare("SELECT * FROM `pengajuan` WHERE user_id = ? ORDER BY waktu_pengajuan DESC");
$stmtHist->execute([$user_id]);
$pengajuan_list = $stmtHist->fetchAll();

$ditolak_items = [];
foreach ($pengajuan_list as $p) {
    if ($p['status'] === 'ditolak') {
        $ditolak_items[] = $p;
    }
}
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-teal-800 to-teal-950 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="z-10 space-y-2">
            <div class="inline-flex items-center space-x-2 bg-white/15 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold">
                <span class="material-icons-outlined text-sm">lock</span>
                <span>Akses Terkunci Admin Desa • Hanya Jenis Surat "Alokasi Dana Daerah (ADD)"</span>
            </div>
            <h2 class="text-3xl font-extrabold tracking-tight">Portal Pengajuan Surat ADD — <?php echo htmlspecialchars($instansi_desa); ?></h2>
            <p class="text-teal-100 text-sm max-w-2xl">Unggah persyaratan pencairan ADD untuk diverifikasi melalui alur berjenjang: <strong>Front Office (PTSP) → Kasi PMD (Paraf Substansi) → Sekcam (Paraf Koordinasi) → Camat (Approve & TTE QR Code)</strong>.</p>
        </div>
        <a href="#buat-pengajuan-add" class="z-10 bg-white text-teal-900 font-extrabold px-6 py-3.5 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2 shrink-0">
            <span class="material-icons-outlined text-xl">note_add</span>
            <span>Ajukan Surat ADD</span>
        </a>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white select-none pointer-events-none">
            <span class="material-icons-outlined text-[200px]">account_balance_wallet</span>
        </div>
    </div>

    <!-- NOTIFIKASI OTOMATIS JIKA BERKAS ADD DITOLAK OLEH PTSP / KECAMATAN -->
    <?php if (!empty($ditolak_items)): ?>
        <div class="bg-rose-50 border-2 border-rose-300 rounded-3xl p-6 shadow-lg space-y-4">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-rose-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-md shadow-rose-600/30">
                    <span class="material-icons-outlined text-2xl">notifications_active</span>
                </div>
                <div class="flex-1">
                    <span class="inline-block px-2.5 py-0.5 bg-rose-200 text-rose-900 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1">Notifikasi Front Office (PTSP) Kecamatan</span>
                    <h4 class="font-extrabold text-lg text-rose-950">Berkas Permohonan ADD Desa Anda Dikembalikan untuk Diupload Ulang!</h4>
                    <p class="text-xs text-rose-800">Silakan periksa alasan penolakan di bawah ini dan klik <strong>"Perbaiki & Upload Ulang Syarat ADD"</strong> agar berkas kembali masuk ke meja PTSP.</p>
                </div>
            </div>
            <div class="space-y-3 pt-2">
                <?php foreach ($ditolak_items as $dt): 
                    $dt_data = json_decode($dt['data_form'], true) ?? [];
                    $reg_no = 'REG-' . date('Ymd', strtotime($dt['waktu_pengajuan'])) . '-' . str_pad($dt['id'], 3, '0', STR_PAD_LEFT);
                ?>
                    <div class="bg-white p-4 rounded-2xl border border-rose-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-extrabold text-sm text-slate-900">Surat ADD — <?php echo htmlspecialchars($dt_data['tahap_alokasi'] ?? 'Tahap I'); ?> (Rp <?php echo number_format((float)($dt_data['jumlah_dana'] ?? 0), 0, ',', '.'); ?>)</span>
                                <span class="text-xs font-mono text-slate-500">(<?php echo $reg_no; ?>)</span>
                                <?php if (!empty($dt['ditolak_oleh'])): ?>
                                    <span class="text-[11px] bg-rose-100 text-rose-800 px-2 py-0.5 rounded-md font-bold">Oleh: <?php echo htmlspecialchars($dt['ditolak_oleh']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-rose-700 font-semibold">Catatan Koreksi: "<?php echo htmlspecialchars($dt['catatan_tolak'] ?: 'Mohon lengkapi kembali dokumen RAB / LPJ / Pengantar Desa.'); ?>"</p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>desa/dashboard_desa.php?revisi_id=<?php echo $dt['id']; ?>#buat-pengajuan-add" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-extrabold shadow-md shadow-rose-600/20 flex items-center space-x-1.5 shrink-0 transition-all">
                            <span class="material-icons-outlined text-sm">cloud_upload</span>
                            <span>Perbaiki & Upload Ulang Syarat ADD</span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl shadow-md flex items-center space-x-4">
            <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-600/30">
                <span class="material-icons-outlined text-3xl">task_alt</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-emerald-900">Berkas ADD Berhasil Terkirim!</h4>
                <p class="text-xs text-emerald-700 font-medium"><?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-6 rounded-3xl shadow-md flex items-center space-x-4">
            <div class="w-14 h-14 bg-rose-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-rose-600/30">
                <span class="material-icons-outlined text-3xl">error</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-rose-900">Gagal Mengirim Pengajuan ADD</h4>
                <p class="text-xs text-rose-700 font-medium"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submission History Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="material-icons-outlined text-teal-800 text-2xl">account_balance</span>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Pelacakan Alur Berjenjang Surat ADD — <?php echo htmlspecialchars($instansi_desa); ?></h3>
                    <p class="text-xs text-slate-400">PTSP Kecamatan → Kasi PMD → Sekretaris Camat → Camat (Approve & TTE QR Code)</p>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[760px] text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Nominal & Kades</th>
                        <th class="px-6 py-4 whitespace-nowrap">Tahap / Peruntukan</th>
                        <th class="px-6 py-4 whitespace-nowrap">Tahapan Alur Berjenjang</th>
                        <th class="px-6 py-4 whitespace-nowrap">Catatan / Nomor Surat</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($pengajuan_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 font-semibold text-sm">
                                Belum ada riwayat permohonan ADD. Silakan ajukan melalui formulir di bawah.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengajuan_list as $row): 
                            $data = json_decode($row['data_form'], true) ?? [];
                            $nominal = $data['jumlah_dana'] ?? 0;
                            $kades   = $data['nama_kades'] ?? 'Kepala Desa';
                            $tahap   = $data['tahap_alokasi'] ?? 'Tahap I';
                            $peruntukan = $data['peruntukan_dana'] ?? '-';
                            $sInfo = get_status_info($row['status'], $row['kasi_tujuan'] ?: 'kasi_pmd');
                        ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">Rp <?php echo number_format((float)$nominal, 0, ',', '.'); ?></p>
                                    <p class="text-xs text-slate-400">Kades: <?php echo htmlspecialchars($kades); ?></p>
                                    <p class="text-[11px] text-slate-400"><?php echo date('d M Y, H:i', strtotime($row['waktu_pengajuan'])); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($tahap); ?> <?php echo !empty($row['is_revisi']) ? '<span class="text-amber-700 text-xs font-bold">(Revisi)</span>' : ''; ?></p>
                                    <p class="text-xs text-slate-500 max-w-xs truncate"><?php echo htmlspecialchars($peruntukan); ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap <?php echo $sInfo['badge_class']; ?>">
                                        <span class="material-icons-outlined text-sm flex-shrink-0"><?php echo $sInfo['icon']; ?></span>
                                        <span class="whitespace-nowrap"><?php echo htmlspecialchars($sInfo['label']); ?></span>
                                    </span>
                                    <?php if ($sInfo['step'] > 0): ?>
                                        <div class="flex items-center space-x-1 mt-2 text-[10px] font-bold">
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 1 ? 'bg-teal-800 text-white' : 'bg-slate-100 text-slate-400'; ?>">1. Layanan PTSP</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 2 ? 'bg-teal-800 text-white' : 'bg-slate-100 text-slate-400'; ?>">2. Kasi PMD</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 3 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400'; ?>">3. Selesai</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 max-w-xs">
                                    <?php if ($row['status'] === 'ditolak'): ?>
                                        <span class="text-rose-700 font-bold"><?php echo htmlspecialchars($row['catatan_tolak'] ?: 'Silakan upload ulang berkas ADD.'); ?></span>
                                    <?php elseif ($row['status'] === 'disetujui'): ?>
                                        <span class="text-emerald-700 font-bold">No: <?php echo htmlspecialchars($row['nomor_surat_generated']); ?> (Disetujui)</span>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($sInfo['desc']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center space-x-1 px-4 py-2 bg-teal-800 hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-md shadow-teal-800/10 transition-all">
                                            <span class="material-icons-outlined text-sm">picture_as_pdf</span>
                                            <span>Unduh PDF Resmi</span>
                                        </a>
                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                        <a href="<?php echo BASE_URL; ?>desa/dashboard_desa.php?revisi_id=<?php echo $row['id']; ?>#buat-pengajuan-add" class="inline-flex items-center space-x-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/15 transition-all">
                                            <span class="material-icons-outlined text-sm">cloud_upload</span>
                                            <span>Upload Ulang</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-500 rounded-xl text-xs font-bold inline-block">Dalam Proses</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Digital Submission Form Card -->
    <div id="buat-pengajuan-add" class="bg-white rounded-3xl border <?php echo $revisi_row ? 'border-rose-400 ring-4 ring-rose-100' : 'border-slate-200'; ?> shadow-xl overflow-hidden">
        <div class="p-8 <?php echo $revisi_row ? 'bg-rose-50/70' : 'bg-slate-50'; ?> border-b border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 <?php echo $revisi_row ? 'bg-rose-600' : 'bg-teal-800'; ?> text-white rounded-2xl flex items-center justify-center shadow-lg">
                    <span class="material-icons-outlined text-3xl"><?php echo $revisi_row ? 'cloud_upload' : 'request_quote'; ?></span>
                </div>
                <div>
                    <?php if ($revisi_row): ?>
                        <span class="inline-block px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1">Mode Perbaikan & Upload Ulang Syarat ADD</span>
                        <h3 class="font-extrabold text-xl text-slate-900">Perbaiki & Upload Ulang Pengajuan Surat ADD</h3>
                        <p class="text-xs text-rose-700 font-semibold mt-0.5">Catatan Penolakan: "<?php echo htmlspecialchars($revisi_row['catatan_tolak'] ?: 'Silakan perbaiki lampiran syarat ADD'); ?>"</p>
                    <?php else: ?>
                        <h3 class="font-extrabold text-xl text-slate-900">Formulir Khusus Admin Desa: Surat Rekomendasi ADD</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Sesuai kebijakan sistem, akun Admin Desa dikunci khusus untuk mengajukan <strong>Surat Alokasi Dana Daerah (ADD)</strong></p>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <?php if ($revisi_row): ?>
                    <a href="<?php echo BASE_URL; ?>desa/dashboard_desa.php#buat-pengajuan-add" class="px-4 py-2.5 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100">Batal Revisi</a>
                <?php else: ?>
                    <span class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-teal-50 border border-teal-200 text-teal-900 rounded-2xl text-xs font-extrabold">
                        <span class="material-icons-outlined text-sm">lock</span>
                        <span>Jenis Surat: Surat ADD (Terkunci)</span>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <form id="form-pengajuan-desa" class="p-8 space-y-8" action="<?php echo BASE_URL; ?>desa/dashboard_desa.php#buat-pengajuan-add" method="POST" enctype="multipart/form-data">
            <?php if ($revisi_row): ?>
                <input type="hidden" name="revisi_id" value="<?php echo (int)$revisi_row['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Pemerintah Desa</label>
                    <input type="text" value="<?php echo htmlspecialchars($instansi_desa); ?>" readonly class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-100 text-slate-500 font-medium text-sm select-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nomor Surat Pengantar / Permohonan Desa</label>
                    <input type="text" name="nomor_surat_desa" value="<?php echo htmlspecialchars($revisi_form_data['nomor_surat_desa'] ?? ''); ?>" placeholder="Contoh: 140/08/Ds-2006/XI/2026" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-teal-800 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Kepala Desa</label>
                    <input type="text" name="nama_kades" value="<?php echo htmlspecialchars($revisi_form_data['nama_kades'] ?? ''); ?>" placeholder="Contoh: H. Suherman, S.E." required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-teal-800 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Jumlah Dana ADD yang Diajukan (Rp)</label>
                    <input type="number" name="jumlah_dana" value="<?php echo htmlspecialchars($revisi_form_data['jumlah_dana'] ?? ''); ?>" placeholder="Contoh: 50950000" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-teal-800 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tahap Alokasi Pencairan</label>
                    <?php $cur_tahap = $revisi_form_data['tahap_alokasi'] ?? ''; ?>
                    <select name="tahap_alokasi" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-teal-800 bg-slate-50 text-slate-800 font-medium text-sm">
                        <option value="">-- Pilih Tahap Alokasi --</option>
                        <option value="Tahap I" <?php echo $cur_tahap === 'Tahap I' ? 'selected' : ''; ?>>Tahap I</option>
                        <option value="Tahap II" <?php echo $cur_tahap === 'Tahap II' ? 'selected' : ''; ?>>Tahap II</option>
                        <option value="Tahap III" <?php echo $cur_tahap === 'Tahap III' ? 'selected' : ''; ?>>Tahap III</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Peruntukan Dana & Nomor Surat Pengantar Desa</label>
                    <textarea name="peruntukan_dana" rows="3" placeholder="Contoh: Permohonan Pencairan ADD Bulan November sesuai Surat Pengantar Nomor B/86/KU.02.03/DS/2026..." required class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-teal-800 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($revisi_form_data['peruntukan_dana'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Upload Supporting Documents Section -->
            <div class="pt-6 border-t border-slate-100 space-y-6">
                <div>
                    <h4 class="font-bold text-slate-800 text-base">
                        <?php echo $revisi_row ? 'Unggah Ulang Dokumen Prasyarat ADD (Pilih file yang ingin diperbaiki)' : 'Unggah Dokumen Prasyarat ADD'; ?>
                    </h4>
                    <p class="text-xs text-slate-400 mt-0.5">Format file yang diwajibkan: PDF. Maksimal ukuran file: 5MB per file.</p>
                </div>

                <?php $req_attr = $revisi_row ? '' : 'required'; ?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">assignment_ind</span>
                        <p class="text-xs font-bold text-slate-700">Surat Pengantar Kepala Desa</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file PDF di sini'; ?></p>
                        <input type="file" name="file_pengantar_kades" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>

                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">analytics</span>
                        <p class="text-xs font-bold text-slate-700">Dokumen RAB & SPTJM</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file PDF di sini'; ?></p>
                        <input type="file" name="file_rab" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>

                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">task</span>
                        <p class="text-xs font-bold text-slate-700">LPJ Tahap Sebelumnya</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file PDF di sini'; ?></p>
                        <input type="file" name="file_lpj" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="<?php echo $revisi_row ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20' : 'bg-teal-800 hover:bg-teal-700 shadow-teal-800/20'; ?> text-white font-extrabold px-10 py-4 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl"><?php echo $revisi_row ? 'published_with_changes' : 'send'; ?></span>
                    <span><?php echo $revisi_row ? 'Kirim Ulang Perbaikan ADD ke PTSP' : 'Kirim Pengajuan ADD ke Front Office (PTSP)'; ?></span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('form-pengajuan-desa').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Kirim Pengajuan ADD ke PTSP?',
        text: 'Berkas akan diperiksa oleh Front Office (PTSP), lalu diteruskan ke Kasi PMD, Sekcam, dan Camat untuk TTE.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim Permohonan',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-teal-800 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($success_msg)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Pengajuan ADD Terkirim!',
            text: <?php echo json_encode($success_msg); ?>,
            timer: 3000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php elseif (!empty($error_msg)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: <?php echo json_encode($error_msg); ?>,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
