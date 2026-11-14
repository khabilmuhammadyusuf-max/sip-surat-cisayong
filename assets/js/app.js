// Vanilla JavaScript terisolasi untuk interaktivitas Sip-Surat
document.addEventListener('DOMContentLoaded', () => {
    // 1. Hamburger Menu & Mobile Drawer Sidebar
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebarMenu = document.getElementById('sidebar-menu');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    const toggleSidebar = () => {
        if (sidebarMenu) {
            sidebarMenu.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }
    };

    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // 2. Floating Demo Switcher Widget Toggle
    const demoSwitcherBtn = document.getElementById('demo-switcher-btn');
    const demoSwitcherPanel = document.getElementById('demo-switcher-panel');
    const demoSwitcherClose = document.getElementById('demo-switcher-close');

    if (demoSwitcherBtn && demoSwitcherPanel) {
        demoSwitcherBtn.addEventListener('click', () => {
            demoSwitcherPanel.classList.toggle('hidden');
        });
    }
    if (demoSwitcherClose && demoSwitcherPanel) {
        demoSwitcherClose.addEventListener('click', () => {
            demoSwitcherPanel.classList.add('hidden');
        });
    }

    // 3. File Upload Validation (Menggunakan SweetAlert2 sebagai pengganti alert bawaan browser)
    const validateFileUpload = (inputElem, maxSizeMB, allowedExtensions) => {
        inputElem.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const fileSizeMB = file.size / (1024 * 1024);
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (!allowedExtensions.includes(fileExt)) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Format File Tidak Valid!',
                            text: `Ekstensi yang diizinkan: ${allowedExtensions.join(', ')}`,
                            icon: 'warning',
                            confirmButtonText: 'Mengerti',
                            customClass: {
                                popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                                confirmButton: 'px-7 py-3 bg-blue-900 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(`Format file tidak valid! Ekstensi yang diizinkan: ${allowedExtensions.join(', ')}`);
                    }
                    this.value = ''; // Reset input
                    return;
                }

                if (fileSizeMB > maxSizeMB) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Ukuran File Terlalu Besar!',
                            text: `Maksimal ukuran file adalah ${maxSizeMB}MB. File Anda: ${fileSizeMB.toFixed(2)}MB`,
                            icon: 'error',
                            confirmButtonText: 'Pilih File Lain',
                            customClass: {
                                popup: 'rounded-3xl border border-slate-200 shadow-2xl p-6',
                                confirmButton: 'px-7 py-3 bg-rose-600 text-white font-bold rounded-2xl shadow-lg hover:scale-105 transition-all'
                            },
                            buttonsStyling: false
                        });
                    } else {
                        alert(`Ukuran file terlalu besar! Maksimal ukuran file adalah ${maxSizeMB}MB. File Anda: ${fileSizeMB.toFixed(2)}MB`);
                    }
                    this.value = ''; // Reset input
                    return;
                }

                // Visual confirmation
                const label = this.parentElement.querySelector('.file-name-display');
                if (label) {
                    label.textContent = `${file.name} (${fileSizeMB.toFixed(1)}MB) ✅`;
                    label.classList.add('text-emerald-600', 'font-medium');
                }
            }
        });
    };

    // Attach to specific form inputs if present
    const inputNikahKtp = document.getElementById('file_ktp');
    const inputNikahKk = document.getElementById('file_kk');
    const inputNikahPengantar = document.getElementById('file_pengantar_rt');
    
    if (inputNikahKtp) validateFileUpload(inputNikahKtp, 2, ['pdf', 'jpg', 'jpeg', 'png']);
    if (inputNikahKk) validateFileUpload(inputNikahKk, 2, ['pdf', 'jpg', 'jpeg', 'png']);
    if (inputNikahPengantar) validateFileUpload(inputNikahPengantar, 2, ['pdf', 'jpg', 'jpeg', 'png']);

    const inputAddPengantar = document.getElementById('file_pengantar_kades');
    const inputAddRab = document.getElementById('file_rab');
    const inputAddLpj = document.getElementById('file_lpj');

    if (inputAddPengantar) validateFileUpload(inputAddPengantar, 5, ['pdf']);
    if (inputAddRab) validateFileUpload(inputAddRab, 5, ['pdf']);
    if (inputAddLpj) validateFileUpload(inputAddLpj, 5, ['pdf']);


    // 4. Modal Handling for Admin Verifikasi & Form Submissions
    const setupModal = (btnId, modalId, closeClass) => {
        const btn = document.getElementById(btnId);
        const modal = document.getElementById(modalId);
        if (btn && modal) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                modal.classList.remove('hidden');
            });
            const closeBtns = modal.querySelectorAll(`.${closeClass}`);
            closeBtns.forEach(c => {
                c.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            });
        }
    };

    // Modal Admin Verifikasi
    setupModal('btn-tolak', 'modal-tolak', 'modal-close');
    setupModal('btn-setujui', 'modal-setujui', 'modal-close');
    
    // Modal Hapus & Simpan Template
    setupModal('btn-simpan-template', 'modal-simpan-template', 'modal-close');
    setupModal('btn-reset-template', 'modal-reset-template', 'modal-close');

    // Modal Sukses Submit Form
    setupModal('btn-submit-nikah', 'modal-sukses-nikah', 'modal-close');
    setupModal('btn-submit-add', 'modal-sukses-add', 'modal-close');

    // 5. Tabs & Status Filters on Admin Dashboard
    const filterSelect = document.getElementById('filter-status');
    if (filterSelect) {
        filterSelect.addEventListener('change', function () {
            const selectedStatus = this.value;
            const rows = document.querySelectorAll('.pengajuan-row');
            rows.forEach(row => {
                if (selectedStatus === 'all' || row.dataset.status === selectedStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 6. Print Functionality Trigger
    const printBtn = document.getElementById('btn-cetak-surat');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }
});
