<?php
$user_nama     = $_SESSION['nama'] ?? 'Petugas Kecamatan';
$user_username = $_SESSION['username'] ?? 'ptsp_kecamatan';
$user_role     = $_SESSION['role'] ?? 'admin';
$role_label    = get_role_label($user_role);

// Hitung jumlah antrean yang menunggu tindakan sesuai role yang login
$jumlah_menunggu = 0;
if (isset($pdo)) {
    if ($user_role === 'ptsp') {
        $stmtCekAntre = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status IN ('menunggu', 'menunggu_ptsp')");
        $jumlah_menunggu = (int)$stmtCekAntre->fetchColumn();
    } elseif (is_kasi_role($user_role)) {
        $stmtCekAntre = $pdo->prepare("SELECT COUNT(*) FROM `pengajuan` WHERE status = 'menunggu_kasi' AND (kasi_tujuan = ? OR kasi_tujuan IS NULL)");
        $stmtCekAntre->execute([$user_role]);
        $jumlah_menunggu = (int)$stmtCekAntre->fetchColumn();
    } elseif ($user_role === 'sekcam') {
        $stmtCekAntre = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status = 'menunggu_sekcam'");
        $jumlah_menunggu = (int)$stmtCekAntre->fetchColumn();
    } elseif ($user_role === 'camat') {
        $stmtCekAntre = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status = 'menunggu_camat'");
        $jumlah_menunggu = (int)$stmtCekAntre->fetchColumn();
    } else {
        // Superadmin melihat seluruh antrean aktif
        $stmtCekAntre = $pdo->query("SELECT COUNT(*) FROM `pengajuan` WHERE status IN ('menunggu', 'menunggu_ptsp', 'menunggu_kasi', 'menunggu_sekcam', 'menunggu_camat')");
        $jumlah_menunggu = (int)$stmtCekAntre->fetchColumn();
    }
}

// Inisial profil
$initials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $user_nama), 0, 2) ?: 'KC');
?>
<!-- Mobile Navigation Drawer Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden no-print transition-opacity"></div>

<!-- Sidebar Layout -->
<aside id="sidebar-menu" class="w-64 bg-white border-r border-slate-200 flex flex-col fixed md:sticky top-0 left-0 h-screen z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out no-print shadow-xl md:shadow-none">
    <!-- Brand Header -->
    <div class="h-16 bg-blue-900 text-white flex items-center px-6 space-x-3 shadow-sm">
        <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Tasikmalaya" class="w-8 h-8 object-contain">
        <div>
            <span class="text-base font-extrabold tracking-wider block leading-none">SIP-KECAMATAN</span>
            <span class="text-[10px] text-blue-200 font-medium">Alur Pelayanan Berjenjang</span>
        </div>
    </div>

    <!-- User Profile Banner -->
    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center space-x-3">
        <div class="w-10 h-10 bg-amber-600 text-white rounded-full flex items-center justify-center font-bold text-xs shadow-inner shrink-0">
            <?php echo htmlspecialchars($initials); ?>
        </div>
        <div class="overflow-hidden">
            <p class="font-bold text-xs text-slate-800 truncate"><?php echo htmlspecialchars($user_nama); ?></p>
            <p class="text-[11px] font-bold text-blue-900 truncate"><?php echo htmlspecialchars($role_label); ?></p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Meja Pelayanan</div>
        
        <a href="<?php echo BASE_URL; ?>admin/dashboard_admin.php" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_admin.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <div class="flex items-center space-x-3">
                <span class="material-icons-outlined text-lg">account_tree</span>
                <span>Dasbor & Antrean</span>
            </div>
            <?php if ($jumlah_menunggu > 0): ?>
                <span class="bg-rose-500 text-white text-xs px-2 py-0.5 rounded-full font-bold"><?php echo $jumlah_menunggu; ?></span>
            <?php endif; ?>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/verifikasi_admin.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'verifikasi_admin.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">fact_check</span>
            <span>
                <?php
                if ($user_role === 'ptsp') echo 'Validasi Berkas (PTSP)';
                elseif (is_kasi_role($user_role)) echo 'Substansi & Paraf Kasi';
                elseif ($user_role === 'sekcam') echo 'Paraf Koordinasi Sekcam';
                elseif ($user_role === 'camat') echo 'Approve & TTE Camat';
                else echo 'Meja Verifikasi Berjenjang';
                ?>
            </span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/admin_riwayat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_riwayat.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">history_edu</span>
            <span>Riwayat & Arsip Surat</span>
        </a>

        <a href="<?php echo BASE_URL; ?>admin/admin_laporan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_laporan.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">insert_chart_outlined</span>
            <span>Statistik & Laporan</span>
        </a>

        <?php if (in_array($user_role, ['admin', 'ptsp', 'sekcam', 'camat']) || is_kasi_role($user_role)): ?>
            <div class="pt-4 pb-1 text-xs font-semibold text-slate-400 uppercase tracking-wider px-3">Konfigurasi & Master</div>

            <a href="<?php echo BASE_URL; ?>admin/admin_master_surat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_master_surat.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                <span class="material-icons-outlined text-lg">folder_special</span>
                <span>Master Jenis Surat</span>
            </a>

            <a href="<?php echo BASE_URL; ?>admin/template_admin.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'template_admin.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                <span class="material-icons-outlined text-lg">auto_awesome</span>
                <span>Manajemen Template</span>
            </a>

            <a href="<?php echo BASE_URL; ?>admin/pengaturan_ttd.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'pengaturan_ttd.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                <span class="material-icons-outlined text-lg">draw</span>
                <span>Pengaturan Tanda Tangan</span>
            </a>

            <?php if ($user_role === 'admin'): ?>
                <a href="<?php echo BASE_URL; ?>admin/admin_users.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
                    <span class="material-icons-outlined text-lg">manage_accounts</span>
                    <span>Manajemen Data User</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            <span class="material-icons-outlined text-lg">picture_as_pdf</span>
            <span>Output PDF Ber-TTE</span>
        </a>

        <div class="pt-4 pb-1 text-xs font-semibold text-slate-400 uppercase tracking-wider px-3">Sesi Akun</div>
        
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <span class="material-icons-outlined text-lg">swap_horiz</span>
            <span>Ganti Role / Keluar</span>
        </a>
    </nav>

    <!-- Sidebar Footer Info -->
    <div class="p-4 border-t border-slate-100 text-xs text-slate-400">
        <p class="font-bold text-slate-600">PTSP → Kasi → Sekcam → Camat</p>
        <p>Sip-Surat Kecamatan v3.0</p>
    </div>
</aside>
