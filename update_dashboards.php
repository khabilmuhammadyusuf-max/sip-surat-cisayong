<?php
$file = __DIR__ . '/masyarakat/dashboard_masyarakat.php';
$content = file_get_contents($file);

// 1. Parse text and file fields
$replacement1 = <<<PHP
\$form_fields = json_decode(\$current_template['form_fields'] ?? '[]', true) ?? [];

\$text_fields = [];
\$file_fields = [];
foreach (\$form_fields as \$f) {
    if (\$f['type'] === 'file') {
        \$file_fields[] = \$f;
    } else {
        \$text_fields[] = \$f;
    }
}
PHP;
$content = str_replace("\$form_fields = json_decode(\$current_template['form_fields'] ?? '[]', true) ?? [];", $replacement1, $content);

// 2. Change POST handler for dynamic files
$post_search = <<<PHP
            \$files = [
                'file_ktp' => 'KTP Calon Pengantin / Pemohon',
                'file_kk'  => 'Kartu Keluarga',
                'file_pengantar_rt' => 'Surat Pengantar RT/RW'
            ];
PHP;
$post_replace = <<<PHP
            // Define dynamic files
            \$files = [];
            if (\$jenis_surat_post === 'Dispensasi Nikah' || empty(\$file_fields)) {
                \$files = [
                    'file_ktp' => 'KTP Calon Pengantin / Pemohon',
                    'file_kk'  => 'Kartu Keluarga',
                    'file_pengantar_rt' => 'Surat Pengantar RT/RW'
                ];
            } else {
                foreach (\$file_fields as \$f) {
                    \$files[\$f['name']] = \$f['label'];
                }
            }
PHP;
$content = str_replace($post_search, $post_replace, $content);

// 3. Change HTML rendering to use $text_fields
$content = str_replace('<?php if (!empty($form_fields)): ?>', '<?php if (!empty($text_fields)): ?>', $content);
$content = str_replace('foreach ($form_fields as $field):', 'foreach ($text_fields as $field):', $content);

// 4. Change HTML rendering for upload section
$upload_search = <<<HTML
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">badge</span>
                        <p class="text-xs font-bold text-slate-700">KTP Pemohon</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                        <input type="file" name="file_ktp" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">folder_shared</span>
                        <p class="text-xs font-bold text-slate-700">Kartu Keluarga (KK)</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                        <input type="file" name="file_kk" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">home_work</span>
                        <p class="text-xs font-bold text-slate-700">Surat Pengantar RT/RW</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                        <input type="file" name="file_pengantar_rt" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                </div>
HTML;

$upload_replace = <<<HTML
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <?php if (\$jenis_aktif === 'Dispensasi Nikah' || empty(\$file_fields)): ?>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">badge</span>
                            <p class="text-xs font-bold text-slate-700">KTP Pemohon</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                            <input type="file" name="file_ktp" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">folder_shared</span>
                            <p class="text-xs font-bold text-slate-700">Kartu Keluarga (KK)</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                            <input type="file" name="file_kk" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">home_work</span>
                            <p class="text-xs font-bold text-slate-700">Surat Pengantar RT/RW</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                            <input type="file" name="file_pengantar_rt" required class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                    <?php else: ?>
                        <?php foreach (\$file_fields as \$ff): ?>
                            <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-blue-900/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                                <span class="material-icons-outlined text-4xl text-blue-900 mb-2 group-hover:scale-110 transition-transform">upload_file</span>
                                <p class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars(\$ff['label']); ?></p>
                                <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file di sini</p>
                                <input type="file" name="<?php echo htmlspecialchars(\$ff['name']); ?>" <?php echo \$ff['required'] ? 'required' : ''; ?> class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf,.jpg,.jpeg,.png" onchange="this.previousElementSibling.textContent = this.files[0].name">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
HTML;

$content = str_replace($upload_search, $upload_replace, $content);

file_put_contents($file, $content);
echo "dashboard_masyarakat.php updated.\n";

// Update dashboard_desa.php
$file_desa = __DIR__ . '/desa/dashboard_desa.php';
$content_desa = file_get_contents($file_desa);

$desa_search_post = <<<PHP
            \$files = [
                'file_pengantar_kades' => 'Surat Pengantar Kepala Desa',
                'file_rab'             => 'Dokumen RAB (Anggaran)',
                'file_lpj'             => 'LPJ Tahap Sebelumnya'
            ];
PHP;
$desa_replace_post = <<<PHP
            // Cek template ADD
            \$stmtTmpl = \$pdo->prepare("SELECT form_fields FROM `templates` WHERE jenis_surat = 'Alokasi Dana Daerah (ADD)' LIMIT 1");
            \$stmtTmpl->execute();
            \$tmplADD = \$stmtTmpl->fetch();
            \$file_fields = [];
            if (\$tmplADD) {
                \$ffs = json_decode(\$tmplADD['form_fields'], true) ?? [];
                foreach (\$ffs as \$f) {
                    if (\$f['type'] === 'file') {
                        \$file_fields[] = \$f;
                    }
                }
            }

            if (empty(\$file_fields)) {
                \$files = [
                    'file_pengantar_kades' => 'Surat Pengantar Kepala Desa',
                    'file_rab'             => 'Dokumen RAB (Anggaran)',
                    'file_lpj'             => 'LPJ Tahap Sebelumnya'
                ];
            } else {
                \$files = [];
                foreach (\$file_fields as \$f) {
                    \$files[\$f['name']] = \$f['label'];
                }
            }
PHP;
$content_desa = str_replace($desa_search_post, $desa_replace_post, $content_desa);

$desa_search_upload = <<<HTML
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <!-- Surat Pengantar Kades -->
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">assignment_ind</span>
                        <p class="text-xs font-bold text-slate-700">Surat Pengantar Kepala Desa</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file PDF</p>
                        <input type="file" name="file_pengantar_kades" required accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                    <!-- RAB -->
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">description</span>
                        <p class="text-xs font-bold text-slate-700">Dokumen RAB (Anggaran)</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file PDF</p>
                        <input type="file" name="file_rab" required accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                    <!-- LPJ -->
                    <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                        <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">history_edu</span>
                        <p class="text-xs font-bold text-slate-700">LPJ Tahap Sebelumnya</p>
                        <p class="text-[11px] text-slate-400 mt-1 file-name-display">Opsional (Untuk Tahap II & III)</p>
                        <input type="file" name="file_lpj" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                    </div>
                </div>
HTML;

$desa_replace_upload = <<<HTML
<?php
// Cek template ADD untuk form display
\$stmtTmpl = \$pdo->prepare("SELECT form_fields FROM `templates` WHERE jenis_surat = 'Alokasi Dana Daerah (ADD)' LIMIT 1");
\$stmtTmpl->execute();
\$tmplADD = \$stmtTmpl->fetch();
\$file_fields = [];
if (\$tmplADD) {
    \$ffs = json_decode(\$tmplADD['form_fields'], true) ?? [];
    foreach (\$ffs as \$f) {
        if (\$f['type'] === 'file') {
            \$file_fields[] = \$f;
        }
    }
}
?>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <?php if (empty(\$file_fields)): ?>
                        <!-- Surat Pengantar Kades -->
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">assignment_ind</span>
                            <p class="text-xs font-bold text-slate-700">Surat Pengantar Kepala Desa</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file PDF</p>
                            <input type="file" name="file_pengantar_kades" required accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <!-- RAB -->
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">description</span>
                            <p class="text-xs font-bold text-slate-700">Dokumen RAB (Anggaran)</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file PDF</p>
                            <input type="file" name="file_rab" required accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                        <!-- LPJ -->
                        <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                            <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">history_edu</span>
                            <p class="text-xs font-bold text-slate-700">LPJ Tahap Sebelumnya</p>
                            <p class="text-[11px] text-slate-400 mt-1 file-name-display">Opsional (Untuk Tahap II & III)</p>
                            <input type="file" name="file_lpj" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.previousElementSibling.textContent = this.files[0].name">
                        </div>
                    <?php else: ?>
                        <?php foreach (\$file_fields as \$ff): ?>
                            <div class="border-2 border-dashed border-slate-200 rounded-3xl p-6 text-center hover:border-teal-800/40 transition-colors bg-slate-50/50 flex flex-col items-center justify-center relative group">
                                <span class="material-icons-outlined text-4xl text-teal-800 mb-2 group-hover:scale-110 transition-transform">upload_file</span>
                                <p class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars(\$ff['label']); ?></p>
                                <p class="text-[11px] text-slate-400 mt-1 file-name-display">Pilih file PDF</p>
                                <input type="file" name="<?php echo htmlspecialchars(\$ff['name']); ?>" <?php echo \$ff['required'] ? 'required' : ''; ?> class="absolute inset-0 opacity-0 cursor-pointer" accept=".pdf" onchange="this.previousElementSibling.textContent = this.files[0].name">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
HTML;

$content_desa = str_replace($desa_search_upload, $desa_replace_upload, $content_desa);
file_put_contents($file_desa, $content_desa);
echo "dashboard_desa.php updated.\n";
