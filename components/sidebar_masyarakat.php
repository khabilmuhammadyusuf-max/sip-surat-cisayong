<?php
$user_nama = $_SESSION['nama'] ?? 'Ahmad Warga';
$user_username = $_SESSION['username'] ?? '3201928391820001';
?>
<!-- Mobile Navigation Drawer Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden no-print transition-opacity"></div>

<!-- Sidebar Layout -->
<aside id="sidebar-menu" class="w-64 bg-white border-r border-slate-200 flex flex-col fixed md:sticky top-0 left-0 h-screen z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out no-print shadow-xl md:shadow-none">
    <!-- Brand Header -->
    <div class="h-16 bg-blue-900 text-white flex items-center px-6 space-x-3 shadow-sm">
        <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Tasikmalaya" class="w-8 h-8 object-contain">
        <span class="text-xl font-bold tracking-wider">SIP-SURAT</span>
    </div>

    <!-- User Profile Banner -->
    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center space-x-3">
        <div class="w-10 h-10 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold text-sm shadow-inner">
            M
        </div>
        <div class="overflow-hidden">
            <p class="font-semibold text-sm text-slate-800 truncate"><?php echo htmlspecialchars($user_nama); ?></p>
            <p class="text-xs text-slate-500 truncate">Masyarakat • NIK <?php echo htmlspecialchars($user_username); ?></p>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-3 mb-2">Menu Utama</div>
        
        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_masyarakat.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">dashboard</span>
            <span>Dasbor Masyarakat</span>
        </a>

        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php#buat-pengajuan" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            <span class="material-icons-outlined text-lg">description</span>
            <span>Pengajuan Surat</span>
        </a>

        <a href="<?php echo BASE_URL; ?>masyarakat/masyarakat_riwayat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'masyarakat_riwayat.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">history</span>
            <span>Riwayat Pengajuan Saya</span>
        </a>

        <a href="<?php echo BASE_URL; ?>masyarakat/masyarakat_panduan.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo (basename($_SERVER['PHP_SELF']) == 'masyarakat_panduan.php') ? 'bg-blue-900 text-white shadow-md shadow-blue-900/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'; ?>">
            <span class="material-icons-outlined text-lg">menu_book</span>
            <span>Panduan & Persyaratan</span>
        </a>

        <a href="<?php echo BASE_URL; ?>surat/cetak_surat.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors">
            <span class="material-icons-outlined text-lg">print</span>
            <span>Cetak Surat</span>
        </a>

        <div class="pt-4 pb-1 text-xs font-semibold text-slate-400 uppercase tracking-wider px-3">Pengaturan</div>
        
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 transition-colors">
            <span class="material-icons-outlined text-lg">logout</span>
            <span>Keluar (Logout)</span>
        </a>
    </nav>

    <!-- Sidebar Footer Info -->
    <div class="p-4 border-t border-slate-100 text-xs text-slate-400">
        <p class="font-medium text-slate-500">Layanan Bantuan</p>
        <p>Senin - Jumat (08:00 - 15:30)</p>
    </div>
</aside>
