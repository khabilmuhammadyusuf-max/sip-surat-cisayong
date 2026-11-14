<?php
$page_title = "Statistik & Laporan Pelayanan Surat";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php" . "");
    exit;
}

// Aggregate queries
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM `pengajuan`");
$total_surat = (int)$stmtTotal->fetchColumn();

$stmtSetuju = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status='disetujui'");
$total_setuju = (int)$stmtSetuju->fetchColumn();

$stmtTolak = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status='ditolak'");
$total_tolak = (int)$stmtTolak->fetchColumn();

$stmtMenunggu = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status IN ('menunggu', 'menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat')");
$total_menunggu = (int)$stmtMenunggu->fetchColumn();

// Breakdown by jenis_surat
$stmtBreakdown = $pdo->query("SELECT jenis_surat, COUNT(*) as total, SUM(CASE WHEN status='disetujui' THEN 1 ELSE 0 END) as setuju, SUM(CASE WHEN status='ditolak' THEN 1 ELSE 0 END) as tolak, SUM(CASE WHEN status IN ('menunggu', 'menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat') THEN 1 ELSE 0 END) as menunggu FROM `pengajuan` GROUP BY jenis_surat");
$breakdown_list = $stmtBreakdown->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="no-print bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">analytics</span>
                <span>Statistik Real-time Pelayanan Publik</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Rekapitulasi & Laporan Layanan Surat</h2>
            <p class="text-xs text-slate-500">Pantau performa layanan surat kecamatan, persentase kelulusan berkas, dan hasil rekapitulasi data.</p>
        </div>
        <button onclick="window.print()" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-bold px-7 py-3.5 rounded-2xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2">
            <span class="material-icons-outlined text-lg">print</span>
            <span>Cetak Laporan Resmi</span>
        </button>
    </div>

    <!-- Print Only Kop Header -->
    <div class="hidden print:block text-center space-y-1 border-b-2 border-slate-800 pb-6 mb-8">
        <h3 class="font-bold text-xl text-slate-900 uppercase">Pemerintah Kabupaten Tasikmalaya</h3>
        <h2 class="font-extrabold text-2xl text-slate-900 uppercase">Kantor Kecamatan Cisayong</h2>
        <p class="text-sm text-slate-600">Laporan Statistik Rekapitulasi Pelayanan Sip-Surat Digital</p>
        <p class="text-xs text-slate-500">Tanggal Cetak: <?php echo date('d F Y, H:i'); ?></p>
    </div>

    <!-- Analytics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-14 h-14 bg-blue-50 text-blue-900 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">description</span>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-slate-900"><?php echo $total_surat; ?></p>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pengajuan</p>
            </div>
        </div>

        <!-- Disetujui -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-800 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">verified</span>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-slate-900"><?php echo $total_setuju; ?></p>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Telah Disetujui</p>
            </div>
        </div>

        <!-- Menunggu -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-14 h-14 bg-amber-50 text-amber-700 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">hourglass_empty</span>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-slate-900"><?php echo $total_menunggu; ?></p>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Menunggu Verifikasi</p>
            </div>
        </div>

        <!-- Ditolak -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-14 h-14 bg-rose-50 text-rose-700 rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">gpp_bad</span>
            </div>
            <div>
                <p class="text-2xl font-extrabold text-slate-900"><?php echo $total_tolak; ?></p>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ditolak / Batal</p>
            </div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-900 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-900/20">
                    <span class="material-icons-outlined text-xl">donut_large</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900">Rincian Performa Per Jenis Surat</h3>
            </div>
            <span class="text-xs text-slate-500 font-semibold">Update Otomatis</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[750px] text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Jenis Surat / Layanan</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Total Berkas</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Disetujui</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Menunggu</th>
                        <th class="px-6 py-4 text-center whitespace-nowrap">Ditolak</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Tingkat Persetujuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($breakdown_list)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-semibold text-sm">
                                Belum ada data pengajuan surat dalam sistem.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($breakdown_list as $row): 
                            $rate = $row['total'] > 0 ? round(($row['setuju'] / $row['total']) * 100, 1) : 0;
                        ?>
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-6 py-4 font-bold text-slate-900"><?php echo htmlspecialchars($row['jenis_surat']); ?></td>
                                <td class="px-6 py-4 text-center font-bold text-slate-800"><?php echo $row['total']; ?></td>
                                <td class="px-6 py-4 text-center font-bold text-emerald-700"><?php echo $row['setuju']; ?></td>
                                <td class="px-6 py-4 text-center font-bold text-amber-600"><?php echo $row['menunggu']; ?></td>
                                <td class="px-6 py-4 text-center font-bold text-rose-700"><?php echo $row['tolak']; ?></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <span class="font-bold text-slate-800"><?php echo $rate; ?>%</span>
                                        <div class="w-16 h-2 bg-slate-200 rounded-full overflow-hidden no-print">
                                            <div class="h-full bg-emerald-600 rounded-full" style="width: <?php echo $rate; ?>%;"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Print Signatures Section -->
    <div class="hidden print:flex justify-end pt-16 text-slate-800 text-sm">
        <div class="text-left w-72 space-y-16">
            <div>
                <p>Cisayong, <?php echo date('d F Y'); ?></p>
                <p class="font-bold">Camat Cisayong</p>
            </div>
            <div>
                <p class="font-bold text-slate-900 underline">AYI MULYANA HERNIWAN, SE., M.Si.</p>
                <p>NIP. 19721003 199403 1 012</p>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
