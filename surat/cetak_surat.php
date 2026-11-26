<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$page_title = "Unduh & Cetak PDF Surat Resmi Ber-TTE";
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_role = $_SESSION['role'] ?? 'masyarakat';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Jika tidak ada ID, cari pengajuan terakhir yang disetujui sesuai role
if (!$id) {
    if (is_kecamatan_role($user_role)) {
        $stmtFirst = $pdo->query("SELECT id FROM `pengajuan` WHERE status='disetujui' ORDER BY waktu_verifikasi DESC LIMIT 1");
    } else {
        $stmtFirst = $pdo->prepare("SELECT id FROM `pengajuan` WHERE status='disetujui' AND user_id = ? ORDER BY waktu_verifikasi DESC LIMIT 1");
        $stmtFirst->execute([$_SESSION['user_id']]);
    }
    $id = (int)$stmtFirst->fetchColumn();
}

if (!$id) {
    include __DIR__ . '/../components/header.php';
    if (is_kecamatan_role($user_role)) include __DIR__ . '/../components/sidebar_admin.php';
    elseif ($user_role === 'desa') include __DIR__ . '/../components/sidebar_desa.php';
    else include __DIR__ . '/../components/sidebar_masyarakat.php';
    echo '<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8"><div class="bg-white p-8 rounded-3xl border border-slate-200 text-center"><h3 class="font-bold text-xl text-slate-800">Belum ada surat yang selesai diproses untuk akun Anda.</h3></div></main>';
    include __DIR__ . '/../components/footer.php';
    exit;
}

// Handle POST: Pembubuhan atau Penarikan Tanda Tangan oleh Camat / Staf Kecamatan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_kecamatan_role($user_role)) {
    $action = $_POST['action'] ?? '';
    if ($action === 'bubuhkan_ttd') {
        $ttd_id = (int)($_POST['ttd_id'] ?? 0);
        if ($ttd_id <= 0) {
            $ttd_id = (int)$pdo->query("SELECT id FROM `tanda_tangan` WHERE is_default = 1 LIMIT 1")->fetchColumn();
        }
        $operator_label = ($_SESSION['nama'] ?? 'Pejabat') . ' (' . get_role_label($user_role) . ')';
        $stmtTtd = $pdo->prepare("UPDATE `pengajuan` SET is_ttd_dibubuhkan = 1, ttd_id = ?, waktu_ttd_dibubuhkan = NOW(), ttd_dibubuhkan_oleh = ? WHERE id = ?");
        $stmtTtd->execute([$ttd_id, $operator_label, $id]);
        header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&signed=1");
        exit;
    } elseif ($action === 'tarik_ttd') {
        $stmtTtd = $pdo->prepare("UPDATE `pengajuan` SET is_ttd_dibubuhkan = 0, waktu_ttd_dibubuhkan = NULL, ttd_dibubuhkan_oleh = NULL WHERE id = ?");
        $stmtTtd->execute([$id]);
        header("Location: " . BASE_URL . "surat/cetak_surat.php?id=" . $id . "&unsigned=1");
        exit;
    }
}

// Fetch pengajuan
$stmtP = $pdo->prepare("SELECT p.*, u.nama, u.username, u.instansi_desa, u.phone, u.email FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id WHERE p.id = ?");
$stmtP->execute([$id]);
$pengajuan = $stmtP->fetch();

if (!$pengajuan) {
    include __DIR__ . '/../components/header.php';
    echo '<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8"><div class="bg-white p-8 rounded-3xl border border-slate-200 text-center"><h3 class="font-bold text-xl text-slate-800">Data surat tidak ditemukan.</h3></div></main>';
    include __DIR__ . '/../components/footer.php';
    exit;
}

// Validasi otorisasi kepemilikan surat
if (!is_kecamatan_role($user_role) && $pengajuan['user_id'] != $_SESSION['user_id']) {
    include __DIR__ . '/../components/header.php';
    echo '<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8"><div class="bg-white p-8 rounded-3xl border border-rose-200 text-center"><h3 class="font-bold text-xl text-rose-800">Akses Ditolak: Anda tidak berhak melihat atau mengunduh surat milik pengguna lain.</h3></div></main>';
    include __DIR__ . '/../components/footer.php';
    exit;
}

include __DIR__ . '/../components/header.php';

// Cek sesi untuk menentukan sidebar yang disertakan
if (is_kecamatan_role($user_role)) {
    include __DIR__ . '/../components/sidebar_admin.php';
} elseif ($user_role === 'desa') {
    include __DIR__ . '/../components/sidebar_desa.php';
} else {
    include __DIR__ . '/../components/sidebar_masyarakat.php';
}

$jenis_surat = $pengajuan['jenis_surat'];
$data_form = json_decode($pengajuan['data_form'], true) ?? [];
$waktu_verif = !empty($pengajuan['waktu_verifikasi']) ? strtotime($pengajuan['waktu_verifikasi']) : time();

function tanggal_indonesia($waktu) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return date('d', $waktu) . ' ' . $bulan[(int)date('m', $waktu)] . ' ' . date('Y', $waktu);
}
$tanggal_surat_tampil = tanggal_indonesia($waktu_verif);
$nomor_surat = $pengajuan['nomor_surat_generated'] ?? ($jenis_surat === 'Dispensasi Nikah' ? '400.8/116/Kec/2026' : 'B/900.10.2.4/134/Kec.Pum/2026');
$tte_kode = $pengajuan['tte_kode'] ?? ('TTE-CSY-' . date('Ymd', $waktu_verif) . '-' . str_pad($pengajuan['id'], 4, '0', STR_PAD_LEFT));

// Status Tanda Tangan
$is_ttd_dibubuhkan = (int)($pengajuan['is_ttd_dibubuhkan'] ?? 0);
$all_ttd = $pdo->query("SELECT * FROM `tanda_tangan` ORDER BY is_default DESC, id ASC")->fetchAll();
$active_ttd_id = (int)($pengajuan['ttd_id'] ?? 0);
$current_ttd = null;

if ($active_ttd_id > 0) {
    foreach ($all_ttd as $t) {
        if ((int)$t['id'] === $active_ttd_id) {
            $current_ttd = $t;
            break;
        }
    }
}
if (!$current_ttd) {
    foreach ($all_ttd as $t) {
        if ($t['is_default']) {
            $current_ttd = $t;
            break;
        }
    }
}
if (!$current_ttd && !empty($all_ttd)) {
    $current_ttd = $all_ttd[0];
}

// Data Telepon Pemohon untuk Notifikasi WhatsApp Resmi
$user_phone = $pengajuan['phone'] ?? '';
if (empty($user_phone) && !empty($data_form)) {
    $user_phone = $data_form['no_hp'] ?? ($data_form['telepon'] ?? ($data_form['nomor_hp'] ?? ($data_form['kontak'] ?? ($data_form['wa'] ?? ''))));
}
$wa_notif_url = get_whatsapp_notification_url($user_phone, $pengajuan['nama'], $jenis_surat, $nomor_surat, $id, 'disetujui');

// Query Dokumen yang telah disetujui untuk Dropdown Pemilih Cepat
if (is_kecamatan_role($user_role)) {
    $stmtApprovedDocs = $pdo->query("SELECT id, jenis_surat, nomor_surat_generated, waktu_pengajuan FROM `pengajuan` WHERE status = 'disetujui' ORDER BY id DESC LIMIT 30");
} else {
    $stmtApprovedDocs = $pdo->prepare("SELECT id, jenis_surat, nomor_surat_generated, waktu_pengajuan FROM `pengajuan` WHERE status = 'disetujui' AND user_id = ? ORDER BY id DESC LIMIT 30");
    $stmtApprovedDocs->execute([$_SESSION['user_id']]);
}
$approved_docs_list = $stmtApprovedDocs->fetchAll();

// Fetch Template
$stmtT = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
$stmtT->execute([$jenis_surat]);
$template = $stmtT->fetch();

if (!$template) {
    $template = [
        'judul_surat'           => strtoupper($jenis_surat),
        'sifat'                 => '',
        'lampiran'              => '',
        'perihal'               => '',
        'tujuan'                => '',
        'pembuka'               => 'Berdasarkan permohonan yang bersangkutan serta hasil verifikasi berjenjang Kecamatan Cisayong, dengan ini menerangkan bahwa:',
        'penutup'               => 'Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.',
        'penandatangan_nama'    => 'AYI MULYANA HERNIWAN, SE., M.Si.',
        'penandatangan_nip'     => '19720315 199803 1 004',
        'penandatangan_jabatan' => 'CAMAT CISAYONG',
        'penandatangan_pangkat' => 'Pembina : Tk. I IV/b',
        'is_tte'                => 1
    ];
}

// Gunakan redaksi hasil pemeriksaan Kasi Terkait jika ada
$pembuka_final = !empty($pengajuan['redaksi_kasi_pembuka']) ? $pengajuan['redaksi_kasi_pembuka'] : $template['pembuka'];
$penutup_final = !empty($pengajuan['redaksi_kasi_penutup']) ? $pengajuan['redaksi_kasi_penutup'] : $template['penutup'];
$camat_nama_final = !empty($pengajuan['tte_camat_oleh']) ? strtoupper($pengajuan['tte_camat_oleh']) : $template['penandatangan_nama'];
$camat_nip_final  = !empty($pengajuan['tte_camat_nip']) ? $pengajuan['tte_camat_nip'] : $template['penandatangan_nip'];

// Penamaan file PDF dinamis
$nama_pemohon = $data_form['nama_pemohon'] ?? ($pengajuan['instansi_desa'] ?? $pengajuan['nama']);
$clean_nama = preg_replace('/[^A-Za-z0-9]/', '_', trim($nama_pemohon));
$clean_nomor = preg_replace('/[^A-Za-z0-9]/', '_', trim($nomor_surat));
$pdf_filename = "Surat_" . preg_replace('/[^A-Za-z0-9]/', '_', $jenis_surat) . "_" . $clean_nama . "_" . $clean_nomor;
?>

<!-- html2pdf.js CDN untuk Unduh Langsung File PDF -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Tinos:ital,wght@0,400;0,700;1,400;1,700&display=swap');

/* Pembungkus Kertas di Layar Web */
.paper-wrapper {
    display: flex;
    justify-content: center;
    padding: 10px 0 30px 0;
    width: 100%;
}

/* Lembar Naskah Dinas Resmi Standar A4 Fisik (210mm x 297mm) */
.sheet-a4 {
    width: 210mm;
    min-height: 297mm;
    background: #ffffff;
    /* Margin standar kedinasan: Atas 12mm, Kanan 18mm, Bawah 12mm, Kiri 22mm */
    padding: 12mm 18mm 12mm 22mm;
    box-sizing: border-box;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    color: #000000;
    font-family: 'Tinos', 'Times New Roman', Times, serif;
    font-size: 11pt;
    line-height: 1.35;
    position: relative;
    border: 1px solid #cbd5e1;
    margin: 0 auto;
}

@media print {
    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        color: #000000 !important;
    }
    .no-print {
        display: none !important;
    }
    aside, header {
        display: none !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    .paper-wrapper {
        padding: 0 !important;
        background: transparent !important;
    }
    .sheet-a4 {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        min-height: auto !important;
    }
    @page {
        size: A4 portrait;
        margin: 12mm 18mm 12mm 22mm;
    }
}
</style>

<script>
document.title = <?php echo json_encode($pdf_filename); ?>;

function unduhPdfLangsung() {
    const source = document.getElementById('lembar-surat-resmi');
    if (!source) return;
    
    Swal.fire({
        title: 'Menyiapkan PDF Resmi...',
        text: 'Sedang memproses naskah dinas format A4 (1 halaman utuh).',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // 1. Buat kontainer kloning terisolasi di sudut (0, 0)
    // Hal ini krusial agar html2canvas TIDAK terpotong oleh sidebar dashboard di sebelah kiri!
    const container = document.createElement('div');
    container.id = 'temp-pdf-render-box';
    container.style.position = 'fixed';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '794px';
    container.style.zIndex = '999999';
    container.style.background = '#ffffff';
    container.style.margin = '0';
    container.style.padding = '0';

    const clone = source.cloneNode(true);
    clone.style.boxShadow = 'none';
    clone.style.border = 'none';
    clone.style.margin = '0';
    clone.style.width = '794px';
    clone.style.maxWidth = '794px';
    clone.style.boxSizing = 'border-box';
    clone.style.minHeight = 'auto';
    clone.style.height = 'auto';

    container.appendChild(clone);
    document.body.appendChild(container);

    const opt = {
        margin:       [2, 0, 2, 0], // Margin atas/bawah tipis 2mm agar batas naskah dinas pas 1 lembar A4
        filename:     <?php echo json_encode($pdf_filename . '.pdf'); ?>,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { 
            scale: 2, 
            useCORS: true, 
            logging: false,
            letterRendering: true,
            scrollX: 0,
            scrollY: 0,
            width: 794
        },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak:    { mode: 'avoid-all' }
    };

    html2pdf().set(opt).from(clone).save().then(() => {
        if (container.parentNode) document.body.removeChild(container);
        Swal.close();
        Swal.fire({
            title: 'Berhasil Diunduh!',
            text: 'Dokumen PDF resmi berformat A4 telah tersimpan dengan rapi.',
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            confirmButtonText: 'Selesai'
        });
    }).catch(err => {
        if (container.parentNode) document.body.removeChild(container);
        Swal.fire('Gagal', 'Terjadi kendala saat mengunduh PDF: ' + err, 'error');
    });
}
</script>

<!-- Main Content Area -->
<main class="flex-1 p-4 sm:p-6 md:p-8 max-w-7xl mx-auto space-y-6 overflow-y-auto bg-slate-100">
    
    <!-- Feedback Alerts -->
    <?php if (isset($_GET['signed'])): ?>
        <div class="no-print bg-emerald-50 border-2 border-emerald-300 text-emerald-900 p-4 sm:p-5 rounded-3xl shadow-md flex items-center space-x-3">
            <span class="material-icons-outlined text-2xl text-emerald-700 flex-shrink-0">check_circle</span>
            <div>
                <h4 class="font-extrabold text-sm">Tanda Tangan Berhasil Dibubuhkan ke Surat!</h4>
                <p class="text-xs text-emerald-700">Kotak tanda tangan resmi telah aktif dan tercetak pada lembar surat di bawah.</p>
            </div>
        </div>
    <?php elseif (isset($_GET['unsigned'])): ?>
        <div class="no-print bg-amber-50 border-2 border-amber-300 text-amber-900 p-4 sm:p-5 rounded-3xl shadow-md flex items-center space-x-3">
            <span class="material-icons-outlined text-2xl text-amber-700 flex-shrink-0">info</span>
            <div>
                <h4 class="font-extrabold text-sm">Tanda Tangan Berhasil Ditarik / Dibatalkan!</h4>
                <p class="text-xs text-amber-800">Tanda tangan telah dihapus dari surat dan kini berstatus belum bertandatangan.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Signature Status Banner for Kecamatan Staff -->
    <?php if (is_kecamatan_role($user_role)): ?>
        <div class="no-print p-4 sm:p-5 rounded-3xl border <?php echo $is_ttd_dibubuhkan ? 'bg-emerald-50/90 border-emerald-300' : 'bg-amber-50/90 border-amber-300'; ?> shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <span class="material-icons-outlined text-2xl <?php echo $is_ttd_dibubuhkan ? 'text-emerald-700' : 'text-amber-700'; ?> flex-shrink-0 mt-0.5">
                    <?php echo $is_ttd_dibubuhkan ? 'verified' : 'pending_actions'; ?>
                </span>
                <div>
                    <h3 class="font-extrabold text-sm text-slate-900">
                        <?php if ($is_ttd_dibubuhkan): ?>
                            Tanda Tangan Telah Dibubuhkan pada Surat
                        <?php else: ?>
                            Tanda Tangan Belum Dibubuhkan ke Surat Ini
                        <?php endif; ?>
                    </h3>
                    <p class="text-xs <?php echo $is_ttd_dibubuhkan ? 'text-emerald-800' : 'text-amber-800'; ?> mt-0.5">
                        <?php if ($is_ttd_dibubuhkan): ?>
                            Dibubuhkan oleh: <strong><?php echo htmlspecialchars($pengajuan['ttd_dibubuhkan_oleh'] ?? 'Camat'); ?></strong>
                            <?php if (!empty($pengajuan['waktu_ttd_dibubuhkan'])): ?>
                                pada <?php echo date('d M Y, H:i', strtotime($pengajuan['waktu_ttd_dibubuhkan'])); ?>
                            <?php endif; ?>
                            • Format TTD: <strong><?php echo htmlspecialchars($current_ttd['judul_ttd'] ?? 'TTE Resmi'); ?></strong>
                        <?php else: ?>
                            Surat ini telah disetujui, namun tanda tangan <strong>tidak muncul otomatis</strong>. Silakan klik tombol di samping untuk membubuhkan tanda tangan yang diinginkan.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <?php if ($is_ttd_dibubuhkan): ?>
                    <button type="button" onclick="openModalTTD()" class="px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-md transition-all flex items-center space-x-1.5">
                        <span class="material-icons-outlined text-sm">swap_horiz</span>
                        <span>Ganti Tanda Tangan</span>
                    </button>
                    <form method="POST" action="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $id; ?>" onsubmit="return confirm('Tarik / batalkan tanda tangan dari dokumen ini?');" class="inline">
                        <input type="hidden" name="action" value="tarik_ttd">
                        <button type="submit" class="px-3.5 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-colors flex items-center space-x-1">
                            <span class="material-icons-outlined text-sm">remove_circle_outline</span>
                            <span>Tarik TTD</span>
                        </button>
                    </form>
                <?php else: ?>
                    <button type="button" onclick="openModalTTD()" class="px-5 py-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-extrabold shadow-lg shadow-emerald-700/25 hover:scale-105 transition-all flex items-center space-x-2">
                        <span class="material-icons-outlined text-base">draw</span>
                        <span>Bubuhkan Tanda Tangan Sekarang</span>
                    </button>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" class="px-3.5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-colors flex items-center space-x-1" title="Buka Pengaturan Tanda Tangan">
                    <span class="material-icons-outlined text-sm">settings</span>
                    <span>Pengaturan TTD</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Web Action Controls (Hidden on print) -->
    <div class="no-print bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">folder_open</span>
                <span><?php echo htmlspecialchars($jenis_surat); ?></span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Lembar Naskah Dinas Resmi</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                Nomor Surat: <strong><?php echo htmlspecialchars($nomor_surat); ?></strong> • Pemohon: <strong><?php echo htmlspecialchars($nama_pemohon); ?></strong>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto justify-end">
            <!-- Dropdown Pindah Surat Lain (Jika ada) -->
            <?php if (!empty($approved_docs_list) && count($approved_docs_list) > 1): ?>
                <div class="w-full sm:w-auto">
                    <select onchange="if(this.value) window.location.href='<?php echo BASE_URL; ?>surat/cetak_surat.php?id=' + this.value" class="w-full sm:w-auto px-3.5 py-3 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-bold text-xs cursor-pointer focus:outline-none focus:border-blue-900">
                        <option value="">-- Pilih Dokumen Selesai Lain --</option>
                        <?php foreach ($approved_docs_list as $adoc): ?>
                            <option value="<?php echo $adoc['id']; ?>" <?php echo ((int)$adoc['id'] === (int)$id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($adoc['jenis_surat']); ?> (<?php echo htmlspecialchars($adoc['nomor_surat_generated'] ?? 'ID: ' . $adoc['id']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Tombol Kirim Notifikasi WhatsApp ke Pemohon -->
            <?php if ($is_ttd_dibubuhkan === 1): ?>
                <?php if (!empty($wa_notif_url)): ?>
                    <a href="<?php echo htmlspecialchars($wa_notif_url); ?>" target="_blank" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-5 py-3 rounded-xl shadow-lg shadow-emerald-600/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 text-xs" title="Kirim Notifikasi WhatsApp Resmi ke Pemohon">
                        <span class="material-icons-outlined text-base">chat</span>
                        <span>Kirim WA ke Pemohon</span>
                    </a>
                <?php else: ?>
                    <button type="button" onclick="kirimManualWA('<?php echo htmlspecialchars(addslashes($nama_pemohon)); ?>', '<?php echo htmlspecialchars(addslashes($nomor_surat)); ?>', <?php echo $id; ?>)" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-5 py-3 rounded-xl shadow-lg shadow-emerald-600/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 text-xs" title="Kirim Notifikasi WhatsApp ke Pemohon">
                        <span class="material-icons-outlined text-base">chat</span>
                        <span>Kirim WA ke Pemohon</span>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Tombol Unduh PDF Langsung -->
            <button onclick="unduhPdfLangsung()" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-extrabold px-5 py-3 rounded-xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 text-xs">
                <span class="material-icons-outlined text-base">picture_as_pdf</span>
                <span>Unduh File PDF (.pdf)</span>
            </button>
            <!-- Tombol Cetak / Save PDF Browser -->
            <button onclick="window.print()" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white font-bold px-5 py-3 rounded-xl shadow-lg hover:scale-105 transition-all flex items-center justify-center space-x-2 text-xs">
                <span class="material-icons-outlined text-base">print</span>
                <span>Cetak Lembar A4</span>
            </button>
            <!-- Tombol Download Word -->
            <a href="<?php echo BASE_URL; ?>surat/download_word.php?id=<?php echo $id; ?>" class="w-full sm:w-auto bg-teal-800 hover:bg-teal-700 text-white font-bold px-5 py-3 rounded-xl shadow-lg shadow-teal-800/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 text-xs">
                <span class="material-icons-outlined text-base">description</span>
                <span>Unduh Word (.doc)</span>
            </a>
        </div>
    </div>

    <!-- Official Paper Sheet Container (Format Presisi Kertas A4 Naskah Dinas) -->
    <div class="paper-wrapper">
        <div id="lembar-surat-resmi" class="sheet-a4">
            
            <!-- KOP SURAT RESMI KECAMATAN CISAYONG (GARIS GANDA DINAS KOKOH) -->
            <table style="width: 100%; border-bottom: 3px double #000000; padding-bottom: 8px; margin-bottom: 12px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 75px; vertical-align: middle; text-align: center; padding-right: 12px;">
                        <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Kabupaten Tasikmalaya" style="width: 68px; height: auto; display: block; margin: 0 auto;">
                    </td>
                    <td style="vertical-align: middle; text-align: center; padding-right: 25px;">
                        <div style="font-size: 13pt; font-weight: bold; letter-spacing: 0.5px; line-height: 1.2; font-family: 'Times New Roman', Times, serif;">PEMERINTAH KABUPATEN TASIKMALAYA</div>
                        <div style="font-size: 16.5pt; font-weight: bold; letter-spacing: 1px; line-height: 1.2; margin: 2px 0; font-family: 'Times New Roman', Times, serif;">KECAMATAN CISAYONG</div>
                        <div style="font-size: 9pt; line-height: 1.25; font-family: 'Times New Roman', Times, serif;">
                            Jalan. Raya Cisayong Nomor. 20 Telepon. (0265) 420456<br>
                            Faksimile 0265) 420456 (E-mail) cisayong@tasikmalayakab.go.id<br>
                            Cisayong – 46153
                        </div>
                    </td>
                </tr>
            </table>

            <!-- ISI KONTEN SURAT BERDASARKAN JENIS SURAT -->
            <?php if ($jenis_surat === 'Alokasi Dana Daerah (ADD)'): ?>
                <!-- MODEL SURAT DINAS RESMI (ADD DESA SESUAI TEMPLATE DINAS OPD) -->
                <table style="width: 100%; font-size: 11pt; margin-bottom: 10px;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="width: 56%; vertical-align: top;">
                            <table style="width: 100%; font-size: 11pt; line-height: 1.35;" cellpadding="1" cellspacing="0">
                                <tr><td style="width: 26%;">Nomor</td><td style="width: 4%;">:</td><td><?php echo htmlspecialchars($nomor_surat); ?></td></tr>
                                <tr><td>Sifat</td><td>:</td><td><?php echo htmlspecialchars($template['sifat'] ?: 'Biasa'); ?></td></tr>
                                <tr><td>Lampiran</td><td>:</td><td><?php echo htmlspecialchars($template['lampiran'] ?: '1 (satu) Berkas'); ?></td></tr>
                                <tr><td>Hal</td><td>:</td><td><strong><?php echo htmlspecialchars($template['perihal'] ?: 'Permohonan Rekomendasi Pencairan Alokasi Dana Desa (ADD)'); ?></strong></td></tr>
                            </table>
                        </td>
                        <td style="width: 44%; vertical-align: top; text-align: left; padding-left: 20px; line-height: 1.35;">
                            Cisayong, <?php echo $tanggal_surat_tampil; ?><br><br>
                            Kepada:<br>
                            Yth. <strong>Kepala Dinas Pemberdayaan Masyarakat dan Desa</strong><br>
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kabupaten Tasikmalaya<br>
                            di<br>
                            &nbsp;&nbsp;&nbsp;&nbsp;<strong>SINGAPARNA</strong>
                        </td>
                    </tr>
                </table>

                <!-- Salam Pembuka Tulisan Arab -->
                <div style="text-align: center; margin: 8px 0 10px 0; font-family: 'Traditional Arabic', 'Amiri', 'Times New Roman', serif;">
                    <div style="font-size: 14pt; font-weight: bold; line-height: 1.2;">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</div>
                    <div style="font-size: 12pt; margin-top: 3px; font-weight: bold; line-height: 1.2;">السَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</div>
                </div>

                <div style="font-size: 11pt; text-align: justify; margin-bottom: 8px; line-height: 1.4;">
                    <p style="text-indent: 35px; margin: 0 0 6px 0;">
                        <?php 
                            $desa_nama_txt = $data_form['instansi_desa'] ?? ($desa_pemohon ?: 'Desa');
                            $no_surat_desa_txt = !empty($data_form['nomor_surat_desa']) ? ' Nomor: ' . htmlspecialchars($data_form['nomor_surat_desa']) : '';
                        ?>
                        Berdasarkan Surat Permohonan dari Kepala Desa <?php echo htmlspecialchars($desa_nama_txt); ?><?php echo $no_surat_desa_txt; ?> perihal Permohonan Rekomendasi Pencairan Alokasi Dana Desa (ADD) <?php echo htmlspecialchars($data_form['tahap_alokasi'] ?? 'Tahap I'); ?> Tahun Anggaran <?php echo date('Y'); ?>, setelah diadakan penelitian kelengkapan berkas/persyaratan administrasi ternyata telah memenuhi ketentuan yang berlaku.
                    </p>
                    <p style="text-indent: 35px; margin: 0 0 6px 0;">
                        Sehubungan hal tersebut di atas, bersama ini kami sampaikan Rekomendasi Pencairan Alokasi Dana Desa (ADD) Desa <?php echo htmlspecialchars($desa_nama_txt); ?> <?php echo htmlspecialchars($data_form['tahap_alokasi'] ?? 'Tahap I'); ?> dengan rincian sebagai berikut:
                    </p>
                </div>

                <div style="margin: 0 0 8px 25px;">
                    <table style="width: 95%; font-size: 11pt; line-height: 1.3;" cellpadding="2" cellspacing="0">
                        <tr>
                            <td style="width: 32%; vertical-align: top;">Nama Kepala Desa</td>
                            <td style="width: 3%; vertical-align: top;">:</td>
                            <td style="vertical-align: top;"><strong><?php echo htmlspecialchars($data_form['nama_kades'] ?? '-'); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Pemerintah Desa</td>
                            <td style="vertical-align: top;">:</td>
                            <td style="vertical-align: top;"><strong><?php echo htmlspecialchars($desa_nama_txt); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Tahap Pencairan</td>
                            <td style="vertical-align: top;">:</td>
                            <td style="vertical-align: top;"><strong><?php echo htmlspecialchars($data_form['tahap_alokasi'] ?? 'Tahap I'); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Jumlah Dana (ADD)</td>
                            <td style="vertical-align: top;">:</td>
                            <td style="vertical-align: top;"><strong>Rp <?php echo is_numeric($data_form['jumlah_dana'] ?? '') ? number_format((float)$data_form['jumlah_dana'], 0, ',', '.') : htmlspecialchars($data_form['jumlah_dana'] ?? '0'); ?></strong></td>
                        </tr>
                        <tr>
                            <td style="vertical-align: top;">Peruntukan Kegiatan</td>
                            <td style="vertical-align: top;">:</td>
                            <td style="vertical-align: top; text-align: justify;"><em><?php echo htmlspecialchars($data_form['peruntukan_dana'] ?? '-'); ?></em></td>
                        </tr>
                    </table>
                </div>

                <div style="font-size: 11pt; text-align: justify; margin-bottom: 6px; line-height: 1.4;">
                    <p style="text-indent: 35px; margin: 0;">
                        Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya, atas perhatian dan kerjasamanya kami ucapkan terima kasih.
                    </p>
                </div>

                <!-- Salam Penutup Tulisan Arab -->
                <div style="text-align: left; margin: 6px 0 12px 0; font-family: 'Traditional Arabic', 'Amiri', 'Times New Roman', serif;">
                    <div style="font-size: 12pt; font-weight: bold; line-height: 1.2;">وَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ</div>
                </div>

            <?php elseif ($jenis_surat === 'Dispensasi Nikah'): ?>
                <!-- MODEL DISPENSASI NIKAH -->
                <div style="text-align: center; margin-bottom: 10px;">
                    <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                        <?php echo htmlspecialchars($template['judul_surat']); ?>
                    </div>
                    <div style="font-size: 11pt; margin-top: 2px;">
                        NOMOR: <?php echo htmlspecialchars($nomor_surat); ?>
                    </div>
                </div>

                <div style="font-size: 10.5pt; text-align: justify; margin-bottom: 6px; line-height: 1.3;">
                    <p style="text-indent: 35px; margin: 0;">
                        <?php echo nl2br(htmlspecialchars($pembuka_final)); ?>
                    </p>
                </div>

                <div style="margin: 0 0 6px 20px;">
                    <table style="width: 96%; font-size: 10.5pt; line-height: 1.25;" cellpadding="1" cellspacing="0">
                        <?php 
                            $calon = $data_form['nama_pemohon'] ?? 'MIFTAH MULFARID';
                            $ttl   = ($data_form['tempat_lahir'] ?? 'Garut') . ', ' . (!empty($data_form['tanggal_lahir']) ? tanggal_indonesia(strtotime($data_form['tanggal_lahir'])) : '-');
                            $agama = $data_form['agama'] ?? 'Islam';
                            $pekerjaan = $data_form['pekerjaan'] ?? '-';
                            $alamat = $data_form['alamat'] ?? '-';
                            
                            $pasangan = $data_form['nama_pasangan'] ?? '-';
                            $ttl_pasangan = ($data_form['tempat_lahir_pasangan'] ?? 'Tasikmalaya') . ', ' . (!empty($data_form['tanggal_lahir_pasangan']) ? tanggal_indonesia(strtotime($data_form['tanggal_lahir_pasangan'])) : '-');
                            $agama_pasangan = $data_form['agama_pasangan'] ?? 'Islam';
                            $pekerjaan_pasangan = $data_form['pekerjaan_pasangan'] ?? '-';
                            $alamat_pasangan = $data_form['alamat_pasangan'] ?? '-';
                            $alasan = $data_form['alasan_dispensasi'] ?? 'kurang dari 10 hari kerja';
                        ?>
                        <tr><td style="width: 32%; vertical-align: top; padding: 1px 0;">Nama</td><td style="width: 3%; vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><strong><?php echo htmlspecialchars(strtoupper($calon)); ?></strong></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Tempat / Tanggal Lahir</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($ttl); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Agama</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($agama); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Pekerjaan</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($pekerjaan); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Alamat</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0; text-align: justify;"><?php echo htmlspecialchars($alamat); ?></td></tr>
                    </table>
                </div>

                <div style="font-size: 10.5pt; text-align: justify; margin-bottom: 4px; line-height: 1.3;">
                    <p style="text-indent: 35px; margin: 0;">Untuk melaksanakan akad nikah dengan seorang Perempuan:</p>
                </div>

                <div style="margin: 0 0 6px 20px;">
                    <table style="width: 96%; font-size: 10.5pt; line-height: 1.25;" cellpadding="1" cellspacing="0">
                        <tr><td style="width: 32%; vertical-align: top; padding: 1px 0;">Nama</td><td style="width: 3%; vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><strong><?php echo htmlspecialchars(strtoupper($pasangan)); ?></strong></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Tempat / Tanggal Lahir</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($ttl_pasangan); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Agama</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($agama_pasangan); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Pekerjaan</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0;"><?php echo htmlspecialchars($pekerjaan_pasangan); ?></td></tr>
                        <tr><td style="vertical-align: top; padding: 1px 0;">Alamat</td><td style="vertical-align: top; padding: 1px 0;">:</td><td style="vertical-align: top; padding: 1px 0; text-align: justify;"><?php echo htmlspecialchars($alamat_pasangan); ?></td></tr>
                    </table>
                </div>

                <div style="font-size: 10.5pt; text-align: justify; margin-bottom: 6px; line-height: 1.3;">
                    <p style="text-indent: 35px; margin: 0;">
                        Sesuai dengan Permohonan Akad Nikah yang akan dilaksanakan pada tanggal <?php echo !empty($data_form['tanggal_akad']) ? tanggal_indonesia(strtotime($data_form['tanggal_akad'])) : '-'; ?> dengan alasan <?php echo htmlspecialchars($alasan); ?>.
                    </p>
                </div>

                <div style="font-size: 10.5pt; text-align: justify; margin-bottom: 10px; line-height: 1.3;">
                    <p style="text-indent: 35px; margin: 0;">
                        <?php echo nl2br(htmlspecialchars($penutup_final)); ?>
                    </p>
                </div>

            <?php else: ?>
                <!-- MODEL SURAT KETERANGAN UMUM WARGA (SKTM, SKU, AHLI WARIS, DLL) -->
                <div style="text-align: center; margin-bottom: 12px;">
                    <div style="font-size: 13pt; font-weight: bold; text-decoration: underline; text-transform: uppercase;">
                        <?php echo htmlspecialchars($template['judul_surat']); ?>
                    </div>
                    <div style="font-size: 11pt; margin-top: 2px;">
                        NOMOR: <?php echo htmlspecialchars($nomor_surat); ?>
                    </div>
                </div>

                <div style="font-size: 11pt; text-align: justify; margin-bottom: 8px; line-height: 1.35;">
                    <p style="text-indent: 35px; margin: 0;">
                        <?php echo nl2br(htmlspecialchars($pembuka_final)); ?>
                    </p>
                </div>

                <?php $is_long_form = count($data_form) > 7; ?>
                <div style="margin: 0 0 <?php echo $is_long_form ? '6px' : '10px'; ?> 20px;">
                    <table style="width: 96%; font-size: <?php echo $is_long_form ? '10pt' : '11pt'; ?>; line-height: <?php echo $is_long_form ? '1.2' : '1.3'; ?>;" cellpadding="1" cellspacing="0">
                        <?php foreach ($data_form as $key => $val): 
                            $label = ucwords(str_replace('_', ' ', $key));
                        ?>
                            <tr>
                                <td style="width: 32%; vertical-align: top; padding: <?php echo $is_long_form ? '1px' : '1.5px'; ?> 0;"><?php echo htmlspecialchars($label); ?></td>
                                <td style="width: 3%; vertical-align: top; padding: <?php echo $is_long_form ? '1px' : '1.5px'; ?> 0;">:</td>
                                <td style="vertical-align: top; padding: <?php echo $is_long_form ? '1px' : '1.5px'; ?> 0;">
                                    <?php echo $key === 'nama_pemohon' ? '<strong>' . htmlspecialchars(strtoupper((string)$val)) . '</strong>' : nl2br(htmlspecialchars((string)$val)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <div style="font-size: 11pt; text-align: justify; margin-bottom: 12px; line-height: 1.35;">
                    <p style="text-indent: 35px; margin: 0;">
                        <?php echo nl2br(htmlspecialchars($penutup_final)); ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- BLOK TANDA TANGAN (KOTAK RESMI DINAS ATAU GAMBAR SPESIMEN) -->
            <table style="width: 100%; margin-bottom: 12px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 48%;"></td>
                    <td style="width: 52%; text-align: center;" align="center">
                        <div style="display: inline-block; text-align: center; width: 310px;">
                            <p style="font-size: 11pt; margin: 0 0 6px 0;">Cisayong, <?php echo $tanggal_surat_tampil; ?></p>
                            
                            <?php if ($is_ttd_dibubuhkan === 1): ?>
                                <?php if ($current_ttd && $current_ttd['tipe_ttd'] === 'gambar_upload' && !empty($current_ttd['gambar_ttd'])): ?>
                                    <!-- FORMAT MURNI GAMBAR TANDA TANGAN (HANYA BENTUK GAMBAR SAJA) -->
                                    <div style="display: flex; justify-content: center; align-items: center; min-height: 70px; padding: 2px 0;">
                                        <img src="<?php echo BASE_URL . $current_ttd['gambar_ttd']; ?>" alt="Tanda Tangan Resmi" style="max-height: 85px; max-width: 270px; object-fit: contain; display: block; margin: 0 auto;">
                                    </div>
                                <?php else: ?>
                                    <!-- KOTAK TTE RESMI BERBINGKAI & BERLOGO (BSrE BSSN) -->
                                    <div style="border: 1.5px solid #334155; padding: 8px 12px; background: #ffffff; display: flex; align-items: center; gap: 12px; text-align: left; box-sizing: border-box;">
                                        <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Kabupaten Tasikmalaya" style="height: 55px; width: auto; object-fit: contain; flex-shrink: 0;">
                                        <div style="font-size: 8.5pt; color: #000; line-height: 1.3; font-family: 'Times New Roman', Times, serif;">
                                            <span style="font-size: 7.5pt; color: #475569;"><?php echo htmlspecialchars($current_ttd['teks_atas'] ?? 'Ditandatangani secara elektronik oleh'); ?></span><br>
                                            <strong style="text-transform: uppercase; font-size: 9pt;"><?php echo htmlspecialchars($current_ttd['jabatan'] ?? 'CAMAT CISAYONG'); ?></strong><br><br>
                                            <strong style="text-decoration: underline; font-size: 9pt;"><?php echo htmlspecialchars($current_ttd['nama_pejabat'] ?? $camat_nama_final); ?></strong><br>
                                            <span><?php echo htmlspecialchars($current_ttd['pangkat_golongan'] ?? ($template['penandatangan_pangkat'] ?? 'Pembina : Tk. I IV/b')); ?></span><br>
                                            <?php if (!empty($current_ttd['nip'])): ?>
                                                <span style="font-size: 7.5pt;">NIP. <?php echo htmlspecialchars($current_ttd['nip']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- TAMPILAN JIKA BELUM DIBUBUHI TANDA TANGAN -->
                                <div style="border: 1.5px dashed #94a3b8; padding: 12px; background: #f8fafc; text-align: center; border-radius: 6px;">
                                    <p style="font-size: 9pt; font-weight: bold; color: #e11d48; margin: 0 0 2px 0; font-family: sans-serif;">
                                        [ BELUM DIBUBUHI TANDA TANGAN ]
                                    </p>
                                    <p style="font-size: 7.5pt; color: #64748b; margin: 0; font-family: sans-serif;">
                                        Dokumen menunggu tombol pembubuhan tanda tangan pejabat.
                                    </p>
                                    <?php if (is_kecamatan_role($user_role)): ?>
                                        <button type="button" onclick="openModalTTD()" class="no-print" style="margin-top: 6px; padding: 5px 12px; background: #1e3a8a; color: white; border: none; border-radius: 6px; font-size: 8pt; font-weight: bold; cursor: pointer;">
                                            🖋️ Bubuhkan Sekarang
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- FOOTER VERIFIKASI RESMI KECAMATAN -->
            <div style="border-top: 1px solid #94a3b8; padding-top: 6px; display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 7.5pt; line-height: 1.3; color: #475569; font-family: 'Times New Roman', Times, serif;">
                <div>
                    Dokumen Pelayanan Resmi Kecamatan Cisayong Kabupaten Tasikmalaya.<br>
                    <strong>Verifikasi Pelayanan:</strong> Front Office Layanan / PTSP (<?php echo htmlspecialchars($pengajuan['ptsp_oleh'] ?? 'Valid'); ?>) • Kasi Terkait (<?php echo htmlspecialchars($pengajuan['paraf_kasi_oleh'] ?? 'Disetujui'); ?>)
                </div>
                <div style="text-align: right; font-family: monospace; font-size: 7.5pt; flex-shrink: 0;">
                    <?php if ($status_norm === 'disetujui'): ?>
                        <span style="color: #047857; font-weight: bold;">[ RESMI & SAH ]</span>
                    <?php else: ?>
                        <span style="color: #e11d48; font-weight: bold;">[ DRAFT ]</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<!-- MODAL PILIH / BUKUKAN TANDA TANGAN -->
<div id="modal-ttd" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 space-y-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center space-x-2.5">
                <span class="material-icons-outlined text-blue-900 text-2xl">draw</span>
                <h3 class="font-extrabold text-slate-900 text-base">Pilih & Bubuhkan Tanda Tangan</h3>
            </div>
            <button type="button" onclick="closeModalTTD()" class="text-slate-400 hover:text-slate-600">
                <span class="material-icons-outlined text-xl">close</span>
            </button>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $id; ?>" class="space-y-4">
            <input type="hidden" name="action" value="bubuhkan_ttd">

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Pilih Profil Tanda Tangan:
                </label>
                <select name="ttd_id" id="select-ttd" onchange="previewSelectedTTD(this.value)" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-900 font-bold text-xs cursor-pointer">
                    <?php foreach ($all_ttd as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ($current_ttd && $current_ttd['id'] == $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['judul_ttd']); ?> (<?php echo htmlspecialchars($t['nama_pejabat']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Preview Box Modal -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 text-center space-y-2">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pratinjau Bentuk Kotak TTE:</p>
                <div id="modal-preview-box" class="flex justify-center">
                    <!-- Dynamic preview loaded by JS -->
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModalTTD()" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-6 py-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs shadow-lg shadow-emerald-700/20 hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-base">check</span>
                    <span>Konfirmasi Bubuhkan Tanda Tangan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const ttdData = <?php echo json_encode($all_ttd); ?>;
const logoUrl = <?php echo json_encode(BASE_URL . 'assets/logo/Tasikmalaya_Regency_Seal.png'); ?>;
const baseUrl = <?php echo json_encode(BASE_URL); ?>;

function openModalTTD() {
    document.getElementById('modal-ttd').classList.remove('hidden');
    const sel = document.getElementById('select-ttd');
    if (sel) {
        previewSelectedTTD(sel.value);
    }
}

function closeModalTTD() {
    document.getElementById('modal-ttd').classList.add('hidden');
}

function previewSelectedTTD(id) {
    const item = ttdData.find(t => t.id == id) || ttdData[0];
    const previewContainer = document.getElementById('modal-preview-box');
    if (!item || !previewContainer) return;

    if (item.tipe_ttd === 'gambar_upload' && item.gambar_ttd) {
        previewContainer.innerHTML = `
            <div style="padding: 10px; background: #ffffff; display: flex; justify-content: center; align-items: center; width: 320px; border: 1.5px dashed #cbd5e1; border-radius: 8px;">
                <img src="${baseUrl + item.gambar_ttd}" style="max-height: 100px; max-width: 300px; object-fit: contain; margin: 0 auto; display: block;">
            </div>
        `;
    } else {
        previewContainer.innerHTML = `
            <div style="border: 1.5px solid #475569; padding: 10px 14px; background: #ffffff; display: flex; align-items: center; gap: 14px; width: 320px; text-align: left; box-sizing: border-box; font-family: 'Times New Roman', serif;">
                <img src="${logoUrl}" style="height: 60px; width: auto; object-fit: contain;">
                <div style="font-size: 8.5pt; color: #000; line-height: 1.3;">
                    <span style="font-size: 7.5pt; color: #475569;">${escapeHtml(item.teks_atas || 'Ditandatangani secara elektronik oleh')}</span><br>
                    <strong style="text-transform: uppercase; font-size: 9pt;">${escapeHtml(item.jabatan)}</strong><br><br>
                    <strong style="text-decoration: underline; font-size: 9pt;">${escapeHtml(item.nama_pejabat)}</strong><br>
                    <span>${escapeHtml(item.pangkat_golongan || '')}</span><br>
                    ${item.nip ? '<span style="font-size: 7.5pt;">NIP. ' + escapeHtml(item.nip) + '</span>' : ''}
                </div>
            </div>
        `;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function kirimManualWA(nama, nomor_surat, pengajuan_id) {
    Swal.fire({
        title: 'Kirim Notifikasi WhatsApp',
        text: 'Masukkan nomor WhatsApp aktif pemohon (' + nama + '):',
        input: 'text',
        inputPlaceholder: 'Contoh: 08123456789 atau 628123456789',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Kirim via WhatsApp',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false,
        inputValidator: (val) => {
            if (!val || val.trim().length < 9) {
                return 'Harap masukkan nomor WhatsApp yang valid!';
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            let phone = result.value.replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) phone = '62' + phone.substring(1);
            else if (phone.startsWith('8')) phone = '62' + phone;

            const host = window.location.host;
            const dlUrl = window.location.protocol + '//' + host + baseUrl + 'surat/cetak_surat.php?id=' + pengajuan_id;
            const msg = `🏛️ *PEMBERITAHUAN PELAYANAN SURAT DIGITAL*\n*KECAMATAN CISAYONG - KABUPATEN TASIKMALAYA*\n\nYth. Bpk/Ibu *${nama}*,\n\nPermohonan surat Anda telah *SELESAI DIPROSES & DITERBITKAN RESMI*:\n📄 *Jenis Surat:* <?php echo htmlspecialchars(addslashes($jenis_surat)); ?>\n🔢 *Nomor Surat:* ${nomor_surat}\n✅ *Status:* Selesai & Disetujui\n\nDokumen resmi kini siap diunduh langsung dalam format PDF siap cetak melalui tautan portal berikut:\n👉 ${dlUrl}\n\n_Pesan otomatis dari Sistem Pelayanan Surat Digital Kecamatan Cisayong._`;

            window.open('https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(msg), '_blank');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['signed']) && $_GET['signed'] == 1): ?>
        Swal.fire({
            title: 'Tanda Tangan Berhasil Dibubuhkan!',
            text: 'Tanda tangan resmi telah disematkan pada lembar surat <?php echo htmlspecialchars($nomor_surat); ?>. Kirimkan notifikasi WhatsApp ke pemohon sekarang?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: '📱 Kirim WhatsApp ke Pemohon',
            cancelButtonText: 'Selesai',
            customClass: {
                popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                confirmButton: 'px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all mr-3',
                cancelButton: 'px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all'
            },
            buttonsStyling: false
        }).then((res) => {
            if (res.isConfirmed) {
                <?php if (!empty($wa_notif_url)): ?>
                    window.open('<?php echo $wa_notif_url; ?>', '_blank');
                <?php else: ?>
                    kirimManualWA('<?php echo htmlspecialchars(addslashes($nama_pemohon)); ?>', '<?php echo htmlspecialchars(addslashes($nomor_surat)); ?>', <?php echo $id; ?>);
                <?php endif; ?>
            }
        });
    <?php elseif (isset($_GET['unsigned']) && $_GET['unsigned'] == 1): ?>
        Swal.fire({
            title: 'Tanda Tangan Ditarik',
            text: 'Tanda tangan resmi pada lembar surat ini berhasil ditarik kembali.',
            icon: 'info',
            timer: 2500,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
