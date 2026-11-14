<?php
$page_title = "Panduan & Persyaratan Dispensasi Nikah";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_masyarakat.php';
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-5xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="space-y-2">
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full">
                <span class="material-icons-outlined text-xs">import_contacts</span>
                <span>Pusat Layanan & Informasi Publik</span>
            </div>
            <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Panduan Persyaratan Dispensasi Nikah</h2>
            <p class="text-sm text-slate-500">Penjelasan prosedur pengajuan dispensasi waktu nikah (kurang dari 10 hari kerja) di lingkungan Kantor Kecamatan Cisayong.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>masyarakat/dashboard_masyarakat.php#buat-pengajuan" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-bold px-8 py-4 rounded-2xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2 shrink-0">
            <span class="material-icons-outlined text-lg">rate_review</span>
            <span>Mulai Buat Pengajuan</span>
        </a>
    </div>

    <!-- Content Grid -->
    <div class="space-y-8">
        <!-- Dasark Hukum Card -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl space-y-4">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-700 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="material-icons-outlined text-2xl">gavel</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Dasar Hukum & Peruntukan</h3>
                    <p class="text-xs text-slate-500">Peraturan yang melandasi terbitnya Dispensasi Nikah Camat</p>
                </div>
            </div>
            <p class="text-slate-600 text-sm leading-relaxed pt-2">
                Berdasarkan <strong>Peraturan Pemerintah (PP) Nomor 9 Tahun 1975 Bab II Pasal 1</strong>, pendaftaran kehendak nikah di Kantor Urusan Agama (KUA) sekurang-kurangnya dilakukan 10 (sepuluh) hari kerja sebelum tanggal pelaksanaan akad nikah.
            </p>
            <p class="text-slate-600 text-sm leading-relaxed">
                Apabila pendaftaran dan jadwal pelaksanaan akad nikah dilakukan dalam waktu kurang dari 10 hari kerja, maka calon pengantin diwajibkan melampirkan <strong>Surat Dispensasi Nikah</strong> yang ditandatangani oleh Camat (atas nama Bupati/Kepala Daerah).
            </p>
        </div>

        <!-- Syarat Kelengkapan Card -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl space-y-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-50 text-blue-900 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="material-icons-outlined text-2xl">rule_folder</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Kelengkapan Persyaratan Berkas</h3>
                    <p class="text-xs text-slate-500">Persiapkan dokumen berikut untuk diinput pada formulir aplikasi</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-4">
                    <span class="material-icons-outlined text-blue-900 mt-1">description</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Surat Pengantar RT & RW</h4>
                        <p class="text-xs text-slate-500 mt-1">Dilengkapi tanda tangan dan stempel sah dari pengurus lingkungan setempat (Desa/Kelurahan).</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-4">
                    <span class="material-icons-outlined text-blue-900 mt-1">contact_mail</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Identitas KTP & Kartu Keluarga (KK)</h4>
                        <p class="text-xs text-slate-500 mt-1">Salinan atau foto KTP & KK milik calon pengantin pria dan wanita.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-4">
                    <span class="material-icons-outlined text-blue-900 mt-1">auto_stories</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Pendaftaran KUA & Jadwal Akad</h4>
                        <p class="text-xs text-slate-500 mt-1">Bukti tanda pendaftaran dari KUA yang menunjukkan tanggal pelaksanaan akad nikah yang kurang dari 10 hari.</p>
                    </div>
                </div>
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 flex items-start space-x-4">
                    <span class="material-icons-outlined text-blue-900 mt-1">verified_user</span>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Surat Keterangan / Alasan Mendesak</h4>
                        <p class="text-xs text-slate-500 mt-1">Keterangan alasan keperluan menyegerakan akad nikah (misal: penugasan kerja, kondisi keluarga, dll).</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prosedur Pengajuan Steps -->
        <div class="bg-white p-8 rounded-3xl border border-slate-200 shadow-xl space-y-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-emerald-50 text-emerald-800 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="material-icons-outlined text-2xl">near_me</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Alur Prosedur Verifikasi</h3>
                    <p class="text-xs text-slate-500">Proses dari awal mengisi form hingga pencetakan dokumen</p>
                </div>
            </div>

            <div class="space-y-6 pt-2">
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold text-sm shrink-0">1</div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Mengisi Form Dasbor</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Masyarakat melengkapi form pengajuan di aplikasi, mencakup biodata diri dan pasangan serta jadwal akad.</p>
                    </div>
                </div>
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold text-sm shrink-0">2</div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Verifikasi Admin Camat</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Petugas kecamatan akan memverifikasi kesesuaian berkas. Jika benar, surat akan ditandatangani secara elektronik (TTE) oleh Camat.</p>
                    </div>
                </div>
                <div class="flex items-start space-x-4">
                    <div class="w-8 h-8 bg-emerald-700 text-white rounded-full flex items-center justify-center font-bold text-sm shrink-0">3</div>
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Cetak Surat Mandiri</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Setelah status berubah menjadi 'Disetujui', Anda dapat mencetak surat PDF ber-TTE resmi atau mengunduh dokumen MS Word.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
