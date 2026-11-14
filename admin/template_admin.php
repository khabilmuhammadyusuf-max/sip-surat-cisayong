<?php
$page_title = "Manajemen Template Surat (No-Code Editor)";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php" . "");
    exit;
}

$jenis = $_GET['jenis'] ?? 'Dispensasi Nikah';
$success_msg = '';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_post = $_POST['jenis_surat'] ?? 'Dispensasi Nikah';
    $kode_klasifikasi = trim($_POST['kode_klasifikasi'] ?? '');
    $format_nomor = trim($_POST['format_nomor'] ?? '');
    $judul_surat = trim($_POST['judul_surat'] ?? '');
    $sifat = trim($_POST['sifat'] ?? '');
    $lampiran = trim($_POST['lampiran'] ?? '');
    $perihal = trim($_POST['perihal'] ?? '');
    $tujuan = trim($_POST['tujuan'] ?? '');
    $pembuka = trim($_POST['pembuka'] ?? '');
    $penutup = trim($_POST['penutup'] ?? '');
    $penandatangan_nama = trim($_POST['penandatangan_nama'] ?? '');
    $penandatangan_nip = trim($_POST['penandatangan_nip'] ?? '');
    $penandatangan_jabatan = trim($_POST['penandatangan_jabatan'] ?? '');
    $penandatangan_pangkat = trim($_POST['penandatangan_pangkat'] ?? '');
    $is_tte = isset($_POST['is_tte']) ? (int)$_POST['is_tte'] : 1;
    
    $stmtUpd = $pdo->prepare("UPDATE `templates` SET kode_klasifikasi = ?, format_nomor = ?, judul_surat = ?, sifat = ?, lampiran = ?, perihal = ?, tujuan = ?, pembuka = ?, penutup = ?, penandatangan_nama = ?, penandatangan_nip = ?, penandatangan_jabatan = ?, penandatangan_pangkat = ?, is_tte = ?, updated_at = NOW() WHERE jenis_surat = ?");
    $stmtUpd->execute([$kode_klasifikasi, $format_nomor, $judul_surat, $sifat, $lampiran, $perihal, $tujuan, $pembuka, $penutup, $penandatangan_nama, $penandatangan_nip, $penandatangan_jabatan, $penandatangan_pangkat, $is_tte, $jenis_post]);
    
    $success_msg = "Pengaturan Template Surat $jenis_post berhasil diperbarui!";
    $jenis = $jenis_post;
}

// Fetch current template
$stmtT = $pdo->prepare("SELECT * FROM `templates` WHERE jenis_surat = ?");
$stmtT->execute([$jenis]);
$template = $stmtT->fetch();

if (!$template) {
    // Re-seed if missing
    $default_kode = $jenis === 'Dispensasi Nikah' ? '400.8' : '900.10.2.4';
    $default_format = $jenis === 'Dispensasi Nikah' ? '[KODE]/[URUT]/Kec/[TAHUN]' : 'B/[KODE]/[URUT]/Kec.Pum/[TAHUN]';
    $default_judul = $jenis === 'Dispensasi Nikah' ? 'SURAT KETERANGAN' : 'Rekomendasi Pencairan ADD';
    $default_sifat = $jenis === 'Dispensasi Nikah' ? '' : 'Penting';
    $default_lampiran = $jenis === 'Dispensasi Nikah' ? '' : '1 (satu) berkas';
    $default_perihal = $jenis === 'Dispensasi Nikah' ? '' : 'Rekomendasi Pencairan ADD';
    $default_tujuan = $jenis === 'Dispensasi Nikah' ? '' : "Bupati Tasikmalaya\nMelalui :\nKepala DPMD Kab. Tasikmalaya\ndi\nSINGAPARNA";
    $default_pembuka = $jenis === 'Dispensasi Nikah' ? "Sesuai dengan Peraturan Pemerintah Nomor 9 tahun 1975 Bab II Pasal 1, Camat Cisayong Atas Nama Bupati Tasikmalaya, dengan ini memberikan Dispensasi Nikah kepada:" : "Berdasarkan permohonan pencairan ADD Desa Jatihurip Kecamatan Cisayong nomor B/86/KU.02.03/DS/2025 tanggal 22 Oktober 2025 perihal Permohonan Pencairan Alokasi Dana Desa (ADD), berdasarkan hasil verifikasi dengan ini kami merekomendasikan permohonan Pencairan Alokasi Dana Desa (ADD) dimaksud untuk Bulan November sebesar Rp. 50.950.000,00 (Lima Puluh Juta Sembilan Ratus Lima Puluh Ribu Rupiah).\n\nAdapun kelengkapan pengajuan pencairan sebagaimana terlampir, meliputi :\n1. Surat Permohonan Pencairan;\n2. Fakta Integritas;\n3. Bukti Pembayaran;\n4. Surat Pernyataan Tanggungjawab Mutlak (SPTJM);\n5. Foto copy KTP Kepala Desa dan Bendahara Desa;\n6. Foto copy Rekening Pemerintahan Desa;\n7. Foto Copy NPWP;\n8. Lembar Verifikasi Kecamatan;\n9. Data Jumlah Kaur, Kasi, Kadus, BPD, RT dan RW.";
    $default_penutup = $jenis === 'Dispensasi Nikah' ? "Demikian agar menjadi maklum dan untuk digunakan sebagaimana mestinya." : "Demikian untuk maklum, atas terkabulnya kami ucapkan terimakasih.";
    $default_nama = $jenis === 'Dispensasi Nikah' ? 'SUTARYO, S.T.' : 'AYI MULYANA HERNIWAN, SE., M.Si.';
    $default_nip = $jenis === 'Dispensasi Nikah' ? '19740817 199803 1 004' : '19721003 199403 1 012';
    $default_jabatan = 'CAMAT CISAYONG';
    $default_pangkat = $jenis === 'Dispensasi Nikah' ? 'Pembina' : 'Pembina : Tk.I IV/b';
    $default_tte = $jenis === 'Dispensasi Nikah' ? 1 : 0;

    $stmtIns = $pdo->prepare("INSERT INTO `templates` (`jenis_surat`, `kode_klasifikasi`, `format_nomor`, `judul_surat`, `sifat`, `lampiran`, `perihal`, `tujuan`, `pembuka`, `penutup`, `penandatangan_nama`, `penandatangan_nip`, `penandatangan_jabatan`, `penandatangan_pangkat`, `is_tte`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtIns->execute([$jenis, $default_kode, $default_format, $default_judul, $default_sifat, $default_lampiran, $default_perihal, $default_tujuan, $default_pembuka, $default_penutup, $default_nama, $default_nip, $default_jabatan, $default_pangkat, $default_tte]);
    
    $stmtT->execute([$jenis]);
    $template = $stmtT->fetch();
}
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Top Action Bar -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">auto_awesome</span>
                <span>No-Code Template Editor (Kecamatan Cisayong)</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Template & Format Penomoran</h2>
            <p class="text-xs text-slate-500">Sesuaikan redaksi surat dan tata letak HVS A4 dengan mudah tanpa memerlukan keahlian coding / pemrograman.</p>
        </div>
        <div class="flex items-center space-x-3 w-full md:w-auto justify-end">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Template Surat:</label>
            <select onchange="window.location.href='<?php echo BASE_URL; ?>admin/template_admin.php?jenis=' + encodeURIComponent(this.value)" class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-blue-900 focus:outline-none focus:border-blue-900 transition-colors cursor-pointer">
                <?php
                $stmtAllTmpl = $pdo->query("SELECT jenis_surat FROM `templates` ORDER BY id ASC");
                while ($trow = $stmtAllTmpl->fetch()) {
                    $sel = ($jenis === $trow['jenis_surat']) ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($trow['jenis_surat']).'" '.$sel.'>Surat '.htmlspecialchars($trow['jenis_surat']).'</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl shadow-md flex items-center space-x-4 animate-fade-in-up">
            <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-600/30">
                <span class="material-icons-outlined text-3xl">task_alt</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-emerald-900">Berhasil Menyimpan Template!</h4>
                <p class="text-xs text-emerald-700 font-medium"><?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- No-Code Editor Form -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-8 bg-slate-50 border-b border-slate-100 flex items-center space-x-4">
            <div class="w-14 h-14 bg-blue-900 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/20">
                <span class="material-icons-outlined text-3xl">edit_document</span>
            </div>
            <div>
                <h3 class="font-extrabold text-xl text-slate-900">Formulir Setel Template: <?php echo htmlspecialchars($jenis); ?></h3>
                <p class="text-xs text-slate-500 mt-0.5">Isi bagian judul, klasifikasi, redaksi pembuka/penutup, dan pejabat penandatangan di bawah ini</p>
            </div>
        </div>

        <form id="form-template" class="p-8 space-y-8" action="<?php echo BASE_URL; ?>admin/template_admin.php?jenis=<?php echo urlencode($jenis); ?>" method="POST">
            <input type="hidden" name="jenis_surat" value="<?php echo htmlspecialchars($jenis); ?>">

            <!-- BAGIAN 1: JUDUL & KLASIFIKASI -->
            <div class="space-y-4">
                <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                    <span class="material-icons-outlined text-xl">pin</span>
                    <span>1. Judul Surat & Format Penomoran Otomatis</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Judul Surat Resmi</label>
                        <input type="text" name="judul_surat" value="<?php echo htmlspecialchars($template['judul_surat']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kode Klasifikasi Surat</label>
                        <input type="text" name="kode_klasifikasi" value="<?php echo htmlspecialchars($template['kode_klasifikasi']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        <p class="text-[11px] text-slate-400 mt-1">Contoh: 400.8 / 900.10.2.4</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Format String Penomoran</label>
                        <input type="text" name="format_nomor" value="<?php echo htmlspecialchars($template['format_nomor']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                        <p class="text-[11px] text-slate-400 mt-1">Gunakan tag: [KODE], [URUT], [TAHUN]</p>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 2: ATRIBUT SURAT (KHUSUS ADD / SURAT RESMI LAINNYA) -->
            <div class="space-y-4 pt-6 border-t border-slate-100">
                <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                    <span class="material-icons-outlined text-xl">list_alt</span>
                    <span>2. Atribut Surat (Sifat, Lampiran, Perihal & Tujuan)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Sifat Surat</label>
                        <input type="text" name="sifat" value="<?php echo htmlspecialchars($template['sifat'] ?? ''); ?>" placeholder="Contoh: Penting (kosongkan jika tidak perlu)" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Lampiran</label>
                        <input type="text" name="lampiran" value="<?php echo htmlspecialchars($template['lampiran'] ?? ''); ?>" placeholder="Contoh: 1 (satu) berkas" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Perihal</label>
                        <input type="text" name="perihal" value="<?php echo htmlspecialchars($template['perihal'] ?? ''); ?>" placeholder="Contoh: Rekomendasi Pencairan ADD" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Tujuan Surat / Kepada Yth.</label>
                        <textarea name="tujuan" rows="3" placeholder="Contoh: Bupati Tasikmalaya&#10;Melalui : Kepala DPMD..." class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($template['tujuan'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 3: REDAKSI SURAT -->
            <div class="space-y-4 pt-6 border-t border-slate-100">
                <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                    <span class="material-icons-outlined text-xl">article</span>
                    <span>3. Bagian Redaksi / Isi Surat</span>
                </div>
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kalimat Pembuka / Mukadimah</label>
                        <textarea name="pembuka" rows="6" required class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($template['pembuka']); ?></textarea>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-2xl border border-blue-200 text-xs text-blue-900 space-y-1">
                        <p class="font-bold flex items-center space-x-1">
                            <span class="material-icons-outlined text-sm">info</span>
                            <span>Catatan Susunan Tabel Data Pemohon</span>
                        </p>
                        <p>Tabel biodata pemohon (Nama, Tempat/Tgl Lahir, Agama, Pekerjaan, Alamat, dll) akan disisipkan secara otomatis oleh sistem di antara Kalimat Pembuka dan Kalimat Penutup.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kalimat Penutup / Klausul</label>
                        <textarea name="penutup" rows="3" required class="w-full p-4 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm"><?php echo htmlspecialchars($template['penutup']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- BAGIAN 4: PEJABAT PENANDATANGAN -->
            <div class="space-y-4 pt-6 border-t border-slate-100">
                <div class="flex items-center space-x-2 text-blue-900 font-extrabold text-base border-b border-slate-100 pb-2">
                    <span class="material-icons-outlined text-xl">draw</span>
                    <span>4. Bagian Pejabat Penandatangan (Footer)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Pejabat (KAPITAL)</label>
                        <input type="text" name="penandatangan_nama" value="<?php echo htmlspecialchars($template['penandatangan_nama']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nomor Induk Pegawai (NIP)</label>
                        <input type="text" name="penandatangan_nip" value="<?php echo htmlspecialchars($template['penandatangan_nip']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Jabatan Penandatangan</label>
                        <input type="text" name="penandatangan_jabatan" value="<?php echo htmlspecialchars($template['penandatangan_jabatan']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Pangkat / Golongan</label>
                        <input type="text" name="penandatangan_pangkat" value="<?php echo htmlspecialchars($template['penandatangan_pangkat']); ?>" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div class="md:col-span-4 flex items-center space-x-3 bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <input type="checkbox" name="is_tte" value="1" <?php echo $template['is_tte'] ? 'checked' : ''; ?> class="w-5 h-5 text-blue-900 rounded border-slate-300 focus:ring-blue-900">
                        <div>
                            <label class="text-sm font-bold text-slate-800">Gunakan Tanda Tangan Elektronik (TTE) Tersertifikasi BSrE</label>
                            <p class="text-xs text-slate-500">Jika dicentang, sistem akan menampilkan keterangan TTE BSrE. Jika tidak, menyediakan ruang tanda tangan basah.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="bg-blue-900 hover:bg-blue-800 text-white font-extrabold px-10 py-4 rounded-2xl shadow-xl shadow-blue-900/20 hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl">save</span>
                    <span>Simpan Template Surat</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('form-template').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi Simpan Template',
        text: 'Apakah Anda yakin ingin menyimpan perubahan redaksi dan pengaturan TTE pada template ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan Template',
        cancelButtonText: 'Batal Periksa Lagi',
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
            title: 'Berhasil Disimpan!',
            text: <?php echo json_encode($success_msg); ?>,
            timer: 2500,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
