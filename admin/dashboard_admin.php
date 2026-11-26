<?php
$page_title = "Dasbor Kecamatan - Monitoring Alur Berjenjang";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$user_role  = $_SESSION['role'];
$role_label = get_role_label($user_role);
$success_msg = $_GET['msg'] ?? '';

// Fetch Stats per Tahapan Alur Berjenjang
$stmtStats = $pdo->query("SELECT status, COUNT(*) as cnt FROM `pengajuan` GROUP BY status");
$stats = [
    'menunggu_ptsp'   => 0,
    'menunggu_kasi'   => 0,
    'menunggu_sekcam' => 0,
    'menunggu_camat'  => 0,
    'disetujui'       => 0,
    'ditolak'         => 0,
    'total'           => 0
];
while ($row = $stmtStats->fetch()) {
    $st = ($row['status'] === 'menunggu' || empty($row['status'])) ? 'menunggu_ptsp' : $row['status'];
    if (isset($stats[$st])) {
        $stats[$st] += (int)$row['cnt'];
    }
    $stats['total'] += (int)$row['cnt'];
}

// Fetch Submissions with User Info
$stmtList = $pdo->query("SELECT p.*, u.nama, u.username, u.instansi_desa, u.phone, u.role as pemohon_role FROM `pengajuan` p JOIN `users` u ON p.user_id = u.id ORDER BY p.waktu_pengajuan DESC");
$pengajuan_list = $stmtList->fetchAll();

// Cari 1 pengajuan yang menjadi tugas meja user saat ini
$next_verif = null;
$tugas_meja_count = 0;
foreach ($pengajuan_list as $p) {
    if (can_role_verify_submission($user_role, $p)) {
        $tugas_meja_count++;
        if (!$next_verif) {
            $next_verif = $p['id'];
        }
    }
}
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-indigo-900 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="z-10 space-y-2">
            <div class="inline-flex items-center space-x-2 bg-amber-400 text-slate-900 px-3 py-1 rounded-full text-xs font-black tracking-wider uppercase">
                <span class="material-icons-outlined text-sm">account_tree</span>
                <span>Meja Kerja: <?php echo htmlspecialchars($role_label); ?></span>
            </div>
            <h2 class="text-3xl font-extrabold tracking-tight">Alur Pelayanan Terpadu Kecamatan Cisayong</h2>
            <p class="text-blue-100 text-sm max-w-2xl">
                <?php if ($user_role === 'ptsp'): ?>
                    Periksa kelengkapan berkas dari Warga (Surat Umum) dan Admin Desa (Surat ADD). Kembalikan jika belum lengkap, atau teruskan ke Kasi Terkait jika valid.
                <?php elseif (is_kasi_role($user_role)): ?>
                    Periksa substansi permohonan & redaksi surat pada bidang Anda, lalu berikan <strong>Persetujuan & Terbitkan Surat Resmi</strong> secara langsung.
                <?php else: ?>
                    Pantau dan kelola seluruh tahapan verifikasi terpadu (Front Office Layanan → Kasi Terkait → Surat Resmi Diterbitkan).
                <?php endif; ?>
            </p>
        </div>
        <div class="z-10 flex flex-wrap gap-2.5">
            <?php if ($next_verif): ?>
                <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php?id=<?php echo $next_verif; ?>" class="bg-white text-blue-900 font-extrabold px-6 py-3.5 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2 shrink-0">
                    <span class="material-icons-outlined text-xl">fact_check</span>
                    <span>Proses Tugas Meja Anda (<?php echo $tugas_meja_count; ?>)</span>
                </a>
            <?php else: ?>
                <button type="button" onclick="filterTahapan('all')" class="bg-white text-blue-900 font-extrabold px-5 py-3 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2 shrink-0 text-xs">
                    <span class="material-icons-outlined text-lg">toc</span>
                    <span>Pantau Semua Antrean (<?php echo count($pengajuan_list); ?>)</span>
                </button>
                <a href="<?php echo BASE_URL; ?>admin/admin_riwayat.php" class="bg-white/20 hover:bg-white/30 text-white font-bold px-4 py-3 rounded-2xl transition-all flex items-center space-x-2 shrink-0 text-xs border border-white/20">
                    <span class="material-icons-outlined text-lg">history_edu</span>
                    <span>Arsip Surat Selesai</span>
                </a>
                <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" class="bg-white/20 hover:bg-white/30 text-white font-bold px-4 py-3 rounded-2xl transition-all flex items-center space-x-2 shrink-0 text-xs border border-white/20">
                    <span class="material-icons-outlined text-lg">draw</span>
                    <span>Pengaturan TTD</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="absolute -right-10 -bottom-10 opacity-10 text-white select-none pointer-events-none">
            <span class="material-icons-outlined text-[200px]">admin_panel_settings</span>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl shadow-md flex items-center space-x-4">
            <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-600/30">
                <span class="material-icons-outlined text-3xl">task_alt</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-emerald-900">Status Alur Berjenjang Diperbarui</h4>
                <p class="text-xs text-emerald-700 font-medium"><?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pipeline Analytics Cards (Alur Terpadu: Layanan PTSP -> Kasi -> Selesai) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div onclick="filterTahapan('menunggu_ptsp')" title="Klik untuk memfilter antrean di Meja Layanan / PTSP" class="cursor-pointer bg-white p-5 rounded-2xl border <?php echo $user_role === 'ptsp' ? 'border-amber-400 ring-2 ring-amber-100' : 'border-slate-200'; ?> shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="w-9 h-9 bg-amber-50 text-amber-700 rounded-xl flex items-center justify-center">
                    <span class="material-icons-outlined text-lg">support_agent</span>
                </span>
                <span class="text-[10px] font-extrabold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">TAHAP 1</span>
            </div>
            <p class="text-2xl font-black text-slate-800"><?php echo $stats['menunggu_ptsp']; ?></p>
            <p class="text-[11px] font-bold text-slate-500 mt-0.5">Front Office (Layanan / PTSP)</p>
        </div>

        <div onclick="filterTahapan('menunggu_kasi')" title="Klik untuk memfilter antrean di Meja Kasi Terkait" class="cursor-pointer bg-white p-5 rounded-2xl border <?php echo is_kasi_role($user_role) ? 'border-blue-500 ring-2 ring-blue-100' : 'border-slate-200'; ?> shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="w-9 h-9 bg-blue-50 text-blue-900 rounded-xl flex items-center justify-center">
                    <span class="material-icons-outlined text-lg">rate_review</span>
                </span>
                <span class="text-[10px] font-extrabold text-blue-900 bg-blue-50 px-2 py-0.5 rounded-full">TAHAP 2</span>
            </div>
            <p class="text-2xl font-black text-slate-800"><?php echo $stats['menunggu_kasi']; ?></p>
            <p class="text-[11px] font-bold text-slate-500 mt-0.5">Kasi Terkait (Pemeriksaan & Setujui)</p>
        </div>

        <div onclick="filterTahapan('disetujui')" title="Klik untuk memfilter surat selesai yang telah diterbitkan" class="cursor-pointer bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="w-9 h-9 bg-emerald-50 text-emerald-700 rounded-xl flex items-center justify-center">
                    <span class="material-icons-outlined text-lg">task_alt</span>
                </span>
                <span class="text-[10px] font-extrabold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">SELESAI</span>
            </div>
            <p class="text-2xl font-black text-slate-800"><?php echo $stats['disetujui']; ?></p>
            <p class="text-[11px] font-bold text-slate-500 mt-0.5">Surat Resmi Diterbitkan</p>
        </div>

        <div onclick="filterTahapan('ditolak')" title="Klik untuk memfilter berkas yang dikembalikan / ditolak" class="cursor-pointer bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-95 transition-all">
            <div class="flex items-center justify-between mb-2">
                <span class="w-9 h-9 bg-rose-50 text-rose-600 rounded-xl flex items-center justify-center">
                    <span class="material-icons-outlined text-lg">assignment_return</span>
                </span>
                <span class="text-[10px] font-extrabold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">REVISI</span>
            </div>
            <p class="text-2xl font-black text-slate-800"><?php echo $stats['ditolak']; ?></p>
            <p class="text-[11px] font-bold text-slate-500 mt-0.5">Ditolak / Perbaikan Berkas</p>
        </div>
    </div>

    <!-- Management Queue Table Card -->
    <div id="tabel-antrean-card" class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="material-icons-outlined text-blue-900 text-2xl">toc</span>
                <div>
                    <h3 class="font-bold text-lg text-slate-800">Daftar Antrean Berkas Permohonan Surat</h3>
                    <p class="text-xs text-slate-400">Menampilkan posisi berkas pada alur Front Office (PTSP) → Kasi Terkait → Sekcam → Camat</p>
                </div>
            </div>
            <div class="flex items-center space-x-3 w-full sm:w-auto">
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter Tahap:</label>
                <select id="filter-status" class="px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:border-blue-900 transition-colors">
                    <option value="all">Semua Tahapan</option>
                    <option value="tugas_saya" <?php echo $tugas_meja_count > 0 ? 'selected' : ''; ?>>📌 Tugas Meja Saya (<?php echo $tugas_meja_count; ?>)</option>
                    <option value="menunggu_ptsp">1. Di Meja PTSP</option>
                    <option value="menunggu_kasi">2. Di Meja Kasi Terkait</option>
                    <option value="menunggu_sekcam">3. Di Meja Sekcam</option>
                    <option value="menunggu_camat">4. Di Meja Camat (TTE)</option>
                    <option value="disetujui">5. Selesai (Terbit PDF)</option>
                    <option value="ditolak">Ditolak (Upload Ulang)</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[780px] text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Nomor / Jenis Surat</th>
                        <th class="px-6 py-4 whitespace-nowrap">Pemohon & Kategori</th>
                        <th class="px-6 py-4 whitespace-nowrap">Kasi Terkait</th>
                        <th class="px-6 py-4 whitespace-nowrap">Tahapan Saat Ini</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Tindakan Pejabat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($pengajuan_list)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 font-semibold text-sm">
                                Belum ada antrean pengajuan surat yang masuk.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pengajuan_list as $row): 
                            $jenis_surat = $row['jenis_surat'];
                            $st_norm = ($row['status'] === 'menunggu' || empty($row['status'])) ? 'menunggu_ptsp' : $row['status'];
                            $nomor_tampil = $row['nomor_surat_generated'] ?? 'REG-' . date('Ymd', strtotime($row['waktu_pengajuan'])) . '-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                            $pemohon = $row['nama'];
                            $keterangan = !empty($row['instansi_desa']) ? "Admin Desa: " . $row['instansi_desa'] : "Warga • NIK: " . $row['username'];
                            $kasi_tujuan = $row['kasi_tujuan'] ?: get_default_kasi_for_surat($jenis_surat);
                            $sInfo = get_status_info($st_norm, $kasi_tujuan);
                            $is_my_task = can_role_verify_submission($user_role, $row);

                            // Cari no telepon untuk notif WhatsApp
                            $user_phone = $row['phone'] ?? '';
                            if (empty($user_phone) && !empty($row['data_form'])) {
                                $df = json_decode($row['data_form'], true) ?? [];
                                $user_phone = $df['no_hp'] ?? ($df['telepon'] ?? ($df['nomor_hp'] ?? ($df['kontak'] ?? ($df['wa'] ?? ''))));
                            }
                            $wa_row_url = get_whatsapp_notification_url($user_phone, $pemohon, $jenis_surat, $nomor_tampil, $row['id'], 'disetujui');
                        ?>
                            <tr class="pengajuan-row hover:bg-slate-50/70 <?php echo $is_my_task ? 'bg-amber-50/30' : ''; ?>" data-status="<?php echo htmlspecialchars($st_norm); ?>" data-mytask="<?php echo $is_my_task ? '1' : '0'; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($nomor_tampil); ?></p>
                                        <?php if (!empty($row['is_revisi'])): ?>
                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-extrabold">Upload Ulang</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="inline-block mt-1 px-2.5 py-0.5 <?php echo $jenis_surat === 'Alokasi Dana Daerah (ADD)' ? 'bg-teal-50 text-teal-900 border-teal-200' : 'bg-blue-50 text-blue-900 border-blue-200'; ?> rounded-md text-[11px] font-bold border">
                                        <?php echo htmlspecialchars($jenis_surat); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($pemohon); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($keterangan); ?></p>
                                    <p class="text-[11px] text-slate-400"><?php echo date('d M Y, H:i', strtotime($row['waktu_pengajuan'])); ?></p>
                                </td>
                                <td class="px-6 py-4 text-xs font-bold text-slate-700">
                                    <?php echo htmlspecialchars(get_role_label($kasi_tujuan)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-full text-xs font-bold border whitespace-nowrap <?php echo $sInfo['badge_class']; ?>">
                                        <span class="material-icons-outlined text-sm flex-shrink-0"><?php echo $sInfo['icon']; ?></span>
                                        <span class="whitespace-nowrap"><?php echo htmlspecialchars($sInfo['label']); ?></span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <?php if ($is_my_task): ?>
                                        <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center space-x-1.5 px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white rounded-xl text-xs font-extrabold shadow-md shadow-blue-900/15 transition-all">
                                            <span class="material-icons-outlined text-sm">fact_check</span>
                                            <span>
                                                <?php
                                                if ($st_norm === 'menunggu_ptsp') echo 'Validasi PTSP';
                                                elseif ($st_norm === 'menunggu_kasi') echo 'Substansi & Paraf Kasi';
                                                elseif ($st_norm === 'menunggu_sekcam') echo 'Paraf Koordinasi Sekcam';
                                                elseif ($st_norm === 'menunggu_camat') echo 'Approve & TTE Camat';
                                                else echo 'Proses Berkas';
                                                ?>
                                            </span>
                                        </a>
                                    <?php elseif ($st_norm === 'disetujui'): ?>
                                        <div class="inline-flex items-center gap-1.5 justify-end whitespace-nowrap">
                                            <?php if (!empty($wa_row_url)): ?>
                                                <a href="<?php echo htmlspecialchars($wa_row_url); ?>" target="_blank" class="inline-flex items-center space-x-1 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm" title="Kirim Notifikasi WhatsApp Selesai ke Pemohon">
                                                    <span class="material-icons-outlined text-sm">chat</span>
                                                    <span>Kirim WA</span>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center space-x-1 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition-all">
                                                <span class="material-icons-outlined text-sm">picture_as_pdf</span>
                                                <span>Lihat PDF & TTE</span>
                                            </a>
                                        </div>
                                    <?php elseif ($st_norm === 'ditolak'): ?>
                                        <button onclick="lihatAlasanTolak(<?php echo htmlspecialchars(json_encode($row['catatan_tolak'] ?? '-')); ?>)" class="inline-flex items-center space-x-1 px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded-xl text-xs font-bold transition-all">
                                            <span class="material-icons-outlined text-sm">help_outline</span>
                                            <span>Catatan Tolak</span>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center space-x-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all">
                                            <span class="material-icons-outlined text-sm">visibility</span>
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

<script>
function applyQueueFilter() {
    const status = document.getElementById('filter-status').value;
    const rows = document.querySelectorAll('.pengajuan-row');
    rows.forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else if (status === 'tugas_saya') {
            row.style.display = (row.getAttribute('data-mytask') === '1') ? '' : 'none';
        } else {
            row.style.display = (row.getAttribute('data-status') === status) ? '' : 'none';
        }
    });
}

function filterTahapan(status) {
    const sel = document.getElementById('filter-status');
    if (sel) {
        sel.value = status;
        applyQueueFilter();
        const tbl = document.getElementById('tabel-antrean-card');
        if (tbl) {
            tbl.scrollIntoView({ behavior: 'smooth' });
        }
    }
}

document.getElementById('filter-status').addEventListener('change', applyQueueFilter);
document.addEventListener('DOMContentLoaded', function() {
    applyQueueFilter();
    
    <?php if (!empty($success_msg)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: <?php echo json_encode($success_msg); ?>,
            timer: 3000,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});

function lihatAlasanTolak(catatan) {
    Swal.fire({
        title: 'Alasan Penolakan / Pengembalian Berkas',
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
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
