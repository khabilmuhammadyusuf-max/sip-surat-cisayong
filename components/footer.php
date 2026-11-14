</div> <!-- End Wrapper -->

<!-- Footer -->
<footer class="bg-white border-t border-slate-200 py-6 px-4 md:px-8 text-center text-slate-500 text-sm mt-auto no-print shadow-sm">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center space-x-2 text-blue-900 font-semibold">
            <span class="material-icons-outlined text-xl">account_balance</span>
            <span>Kantor Pelayanan Kecamatan Digital</span>
        </div>
        <p>&copy; <?php echo date('Y'); ?> Sip-Surat (Sistem Pelayanan Surat Digital Kecamatan). Hak Cipta Dilindungi.</p>
        <div class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-full border border-slate-200 font-semibold">
            Versi 2.0 (Fullstack DB Mode • SweetAlert2 Powered)
        </div>
    </div>
</footer>

<!-- Include Vanilla JS App -->
<script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>

<!-- SweetAlert2 Global Handlers & Auto-Triggers -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Mengecek parameter URL untuk pesan sukses atau error (misal ?msg=... atau ?error=...)
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const successMsg = urlParams.get('success');
    const errorMsg = urlParams.get('error');

    if (msg || successMsg) {
        Swal.fire({
            title: 'Berhasil!',
            text: msg || successMsg,
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            confirmButtonText: 'Tutup',
            customClass: {
                popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
            },
            buttonsStyling: false
        }).then(() => {
            // Bersihkan parameter URL tanpa refresh halaman agar alert tidak muncul berulang saat refresh
            window.history.replaceState(null, null, window.location.pathname);
        });
    }

    if (errorMsg) {
        Swal.fire({
            title: 'Informasi / Perhatian',
            text: errorMsg,
            icon: 'error',
            confirmButtonColor: '#e11d48',
            confirmButtonText: 'Mengerti',
            customClass: {
                popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                confirmButton: 'px-7 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
            },
            buttonsStyling: false
        }).then(() => {
            window.history.replaceState(null, null, window.location.pathname);
        });
    }

    // 2. Mengganti konfirmasi default browser (seperti tombol Keluar/Logout) dengan SweetAlert2
    const confirmLinks = document.querySelectorAll('a[href*="logout.php"]');
    confirmLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetHref = this.href;
            Swal.fire({
                title: 'Konfirmasi Keluar',
                text: 'Apakah Anda yakin ingin keluar dari sistem Sip-Surat?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Keluar',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                    confirmButton: 'px-7 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all mr-3',
                    cancelButton: 'px-7 py-3 bg-slate-100 text-slate-600 font-bold rounded-2xl hover:bg-slate-200 transition-all'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = targetHref;
                }
            });
        });
    });
});
</script>

</body>
</html>
