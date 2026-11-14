<?php
$page_title = "Master Surat & Form Builder";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || !is_kecamatan_role($_SESSION['role'] ?? '')) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit;
}

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $jenis_surat      = trim($_POST['jenis_surat'] ?? '');
        $kategori_pemohon = ($_POST['kategori_pemohon'] ?? 'masyarakat') === 'desa' ? 'desa' : 'masyarakat';
        $kasi_default     = trim($_POST['kasi_default'] ?? 'kasi_kesra');
        $form_fields      = $_POST['form_fields'] ?? '[]';

        if (!empty($jenis_surat)) {
            try {
                $stmtIns = $pdo->prepare("INSERT INTO `templates` 
                    (`jenis_surat`, `kategori_pemohon`, `kasi_default`, `kode_klasifikasi`, `format_nomor`, `judul_surat`, `pembuka`, `penutup`, `penandatangan_nama`, `penandatangan_nip`, `penandatangan_jabatan`, `penandatangan_pangkat`, `is_tte`, `form_fields`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([
                    $jenis_surat,
                    $kategori_pemohon,
                    $kasi_default,
                    '400',
                    '[KODE]/[URUT]/Kec/[TAHUN]',
                    strtoupper($jenis_surat),
                    "Berdasarkan permohonan yang bersangkutan serta hasil pemeriksaan berkas, dengan ini Camat Cisayong menerangkan bahwa:\n",
                    "Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.",
                    'AYI MULYANA HERNIWAN, SE., M.Si.',
                    '19721003 199403 1 012',
                    'CAMAT CISAYONG',
                    'Pembina Tk.I - IV/b',
                    1,
                    $form_fields
                ]);
                $success_msg = "Jenis surat '$jenis_surat' berhasil ditambahkan dan siap digunakan dalam alur berjenjang.";
            } catch (Exception $e) {
                $error_msg = "Gagal menambahkan jenis surat: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id               = (int)($_POST['id'] ?? 0);
        $jenis_surat      = trim($_POST['jenis_surat'] ?? '');
        $kategori_pemohon = ($_POST['kategori_pemohon'] ?? 'masyarakat') === 'desa' ? 'desa' : 'masyarakat';
        $kasi_default     = trim($_POST['kasi_default'] ?? 'kasi_kesra');
        $form_fields      = $_POST['form_fields'] ?? '[]';

        if (!empty($jenis_surat) && $id > 0) {
            try {
                $pdo->prepare("UPDATE `templates` SET `jenis_surat` = ?, `kategori_pemohon` = ?, `kasi_default` = ?, `form_fields` = ? WHERE id = ?")
                    ->execute([$jenis_surat, $kategori_pemohon, $kasi_default, $form_fields, $id]);
                $success_msg = "Jenis surat dan konfigurasi Kasi Terkait berhasil diperbarui.";
            } catch (Exception $e) {
                $error_msg = "Gagal memperbarui jenis surat: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM `templates` WHERE id = ?")->execute([$id]);
            $success_msg = "Jenis surat berhasil dihapus.";
        } catch (Exception $e) {
            $error_msg = "Gagal menghapus jenis surat: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->query("SELECT id, jenis_surat, kategori_pemohon, kasi_default, form_fields FROM `templates` ORDER BY kategori_pemohon ASC, id ASC");
$templates = $stmt->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Top Action Bar -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">folder_special</span>
                <span>Master Jenis Surat & Routing Kasi Terkait</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Jenis Surat & Form Builder</h2>
            <p class="text-xs text-slate-500">Atur pembagian Jenis Surat Umum (Warga) vs Khusus Desa (ADD), serta pemetaan otomatis ke Kasi Terkait.</p>
        </div>
        <button onclick="openAddModal()" class="bg-blue-900 hover:bg-blue-800 text-white font-extrabold px-6 py-3 rounded-2xl shadow-xl hover:scale-105 transition-all flex items-center space-x-2">
            <span class="material-icons-outlined text-xl">add_circle</span>
            <span>Tambah Jenis Surat</span>
        </button>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl shadow-md flex items-center space-x-4">
            <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">task_alt</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-emerald-900">Berhasil!</h4>
                <p class="text-xs text-emerald-700 font-medium"><?php echo htmlspecialchars($success_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-6 rounded-3xl shadow-md flex items-center space-x-4">
            <div class="w-14 h-14 bg-rose-600 text-white rounded-2xl flex items-center justify-center shrink-0">
                <span class="material-icons-outlined text-3xl">error</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-rose-900">Gagal!</h4>
                <p class="text-xs text-rose-700 font-medium"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- List Tabel -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[950px] text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Nama Jenis Surat</th>
                        <th class="px-6 py-4 whitespace-nowrap">Hak Akses Pemohon</th>
                        <th class="px-6 py-4 whitespace-nowrap">Disposisi Kasi Terkait</th>
                        <th class="px-6 py-4 whitespace-nowrap">Field Form</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($templates as $row): 
                        $fields = json_decode($row['form_fields'], true) ?? [];
                        $total_fields = count($fields);
                    ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-bold text-slate-900 whitespace-nowrap">
                                <?php echo htmlspecialchars($row['jenis_surat']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($row['kategori_pemohon'] === 'desa'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-teal-50 text-teal-900 border border-teal-200 rounded-full text-xs font-bold whitespace-nowrap shadow-sm">
                                        <span>🏛️</span>
                                        <span>Khusus Admin Desa (ADD)</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-900 border border-blue-200 rounded-full text-xs font-bold whitespace-nowrap shadow-sm">
                                        <span>👤</span>
                                        <span>Surat Umum (Warga)</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-xs font-bold text-slate-700 whitespace-nowrap">
                                <?php echo htmlspecialchars(get_role_label($row['kasi_default'] ?: 'kasi_kesra')); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-lg text-xs font-bold"><?php echo $total_fields > 0 ? $total_fields . ' Field Dinamis' : 'Form Standar'; ?></span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a href="<?php echo BASE_URL; ?>admin/template_admin.php?jenis=<?php echo urlencode($row['jenis_surat']); ?>" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-xs font-bold transition-all shadow-sm">
                                        <span class="material-icons-outlined text-sm">edit_document</span>
                                        <span class="whitespace-nowrap">Edit Redaksi</span>
                                    </a>
                                    <button type="button" onclick='editSurat(<?php echo (int)$row["id"]; ?>, <?php echo json_encode($row["jenis_surat"]); ?>, <?php echo json_encode($row["kategori_pemohon"]); ?>, <?php echo json_encode($row["kasi_default"]); ?>, <?php echo json_encode($row["form_fields"] ?: "[]"); ?>)' class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl text-xs font-bold transition-all shadow-sm">
                                        <span class="material-icons-outlined text-sm">tune</span>
                                        <span class="whitespace-nowrap">Edit Form & Kasi</span>
                                    </button>
                                    <?php if ($row['jenis_surat'] !== 'Dispensasi Nikah' && $row['jenis_surat'] !== 'Alokasi Dana Daerah (ADD)'): ?>
                                        <button type="button" onclick="konfirmasiHapusSurat(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['jenis_surat'])); ?>')" class="inline-flex items-center justify-center w-8 h-8 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition-all shadow-sm" title="Hapus Jenis Surat">
                                            <span class="material-icons-outlined text-sm">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal Tambah/Edit Surat & Form Builder -->
<div id="modal-tambah" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 id="modal-title" class="font-extrabold text-xl text-slate-900">Buat Jenis Surat Baru</h3>
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <span class="material-icons-outlined">close</span>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Jenis Surat</label>
                        <input type="text" id="input_jenis_surat" placeholder="Contoh: Surat Keterangan Domisili" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kategori Pemohon</label>
                        <select id="input_kategori_pemohon" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-bold text-sm">
                            <option value="masyarakat">Warga (Surat Umum)</option>
                            <option value="desa">Admin Desa (Khusus Desa)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kasi Terkait Default</label>
                        <select id="input_kasi_default" class="w-full px-4 py-3 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-bold text-sm">
                            <?php foreach (get_kasi_list() as $k_code => $k_name): ?>
                                <option value="<?php echo htmlspecialchars($k_code); ?>"><?php echo htmlspecialchars(get_role_label($k_code)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="border-t border-slate-100 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Form Builder (Field Isian & Syarat Upload)</label>
                        <button type="button" onclick="addField()" class="bg-blue-50 text-blue-900 px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-blue-100 flex items-center space-x-1">
                            <span class="material-icons-outlined text-sm">add</span>
                            <span>Tambah Field</span>
                        </button>
                    </div>
                    <div id="fields-container" class="space-y-3"></div>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-slate-100 bg-slate-50 flex justify-end space-x-3">
            <button onclick="document.getElementById('modal-tambah').classList.add('hidden')" class="px-6 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-2xl hover:bg-slate-50 transition-all">Batal</button>
            <form method="POST" id="form-save-surat">
                <input type="hidden" name="action" id="hidden_action" value="add">
                <input type="hidden" name="id" id="hidden_id" value="">
                <input type="hidden" name="jenis_surat" id="hidden_jenis_surat">
                <input type="hidden" name="kategori_pemohon" id="hidden_kategori_pemohon">
                <input type="hidden" name="kasi_default" id="hidden_kasi_default">
                <input type="hidden" name="form_fields" id="hidden_form_fields">
                <button type="button" onclick="saveSurat()" class="px-6 py-3 bg-blue-900 text-white font-extrabold rounded-2xl shadow-xl shadow-blue-900/20 hover:scale-105 transition-all">
                    Simpan Konfigurasi Surat
                </button>
            </form>
        </div>
    </div>
</div>

<script>
let fields = [];

function openAddModal() {
    document.getElementById('modal-title').innerText = 'Buat Jenis Surat Baru';
    document.getElementById('input_jenis_surat').value = '';
    document.getElementById('input_kategori_pemohon').value = 'masyarakat';
    document.getElementById('input_kasi_default').value = 'kasi_kesra';
    document.getElementById('hidden_action').value = 'add';
    document.getElementById('hidden_id').value = '';
    fields = [];
    renderFields();
    document.getElementById('modal-tambah').classList.remove('hidden');
}

function editSurat(id, jenis, kategori, kasi, fieldsJson) {
    document.getElementById('modal-title').innerText = 'Edit Jenis Surat & Routing Kasi';
    document.getElementById('input_jenis_surat').value = jenis;
    document.getElementById('input_kategori_pemohon').value = kategori || 'masyarakat';
    document.getElementById('input_kasi_default').value = kasi || 'kasi_kesra';
    document.getElementById('hidden_action').value = 'edit';
    document.getElementById('hidden_id').value = id;
    
    try {
        fields = JSON.parse(fieldsJson);
        if (!Array.isArray(fields)) fields = [];
    } catch(e) {
        fields = [];
    }
    
    renderFields();
    document.getElementById('modal-tambah').classList.remove('hidden');
}

function renderFields() {
    const container = document.getElementById('fields-container');
    container.innerHTML = '';
    
    if (fields.length === 0) {
        container.innerHTML = '<div class="text-center py-6 text-sm text-slate-400 border-2 border-dashed border-slate-200 rounded-2xl">Menggunakan field bawaan surat. Klik "Tambah Field" jika ingin kustomisasi.</div>';
        return;
    }

    fields.forEach((f, index) => {
        const div = document.createElement('div');
        div.className = 'flex items-center space-x-3 bg-white p-3 border border-slate-200 rounded-2xl shadow-sm';
        div.innerHTML = `
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                <input type="text" placeholder="Label Field (Misal: Nama Usaha)" value="${f.label || ''}" onchange="updateField(${index}, 'label', this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                <input type="text" placeholder="Nama Variabel" value="${f.name || ''}" onchange="updateField(${index}, 'name', this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm font-mono text-slate-500">
                <select onchange="updateField(${index}, 'type', this.value)" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                    <option value="text" ${f.type==='text'?'selected':''}>Teks Pendek (Text)</option>
                    <option value="textarea" ${f.type==='textarea'?'selected':''}>Teks Panjang (Textarea)</option>
                    <option value="date" ${f.type==='date'?'selected':''}>Tanggal (Date)</option>
                    <option value="number" ${f.type==='number'?'selected':''}>Angka (Number)</option>
                    <option value="file" ${f.type==='file'?'selected':''}>Syarat Upload (File)</option>
                </select>
            </div>
            <button type="button" onclick="removeField(${index})" class="text-rose-500 hover:bg-rose-50 p-2 rounded-xl">
                <span class="material-icons-outlined">delete</span>
            </button>
        `;
        container.appendChild(div);
    });
}

function addField() {
    fields.push({ label: '', name: '', type: 'text', required: true });
    renderFields();
}

function updateField(index, key, value) {
    fields[index][key] = value;
    if (key === 'label' && fields[index]['name'] === '') {
        fields[index]['name'] = value.toLowerCase().replace(/[^a-z0-9]/g, '_');
        renderFields();
    }
}

function removeField(index) {
    fields.splice(index, 1);
    renderFields();
}

function saveSurat() {
    const jenis = document.getElementById('input_jenis_surat').value;
    if (!jenis || !jenis.trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Nama jenis surat wajib diisi!',
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
        return;
    }
    fields.forEach(f => {
        if (!f.name && f.label) {
            f.name = f.label.toLowerCase().replace(/[^a-z0-9]/g, '_');
        }
    });
    document.getElementById('hidden_jenis_surat').value = jenis.trim();
    document.getElementById('hidden_kategori_pemohon').value = document.getElementById('input_kategori_pemohon').value;
    document.getElementById('hidden_kasi_default').value = document.getElementById('input_kasi_default').value;
    document.getElementById('hidden_form_fields').value = JSON.stringify(fields);
    document.getElementById('form-save-surat').submit();
}

function konfirmasiHapusSurat(id, nama) {
    Swal.fire({
        title: 'Hapus Jenis Surat?',
        text: 'Apakah Anda yakin ingin menghapus template "' + nama + '"? Format formulir & template redaksinya akan ikut terhapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-6 py-2.5 bg-rose-600 text-white font-bold rounded-xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-6 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo BASE_URL; ?>admin/admin_master_surat.php';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
            document.body.appendChild(form);
            form.submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($success_msg)): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: <?php echo json_encode($success_msg); ?>,
            timer: 2500,
            showConfirmButton: false,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php elseif (!empty($error_msg)): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: <?php echo json_encode($error_msg); ?>,
            customClass: { popup: 'rounded-3xl border border-slate-200 shadow-2xl' }
        });
    <?php endif; ?>
});

renderFields();
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
