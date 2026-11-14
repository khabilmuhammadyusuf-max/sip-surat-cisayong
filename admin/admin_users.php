<?php
$page_title = "Manajemen Akun & Data Pengguna";
include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/sidebar_admin.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php" . "");
    exit;
}

$success_msg = '';
$error_msg   = '';

// Handle CRUD actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'masyarakat');
        $instansi_desa = trim($_POST['instansi_desa'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($username) && !empty($email)) {
            $username = explode('@', $email)[0];
        }

        if (!empty($nama) && !empty($email) && !empty($password)) {
            // Check if email exists
            $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE email = ?");
            $stmtCek->execute([$email]);
            if ($stmtCek->fetchColumn() > 0) {
                $error_msg = "Alamat email '$email' sudah terdaftar di sistem!";
            } else {
                $passHash = password_hash($password, PASSWORD_BCRYPT);
                $stmtIns = $pdo->prepare("INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`, `instansi_desa`, `phone`) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([$nama, $username, $email, $passHash, $role, $instansi_desa ?: null, $phone ?: null]);
                $success_msg = "Pengguna baru '$nama' ($role) berhasil ditambahkan dengan email '$email'!";
            }
        } else {
            $error_msg = "Harap mengisi Nama, Email Login, dan Password.";
        }
    } elseif ($action === 'edit') {
        $edit_id = (int)($_POST['user_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? 'masyarakat');
        $instansi_desa = trim($_POST['instansi_desa'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($edit_id > 0 && !empty($nama) && !empty($email)) {
            // Check email conflict
            $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE email = ? AND id != ?");
            $stmtCek->execute([$email, $edit_id]);
            if ($stmtCek->fetchColumn() > 0) {
                $error_msg = "Email '$email' sudah dipakai oleh pengguna lain!";
            } else {
                if (!empty($password)) {
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtUpd = $pdo->prepare("UPDATE `users` SET nama=?, username=?, email=?, password=?, role=?, instansi_desa=?, phone=? WHERE id=?");
                    $stmtUpd->execute([$nama, $username, $email, $passHash, $role, $instansi_desa ?: null, $phone ?: null, $edit_id]);
                } else {
                    $stmtUpd = $pdo->prepare("UPDATE `users` SET nama=?, username=?, email=?, role=?, instansi_desa=?, phone=? WHERE id=?");
                    $stmtUpd->execute([$nama, $username, $email, $role, $instansi_desa ?: null, $phone ?: null, $edit_id]);
                }
                $success_msg = "Data pengguna '$nama' berhasil diperbarui!";
            }
        }
    } elseif ($action === 'delete') {
        $del_id = (int)($_POST['user_id'] ?? 0);
        if ($del_id > 0 && $del_id !== $_SESSION['user_id']) {
            $stmtDel = $pdo->prepare("DELETE FROM `users` WHERE id = ?");
            $stmtDel->execute([$del_id]);
            $success_msg = "Pengguna berhasil dihapus dari sistem.";
        } elseif ($del_id === $_SESSION['user_id']) {
            $error_msg = "Anda tidak dapat menghapus akun Anda sendiri saat sedang login!";
        }
    }
}

// Fetch all users
$stmtUsers = $pdo->query("SELECT * FROM `users` ORDER BY role, created_at DESC");
$users = $stmtUsers->fetchAll();
?>

<!-- Main Content Area -->
<main class="flex-1 p-6 md:p-10 max-w-7xl mx-auto space-y-8 overflow-y-auto">
    <!-- Action Header -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 text-blue-900 font-bold text-xs bg-blue-50 border border-blue-200 px-3 py-1 rounded-full mb-2">
                <span class="material-icons-outlined text-xs">manage_accounts</span>
                <span>Administrator Modul (Kecamatan Cisayong)</span>
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Manajemen Master Data Pengguna</h2>
            <p class="text-xs text-slate-500">Kelola akun Masyarakat (Pemohon Dispen Nikah), Perangkat Desa (ADD), dan Admin Camat.</p>
        </div>
        <a href="#tambah-user" class="w-full sm:w-auto bg-blue-900 hover:bg-blue-800 text-white font-bold px-7 py-3.5 rounded-2xl shadow-lg shadow-blue-900/20 hover:scale-105 transition-all flex items-center justify-center space-x-2">
            <span class="material-icons-outlined text-lg">person_add</span>
            <span>Tambah Akun Baru</span>
        </a>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-6 rounded-3xl shadow-md flex items-center space-x-4 animate-fade-in-up">
            <div class="w-14 h-14 bg-emerald-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-emerald-600/30">
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
            <div class="w-14 h-14 bg-rose-600 text-white rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-rose-600/30">
                <span class="material-icons-outlined text-3xl">error</span>
            </div>
            <div class="space-y-1">
                <h4 class="font-extrabold text-lg text-rose-900">Gagal Memproses Data</h4>
                <p class="text-xs text-rose-700 font-medium"><?php echo htmlspecialchars($error_msg); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- User Table -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-6 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-900 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-900/20">
                    <span class="material-icons-outlined text-xl">people</span>
                </div>
                <h3 class="font-bold text-lg text-slate-900">Daftar Pengguna Terdaftar</h3>
            </div>
            <span class="text-xs text-slate-500 font-semibold">Total: <?php echo count($users); ?> akun</span>
        </div>
        <div class="overflow-x-auto rounded-b-3xl">
            <table class="w-full min-w-[850px] text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs text-slate-500 font-bold uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 whitespace-nowrap">Nama Pengguna</th>
                        <th class="px-6 py-4 whitespace-nowrap">Email Login</th>
                        <th class="px-6 py-4 whitespace-nowrap">Username / NIK</th>
                        <th class="px-6 py-4 whitespace-nowrap">Hak Akses (Role)</th>
                        <th class="px-6 py-4 whitespace-nowrap">Instansi / Kontak</th>
                        <th class="px-6 py-4 whitespace-nowrap">Tanggal Daftar</th>
                        <th class="px-6 py-4 text-right whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs shrink-0">
                                        <?php echo strtoupper(substr($u['nama'], 0, 2)); ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($u['nama']); ?></p>
                                        <p class="text-xs text-slate-400">ID: #<?php echo $u['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-bold text-blue-900 text-xs"><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                            <td class="px-6 py-4 font-semibold text-slate-800 text-xs"><?php echo htmlspecialchars($u['username']); ?></td>
                            <td class="px-6 py-4">
                                <?php if (is_kecamatan_role($u['role'])): ?>
                                    <span class="inline-flex items-center space-x-1 px-3 py-1 bg-purple-50 text-purple-800 rounded-full text-xs font-bold border border-purple-200">
                                        <span class="material-icons-outlined text-xs">verified_user</span>
                                        <span><?php echo htmlspecialchars(get_role_label($u['role'])); ?></span>
                                    </span>
                                <?php elseif ($u['role'] === 'desa'): ?>
                                    <span class="inline-flex items-center space-x-1 px-3 py-1 bg-teal-50 text-teal-800 rounded-full text-xs font-bold border border-teal-200">
                                        <span class="material-icons-outlined text-xs">account_balance</span>
                                        <span>Admin Desa</span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center space-x-1 px-3 py-1 bg-blue-50 text-blue-900 rounded-full text-xs font-bold border border-blue-200">
                                        <span class="material-icons-outlined text-xs">person</span>
                                        <span>Warga / Masyarakat</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($u['instansi_desa'] ?? '-'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($u['phone'] ?? 'Tidak ada telp'); ?></p>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500"><?php echo date('d M Y, H:i', strtotime($u['created_at'])); ?></td>
                            <td class="px-6 py-4 text-right space-x-1">
                                <!-- Edit Button -->
                                <button onclick="bukaEditUser(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-colors">
                                    <span class="material-icons-outlined text-xs mr-1">edit</span> Edit
                                </button>
                                <!-- Delete Form -->
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form id="form-delete-<?php echo $u['id']; ?>" action="<?php echo BASE_URL; ?>admin/admin_users.php" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="button" onclick="konfirmasiHapusUser(<?php echo $u['id']; ?>, <?php echo htmlspecialchars(json_encode($u['nama'])); ?>)" class="inline-flex items-center px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl text-xs font-bold transition-colors">
                                            <span class="material-icons-outlined text-xs mr-1">delete</span> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD USER FORM -->
    <div id="tambah-user" class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <div class="p-8 bg-slate-50 border-b border-slate-100 flex items-center space-x-4">
            <div class="w-14 h-14 bg-blue-900 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-900/20">
                <span class="material-icons-outlined text-3xl">person_add</span>
            </div>
            <div>
                <h3 class="font-extrabold text-xl text-slate-900">Formulir Tambah Pengguna Baru</h3>
                <p class="text-xs text-slate-500 mt-0.5">Daftarkan akun masyarakat, perangkat desa, atau administrator kecamatan</p>
            </div>
        </div>

        <form id="form-add-user" class="p-8 space-y-6" action="<?php echo BASE_URL; ?>admin/admin_users.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" placeholder="Contoh: Ahmad Subagja" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Alamat Email Login <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" placeholder="Contoh: ahmad@cisayong.desa.id" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Username / NIK / ID (Opsional)</label>
                    <input type="text" name="username" placeholder="Boleh NIK 16 digit atau dikosongkan (otomatis dari email)" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kata Sandi (Password) <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" placeholder="••••••••" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Peran / Hak Akses</label>
                    <select name="role" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm cursor-pointer">
                        <option value="masyarakat">Warga / Masyarakat (Surat Umum)</option>
                        <option value="desa">Admin Desa (Khusus Surat ADD)</option>
                        <option value="ptsp">Front Office (PTSP) Kecamatan</option>
                        <option value="kasi_kesra">Kasi Kesra & Pelayanan</option>
                        <option value="kasi_pemerintahan">Kasi Pemerintahan</option>
                        <option value="kasi_pmd">Kasi PMD / Ekbang</option>
                        <option value="kasi_trantib">Kasi Trantibum</option>
                        <option value="sekcam">Sekretaris Camat (Sekcam)</option>
                        <option value="camat">Camat</option>
                        <option value="admin">Superadmin Kecamatan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Instansi Desa (Khusus Role Desa)</label>
                    <input type="text" name="instansi_desa" placeholder="Contoh: Desa Jatihurip (Kosongkan jika masyarakat/admin)" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" placeholder="Contoh: 08123456789" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-blue-900 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex justify-end">
                <button type="submit" class="bg-blue-900 hover:bg-blue-800 text-white font-extrabold px-10 py-4 rounded-2xl shadow-xl shadow-blue-900/20 hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl">person_add</span>
                    <span>Simpan Akun Pengguna</span>
                </button>
            </div>
        </form>
    </div>

    <!-- EDIT USER MODAL (Dibuat Inline Form yang dimunculkan via JS) -->
    <div id="modal-edit-user" class="hidden bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden border-t-8 border-t-amber-500">
        <div class="p-8 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-amber-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-amber-500/20">
                    <span class="material-icons-outlined text-3xl">manage_accounts</span>
                </div>
                <div>
                    <h3 class="font-extrabold text-xl text-slate-900">Perbarui Data Akun Pengguna</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ubah nama, hak akses, instansi, atau setel ulang password</p>
                </div>
            </div>
            <button onclick="tutupEditUser()" class="w-10 h-10 rounded-full bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-600 font-bold transition-colors">✕</button>
        </div>

        <form id="form-edit-user" class="p-8 space-y-6" action="<?php echo BASE_URL; ?>admin/admin_users.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id" value="">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Alamat Email Login <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" id="edit_email" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Username / NIK / NIP</label>
                    <input type="text" name="username" id="edit_username" required oninput="if (/^\d+$/.test(this.value) && this.value.length > 16) this.value = this.value.slice(0, 16);" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Kata Sandi Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah sandi" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Peran / Hak Akses</label>
                    <select name="role" id="edit_role" required class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm cursor-pointer">
                        <option value="masyarakat">Warga / Masyarakat (Surat Umum)</option>
                        <option value="desa">Admin Desa (Khusus Surat ADD)</option>
                        <option value="ptsp">Front Office (PTSP) Kecamatan</option>
                        <option value="kasi_kesra">Kasi Kesra & Pelayanan</option>
                        <option value="kasi_pemerintahan">Kasi Pemerintahan</option>
                        <option value="kasi_pmd">Kasi PMD / Ekbang</option>
                        <option value="kasi_trantib">Kasi Trantibum</option>
                        <option value="sekcam">Sekretaris Camat (Sekcam)</option>
                        <option value="camat">Camat</option>
                        <option value="admin">Superadmin Kecamatan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Instansi Desa</label>
                    <input type="text" name="instansi_desa" id="edit_instansi_desa" placeholder="Contoh: Desa Jatihurip" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">Nomor Telepon / WhatsApp</label>
                    <input type="text" name="phone" id="edit_phone" placeholder="Contoh: 08123456789" class="w-full px-4 py-3.5 rounded-2xl border border-slate-200 focus:outline-none focus:border-amber-500 bg-slate-50 text-slate-800 font-medium text-sm">
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" onclick="tutupEditUser()" class="px-7 py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl transition-colors">Batal</button>
                <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-extrabold px-10 py-4 rounded-2xl shadow-xl shadow-amber-600/20 hover:scale-105 transition-all flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl">save</span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
function bukaEditUser(user) {
    const modal = document.getElementById('modal-edit-user');
    modal.classList.remove('hidden');
    
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_nama').value = user.nama;
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_instansi_desa').value = user.instansi_desa || '';
    document.getElementById('edit_phone').value = user.phone || '';
    
    modal.scrollIntoView({ behavior: 'smooth' });
}

function tutupEditUser() {
    document.getElementById('modal-edit-user').classList.add('hidden');
}

function konfirmasiHapusUser(id, nama) {
    Swal.fire({
        title: 'Konfirmasi Hapus',
        text: `Apakah Anda yakin ingin menghapus pengguna '${nama}'?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('form-delete-' + id).submit();
        }
    });
}

document.getElementById('form-add-user').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Simpan Akun Pengguna?',
        text: 'Apakah Anda yakin ingin mendaftarkan akun baru ini ke dalam sistem?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan Akun',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

document.getElementById('form-edit-user').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Simpan Perubahan Akun?',
        text: 'Apakah Anda yakin ingin memperbarui data akun pengguna ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Perbarui',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
            confirmButton: 'px-7 py-3 bg-amber-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
            cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
