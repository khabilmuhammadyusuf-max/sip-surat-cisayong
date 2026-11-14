<?php
$page_title = "Riwayat Pengajuan Surat Umum";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_masyarakat.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil seluruh riwayat pengajuan surat umum milik warga ini
$stmtRiwayat = $pdo->prepare("SELECT * FROM `pengajuan` WHERE user_id = ? ORDER BY waktu_pengajuan DESC");
$stmtRiwayat->execute([$user_id]);
$riwayat_list = $stmtRiwayat->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">history</span>
                <span>Arsip & Pelacakan Surat Warga</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Riwayat & Pelacakan Berjenjang Surat Saya</h2>
            <p class="text-xs text-slate-500">Pantau posisi berkas Anda (PTSP → Kasi Terkait → Sekcam → Camat TTE), unduh PDF resmi, atau upload ulang berkas jika dikembalikan.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php#buat-pengajuan" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-bold px-7 py-3.5 rounded-2xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2">
            <span class="material-icons-outlined text-lg">add_circle_outline</span>
            <span>Buat Pengajuan Baru</span>
        </a>
    </div>

    <!-- History Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-900 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-900/20">
                    <span class="material-icons-outlined text-xl">library_books</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900">Daftar Seluruh Pengajuan Surat Umum Anda</h3>
            </div>
            <span class="text-xs text-slate-500 font-semibold">Total: <?php echo count($riwayat_list); ?> pengajuan</span>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[760px] text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Jenis Surat & Pemohon</th>
                        <th class="px-6 py-4 whitespace-nowrap">Nomor Registrasi / Surat</th>
                        <th class="px-6 py-4 whitespace-nowrap">Status Alur Berjenjang</th>
                        <th class="px-6 py-4 whitespace-nowrap">Jejak Validasi & Paraf</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($riwayat_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 font-semibold text-sm">
                                Anda belum pernah mengajukan surat umum di sistem ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat_list as $row): 
                            $data = json_decode($row['data_form'], true) ?? [];
                            $nama_pemohon = $data['nama_pemohon'] ?? $_SESSION['nama'];
                            $reg_no = 'REG-' . date('Ymd', strtotime($row['waktu_pengajuan'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                            $nomor = $row['nomor_surat_generated'] ?: $reg_no;
                            $sInfo = get_status_info($row['status'], $row['kasi_tujuan']);
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['jenis_surat']); ?></p>
                                    <p class="text-xs text-slate-500">Pemohon: <?php echo htmlspecialchars($nama_pemohon); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-blue-900"><?php echo htmlspecialchars($nomor); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo date('d M Y, H:i', strtotime($row['waktu_pengajuan'])); ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap <?php echo $sInfo['badge_class']; ?>">
                                        <span class="material-icons-outlined text-sm flex-shrink-0"><?php echo $sInfo['icon']; ?></span>
                                        <span class="whitespace-nowrap"><?php echo htmlspecialchars($sInfo['label']); ?></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-600 space-y-0.5">
                                    <?php if (!empty($row['waktu_validasi_ptsp'])): ?>
                                        <div>✅ PTSP: <?php echo htmlspecialchars($row['ptsp_oleh'] ?? 'Valid'); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['waktu_paraf_kasi'])): ?>
                                        <div>✅ Paraf Kasi: <?php echo htmlspecialchars($row['paraf_kasi_oleh'] ?? 'Disetujui'); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['waktu_paraf_sekcam'])): ?>
                                        <div>✅ Paraf Sekcam: <?php echo htmlspecialchars($row['paraf_sekcam_oleh'] ?? 'Disetujui'); ?></div>
                                    <?php endif; ?>
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <div class="font-bold text-emerald-700">🏛️ TTE Camat: <?php echo date('d M Y, H:i', strtotime($row['waktu_verifikasi'])); ?></div>
                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                        <div class="font-bold text-rose-700">❌ Ditolak: <?php echo htmlspecialchars($row['catatan_tolak'] ?? '-'); ?></div>
                                    <?php elseif (empty($row['waktu_validasi_ptsp'])): ?>
                                        <span class="text-slate-400 italic">Menunggu pemeriksaan Front Office (PTSP)...</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-1">
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-700/10 transition-all">
                                            <span class="material-icons-outlined text-xs mr-1">picture_as_pdf</span> Unduh PDF
                                        </a>
                                    <?php elseif ($row['status'] === 'ditolak'): ?>
                                        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php?revisi_id=<?php echo $row['id']; ?>#buat-pengajuan" class="inline-flex items-center px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/15 transition-all">
                                            <span class="material-icons-outlined text-xs mr-1">cloud_upload</span> Upload Ulang
                                        </a>
                                        <button onclick="lihatAlasanTolak(<?php echo htmlspecialchars(json_encode($row['catatan_tolak'] ?? 'Silakan perbaiki berkas dan upload ulang.')); ?>)" class="inline-flex items-center px-3 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-xl text-xs font-bold transition-colors">
                                            <span class="material-icons-outlined text-xs">info</span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400 font-semibold italic">Dalam Proses...</span>
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

<script>
function lihatAlasanTolak(catatan) {
    Swal.fire({
        title: 'Catatan Pengembalian Berkas',
        text: catatan,
        icon: 'warning',
        confirmButtonText: 'Mengerti',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
        },
        buttonsStyling: false
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
