<?php
$page_title = "Panduan & Persyaratan Pencairan ADD";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_desa.php';
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-5xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full">
                <span class="material-icons-outlined text-xs">menu_book</span>
                <span>Manual & Tata Kelola Perangkat Desa</span>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Panduan Verifikasi Pencairan ADD</h2>
            <p class="text-sm text-slate-500">Persyaratan dan tata cara pengajuan Surat Rekomendasi Camat untuk Pencairan Alokasi Dana Desa (ADD).</p>
        </div>
        <a href="<?php echo BASE_URL; ?>desa/dashboard_desa.php#buat-pengajuan-add" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 shrink-0">
            <span class="material-icons-outlined text-lg">play_arrow</span>
            <span>Mulai Ajukan ADD</span>
        </a>
    </div>

    <!-- Content Grid -->
    <div class="space-y-8">
        <!-- Dasark Hukum Card -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl space-y-4">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-amber-50 text-amber-700 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="material-icons-outlined text-2xl">account_balance</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Peruntukan Alokasi Dana Desa</h3>
                    <p class="text-xs text-slate-500">Mekanisme penyaluran dana penyeimbangan desa</p>
                </div>
            </div>
            <p class="text-slate-600 text-sm leading-relaxed pt-2">
                Alokasi Dana Desa (ADD) adalah dana yang dialokasikan oleh Pemerintah Kabupaten/Kota untuk desa, yang bersumber dari bagian dana perimbangan keuangan pusat dan daerah yang diterima oleh Kabupaten/Kota.
            </p>
            <p class="text-slate-600 text-sm leading-relaxed">
                Untuk proses pengajuan ke Bupati Tasikmalaya c.q. Kepala DPMD Kabupaten Tasikmalaya, instansi desa diwajibkan melampirkan <strong>Surat Rekomendasi Pencairan</strong> dari Camat Cisayong beserta lampiran kelengkapan berkas yang telah diferifikasi.
            </p>
        </div>

        <!-- 9 Poin Persyaratan Card -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl space-y-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-900 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="material-icons-outlined text-2xl">inventory</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">9 Kelengkapan Wajib (Checklist)</h3>
                    <p class="text-xs text-slate-500">Pastikan 9 berkas fisik ini telah disiapkan di tingkat desa</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">1</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Surat Permohonan</h4>
                        <p class="text-xs text-slate-500 mt-1">Surat permohonan resmi dari Kepala Desa ke Camat.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">2</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Fakta Integritas</h4>
                        <p class="text-xs text-slate-500 mt-1">Komitmen pelaksanaan anggaran yang transparan dan akuntabel.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">3</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Bukti Pembayaran / Laporan</h4>
                        <p class="text-xs text-slate-500 mt-1">Laporan serapan pencairan tahap sebelumnya.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">4</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">SPTJM</h4>
                        <p class="text-xs text-slate-500 mt-1">Surat Pernyataan Tanggungjawab Mutlak bermaterai.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">5</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">KTP Kades & Bendahara</h4>
                        <p class="text-xs text-slate-500 mt-1">Salinan KTP Kepala Desa dan Bendahara Desa aktif.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">6</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Rekening Pemerintahan</h4>
                        <p class="text-xs text-slate-500 mt-1">Salinan buku rekening kas umum desa.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">7</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">NPWP Instansi</h4>
                        <p class="text-xs text-slate-500 mt-1">Salinan NPWP resmi Pemerintah Desa.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">8</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Lembar Verifikasi</h4>
                        <p class="text-xs text-slate-500 mt-1">Lembar verifikasi tingkat kecamatan yang ditandatangani Kasi.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-3">
                    <span class="w-6 h-6 rounded-full bg-blue-900 text-white flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">9</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Data SOTK & Perangkat</h4>
                        <p class="text-xs text-slate-500 mt-1">Data jumlah Kaur, Kasi, Kadus, BPD, RT dan RW.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
