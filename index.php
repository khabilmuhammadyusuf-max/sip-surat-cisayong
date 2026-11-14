<?php
$page_title = "Sistem Pelayanan Surat Digital Kecamatan Cisayong";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Kabupaten Tasikmalaya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: { 900: '#1e3a8a' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between">

<!-- Top Navigation -->
<header class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <img src="assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Kabupaten Tasikmalaya" class="h-12 w-auto object-contain drop-shadow">
            <div>
                <h1 class="text-lg sm:text-xl font-black text-blue-900 tracking-tight leading-tight">SIP-SURAT</h1>
                <p class="text-xs text-slate-600 font-semibold">Sistem Pelayanan Surat Digital Kecamatan Cisayong</p>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="auth/login.php" class="text-sm font-bold text-blue-900 hover:text-blue-800 px-4 py-2 rounded-xl transition-all">Masuk</a>
            <a href="auth/register_masyarakat.php" class="bg-blue-900 hover:bg-blue-800 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all">Daftar Warga</a>
        </div>
    </div>
</header>

<!-- Hero Section -->
<main class="flex-1 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20 flex flex-col justify-center w-full">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        <!-- Left Content -->
        <div class="lg:col-span-7 space-y-6">
            <div class="inline-flex items-center space-x-2 bg-blue-50 border border-blue-200 rounded-full px-4 py-1.5 text-blue-900 text-xs font-bold shadow-sm">
                <span class="material-icons-outlined text-sm">verified</span>
                <span>Pemerintah Kecamatan Cisayong - Kabupaten Tasikmalaya</span>
            </div>
            <h2 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Sistem Pelayanan Surat Digital <br>
                <span class="text-blue-900 bg-gradient-to-r from-blue-900 to-indigo-800 bg-clip-text text-transparent">Kecamatan Cisayong</span>
            </h2>
            <p class="text-base sm:text-lg text-slate-600 max-w-2xl leading-relaxed">
                Pelayanan surat terpadu dan modern tanpa antre fisik di kantor. Pengajuan surat umum bagi warga (SKTM, Surat Keterangan Ahli Waris, Dispensasi Nikah, SKU) serta pengajuan berkas Alokasi Dana Daerah (ADD) bagi perangkat desa secara cepat, berjenjang, dan transparan.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <a href="auth/register_masyarakat.php" class="bg-blue-900 hover:bg-blue-800 text-white text-center font-bold px-7 py-3.5 rounded-2xl shadow-xl shadow-blue-900/25 hover:scale-105 transition-all flex items-center justify-center space-x-2">
                    <span class="material-icons-outlined text-xl">description</span>
                    <span>Ajukan Surat (Warga)</span>
                </a>
                <a href="auth/register_desa.php" class="bg-white hover:bg-slate-50 text-blue-900 border-2 border-slate-200 hover:border-blue-900 text-center font-bold px-7 py-3.5 rounded-2xl shadow-sm hover:scale-105 transition-all flex items-center justify-center space-x-2">
                    <span class="material-icons-outlined text-xl">account_balance</span>
                    <span>Portal Desa (Surat ADD)</span>
                </a>
                <a href="auth/login.php" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-center font-bold px-6 py-3.5 rounded-2xl hover:scale-105 transition-all flex items-center justify-center space-x-2">
                    <span class="material-icons-outlined text-xl">login</span>
                    <span>Login Masuk</span>
                </a>
            </div>
        </div>

        <!-- Right Feature Cards / Visual -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-slate-200 hover:border-blue-900/30 transition-all">
                <div class="flex items-center space-x-4 mb-3">
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-900">
                        <span class="material-icons-outlined text-2xl">account_tree</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Alur Verifikasi Berjenjang</h3>
                        <p class="text-xs text-slate-500">PTSP → Kasi Terkait → Sekcam → Camat</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Setiap dokumen melalui verifikasi berkas oleh Front Office PTSP, telaah substansi oleh Kasi, paraf koordinasi Sekcam, hingga persetujuan dan Tanda Tangan Elektronik Camat.
                </p>
            </div>

            <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-slate-200 hover:border-blue-900/30 transition-all">
                <div class="flex items-center space-x-4 mb-3">
                    <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-800">
                        <span class="material-icons-outlined text-2xl">qr_code_scanner</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Tanda Tangan & QR Code Resmi</h3>
                        <p class="text-xs text-slate-500">Validitas keaslian dokumen terjamin</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Dokumen dilengkapi tanda tangan elektronik resmi dan QR Code autentikasi yang dapat diunduh langsung dalam format PDF siap cetak.
                </p>
            </div>

            <div class="bg-white p-6 sm:p-7 rounded-3xl shadow-xl border border-slate-200 hover:border-blue-900/30 transition-all">
                <div class="flex items-center space-x-4 mb-3">
                    <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-800">
                        <span class="material-icons-outlined text-2xl">devices</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-slate-900">Akses Mandiri & Pantau Status</h3>
                        <p class="text-xs text-slate-500">Dapat diakses lewat HP dan Komputer</p>
                    </div>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Pemohon dapat memantau pergerakan posisi berkas secara transparan hingga dokumen selesai diproses.
                </p>
            </div>
        </div>
    </div>
</main>

<!-- Footer Content -->
<footer class="bg-white border-t border-slate-200 py-6 px-4 text-center text-slate-500 text-xs shadow-sm">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center space-x-2 text-blue-900 font-bold">
            <img src="assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo" class="h-6 w-auto object-contain">
            <span>Sistem Pelayanan Surat Digital Kecamatan Cisayong</span>
        </div>
        <p>&copy; <?php echo date('Y'); ?> Pemerintah Kecamatan Cisayong, Kabupaten Tasikmalaya. Hak Cipta Dilindungi.</p>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>
