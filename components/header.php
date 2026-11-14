<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
// Define BASE_URL fallback if not loaded properly
if (!defined('BASE_URL')) {
    define('BASE_URL', '/surkec/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Sip-Surat' : 'Sip-Surat | Sistem Pelayanan Surat Digital Kecamatan'; ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            800: '#1e3a8a',
                            900: '#1e3a8a', // Biru Navy Formal
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Times New Roman', 'serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts Inter & Material Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Styling khusus untuk @media print kertas HVS A4 */
        @page {
            size: A4;
            margin: 20mm;
        }
        @media print {
            body { background: white; color: black; font-size: 12pt; }
            .no-print { display: none !important; }
            .print-area { width: 100%; margin: 0; padding: 0; box-shadow: none; border: none; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

<!-- Mobile Top Bar & Hamburger Menu Toggle -->
<header class="md:hidden bg-blue-900 text-white flex items-center justify-between px-4 py-3 shadow-md fixed top-0 left-0 right-0 z-40 no-print">
    <div class="flex items-center space-x-3">
        <img src="<?php echo BASE_URL; ?>assets/logo/Tasikmalaya_Regency_Seal.png" alt="Logo Kecamatan Cisayong" class="h-8 w-auto object-contain">
        <div>
            <h1 class="text-sm font-extrabold tracking-wider leading-none">SIP-SURAT</h1>
            <p class="text-[10px] text-blue-200 font-medium">Kecamatan Cisayong</p>
        </div>
    </div>
    <button id="hamburger-btn" class="p-1.5 focus:outline-none hover:bg-blue-800 rounded-lg transition-colors">
        <span class="material-icons-outlined text-2xl">menu</span>
    </button>
</header>

<!-- Wrapper for sidebar and main content -->
<div class="flex-1 flex pt-14 md:pt-0">
