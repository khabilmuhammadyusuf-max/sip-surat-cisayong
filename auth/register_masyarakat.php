<?php
$page_title = "Pendaftaran Akun Warga";
require_once __DIR__ . '/../components/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $nik      = trim($_POST['nik'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($nama) && !empty($nik) && !empty($email) && !empty($password)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Format alamat email tidak valid!";
        } else {
            // Cek apakah email sudah terdaftar
            $stmtCekEmail = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `email` = ?");
            $stmtCekEmail->execute([$email]);
            if ($stmtCekEmail->fetchColumn() > 0) {
                $error = "Alamat email sudah digunakan! Silakan gunakan email lain atau langsung masuk.";
            } else {
                // Cek NIK
                $stmtCekNik = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
                $stmtCekNik->execute([$nik]);
                if ($stmtCekNik->fetchColumn() > 0) {
                    $error = "NIK tersebut sudah terdaftar! Silakan login menggunakan email Anda.";
                } else {
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, ?, 'masyarakat', ?)");
                    if ($stmt->execute([$nama, $nik, $email, $passHash, $phone])) {
                        $success = "Pendaftaran berhasil! Silakan login menggunakan alamat email Anda.";
                    } else {
                        $error = "Gagal mendaftarkan akun. Terjadi kesalahan sistem.";
                    }
                }
            }
        }
    } else {
        $error = "Harap mengisi seluruh kolom yang bertanda bintang (*).";
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
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daftar Akun Warga</h1>
            <p class="mt-1 text-xs font-semibold text-blue-900 uppercase tracking-wider">Sistem Pelayanan Surat Digital</p>
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

            <form class="space-y-4 pt-1" action="<?php echo BASE_URL; ?>auth/register_masyarakat.php" method="POST">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nama Lengkap (Sesuai KTP) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nama" required placeholder="Contoh: Ahmad Wijaya" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Alamat Email (Untuk Login) <span class="text-rose-500">*</span>
                    </label>
                    <input type="email" name="email" required placeholder="nama@gmail.com" 
                           class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nomor Induk Kependudukan (NIK) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="nik" required maxlength="16" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 16);" 
                           title="NIK harus 16 digit angka" placeholder="16 digit angka NIK KTP" 
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
                    <span class="material-icons-outlined text-lg">person_add</span>
                    <span>Daftarkan Akun</span>
                </button>
            </form>

        <?php endif; ?>

        <div class="text-center text-xs text-slate-500 pt-3 border-t border-slate-100">
            Sudah memiliki akun? <a href="<?php echo BASE_URL; ?>auth/login.php" class="text-blue-900 font-bold hover:underline">Masuk dengan Email</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
