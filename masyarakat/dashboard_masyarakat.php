<?php
$page_title = "Dasbor Warga - Layanan Surat Umum";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_masyarakat.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'masyarakat') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_nama = $_SESSION['nama'] ?? 'Ahmad Warga';
$user_nik  = $_SESSION['username'] ?? '3201928391820001';

$success_msg = '';
$error_msg   = '';

// 1. Ambil hanya Jenis Surat Umum khusus Warga (Surat ADD dikunci khusus Admin Desa)
$stmtTmpls = $pdo->query("SELECT jenis_surat, kategori_pemohon, kasi_default, form_fields FROM `templates` WHERE kategori_pemohon = 'masyarakat' AND jenis_surat != 'Alokasi Dana Daerah (ADD)' ORDER BY id ASC");
$all_templates = $stmtTmpls->fetchAll(PDO::FETCH_ASSOC);

// Cek apakah sedang dalam Mode Upload Ulang (Revisi berkas yang ditolak PTSP/Kecamatan)
$revisi_id = isset($_GET['revisi_id']) ? (int)$_GET['revisi_id'] : (isset($_POST['revisi_id']) ? (int)$_POST['revisi_id'] : 0);
$revisi_row = null;
$revisi_form_data = [];
$revisi_lampiran = [];

if ($revisi_id > 0) {
    $stmtRev = $pdo->prepare("SELECT * FROM `pengajuan` WHERE id = ? AND user_id = ? AND status = 'ditolak'");
    $stmtRev->execute([$revisi_id, $user_id]);
    $revisi_row = $stmtRev->fetch();
    if ($revisi_row) {
        $revisi_form_data = json_decode($revisi_row['data_form'], true) ?? [];
        $stmtRevLamp = $pdo->prepare("SELECT * FROM `lampiran` WHERE pengajuan_id = ?");
        $stmtRevLamp->execute([$revisi_id]);
        $revisi_lampiran = $stmtRevLamp->fetchAll();
    } else {
        $revisi_id = 0;
    }
}

$jenis_aktif = $revisi_row ? $revisi_row['jenis_surat'] : ($_GET['jenis'] ?? ($all_templates[0]['jenis_surat'] ?? 'SKTM (Surat Keterangan Tidak Mampu)'));
// Pastikan Warga tidak bisa memaksa buka jenis surat ADD lewat parameter URL
if ($jenis_aktif === 'Alokasi Dana Daerah (ADD)') {
    $jenis_aktif = $all_templates[0]['jenis_surat'] ?? 'SKTM (Surat Keterangan Tidak Mampu)';
}

$current_template = null;
foreach ($all_templates as $t) {
    if ($t['jenis_surat'] === $jenis_aktif) {
        $current_template = $t;
        break;
    }
}
if (!$current_template && !empty($all_templates)) {
    $current_template = $all_templates[0];
    $jenis_aktif = $current_template['jenis_surat'];
}

$form_fields = json_decode($current_template['form_fields'] ?? '[]', true) ?? [];
$text_fields = [];
$file_fields = [];
foreach ($form_fields as $f) {
    if (($f['type'] ?? '') === 'file') {
        $file_fields[] = $f;
    } else {
        $text_fields[] = $f;
    }
}

// 2. Handle Form Submit (Pengajuan Baru maupun Upload Ulang / Revisi)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_surat_post = trim($_POST['jenis_surat'] ?? $jenis_aktif);
    if ($jenis_surat_post === 'Alokasi Dana Daerah (ADD)') {
        $error_msg = "Akses Ditolak: Surat ADD hanya dapat diajukan oleh Admin Desa.";
    } else {
        $post_template = null;
        foreach ($all_templates as $t) {
            if ($t['jenis_surat'] === $jenis_surat_post) {
                $post_template = $t;
                break;
            }
        }
        $post_form_fields = json_decode($post_template['form_fields'] ?? '[]', true) ?? [];
        $post_file_fields = [];
        foreach ($post_form_fields as $pf) {
            if (($pf['type'] ?? '') === 'file') {
                $post_file_fields[] = $pf;
            }
        }

        $data_form_array = [];
        $is_valid = false;

        if ($jenis_surat_post === 'Dispensasi Nikah') {
            $nama_pemohon = trim($_POST['nama_pemohon'] ?? '');
            $nama_pasangan = trim($_POST['nama_pasangan'] ?? '');
            $tanggal_akad = trim($_POST['tanggal_akad'] ?? '');

            if (!empty($nama_pemohon) && !empty($nama_pasangan) && !empty($tanggal_akad)) {
                $is_valid = true;
                $data_form_array = [
                    'nama_pemohon'          => $nama_pemohon,
                    'nik_pemohon'           => $user_nik,
                    'tempat_lahir'          => trim($_POST['tempat_lahir'] ?? ''),
                    'tanggal_lahir'         => trim($_POST['tanggal_lahir'] ?? ''),
                    'agama'                 => trim($_POST['agama'] ?? 'Islam'),
                    'pekerjaan'             => trim($_POST['pekerjaan'] ?? ''),
                    'alamat'                => trim($_POST['alamat'] ?? ''),
                    'nama_pasangan'         => $nama_pasangan,
                    'tempat_lahir_pasangan' => trim($_POST['tempat_lahir_pasangan'] ?? ''),
                    'tanggal_lahir_pasangan'=> trim($_POST['tanggal_lahir_pasangan'] ?? ''),
                    'agama_pasangan'        => trim($_POST['agama_pasangan'] ?? 'Islam'),
                    'pekerjaan_pasangan'    => trim($_POST['pekerjaan_pasangan'] ?? ''),
                    'alamat_pasangan'       => trim($_POST['alamat_pasangan'] ?? ''),
                    'tanggal_akad'          => $tanggal_akad,
                    'alasan_dispensasi'     => trim($_POST['alasan_dispensasi'] ?? '')
                ];
            } else {
                $error_msg = "Harap mengisi seluruh data form yang diwajibkan (Nama, Nama Pasangan, Tanggal Akad).";
            }
        } else {
            $is_valid = true;
            $data_form_array['nama_pemohon'] = trim($_POST['nama_pemohon'] ?? $user_nama);
            $data_form_array['nik_pemohon']  = $user_nik;
            foreach ($_POST as $key => $val) {
                if (!in_array($key, ['jenis_surat', 'nama_pemohon', 'nik_pemohon', 'revisi_id'], true)) {
                    $data_form_array[$key] = trim((string)$val);
                }
            }
        }

        if ($is_valid) {
            $data_form = json_encode($data_form_array);
            $kasi_tujuan = get_default_kasi_for_surat($jenis_surat_post, $post_template['kasi_default'] ?? null);

            try {
                if ($revisi_id > 0 && $revisi_row) {
                    // Update pengajuan yang sama (Upload Ulang ke Front Office PTSP)
                    $stmtUpd = $pdo->prepare("UPDATE `pengajuan` SET `data_form` = ?, `status` = 'menunggu_ptsp', `kasi_tujuan` = ?, `catatan_tolak` = NULL, `ditolak_oleh` = NULL, `is_revisi` = 1, `waktu_revisi` = NOW() WHERE `id` = ? AND `user_id` = ?");
                    $stmtUpd->execute([$data_form, $kasi_tujuan, $revisi_id, $user_id]);
                    $pengajuan_id = $revisi_id;
                } else {
                    // Insert pengajuan baru ke Front Office (PTSP)
                    $stmtInsert = $pdo->prepare("INSERT INTO `pengajuan` (`user_id`, `jenis_surat`, `data_form`, `status`, `kasi_tujuan`) VALUES (?, ?, ?, 'menunggu_ptsp', ?)");
                    $stmtInsert->execute([$user_id, $jenis_surat_post, $data_form, $kasi_tujuan]);
                    $pengajuan_id = $pdo->lastInsertId();
                }

                // Handle uploads
                $upload_dir = __DIR__ . '/../uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $files = [];
                if ($jenis_surat_post === 'Dispensasi Nikah' || empty($post_file_fields)) {
                    $files = [
                        'file_ktp'          => 'KTP Calon Pengantin / Pemohon',
                        'file_kk'           => 'Kartu Keluarga',
                        'file_pengantar_rt' => 'Surat Pengantar RT/RW & Desa'
                    ];
                } else {
                    foreach ($post_file_fields as $f) {
                        $files[$f['name']] = $f['label'];
                    }
                }

                foreach ($files as $input_name => $berkas_title) {
                    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                        $tmp_name = $_FILES[$input_name]['tmp_name'];
                        $name     = basename($_FILES[$input_name]['name']);
                        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        $new_name = "berkas_{$pengajuan_id}_{$input_name}_" . time() . ".$ext";
                        $dest     = $upload_dir . $new_name;

                        if (move_uploaded_file($tmp_name, $dest)) {
                            // Jika revisi, hapus record lampiran lama dengan nama_berkas yang sama
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
                    $success_msg = "Perbaikan & Upload Ulang berkas $jenis_surat_post berhasil dikirim kembali ke Front Office (PTSP) Kecamatan!";
                    $revisi_id = 0;
                    $revisi_row = null;
                    $revisi_form_data = [];
                } else {
                    $success_msg = "Pengajuan $jenis_surat_post berhasil dikirim ke Front Office (PTSP) Kecamatan untuk diverifikasi!";
                }
                $jenis_aktif = $jenis_surat_post;
            } catch (\Exception $e) {
                $error_msg = "Terjadi kesalahan sistem: " . $e->getMessage();
            }
        }
    }
}

// 3. Fetch Riwayat & Statistik
$stmtHist = $pdo->prepare("SELECT * FROM `pengajuan` WHERE user_id = ? ORDER BY waktu_pengajuan DESC");
$stmtHist->execute([$user_id]);
$pengajuan_list = $stmtHist->fetchAll();

$stats = ['diproses' => 0, 'disetujui' => 0, 'ditolak' => 0];
$ditolak_items = [];
foreach ($pengajuan_list as $p) {
    if ($p['status'] === 'disetujui') {
        $stats['disetujui']++;
    } elseif ($p['status'] === 'ditolak') {
        $stats['ditolak']++;
        $ditolak_items[] = $p;
    } else {
        $stats['diproses']++;
    }
}
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="z-10 space-y-2">
            <div class="inline-flex items-center space-x-2 bg-white/15 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold">
                <span class="material-icons-outlined text-sm">verified_user</span>
                <span>Portal Warga • <?php echo htmlspecialchars($user_nama); ?> (NIK: <?php echo htmlspecialchars($user_nik); ?>)</span>
            </div>
            <h2 class="text-3xl font-extrabold tracking-tight">Layanan Surat Umum Masyarakat</h2>
            <p class="text-blue-100 text-sm max-w-2xl">Pilih jenis surat umum (SKTM, Ahli Waris, Dispensasi Nikah, SKU/Domisili, Izin Keramaian), unggah persyaratan, dan pantau progres validasi berjenjang (PTSP → Kasi → Sekcam → Camat TTE) hingga siap unduh PDF.</p>
        </div>
        <a href="#buat-pengajuan" class="z-10 bg-white text-blue-900 font-extrabold px-6 py-3.5 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2 shrink-0">
            <span class="material-icons-outlined text-xl">add_circle</span>
            <span>Pilih Surat & Ajukan</span>
        </a>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white select-none pointer-events-none">
            <span class="material-icons-outlined text-[200px]">auto_stories</span>
        </div>
    </div>

    <!-- NOTIFIKASI OTOMATIS JIKA ADA BERKAS DITOLAK OLEH PTSP / KECAMATAN -->
    <?php if (!empty($ditolak_items)): ?>
        <div class="bg-rose-50 border-2 border-rose-300 rounded-3xl p-6 shadow-lg space-y-4">
            <div class="flex items-start space-x-4">
                <div class="w-12 h-12 bg-rose-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-md shadow-rose-600/30">
                    <span class="material-icons-outlined text-2xl">notifications_active</span>
                </div>
                <div class="flex-1">
                    <span class="inline-block px-2.5 py-0.5 bg-rose-200 text-rose-900 rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1">Notifikasi Front Office (PTSP) Kecamatan</span>
                    <h4 class="font-extrabold text-lg text-rose-950">Ada <?php echo count($ditolak_items); ?> Berkas Permohonan Anda yang Perlu Diupload Ulang!</h4>
                    <p class="text-xs text-rose-800">Silakan periksa catatan koreksi di bawah ini dan klik tombol <strong>"Perbaiki & Upload Ulang"</strong> untuk mengirim ulang syarat ke Front Office (PTSP).</p>
                </div>
            </div>
            <div class="space-y-3 pt-2">
                <?php foreach ($ditolak_items as $dt): 
                    $reg_no = 'REG-' . date('Ymd', strtotime($dt['waktu_pengajuan'])) . '-' . str_pad($dt['id'], 3, '0', STR_PAD_LEFT);
                ?>
                    <div class="bg-white p-4 rounded-2xl border border-rose-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center space-x-2">
                                <span class="font-extrabold text-sm text-slate-900"><?php echo htmlspecialchars($dt['jenis_surat']); ?></span>
                                <span class="text-xs font-mono text-slate-500">(<?php echo $reg_no; ?>)</span>
                                <?php if (!empty($dt['ditolak_oleh'])): ?>
                                    <span class="text-[11px] bg-rose-100 text-rose-800 px-2 py-0.5 rounded-md font-bold">Ditolak oleh: <?php echo htmlspecialchars($dt['ditolak_oleh']); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-rose-700 font-semibold">Catatan Koreksi: "<?php echo htmlspecialchars($dt['catatan_tolak'] ?: 'Mohon perbaiki dan lengkapi dokumen persyaratan.'); ?>"</p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php?revisi_id=<?php echo $dt['id']; ?>#buat-pengajuan" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-extrabold shadow-md shadow-rose-600/20 flex items-center space-x-1.5 shrink-0 transition-all">
                            <span class="material-icons-outlined text-sm">cloud_upload</span>
                            <span>Perbaiki & Upload Ulang</span>
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
                <h4 class="font-extrabold text-lg text-emerald-900">Pengajuan Berhasil Dikirim!</h4>
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
                <h4 class="font-extrabold text-lg text-rose-900">Gagal Mengirim Pengajuan</h4>
                <p class="text-xs text-rose-700 font-medium"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Section -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center justify-center text-amber-600">
                <span class="material-icons-outlined text-2xl">account_tree</span>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sedang Diproses Kecamatan</p>
                <p class="text-2xl font-black text-slate-800"><?php echo $stats['diproses']; ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-700">
                <span class="material-icons-outlined text-2xl">qr_code_2</span>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Selesai (TTE & PDF Siap Unduh)</p>
                <p class="text-2xl font-black text-slate-800"><?php echo $stats['disetujui']; ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-rose-50 rounded-xl flex items-center justify-center text-rose-600">
                <span class="material-icons-outlined text-2xl">assignment_return</span>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ditolak (Perlu Upload Ulang)</p>
                <p class="text-2xl font-black text-slate-800"><?php echo $stats['ditolak']; ?></p>
            </div>
        </div>
    </div>

    <!-- Submission History Table with 5-Stage Tracking -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <span class="material-icons-outlined text-blue-900 text-2xl">history</span>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Pelacakan Alur Berjenjang Surat Anda</h3>
                    <p class="text-xs text-slate-400">Alur Resmi: Front Office (PTSP) → Kasi Terkait (Paraf Substansi) → Sekcam (Paraf Koordinasi) → Camat (Approve & TTE QR Code)</p>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[760px] text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Jenis Surat & Pemohon</th>
                        <th class="px-6 py-4 whitespace-nowrap">Waktu Pengajuan</th>
                        <th class="px-6 py-4 whitespace-nowrap">Tahapan Saat Ini</th>
                        <th class="px-6 py-4 whitespace-nowrap">Catatan / Progres Paraf</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($pengajuan_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 font-semibold text-sm">
                                Belum ada riwayat pengajuan surat. Silakan pilih jenis surat umum di bawah.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengajuan_list as $row): 
                            $data = json_decode($row['data_form'], true) ?? [];
                            $pemohon = $data['nama_pemohon'] ?? $user_nama;
                            $sInfo = get_status_info($row['status'], $row['kasi_tujuan']);
                        ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['jenis_surat']); ?></p>
                                    <p class="text-xs text-slate-400">Pemohon: <?php echo htmlspecialchars($pemohon); ?> <?php echo !empty($row['is_revisi']) ? '• <span class="text-amber-700 font-bold">(Telah Diupload Ulang)</span>' : ''; ?></p>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs whitespace-nowrap"><?php echo date('d M Y, H:i', strtotime($row['waktu_pengajuan'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap <?php echo $sInfo['badge_class']; ?>">
                                        <span class="material-icons-outlined text-sm flex-shrink-0"><?php echo $sInfo['icon']; ?></span>
                                        <span class="whitespace-nowrap"><?php echo htmlspecialchars($sInfo['label']); ?></span>
                                    </span>
                                    <!-- Mini Stepper -->
                                    <?php if ($sInfo['step'] > 0): ?>
                                        <div class="flex items-center space-x-1 mt-2 text-[10px] font-bold">
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 1 ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-400'; ?>">1.PTSP</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 2 ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-400'; ?>">2.Kasi</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 3 ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-400'; ?>">3.Sekcam</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 4 ? 'bg-blue-900 text-white' : 'bg-slate-100 text-slate-400'; ?>">4.Camat</span>
                                            <span>→</span>
                                            <span class="px-1.5 py-0.5 rounded <?php echo $sInfo['step'] >= 5 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400'; ?>">5.PDF</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-slate-600 text-xs max-w-xs">
                                    <?php if ($row['status'] === 'ditolak'): ?>
                                        <span class="text-rose-700 font-bold"><?php echo htmlspecialchars($row['catatan_tolak'] ?: 'Berkas ditolak, silakan upload ulang.'); ?></span>
                                    <?php elseif ($row['status'] === 'disetujui'): ?>
                                        <span class="text-emerald-700 font-bold">No: <?php echo htmlspecialchars($row['nomor_surat_generated']); ?> (TTE QR Code Aktif)</span>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($sInfo['desc']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center space-x-1 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-700/15 transition-all">
                                            <span class="material-icons-outlined text-sm">picture_as_pdf</span>
                                            <span>Unduh PDF</span>
                                        </a>
                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php?revisi_id=<?php echo $row['id']; ?>#buat-pengajuan" class="inline-flex items-center space-x-1 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/15 transition-all">
                                            <span class="material-icons-outlined text-sm">cloud_upload</span>
                                            <span>Upload Ulang</span>
                                        </a>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 bg-slate-100 text-slate-500 rounded-xl text-xs font-bold inline-block">Sedang Diverifikasi</span>
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
    <div id="buat-pengajuan" class="bg-white rounded-3xl border <?php echo $revisi_row ? 'border-rose-400 ring-4 ring-rose-100' : 'border-slate-200'; ?> shadow-xl overflow-hidden">
        <div class="p-8 <?php echo $revisi_row ? 'bg-rose-50/70' : 'bg-slate-50'; ?> border-b border-slate-100 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 <?php echo $revisi_row ? 'bg-rose-600' : 'bg-blue-900'; ?> text-white rounded-2xl flex items-center justify-center shadow-lg">
                    <span class="material-icons-outlined text-3xl"><?php echo $revisi_row ? 'cloud_upload' : 'description'; ?></span>
                </div>
                <div>
                    <?php if ($revisi_row): ?>
                        <span class="inline-block px-2.5 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-extrabold uppercase tracking-wider mb-1">Mode Perbaikan & Upload Ulang Berkas</span>
                        <h3 class="font-extrabold text-xl text-slate-900">Perbaiki Berkas: <?php echo htmlspecialchars($jenis_aktif); ?></h3>
                        <p class="text-xs text-rose-700 font-semibold mt-0.5">Alasan Penolakan: "<?php echo htmlspecialchars($revisi_row['catatan_tolak'] ?: 'Silakan perbaiki berkas'); ?>"</p>
                    <?php else: ?>
                        <h3 class="font-extrabold text-xl text-slate-900">Formulir Pengajuan Jenis Surat Umum (Warga)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Pilih jenis surat umum di kanan, isi biodata, dan unggah syarat dokumen untuk dikirim ke Front Office (PTSP)</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-2 w-full md:w-auto">
                <?php if ($revisi_row): ?>
                    <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php#buat-pengajuan" class="px-4 py-3 bg-white border border-slate-300 rounded-2xl text-xs font-bold text-slate-700 hover:bg-slate-100">Batal Revisi</a>
                <?php else: ?>
                    <select onchange="window.location.href='<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php?jenis=' + encodeURIComponent(this.value) + '#buat-pengajuan'" class="w-full md:w-auto px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-blue-900 focus:outline-none focus:border-blue-900 shadow-sm cursor-pointer">
                        <?php foreach ($all_templates as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['jenis_surat']); ?>" <?php echo $jenis_aktif === $t['jenis_surat'] ? 'selected' : ''; ?>>
                                📄 <?php echo htmlspecialchars($t['jenis_surat']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
        </div>

        <form id="form-pengajuan-masyarakat" class="p-8 space-y-8" action="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php#buat-pengajuan" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="jenis_surat" value="<?php echo htmlspecialchars($jenis_aktif); ?>">
            <?php if ($revisi_row): ?>
                <input type="hidden" name="revisi_id" value="<?php echo (int)$revisi_row['id']; ?>">
            <?php endif; ?>

            <!-- Info Tujuan Kasi Otomatis -->
            <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-2xl flex items-center justify-between text-xs text-blue-900">
                <div class="flex items-center space-x-2">
                    <span class="material-icons-outlined text-base">route</span>
                    <span>Alur Surat Ini: <strong>Front Office (PTSP)</strong> → <strong><?php echo htmlspecialchars(get_role_label(get_default_kasi_for_surat($jenis_aktif, $current_template['kasi_default'] ?? null))); ?></strong> → <strong>Sekcam</strong> → <strong>Camat (TTE QR Code)</strong></span>
                </div>
            </div>

            <?php if ($jenis_aktif === 'Dispensasi Nikah'): ?>
                <!-- FORM DISPENSASI NIKAH -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-xl">man</span>
                        <span>1. Biodata Calon Pengantin Laki-Laki</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Lengkap Laki-Laki</label>
                            <input type="text" name="nama_pemohon" value="<?php echo htmlspecialchars($revisi_form_data['nama_pemohon'] ?? ''); ?>" placeholder="Contoh: Miftah Mulfarid" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">NIK Pemohon</label>
                            <input type="text" value="<?php echo htmlspecialchars($user_nik); ?>" readonly class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-100 text-slate-500 font-medium text-sm select-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Agama</label>
                            <input type="text" name="agama" value="<?php echo htmlspecialchars($revisi_form_data['agama'] ?? 'Islam'); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" value="<?php echo htmlspecialchars($revisi_form_data['tempat_lahir'] ?? ''); ?>" placeholder="Contoh: Garut" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" value="<?php echo htmlspecialchars($revisi_form_data['tanggal_lahir'] ?? ''); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pekerjaan</label>
                            <input type="text" name="pekerjaan" value="<?php echo htmlspecialchars($revisi_form_data['pekerjaan'] ?? ''); ?>" placeholder="Contoh: Buruh Harian Lepas" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                            <textarea name="alamat" rows="2" placeholder="Contoh: Kp. Nagrog RT 007 RW 002 Desa Sarimukti..." required class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($revisi_form_data['alamat'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t border-slate-100">
                    <div class="flex items-center space-x-2 text-rose-600 font-extrabold text-base border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-xl">woman</span>
                        <span>2. Biodata Calon Pengantin Perempuan</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Lengkap Perempuan</label>
                            <input type="text" name="nama_pasangan" value="<?php echo htmlspecialchars($revisi_form_data['nama_pasangan'] ?? ''); ?>" placeholder="Contoh: Epa Sopiyah" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Agama</label>
                            <input type="text" name="agama_pasangan" value="<?php echo htmlspecialchars($revisi_form_data['agama_pasangan'] ?? 'Islam'); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pekerjaan</label>
                            <input type="text" name="pekerjaan_pasangan" value="<?php echo htmlspecialchars($revisi_form_data['pekerjaan_pasangan'] ?? ''); ?>" placeholder="Contoh: Wiraswasta" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir_pasangan" value="<?php echo htmlspecialchars($revisi_form_data['tempat_lahir_pasangan'] ?? ''); ?>" placeholder="Contoh: Tasikmalaya" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir_pasangan" value="<?php echo htmlspecialchars($revisi_form_data['tanggal_lahir_pasangan'] ?? ''); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                            <textarea name="alamat_pasangan" rows="2" placeholder="Contoh: Kp. Cidahu Tonggoh RT 001 RW 008 Desa Mekarwangi..." required class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-rose-600 bg-rose-50/30 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($revisi_form_data['alamat_pasangan'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pt-6 border-t border-slate-100">
                    <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-xl">event</span>
                        <span>3. Pelaksanaan Akad Nikah & Alasan Dispensasi</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tanggal Pelaksanaan Akad Nikah</label>
                            <input type="date" name="tanggal_akad" value="<?php echo htmlspecialchars($revisi_form_data['tanggal_akad'] ?? ''); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Alasan Permohonan Dispensasi</label>
                            <input type="text" name="alasan_dispensasi" value="<?php echo htmlspecialchars($revisi_form_data['alasan_dispensasi'] ?? ''); ?>" placeholder="Contoh: kurang dari 10 hari kerja sejak pendaftaran KUA..." required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- FORM DINAMIS UNTUK SURAT UMUM WARGA (SKTM, AHLI WARIS, SKU, IZIN KERAMAIAN, DLL) -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-xl">person</span>
                        <span>1. Identitas Warga Pemohon</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Lengkap Pemohon</label>
                            <input type="text" name="nama_pemohon" value="<?php echo htmlspecialchars($revisi_form_data['nama_pemohon'] ?? $user_nama); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-white text-slate-800 font-medium text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">NIK Pemohon</label>
                            <input type="text" value="<?php echo htmlspecialchars($user_nik); ?>" readonly class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 bg-slate-100 text-slate-500 font-medium text-sm select-none">
                        </div>
                    </div>
                </div>

                <?php if (!empty($text_fields)): ?>
                <div class="space-y-4 pt-6 border-t border-slate-100">
                    <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                        <span class="material-icons-outlined text-xl">list_alt</span>
                        <span>2. Data Substansi <?php echo htmlspecialchars($jenis_aktif); ?></span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($text_fields as $field): 
                            $fname = $field['name'];
                            $fval  = $revisi_form_data[$fname] ?? '';
                            $is_full_width = ($field['type'] === 'textarea') ? 'md:col-span-2' : '';
                        ?>
                            <div class="<?php echo $is_full_width; ?>">
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2"><?php echo htmlspecialchars($field['label']); ?></label>
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea name="<?php echo htmlspecialchars($fname); ?>" rows="3" <?php echo !empty($field['required']) ? 'required' : ''; ?> class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($fval); ?></textarea>
                                <?php else: ?>
                                    <input type="<?php echo htmlspecialchars($field['type']); ?>" name="<?php echo htmlspecialchars($fname); ?>" value="<?php echo htmlspecialchars($fval); ?>" <?php echo !empty($field['required']) ? 'required' : ''; ?> class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Upload Supporting Documents Section -->
            <div class="pt-6 border-t border-slate-100 space-y-6">
                <div>
                    <h4 class="font-bold text-slate-800 text-base">
                        <?php echo $revisi_row ? 'Unggah Ulang Dokumen Persyaratan (Kosongkan file yang tidak perlu diganti)' : 'Unggah Dokumen Persyaratan'; ?>
                    </h4>
                    <p class="text-xs text-slate-400 mt-0.5">Format file yang didukung: PDF, JPG, PNG. Maksimal ukuran file: 2MB per dokumen.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <?php 
                    $req_attr = $revisi_row ? '' : 'required';
                    if ($jenis_aktif === 'Dispensasi Nikah' || empty($file_fields)): ?>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">badge</span>
                            <p class="text-xs font-bold text-slate-700">KTP Pemohon</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file di sini'; ?></p>
                            <input type="file" name="file_ktp" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">folder_shared</span>
                            <p class="text-xs font-bold text-slate-700">Kartu Keluarga (KK)</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file di sini'; ?></p>
                            <input type="file" name="file_kk" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">home_work</span>
                            <p class="text-xs font-bold text-slate-700">Surat Pengantar RT/RW & Desa</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file di sini'; ?></p>
                            <input type="file" name="file_pengantar_rt" <?php echo $req_attr; ?> class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                    <?php else: ?>
                        <?php foreach ($file_fields as $ff): ?>
                            <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                                <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">upload_file</span>
                                <p class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($ff['label']); ?></p>
                                <p class="text-[11px] text-slate-400 mt-1 file-name-display"><?php echo $revisi_row ? 'Klik untuk ganti file' : 'Pilih file di sini'; ?></p>
                                <input type="file" name="<?php echo htmlspecialchars($ff['name']); ?>" <?php echo (!$revisi_row && !empty($ff['required'])) ? 'required' : ''; ?> class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.jpg,.jpeg,.png" onchange="this.previousElementSibling.textContent = this.files[0].name">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="<?php echo $revisi_row ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/20' : 'bg-blue-900 hover:bg-blue-800 shadow-blue-900/20'; ?> text-white font-extrabold px-10 py-4 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl"><?php echo $revisi_row ? 'published_with_changes' : 'send'; ?></span>
                    <span><?php echo $revisi_row ? 'Kirim Ulang Perbaikan Berkas ke PTSP' : 'Kirim Pengajuan ke Front Office (PTSP)'; ?></span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('form-pengajuan-masyarakat').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Kirim Berkas ke PTSP Kecamatan?',
        text: 'Pastikan data isian dan dokumen syarat yang diunggah sudah sesuai.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kirim Sekarang',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
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
            title: 'Pengajuan Terkirim!',
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
