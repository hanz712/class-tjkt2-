<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajib_login(['wali_kelas']);

date_default_timezone_set('Asia/Jakarta');

/* =========================
   DATA DASHBOARD
========================= */

$total_siswa = 0;
$hadir_hari_ini = 0;
$belum_hadir = 0;

$tanggal_hari_ini = date("Y-m-d");

/* Total siswa */
$query_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM siswa"
);

if ($query_total) {
    $data_total = mysqli_fetch_assoc($query_total);
    $total_siswa = (int) ($data_total['total'] ?? 0);
}

/* Hadir hari ini */
$query_hadir = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM absensi
     WHERE tanggal = '$tanggal_hari_ini'
     AND status = 'Hadir'"
);

if ($query_hadir) {
    $data_hadir = mysqli_fetch_assoc($query_hadir);
    $hadir_hari_ini = (int) ($data_hadir['total'] ?? 0);
}

/* Belum hadir */
$belum_hadir = max(
    0,
    $total_siswa - $hadir_hari_ini
);

/* Nama wali */
$nama_wali = $_SESSION['nama'] ?? 'Wali Kelas';

/* =========================
   CEK AKUN
========================= */

$username_login = $_SESSION['username'] ?? '';

$profile_photo = '';
$stmt_profile = mysqli_prepare($conn, "SELECT profile_photo FROM users WHERE id = ? AND role = 'wali_kelas' LIMIT 1");
if ($stmt_profile) { $uid_profile=(int)($_SESSION['user_id']??0); mysqli_stmt_bind_param($stmt_profile,'i',$uid_profile); mysqli_stmt_execute($stmt_profile); mysqli_stmt_bind_result($stmt_profile,$profile_photo); mysqli_stmt_fetch($stmt_profile); mysqli_stmt_close($stmt_profile); }
$profile_photo_url = $profile_photo ? '../uploads/profile/' . rawurlencode($profile_photo) : '';

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard - XI TJKT 2
    </title>

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            min-height: 100%;
        }

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                #f4f7fb;

            color:
                #172033;

        }

        /* =========================
           LAYOUT
        ========================= */

        .app {

            min-height: 100vh;

        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: 270px;

            background:
                linear-gradient(
                    180deg,
                    #111827 0%,
                    #0f172a 100%
                );

            color: #ffffff;

            z-index: 1000;

            overflow-y: auto;

            transition:
                transform .25s ease;

            box-shadow:
                8px 0 30px
                rgba(15, 23, 42, .08);

        }

        /* =========================
           SIDEBAR BRAND + LOGO
        ========================= */

        .sidebar-brand {

            display: flex;

            align-items: center;

            gap: 12px;

            padding:
                22px 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,.08);

        }

        .sidebar-brand img {

            width: 52px;
            height: 52px;

            object-fit: cover;

            border-radius: 14px;

            flex-shrink: 0;

            background:
                rgba(255,255,255,.08);

            border:
                1px solid
                rgba(255,255,255,.15);

            box-shadow:
                0 8px 20px
                rgba(0,0,0,.25);

        }

        .brand-text {

            min-width: 0;

        }

        .brand-text strong {

            display: block;

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;

            white-space: nowrap;

        }

        .brand-text small {

            display: block;

            margin-top: 4px;

            color:
                rgba(255,255,255,.52);

            font-size: 11px;

            white-space: nowrap;

        }

        /* =========================
           USER INFO
        ========================= */

        .sidebar-user {

            padding:
                18px 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,.08);

        }

        .user-box {

            display: flex;

            align-items: center;

            gap: 11px;

        }

        .user-avatar {

            width: 40px;
            height: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(59,130,246,.18);

            color:
                #60a5fa;

            flex-shrink: 0;

        }

        .user-info {

            min-width: 0;

        }

        .user-name {

            font-size: 13px;

            font-weight: 600;

            color: #ffffff;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }

        .user-role {

            font-size: 11px;

            color:
                rgba(255,255,255,.5);

            margin-top: 3px;

        }

        /* =========================
           NAVIGATION
        ========================= */

        .nav-title {

            padding:
                20px 20px 9px;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1px;

            text-transform: uppercase;

            color:
                rgba(255,255,255,.35);

        }

        .sidebar-nav {

            padding:
                0 12px 20px;

        }

        .nav-link-custom {

            display: flex;

            align-items: center;

            gap: 12px;

            width: 100%;

            min-height: 44px;

            padding:
                11px 13px;

            margin-bottom: 4px;

            border-radius: 11px;

            color:
                rgba(255,255,255,.67);

            text-decoration: none;

            font-size: 13px;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;

        }

        .nav-link-custom i {

            width: 20px;

            text-align: center;

            font-size: 15px;

            flex-shrink: 0;

        }

        .nav-link-custom:hover {

            background:
                rgba(255,255,255,.07);

            color: #ffffff;

            transform:
                translateX(2px);

        }

        .nav-link-custom.active {

            background:
                rgba(59,130,246,.18);

            color: #ffffff;

            border:
                1px solid
                rgba(96,165,250,.15);

        }

        .nav-link-custom.active i {

            color:
                #60a5fa;

        }

        .nav-link-logout {

            color:
                #fca5a5;

        }

        .nav-link-logout:hover {

            background:
                rgba(239,68,68,.10);

            color:
                #fecaca;

        }

        /* =========================
           MAIN
        ========================= */

        .main {

            margin-left: 270px;

            min-height: 100vh;

        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {

            height: 72px;

            padding:
                0 30px;

            background:
                #ffffff;

            border-bottom:
                1px solid #e7ebf2;

            display: flex;

            align-items: center;

            justify-content: space-between;

            position: sticky;

            top: 0;

            z-index: 900;

        }

        .topbar-left {

            display: flex;

            align-items: center;

            gap: 15px;

        }

        .mobile-menu {

            display: none;

            width: 40px;
            height: 40px;

            border: none;

            border-radius: 10px;

            background:
                #f1f5f9;

            color:
                #334155;

            cursor: pointer;

            font-size: 17px;

        }

        .page-title {

            font-size: 20px;

            font-weight: 700;

            color:
                #111827;

        }

        .page-subtitle {

            margin-top: 3px;

            color:
                #94a3b8;

            font-size: 12px;

        }

        .topbar-right {

            display: flex;

            align-items: center;

            gap: 12px;

        }

        .date-badge {

            display: flex;

            align-items: center;

            gap: 8px;

            padding:
                9px 13px;

            border-radius: 10px;

            background:
                #f8fafc;

            color:
                #64748b;

            font-size: 12px;

        }

        /* =========================
           CONTENT
        ========================= */

        .content {

            padding:
                30px;

        }

        /* =========================
           WELCOME
        ========================= */

        .welcome {

            padding:
                25px 26px;

            border-radius: 18px;

            background:
                linear-gradient(
                    135deg,
                    #1e3a8a,
                    #2563eb
                );

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

            overflow: hidden;

            position: relative;

            box-shadow:
                0 15px 35px
                rgba(37,99,235,.18);

        }

        .welcome::after {

            content: "";

            position: absolute;

            width: 180px;
            height: 180px;

            border-radius: 50%;

            right: -60px;
            top: -80px;

            background:
                rgba(255,255,255,.07);

        }

        .welcome h2 {

            font-size: 21px;

            margin-bottom: 7px;

        }

        .welcome p {

            font-size: 13px;

            color:
                rgba(255,255,255,.72);

        }

        .welcome-icon {

            width: 65px;
            height: 65px;

            border-radius: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                rgba(255,255,255,.12);

            font-size: 27px;

            position: relative;

            z-index: 1;

        }

        /* =========================
           STAT CARDS
        ========================= */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 18px;

            margin-bottom: 25px;

        }

        .stat-card {

            background: #ffffff;

            border:
                1px solid #e8edf4;

            border-radius: 16px;

            padding: 20px;

            display: flex;

            align-items: center;

            gap: 15px;

            box-shadow:
                0 5px 20px
                rgba(15,23,42,.04);

        }

        .stat-icon {

            width: 50px;
            height: 50px;

            border-radius: 14px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            flex-shrink: 0;

        }

        .stat-icon.blue {

            background:
                #eff6ff;

            color:
                #2563eb;

        }

        .stat-icon.green {

            background:
                #ecfdf5;

            color:
                #16a34a;

        }

        .stat-icon.orange {

            background:
                #fff7ed;

            color:
                #ea580c;

        }

        .stat-label {

            font-size: 12px;

            color:
                #94a3b8;

            margin-bottom: 5px;

        }

        .stat-value {

            font-size: 24px;

            font-weight: 700;

            color:
                #111827;

        }

        /* =========================
           QUICK MENU
        ========================= */

        .section-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 14px;

        }

        .section-title {

            font-size: 17px;

            font-weight: 700;

            color:
                #111827;

        }

        .section-description {

            font-size: 12px;

            color:
                #94a3b8;

        }

        .quick-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 15px;

        }

        .quick-card {

            background:
                #ffffff;

            border:
                1px solid #e8edf4;

            border-radius: 15px;

            padding: 19px;

            text-decoration: none;

            color:
                #172033;

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;

            box-shadow:
                0 5px 20px
                rgba(15,23,42,.03);

        }

        .quick-card:hover {

            transform:
                translateY(-3px);

            border-color:
                #dbeafe;

            box-shadow:
                0 12px 25px
                rgba(15,23,42,.07);

        }

        .quick-icon {

            width: 43px;
            height: 43px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 13px;

            background:
                #eff6ff;

            color:
                #2563eb;

        }

        .quick-card:nth-child(2)
        .quick-icon {

            background:
                #ecfdf5;

            color:
                #16a34a;

        }

        .quick-card:nth-child(3)
        .quick-icon {

            background:
                #fff7ed;

            color:
                #ea580c;

        }

        .quick-card:nth-child(4)
        .quick-icon {

            background:
                #f5f3ff;

            color:
                #7c3aed;

        }

        .quick-title {

            font-size: 13px;

            font-weight: 700;

            margin-bottom: 5px;

        }

        .quick-description {

            font-size: 11px;

            line-height: 1.5;

            color:
                #94a3b8;

        }

        /* =========================
           OVERLAY
        ========================= */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(15,23,42,.5);

            z-index: 950;

        }

        /* =========================
           FOOTER
        ========================= */

        .footer {

            text-align: center;

            padding:
                25px 10px;

            color:
                #94a3b8;

            font-size: 11px;

        }

        /* =========================
           TABLET
        ========================= */

        @media (max-width: 1100px) {

            .quick-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 800px) {

            .sidebar {

                transform:
                    translateX(-100%);

            }

            .sidebar.open {

                transform:
                    translateX(0);

            }

            .sidebar-overlay.show {

                display: block;

            }

            .main {

                margin-left: 0;

            }

            .mobile-menu {

                display: flex;

                align-items: center;

                justify-content: center;

            }

            .topbar {

                padding:
                    0 16px;

            }

            .date-badge {

                display: none;

            }

            .content {

                padding:
                    20px 16px;

            }

            .stats-grid {

                grid-template-columns:
                    1fr;

            }

        }

        @media (max-width: 520px) {

            .welcome {

                padding:
                    20px;

            }

            .welcome h2 {

                font-size: 18px;

            }

            .welcome p {

                font-size: 12px;

            }

            .welcome-icon {

                width: 50px;
                height: 50px;

                font-size: 20px;

            }

            .quick-grid {

                grid-template-columns:
                    1fr;

            }

            .page-title {

                font-size: 17px;

            }

        }

    </style>

</head>

<body>

<div class="app">

    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <!-- LOGO -->
        <div class="sidebar-brand">

            <img
                src="../assets/images/logo.png"
                alt="Logo XI TJKT 2"
            >

            <div class="brand-text">

                <strong>
                    XI TJKT 2
                </strong>

                <small>
                    Class Management
                </small>

            </div>

        </div>

        <!-- USER -->
        <div class="sidebar-user">

            <div class="user-box">

                <div class="user-avatar">
                    <?php if ($profile_photo_url): ?>
                        <img src="<?= htmlspecialchars($profile_photo_url) ?>" alt="Foto wali kelas" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <i class="fa-solid fa-user-tie"></i>
                    <?php endif; ?>
                </div>

                <div class="user-info">

                    <div class="user-name">
                        <?= htmlspecialchars($nama_wali) ?>
                    </div>

                    <div class="user-role">
                        Wali Kelas
                    </div>

                </div>

            </div>

        </div>

        <!-- MENU -->
        <div class="nav-title">
            Menu Utama
        </div>

        <nav class="sidebar-nav">

            <a
                href="index.php"
                class="nav-link-custom active"
            >
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a
                href="siswa.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-users"></i>
                <span>Daftar Siswa</span>
            </a>

            <a
                href="../absensi/index.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-qrcode"></i>
                <span>Mulai Absensi</span>
            </a>

            <a
                href="../absensi/rekap.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-chart-column"></i>
                <span>Pantau Absensi</span>
            </a>

            <a
                href="jadwal.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-calendar-days"></i>
                <span>Jadwal Pelajaran</span>
            </a>

            <a
                href="piket.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-broom"></i>
                <span>Jadwal Piket</span>
            </a>

            <a
                href="tugas.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Tugas</span>
            </a>

            <a
                href="galeri.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-images"></i>
                <span>Galeri TJKT 2</span>
            </a>

            <div class="nav-title">
                Informasi Kelas
            </div>

            <a
                href="struktur.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-sitemap"></i>
                <span>Struktur Kelas</span>
            </a>

            <a
                href="tentang.php"
                class="nav-link-custom"
            >
                <i class="fa-solid fa-chalkboard-user"></i>
                <span>Tentang Wali Kelas</span>
            </a>

            <a href="pengaturan.php" class="nav-link-custom">
                <i class="fa-solid fa-gear"></i>
                <span>Pengaturan</span>
            </a>

            <div
                style="
                    height:1px;
                    background:rgba(255,255,255,.08);
                    margin:15px 8px;
                "
    
              
            ></div>

            <a
                href="../logout.php"
                class="nav-link-custom nav-link-logout"
                onclick="
                    return confirm(
                        'Yakin ingin keluar dari akun?'
                    );
                "
            >
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Keluar</span>
            </a>

        </nav>

    </aside>

    <!-- OVERLAY MOBILE -->
    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =========================
         MAIN
    ========================== -->

    <main class="main">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">

                <button
                    type="button"
                    class="mobile-menu"
                    id="mobileMenu"
                    aria-label="Buka menu"
                >
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>

                    <div class="page-title">
                        Dashboard
                    </div>

                    <div class="page-subtitle">
                        Panel Manajemen XI TJKT 2
                    </div>

                </div>

            </div>

            <div class="topbar-right">

                <div class="date-badge">

                    <i class="fa-regular fa-calendar"></i>

                    <?= date("d/m/Y") ?>

                </div>

            </div>

        </header>


        <!-- CONTENT -->
        <section class="content">

            <!-- WELCOME -->
            <div class="welcome">

                <div>

                    <h2>
                        Selamat Datang, Wali Kelas 👋
                    </h2>

                    <p>
                        Kelola data dan aktivitas
                        kelas XI TJKT 2 dari satu tempat.
                    </p>

                </div>

                <div class="welcome-icon">

                    <i class="fa-solid fa-chalkboard-user"></i>

                </div>

            </div>


            <!-- STATISTICS -->
            <div class="stats-grid">

                <!-- TOTAL SISWA -->
                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <div>

                        <div class="stat-label">
                            Total Siswa
                        </div>

                        <div class="stat-value">
                            <?= $total_siswa ?>
                        </div>

                    </div>

                </div>


                <!-- HADIR -->
                <div class="stat-card">

                    <div class="stat-icon green">

                        <i class="fa-solid fa-user-check"></i>

                    </div>

                    <div>

                        <div class="stat-label">
                            Hadir Hari Ini
                        </div>

                        <div class="stat-value">
                            <?= $hadir_hari_ini ?>
                        </div>

                    </div>

                </div>


                <!-- BELUM -->
                <div class="stat-card">

                    <div class="stat-icon orange">

                        <i class="fa-solid fa-user-clock"></i>

                    </div>

                    <div>

                        <div class="stat-label">
                            Belum Hadir
                        </div>

                        <div class="stat-value">
                            <?= $belum_hadir ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- QUICK MENU -->
            <div class="section-header">

                <div>

                    <div class="section-title">
                        Akses Cepat
                    </div>

                    <div class="section-description">
                        Fitur yang sering digunakan
                    </div>

                </div>

            </div>


            <div class="quick-grid">

                <!-- SISWA -->
                <a
                    href="siswa.php"
                    class="quick-card"
                >

                    <div class="quick-icon">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <div class="quick-title">
                        Data Siswa
                    </div>

                    <div class="quick-description">
                        Tambah, lihat, dan kelola
                        data siswa kelas.
                    </div>

                </a>


                <!-- ABSENSI -->
                <a
                    href="../absensi/index.php"
                    class="quick-card"
                >

                    <div class="quick-icon">

                        <i class="fa-solid fa-qrcode"></i>

                    </div>

                    <div class="quick-title">
                        Mulai Absensi
                    </div>

                    <div class="quick-description">
                        Tampilkan QR Code untuk
                        absensi siswa.
                    </div>

                </a>


                <!-- REKAP -->
                <a
                    href="rekap.php"
                    class="quick-card"
                >

                    <div class="quick-icon">

                        <i class="fa-solid fa-chart-column"></i>

                    </div>

                    <div class="quick-title">
                        Pantau Absensi
                    </div>

                    <div class="quick-description">
                        Lihat dan pantau rekap
                        kehadiran siswa.
                    </div>

                </a>


                <!-- JADWAL -->
                <a
                    href="jadwal.php"
                    class="quick-card"
                >

                    <div class="quick-icon">

                        <i class="fa-solid fa-calendar-days"></i>

                    </div>

                    <div class="quick-title">
                        Jadwal Pelajaran
                    </div>

                    <div class="quick-description">
                        Kelola jadwal pelajaran
                        kelas XI TJKT 2.
                    </div>

                </a>

            </div>


            <!-- FOOTER -->
            <div class="footer">

                XI TJKT 2 • Sistem Informasi Kelas

                <br>

                Tahun Pelajaran 2026 / 2027

            </div>

        </section>

    </main>

</div>


<script>

    const sidebar =
        document.getElementById("sidebar");

    const mobileMenu =
        document.getElementById("mobileMenu");

    const sidebarOverlay =
        document.getElementById("sidebarOverlay");


    function bukaSidebar() {

        sidebar.classList.add("open");

        sidebarOverlay.classList.add("show");

    }


    function tutupSidebar() {

        sidebar.classList.remove("open");

        sidebarOverlay.classList.remove("show");

    }


    mobileMenu.addEventListener(
        "click",
        function () {

            if (
                sidebar.classList.contains("open")
            ) {

                tutupSidebar();

            } else {

                bukaSidebar();

            }

        }
    );


    sidebarOverlay.addEventListener(
        "click",
        function () {

            tutupSidebar();

        }
    );


    /*
     * Tutup sidebar setelah memilih
     * menu pada perangkat mobile.
     */

    const navLinks =
        document.querySelectorAll(
            ".nav-link-custom"
        );


    navLinks.forEach(
        function (link) {

            link.addEventListener(
                "click",
                function () {

                    if (
                        window.innerWidth <= 800
                    ) {

                        tutupSidebar();

                    }

                }
            );

        }
    );

</script>

</body>

</html>