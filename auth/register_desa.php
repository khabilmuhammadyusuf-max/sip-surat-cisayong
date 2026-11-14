<?php
$page_title = "Pendaftaran Akun Perangkat Desa";
require_once __DIR__ . '/../components/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama          = trim($_POST['nama'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $username      = trim($_POST['username'] ?? '');
    $instansi_desa = trim($_POST['instansi_desa'] ?? '');
    $phone         = trim($_POST['phone'] ?? '');
    $password      = trim($_POST['password'] ?? '');

    if (!empty($nama) && !empty($email) && !empty($username) && !empty($instansi_desa) && !empty($password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format alamat email tidak valid!";
        } else {
            // Cek apakah email sudah ada
            $stmtCekEmail = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
            $stmtCekEmail->execute([$email]);
            if ($stmtCekEmail->fetchColumn() > 0) {
                $error = "Alamat email sudah terdaftar! Silakan gunakan email lain atau langsung masuk.";
            } else {
                // Cek apakah username sudah ada
                $stmtCek = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
                $stmtCek->execute([$username]);
                if ($stmtCek->fetchColumn() > 0) {
                    $error = "Username tersebut sudah terdaftar! Silakan gunakan username lain.";
                } else {
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`, `instansi_desa`, `phone`) VALUES (?, ?, ?, ?, 'desa', ?, ?)");
                    if ($stmt->execute([$nama, $username, $email, $passHash, $instansi_desa, $phone])) {
                        $success = "Pendaftaran perangkat desa berhasil! Silakan login menggunakan alamat email Anda.";
                    } else {
                        $error = "Gagal mendaftarkan akun. Terjadi kesalahan sistem.";
                    }
                }
            }
        }
    } else {
        $error = "Harap mengisi seluruh kolom yang wajib bertanda bintang (*).";
    }
}
?>

<div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-10 w-full">
    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-2xl border border-slate-200/80 space-y-6">
        
        <!-- Header & Logo Kecamatan -->
        <div class="text-center">
            <div class="flex justify-center mb-4">
                <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" 
                     alt="Logo Kecamatan Cisayong" 
                     class="h-20 w-auto object-contain drop-shadow-md">
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daftar Akun Admin Desa</h1>
            <p class="mt-1 text-xs font-semibold text-blue-900 uppercase tracking-wider">Khusus Pengajuan Surat Alokasi Dana Daerah (ADD)</p>
            <p class="text-xs text-slate-500">Kecamatan Cisayong - Kabupaten Tasikmalaya</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <span class="material-icons-outlined text-lg flex-shrink-0">error</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-5 rounded-2xl text-xs font-bold space-y-4">
                <div class="flex items-center space-x-2">
                    <span class="material-icons-outlined text-xl text-emerald-600">check_circle</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <a href="<?php echo BASE_URL; ?>auth/login.php" class="block w-full text-center bg-blue-900 hover:bg-blue-800 text-white py-3 rounded-xl font-bold transition-all shadow-lg shadow-blue-900/20">
                    Masuk Menggunakan Email
                </a>
            </div>
        <?php else: ?>

            <form class="space-y-4 pt-1" action="<?php echo BASE_URL; ?>auth/register_desa.php" method="POST">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Pejabat / Operator Desa <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama" required placeholder="Contoh: H. Suherman, S.E. (Sekdes)" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alamat Email (Untuk Login) <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required placeholder="desa.namadesa@cisayong.desa.id" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Desa <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="instansi_desa" required placeholder="Contoh: Desa Jatihurip" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Username ID Desa <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="username" required placeholder="Contoh: desa_jatihurip" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor HP / WhatsApp
                    </label>
                    <input type="text" name="phone" placeholder="Contoh: 08123456789" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Password <span class="text-rose-500">*</span>
                    </label>
                    <input type="password" name="password" required placeholder="Minimal 6 karakter" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <button type="submit" class="w-full bg-blue-900 hover:bg-blue-800 text-white py-3.5 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center space-x-2">
                    <span class="material-icons-outlined text-lg">apartment</span>
                    <span>Daftarkan Akun Desa</span>
                </button>
            </form>

        <?php endif; ?>

        <div class="text-center text-xs text-slate-500 pt-3 border-t border-slate-100">
            Sudah memiliki akun desa? <a href="<?php echo BASE_URL; ?>auth/login.php" class="text-blue-900 font-bold hover:underline">Masuk dengan Email</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
