<?php
$page_title = "Pengaturan Tanda Tangan & TTE";
require_once __DIR__ . '/../components/header.php';

// Verifikasi Hak Akses: Hanya Staf/Pimpinan Kecamatan & Admin
if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_role = $_SESSION['role'];
$msg_success = '';
$msg_error   = '';

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id               = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $judul_ttd        = trim($_POST['judul_ttd'] ?? 'Tanda Tangan Elektronik');
        $tipe_ttd         = in_array($_POST['tipe_ttd'] ?? '', ['elektronik_kotak', 'gambar_upload']) ? $_POST['tipe_ttd'] : 'elektronik_kotak';
        $teks_atas        = trim($_POST['teks_atas'] ?? 'Ditandatangani secara elektronik oleh');
        $jabatan          = trim($_POST['jabatan'] ?? 'CAMAT CISAYONG');
        $nama_pejabat     = trim($_POST['nama_pejabat'] ?? '');
        $pangkat_golongan = trim($_POST['pangkat_golongan'] ?? '');
        $nip              = trim($_POST['nip'] ?? '');
        $is_default       = !empty($_POST['is_default']) ? 1 : 0;
        $gambar_ttd       = trim($_POST['existing_gambar'] ?? '');

        // Handle File Upload if provided
        if (isset($_FILES['file_gambar']) && $_FILES['file_gambar']['error'] === UPLOAD_ERR_OK) {
            $fileTmp  = $_FILES['file_gambar']['tmp_name'];
            $fileName = $_FILES['file_gambar']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, ['png', 'jpg', 'jpeg', 'webp'])) {
                $newFileName = 'ttd_' . time() . '_' . uniqid() . '.' . $fileExt;
                $destDir     = __DIR__ . '/../assets/uploads/ttd/';
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }
                $destPath    = $destDir . $newFileName;
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $gambar_ttd = 'assets/uploads/ttd/' . $newFileName;
                } else {
                    $msg_error = "Gagal mengunggah berkas gambar tanda tangan.";
                }
            } else {
                $msg_error = "Format file tidak didukung! Harap unggah format PNG, JPG, JPEG, atau WEBP.";
            }
        }

        if (empty($msg_error)) {
            if ($tipe_ttd !== 'gambar_upload' && empty($nama_pejabat)) {
                $msg_error = "Nama Pejabat wajib diisi untuk format TTE Kotak Elektronik.";
            } else {
                // Untuk tipe gambar upload, jika nama/jabatan dikosongkan, beri nilai default agar database NOT NULL terpenuhi
                if ($tipe_ttd === 'gambar_upload') {
                    if (empty($nama_pejabat))     $nama_pejabat = 'Tanda Tangan Gambar';
                    if (empty($jabatan))          $jabatan = 'CAMAT CISAYONG';
                    if (empty($pangkat_golongan)) $pangkat_golongan = '-';
                    if (empty($nip))              $nip = '-';
                }

                // If set as default, reset other defaults first
                if ($is_default) {
                    $pdo->query("UPDATE `tanda_tangan` SET `is_default` = 0");
                }

                if ($id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE `tanda_tangan` SET 
                        `judul_ttd` = ?, 
                        `tipe_ttd` = ?, 
                        `teks_atas` = ?, 
                        `jabatan` = ?, 
                        `nama_pejabat` = ?, 
                        `pangkat_golongan` = ?, 
                        `nip` = ?, 
                        `gambar_ttd` = ?, 
                        `is_default` = ? 
                        WHERE `id` = ?");
                    $stmt->execute([
                        $judul_ttd, $tipe_ttd, $teks_atas, $jabatan, 
                        $nama_pejabat, $pangkat_golongan, $nip, 
                        $gambar_ttd, $is_default, $id
                    ]);
                    $msg_success = "Data pengaturan tanda tangan berhasil diperbarui.";
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO `tanda_tangan` 
                        (`judul_ttd`, `tipe_ttd`, `teks_atas`, `jabatan`, `nama_pejabat`, `pangkat_golongan`, `nip`, `gambar_ttd`, `is_default`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $judul_ttd, $tipe_ttd, $teks_atas, $jabatan, 
                        $nama_pejabat, $pangkat_golongan, $nip, 
                        $gambar_ttd, $is_default
                    ]);
                    $msg_success = "Tanda tangan baru berhasil ditambahkan.";
                }
            }
        }
    } elseif ($action === 'set_default') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->query("UPDATE `tanda_tangan` SET `is_default` = 0");
            $stmt = $pdo->prepare("UPDATE `tanda_tangan` SET `is_default` = 1 WHERE `id` = ?");
            $stmt->execute([$id]);
            $msg_success = "Tanda tangan default berhasil diperbarui.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Pastikan tidak menghapus jika hanya tersisa 1 tanda tangan
        $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM `tanda_tangan`")->fetchColumn();
        if ($totalCount <= 1) {
            $msg_error = "Tidak dapat menghapus! Minimal harus ada 1 pengaturan tanda tangan dalam sistem.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM `tanda_tangan` WHERE `id` = ?");
            $stmt->execute([$id]);
            // Jika yang terhapus adalah default, pilih salah satu sebagai default
            $defCount = (int)$pdo->query("SELECT COUNT(*) FROM `tanda_tangan` WHERE is_default = 1")->fetchColumn();
            if ($defCount === 0) {
                $pdo->query("UPDATE `tanda_tangan` SET `is_default` = 1 ORDER BY id ASC LIMIT 1");
            }
            $msg_success = "Pengaturan tanda tangan berhasil dihapus.";
        }
    }
}

// Fetch all signatures
$ttd_list = $pdo->query("SELECT * FROM `tanda_tangan` ORDER BY is_default DESC, id ASC")->fetchAll();

// Edit state if id provided in GET
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM `tanda_tangan` WHERE `id` = ?");
    $stmtEdit->execute([$edit_id]);
    $edit_data = $stmtEdit->fetch();
}
?>

<?php require_once __DIR__ . '/../components/sidebar_admin.php'; ?>

<main class="flex-1 bg-slate-50 min-h-screen p-4 sm:p-6 lg:p-8 space-y-6">
    
    <!-- Header Title -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-900 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/20">
                <span class="material-icons-outlined text-2xl">draw</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Pengaturan Tanda Tangan & TTE</h1>
                <p class="text-xs text-slate-500 font-medium">Kelola format kotak TTE Elektronik resmi Kecamatan & spesimen tanda tangan naskah dinas</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="#form-ttd" onclick="resetForm()" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-900/20 transition-all">
                <span class="material-icons-outlined text-sm">add_circle</span>
                <span>Tambah Tanda Tangan Baru</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($msg_success)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs font-bold flex items-center space-x-2">
            <span class="material-icons-outlined text-lg">check_circle</span>
            <span><?php echo htmlspecialchars($msg_success); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($msg_error)): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-2xl text-xs font-bold flex items-center space-x-2">
            <span class="material-icons-outlined text-lg">error</span>
            <span><?php echo htmlspecialchars($msg_error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Info Box: Mekanisme TTE -->
    <div class="bg-blue-50 border border-blue-200/80 rounded-2xl p-4 sm:p-5 flex items-start space-x-3 text-blue-950 text-xs">
        <span class="material-icons-outlined text-blue-900 text-xl flex-shrink-0 mt-0.5">info</span>
        <div class="space-y-1">
            <p class="font-bold text-sm">Alur Pembubuhan Tanda Tangan Resmi</p>
            <p class="text-blue-900/80 leading-relaxed">
                Tanda tangan pada surat <strong>tidak muncul otomatis</strong> begitu saja setelah surat diverifikasi. Pihak Camat atau pejabat berwenang harus mengklik tombol <strong>"Bubuhkan Tanda Tangan"</strong> pada berkas surat yang telah selesai ditelaah. Anda dapat membuat beberapa pilihan format tanda tangan (misal: Camat definitif, Plt. Camat, atau Sekretaris Camat atas nama Camat) dan memilih mana yang akan dibubuhkan.
            </p>
        </div>
    </div>

    <!-- Grid: List Signatures & Form -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Daftar Tanda Tangan yang Tersedia -->
        <div class="lg:col-span-7 space-y-4">
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <span class="material-icons-outlined text-blue-900 text-xl">folder_shared</span>
                        <h2 class="font-extrabold text-slate-900 text-base">Daftar Pilihan Tanda Tangan (<?php echo count($ttd_list); ?>)</h2>
                    </div>
                    <span class="text-xs text-slate-500 font-semibold">Tersimpan di Sistem</span>
                </div>

                <div class="divide-y divide-slate-100 p-4 sm:p-6 space-y-6">
                    <?php foreach ($ttd_list as $ttd): ?>
                        <div class="bg-white rounded-2xl border <?php echo $ttd['is_default'] ? 'border-blue-900 ring-2 ring-blue-900/20' : 'border-slate-200'; ?> p-5 space-y-4 hover:shadow-md transition-shadow">
                            
                            <!-- Header Card TTD -->
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($ttd['judul_ttd']); ?></h3>
                                        <?php if ($ttd['is_default']): ?>
                                            <span class="bg-blue-900 text-white text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">Default Aktif</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Tipe: <span class="font-semibold text-slate-700"><?php echo $ttd['tipe_ttd'] === 'elektronik_kotak' ? 'TTE Format Kotak Berlogo' : 'Gambar / Scan TTD & Stempel'; ?></span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php?edit=<?php echo $ttd['id']; ?>#form-ttd" class="p-2 text-blue-900 hover:bg-blue-50 rounded-xl transition-colors" title="Edit Tanda Tangan">
                                        <span class="material-icons-outlined text-base">edit</span>
                                    </a>
                                    <?php if (!$ttd['is_default']): ?>
                                        <button type="button" onclick="konfirmasiSetDefault(<?php echo $ttd['id']; ?>, '<?php echo htmlspecialchars(addslashes($ttd['judul_ttd'])); ?>')" class="px-2.5 py-1.5 text-xs font-bold text-blue-900 hover:bg-blue-50 rounded-xl border border-blue-200 transition-colors">
                                            Set Default
                                        </button>
                                        <button type="button" onclick="konfirmasiHapusTTD(<?php echo $ttd['id']; ?>, '<?php echo htmlspecialchars(addslashes($ttd['judul_ttd'])); ?>')" class="p-2 text-rose-600 hover:bg-rose-50 rounded-xl transition-colors" title="Hapus Tanda Tangan">
                                            <span class="material-icons-outlined text-base">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Live Visual Render: Kotak TTE seperti di Foto Dokumen Asli -->
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 flex justify-center">
                                <?php if ($ttd['tipe_ttd'] === 'elektronik_kotak'): ?>
                                    <!-- Kotak TTE Resmi Berbingkai (Sesuai Foto Pengguna) -->
                                    <div class="bg-white border-2 border-slate-600/70 p-3 sm:p-4 rounded-sm shadow-sm flex items-center gap-3.5 max-w-sm w-full font-serif">
                                        <div class="flex-shrink-0">
                                            <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Tasikmalaya" class="h-16 w-14 object-contain">
                                        </div>
                                        <div class="text-left text-slate-900 space-y-0.5 leading-tight">
                                            <p class="text-[10px] text-slate-700 italic"><?php echo htmlspecialchars($ttd['teks_atas']); ?></p>
                                            <p class="text-xs font-bold tracking-wide uppercase text-slate-900"><?php echo htmlspecialchars($ttd['jabatan']); ?></p>
                                            <p class="text-xs font-bold underline pt-1 text-slate-900"><?php echo htmlspecialchars($ttd['nama_pejabat']); ?></p>
                                            <?php if (!empty($ttd['pangkat_golongan'])): ?>
                                                <p class="text-[11px] text-slate-800"><?php echo htmlspecialchars($ttd['pangkat_golongan']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($ttd['nip'])): ?>
                                                <p class="text-[10px] text-slate-600 font-sans">NIP. <?php echo htmlspecialchars($ttd['nip']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Tipe Gambar / Scan TTD Stempel Murni (HANYA BENTUK GAMBAR SAJA, TULISAN CAMAT/NAMA/PEMBINA DIHAPUS) -->
                                    <div class="bg-white border border-slate-200 p-4 rounded-xl text-center max-w-sm w-full shadow-sm flex flex-col items-center justify-center min-h-[130px]">
                                        <?php if (!empty($ttd['gambar_ttd']) && file_exists(__DIR__ . '/../' . $ttd['gambar_ttd'])): ?>
                                            <img src="<?php echo BASE_URL . $ttd['gambar_ttd']; ?>" alt="Spesimen Gambar TTD" class="max-h-24 max-w-full object-contain mx-auto rounded">
                                        <?php else: ?>
                                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 text-slate-400 text-xs flex flex-col items-center justify-center w-full py-4">
                                                <span class="material-icons-outlined text-3xl text-slate-300 mb-1">image</span>
                                                <span>(Berkas Gambar TTD / Stempel)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right: Form Tambah / Edit Tanda Tangan -->
        <div id="form-ttd" class="lg:col-span-5 bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-xl space-y-6 sticky top-6">
            <div class="border-b border-slate-100 pb-4">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-blue-900 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200 mb-2 inline-block">
                    <?php echo $edit_data ? 'Mode Edit Data' : 'Tambah Baru'; ?>
                </span>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">
                    <?php echo $edit_data ? 'Edit Pengaturan Tanda Tangan' : 'Form Konfigurasi Tanda Tangan'; ?>
                </h2>
                <p class="text-xs text-slate-500">Sesuaikan teks jabatan, nama pejabat, format kotak TTE, atau upload gambar.</p>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="form-id" value="<?php echo htmlspecialchars($edit_data['id'] ?? ''); ?>">
                <input type="hidden" name="existing_gambar" value="<?php echo htmlspecialchars($edit_data['gambar_ttd'] ?? ''); ?>">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Judul Profil TTD <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="judul_ttd" id="form-judul" required 
                           value="<?php echo htmlspecialchars($edit_data['judul_ttd'] ?? 'Tanda Tangan Elektronik Camat Cisayong'); ?>" 
                           placeholder="Contoh: TTE Camat Definitif / a.n. Camat Sekcam" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tipe / Format Tanda Tangan <span class="text-rose-500">*</span>
                    </label>
                    <select name="tipe_ttd" id="form-tipe" onchange="toggleTipeTTD(this.value)" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-bold text-xs cursor-pointer">
                        <option value="elektronik_kotak" <?php echo ($edit_data['tipe_ttd'] ?? '') === 'elektronik_kotak' ? 'selected' : ''; ?>>
                            Tanda Tangan Elektronik (Kotak Berlogo Resmi - BSrE)
                        </option>
                        <option value="gambar_upload" <?php echo ($edit_data['tipe_ttd'] ?? '') === 'gambar_upload' ? 'selected' : ''; ?>>
                            Gambar / Scan Spesimen TTD & Stempel (Upload File)
                        </option>
                    </select>
                </div>

                <div id="section-upload-gambar" class="<?php echo ($edit_data['tipe_ttd'] ?? '') === 'gambar_upload' ? '' : 'hidden'; ?> bg-blue-50/60 p-4 rounded-2xl border border-blue-200/80 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-blue-950 uppercase tracking-wider mb-1.5">
                            Upload Berkas Gambar TTD / Stempel (.PNG transparan)
                        </label>
                        <input type="file" name="file_gambar" accept="image/png,image/jpeg,image/webp" 
                               class="w-full px-3 py-2 border border-blue-200 rounded-xl bg-white text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-900 file:text-white hover:file:bg-blue-800">
                        <p class="text-[11px] text-blue-900/70 mt-1">Disarankan format PNG transparan agar menyatu rapi pada kertas surat.</p>
                    </div>
                    <div class="bg-white/80 p-3 rounded-xl border border-blue-200 text-[11px] text-blue-900 leading-relaxed">
                        <span class="font-bold">Catatan Format Gambar Murni:</span> Tanda tangan bentuk gambar akan ditampilkan murni hanya gambarnya saja (nama Camat, Camat Cisayong, dan Pembina ditiadakan dari tampilan naskah dinas).
                    </div>
                </div>

                <div id="section-teks-detail" class="<?php echo ($edit_data['tipe_ttd'] ?? '') === 'gambar_upload' ? 'hidden' : ''; ?> space-y-4">
                    <div id="section-teks-atas">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Teks Keterangan Atas
                        </label>
                        <input type="text" name="teks_atas" id="form-teks-atas" 
                               value="<?php echo htmlspecialchars($edit_data['teks_atas'] ?? 'Ditandatangani secara elektronik oleh'); ?>" 
                               placeholder="Ditandatangani secara elektronik oleh" 
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Jabatan Penandatangan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="jabatan" id="form-jabatan" 
                               value="<?php echo htmlspecialchars($edit_data['jabatan'] ?? 'CAMAT CISAYONG'); ?>" 
                               placeholder="Contoh: CAMAT CISAYONG atau SEKRETARIS CAMAT" 
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-bold text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Pejabat (Dengan Gelar) <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="nama_pejabat" id="form-nama" 
                               value="<?php echo htmlspecialchars($edit_data['nama_pejabat'] ?? 'AYI MULYANA HERNIWAN, SE., M.Si.'); ?>" 
                               placeholder="Contoh: AYI MULYANA HERNIWAN, SE., M.Si." 
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-bold text-xs">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Pangkat / Golongan
                            </label>
                            <input type="text" name="pangkat_golongan" id="form-pangkat" 
                                   value="<?php echo htmlspecialchars($edit_data['pangkat_golongan'] ?? 'Pembina Tk. I IV/b'); ?>" 
                                   placeholder="Contoh: Pembina Tk. I IV/b" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Nomor Induk Pegawai (NIP)
                            </label>
                            <input type="text" name="nip" id="form-nip" 
                                   value="<?php echo htmlspecialchars($edit_data['nip'] ?? '19720315 199803 1 004'); ?>" 
                                   placeholder="Contoh: 19720315 199803 1 004" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-xs">
                        </div>
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center space-x-2.5 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" <?php echo !empty($edit_data['is_default']) ? 'checked' : ''; ?> class="w-4 h-4 text-blue-900 rounded border-slate-300 focus:ring-blue-900">
                        <span class="text-xs font-bold text-slate-800">Jadikan Tanda Tangan Default Pembubuhan</span>
                    </label>
                </div>

                <div class="pt-4 flex items-center gap-3">
                    <button type="submit" class="flex-1 bg-blue-900 hover:bg-blue-800 text-white py-3.5 rounded-xl font-bold text-xs shadow-xl shadow-blue-900/20 transition-all flex items-center justify-center space-x-2">
                        <span class="material-icons-outlined text-base">save</span>
                        <span><?php echo $edit_data ? 'Simpan Perubahan' : 'Tambahkan Tanda Tangan'; ?></span>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" class="px-4 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition-colors">
                            Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

    </div>

</main>

<script>
function toggleTipeTTD(tipe) {
    var uploadSec = document.getElementById('section-upload-gambar');
    var detailSec = document.getElementById('section-teks-detail');
    var inputNama = document.getElementById('form-nama');
    var inputJabatan = document.getElementById('form-jabatan');

    if (tipe === 'gambar_upload') {
        uploadSec.classList.remove('hidden');
        detailSec.classList.add('hidden');
        if (inputNama) inputNama.removeAttribute('required');
        if (inputJabatan) inputJabatan.removeAttribute('required');
    } else {
        uploadSec.classList.add('hidden');
        detailSec.classList.remove('hidden');
        if (inputNama) inputNama.setAttribute('required', 'required');
        if (inputJabatan) inputJabatan.setAttribute('required', 'required');
    }
}

function resetForm() {
    document.getElementById('form-id').value = '';
    document.getElementById('form-judul').value = '';
    document.getElementById('form-tipe').value = 'elektronik_kotak';
    toggleTipeTTD('elektronik_kotak');
}

function konfirmasiHapusTTD(id, judul) {
    Swal.fire({
        title: 'Hapus Format Tanda Tangan?',
        text: 'Apakah Anda yakin ingin menghapus profil "' + judul + '"?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-6 py-2.5 bg-rose-600 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo BASE_URL; ?>admin/pengaturan_ttd.php';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function konfirmasiSetDefault(id, judul) {
    Swal.fire({
        title: 'Jadikan Default Pembubuhan?',
        text: 'Tanda tangan "' + judul + '" akan otomatis dipilih saat Camat membubuhkan tanda tangan pada dokumen surat.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Jadikan Default',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-6 py-2.5 bg-blue-900 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo BASE_URL; ?>admin/pengaturan_ttd.php';
            form.innerHTML = '<input type="hidden" name="action" value="set_default"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initial check on load
document.addEventListener('DOMContentLoaded', function() {
    var selTipe = document.getElementById('form-tipe');
    if (selTipe) {
        toggleTipeTTD(selTipe.value);
    }
    
    <?php if (!empty($msg_success)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: <?php echo json_encode($msg_success); ?>,
            timer: 2500,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php elseif (!empty($msg_error)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: <?php echo json_encode($msg_error); ?>,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>

