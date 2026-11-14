<?php
$page_title = "Riwayat & Arsip Dokumen Surat";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php" . "");
    exit;
}

// Filter params
$filter_jenis = $_GET['jenis'] ?? '';
$filter_status = $_GET['status'] ?? '';

$sql = "SELECT p.*, u.nama, u.username, u.instansi_desa, u.phone FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id WHERE p.status IN ('disetujui', 'ditolak')";
$params = [];

if (!empty($filter_jenis)) {
    $sql .= " AND p.jenis_surat = ?";
    $params[] = $filter_jenis;
}

if (!empty($filter_status)) {
    $sql .= " AND p.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY p.waktu_verifikasi DESC, p.waktu_pengajuan DESC";

$stmtHist = $pdo->prepare($sql);
$stmtHist->execute($params);
$riwayat_list = $stmtHist->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">history_edu</span>
                <span>Arsip Permanen Layanan Surat</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Riwayat Validasi & Arsip Surat</h2>
            <p class="text-xs text-slate-500">Seluruh surat yang telah disetujui (siap cetak/unduh) maupun yang ditolak tersimpan rapi sebagai arsip digital.</p>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="<?php echo BASE_URL; ?>admin/admin_riwayat.php" class="flex flex-wrap items-center gap-3 w-full md:w-auto justify-end">
            <select name="jenis" onchange="this.form.submit()" class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-blue-900 focus:outline-none focus:border-blue-900 cursor-pointer">
                <option value="">Semua Jenis Surat</option>
                <option value="Dispensasi Nikah" <?php echo $filter_jenis === 'Dispensasi Nikah' ? 'selected' : ''; ?>>Dispensasi Nikah</option>
                <option value="Alokasi Dana Daerah (ADD)" <?php echo $filter_jenis === 'Alokasi Dana Daerah (ADD)' ? 'selected' : ''; ?>>Alokasi Dana Daerah (ADD)</option>
            </select>
            <select name="status" onchange="this.form.submit()" class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-blue-900 focus:outline-none focus:border-blue-900 cursor-pointer">
                <option value="">Semua Status</option>
                <option value="disetujui" <?php echo $filter_status === 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="ditolak" <?php echo $filter_status === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
            </select>
            <?php if (!empty($filter_jenis) || !empty($filter_status)): ?>
                <a href="<?php echo BASE_URL; ?>admin/admin_riwayat.php" class="px-4 py-3 bg-rose-50 text-rose-700 rounded-2xl text-xs font-bold hover:bg-rose-100 transition-colors">Reset Filter</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- History Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-900 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-900/20">
                    <span class="material-icons-outlined text-xl">assignment_turned_in</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900">Arsip Digital Surat Kecamatan</h3>
            </div>
            <span class="text-xs text-slate-500 font-semibold">Ditemukan: <?php echo count($riwayat_list); ?> arsip</span>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[850px] text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Pemohon / Instansi</th>
                        <th class="px-6 py-4 whitespace-nowrap">Jenis Surat & Detail</th>
                        <th class="px-6 py-4 whitespace-nowrap">Waktu Verifikasi</th>
                        <th class="px-6 py-4 whitespace-nowrap">Status</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($riwayat_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-400 font-semibold text-sm">
                                Tidak ada arsip surat yang sesuai dengan kriteria filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat_list as $row): 
                            $data = json_decode($row['data_form'], true);
                            $detail_tampil = '';
                            if ($row['jenis_surat'] === 'Dispensasi Nikah') {
                                $detail_tampil = ($data['nama_pemohon'] ?? 'Calon') . ' & ' . ($data['nama_pasangan'] ?? 'Pasangan');
                            } else {
                                $detail_tampil = ($row['instansi_desa'] ?? 'Desa') . ' - Rp ' . number_format((float)($data['jumlah_dana'] ?? 0), 0, ',', '.');
                            }
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900"><?php echo htmlspecialchars($row['nama']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($row['instansi_desa'] ?? 'NIK: ' . $row['username']); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-blue-900"><?php echo htmlspecialchars($row['jenis_surat']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($detail_tampil); ?></p>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <?php echo !empty($row['waktu_verifikasi']) ? date('d M Y, H:i', strtotime($row['waktu_verifikasi'])) : date('d M Y, H:i', strtotime($row['waktu_pengajuan'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-50 text-emerald-800 rounded-full text-xs font-bold border border-emerald-200 whitespace-nowrap">
                                            <span class="material-icons-outlined text-sm">check_circle</span>
                                            <span>Disetujui</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-rose-50 text-rose-700 rounded-full text-xs font-bold border border-rose-200 whitespace-nowrap">
                                            <span class="material-icons-outlined text-sm">cancel</span>
                                            <span>Ditolak</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <?php if ($row['status'] === 'disetujui'): ?>
                                        <div class="inline-flex items-center gap-1.5 justify-end whitespace-nowrap">
                                            <?php 
                                            $user_phone = $row['phone'] ?? '';
                                            if (empty($user_phone) && !empty($data)) {
                                                $user_phone = $data['no_hp'] ?? ($data['telepon'] ?? ($data['nomor_hp'] ?? ($data['kontak'] ?? ($data['wa'] ?? ''))));
                                            }
                                            $nomor_tampil = $row['nomor_surat_generated'] ?? ('REG-' . date('Ymd', strtotime($row['waktu_pengajuan'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT));
                                            $wa_url = get_whatsapp_notification_url($user_phone, $row['nama'], $row['jenis_surat'], $nomor_tampil, $row['id'], 'disetujui');
                                            ?>
                                            <?php if (!empty($wa_url)): ?>
                                                <a href="<?php echo htmlspecialchars($wa_url); ?>" target="_blank" class="inline-flex items-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm" title="Kirim Notifikasi WhatsApp">
                                                    <span class="material-icons-outlined text-xs mr-1">chat</span> WA
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-4 py-2 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-900/10 transition-all">
                                                <span class="material-icons-outlined text-xs mr-1">print</span> Lihat & Cetak
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <button onclick="lihatAlasanTolak(<?php echo htmlspecialchars(json_encode($row['catatan_tolak'] ?? 'Tidak ada keterangan')); ?>)" class="inline-flex items-center px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-xl text-xs font-bold transition-colors">
                                            <span class="material-icons-outlined text-xs mr-1">info</span> Alasan Tolak
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Alasan Tolak -->
    <div id="modal-tolak" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-slate-200 space-y-6 animate-fade-in-up">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-rose-100 text-rose-700 rounded-2xl flex items-center justify-center">
                    <span class="material-icons-outlined text-3xl">feedback</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Catatan Penolakan Admin</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Keterangan alasan berkas dikembalikan / ditolak</p>
                </div>
            </div>
            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 text-sm text-slate-700 leading-relaxed" id="teks-alasan-tolak">
                <!-- Diisi via JS -->
            </div>
            <div class="flex justify-end">
                <button onclick="tutupModalTolak()" class="px-7 py-3.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-2xl transition-colors">Tutup Jendela</button>
            </div>
        </div>
    </div>
</main>

<script>
function lihatAlasanTolak(catatan) {
    Swal.fire({
        title: 'Catatan Penolakan Admin',
        text: catatan,
        icon: 'info',
        confirmButtonText: 'Tutup',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
        },
        buttonsStyling: false
    });
}

function tutupModalTolak() {
    document.getElementById('modal-tolak').classList.add('hidden');
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
