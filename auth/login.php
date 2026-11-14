<?php
$page_title = "Login Sistem Pelayanan Surat";
require_once __DIR__ . '/../components/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['nama']          = $user['nama'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['email']         = $user['email'];
            $_SESSION['instansi_desa'] = $user['instansi_desa'];
            $_SESSION['jabatan']       = $user['jabatan'] ?? get_role_label($user['role']);
            $_SESSION['nip']           = $user['nip'] ?? '';
            $_SESSION['username']      = $user['username'];

            if ($user['role'] === 'masyarakat') {
                header("Location: " . BASE_URL . "masyarakat/dashboard_masyarakat.php");
                exit;
            } elseif ($user['role'] === 'desa') {
                header("Location: " . BASE_URL . "desa/dashboard_desa.php");
                exit;
            } elseif (is_kecamatan_role($user['role'])) {
                header("Location: " . BASE_URL . "admin/dashboard_admin.php");
                exit;
            }
        } else {
            $error = "Email atau Password salah! Pastikan email dan password sudah benar.";
        }
    } else {
        $error = "Harap mengisi alamat Email dan Password.";
    }
}
?>

<div class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 w-full">
    <div class="max-w-md w-full bg-white p-8 sm:p-10 rounded-3xl shadow-2xl border border-slate-200/80 space-y-6">
        
        <!-- Header & Logo Kecamatan -->
        <div class="text-center">
            <div class="flex justify-center mb-4">
                <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" 
                     alt="Logo Kecamatan Cisayong" 
                     class="h-20 w-auto object-contain drop-shadow-md hover:scale-105 transition-transform duration-300">
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Masuk ke Akun</h1>
            <p class="mt-1 text-xs font-semibold text-blue-900 uppercase tracking-wider">Sistem Pelayanan Surat Digital</p>
            <p class="text-xs text-slate-500">Kecamatan Cisayong - Kabupaten Tasikmalaya</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-2xl text-xs font-bold flex items-center space-x-2">
                <span class="material-icons-outlined text-lg flex-shrink-0">error</span>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form class="space-y-4" action="<?php echo BASE_URL; ?>auth/login.php" method="POST">
            <div>
                <label for="input-email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Alamat Email
                </label>
                <div class="relative">
                    <span class="material-icons-outlined absolute left-3.5 top-3.5 text-slate-400 text-lg">mail</span>
                    <input type="email" 
                           id="input-email" 
                           name="email" 
                           placeholder="nama@cisayong.desa.id" 
                           required 
                           autocomplete="email"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm transition-all">
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="input-password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                        Password
                    </label>
                </div>
                <div class="relative">
                    <span class="material-icons-outlined absolute left-3.5 top-3.5 text-slate-400 text-lg">lock</span>
                    <input type="password" 
                           id="input-password" 
                           name="password" 
                           placeholder="••••••••" 
                           required 
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-900/20 focus:border-blue-900 bg-slate-50 text-slate-900 font-medium text-sm transition-all">
                </div>
            </div>

            <button type="submit" class="w-full mt-2 bg-blue-900 hover:bg-blue-800 text-white py-3.5 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center space-x-2">
                <span class="material-icons-outlined text-lg">login</span>
                <span>Masuk Sekarang</span>
            </button>
        </form>

        <!-- Link Registrasi -->
        <div class="text-center text-xs text-slate-500 pt-4 border-t border-slate-100 space-y-1">
            <p>Belum memiliki akun?</p>
            <div class="flex items-center justify-center gap-2 font-bold text-blue-900">
                <a href="<?php echo BASE_URL; ?>auth/register_masyarakat.php" class="hover:underline flex items-center gap-1">
                    <span class="material-icons-outlined text-sm">person_add</span> Daftar Warga
                </a>
                <span class="text-slate-300">•</span>
                <a href="<?php echo BASE_URL; ?>auth/register_desa.php" class="hover:underline flex items-center gap-1">
                    <span class="material-icons-outlined text-sm">apartment</span> Daftar Admin Desa
                </a>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
