<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajib_login(['siswa']);

date_default_timezone_set('Asia/Jakarta');

/* =========================
   DATA SESSION
========================= */

$nis = trim($_SESSION['username'] ?? '');
$nama_session = $_SESSION['nama'] ?? 'Siswa';

if ($nis === '') {
    header("Location: ../login.php");
    exit;
}

/* =========================
   DATA SISWA
========================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, nis, nama, jenis_kelamin, qr_code
     FROM siswa
     WHERE nis = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $nis);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$siswa) {
    die("Data siswa tidak ditemukan.");
}

$siswa_id = (int) $siswa['id'];
$nama = $siswa['nama'];

/* =========================
   HARI INDONESIA
========================= */

$hariInggris = date('l');

$hariIndonesia = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu'
];

$hari = $hariIndonesia[$hariInggris] ?? $hariInggris;

$bulanIndonesia = [
    1  => 'Januari',
    2  => 'Februari',
    3  => 'Maret',
    4  => 'April',
    5  => 'Mei',
    6  => 'Juni',
    7  => 'Juli',
    8  => 'Agustus',
    9  => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$tanggal = date('j') . ' ' .
           $bulanIndonesia[(int) date('n')] . ' ' .
           date('Y');

/* =========================
   ABSENSI HARI INI
========================= */

$tanggal_db = date('Y-m-d');

$stmt = mysqli_prepare(
    $conn,
    "SELECT waktu, status, metode
     FROM absensi
     WHERE siswa_id = ?
     AND tanggal = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $siswa_id,
    $tanggal_db
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$absensi = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================
   JADWAL HARI INI
========================= */

$jadwal = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT jam_ke, mata_pelajaran, guru
     FROM jadwal
     WHERE hari = ?
     ORDER BY jam_ke ASC"
);

mysqli_stmt_bind_param($stmt, "s", $hari);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $jadwal[] = $row;
}

mysqli_stmt_close($stmt);

/* =========================
   PIKET HARI INI
========================= */

$piket = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT s.nama
     FROM piket p
     INNER JOIN siswa s ON s.id = p.siswa_id
     WHERE p.hari = ?
     ORDER BY s.nama ASC"
);

mysqli_stmt_bind_param($stmt, "s", $hari);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $piket[] = $row['nama'];
}

mysqli_stmt_close($stmt);

/* =========================
   TUGAS TERBARU
========================= */

$tugas = [];

$queryTugas = mysqli_query(
    $conn,
    "SELECT id, judul, guru, deskripsi, deadline
     FROM tugas
     ORDER BY created_at DESC
     LIMIT 5"
);

if ($queryTugas) {
    while ($row = mysqli_fetch_assoc($queryTugas)) {
        $tugas[] = $row;
    }
}

/* =========================
   GALERI TERBARU
========================= */

$galeri = [];

$queryGaleri = mysqli_query(
    $conn,
    "SELECT id, judul, gambar, deskripsi
     FROM galeri
     ORDER BY created_at DESC
     LIMIT 6"
);

if ($queryGaleri) {
    while ($row = mysqli_fetch_assoc($queryGaleri)) {
        $galeri[] = $row;
    }
}

/* =========================
   FITUR BARU: NOTIFIKASI + CHAT + FOTO PROFIL
========================= */
$unread_pengumuman = 0;
$chat_terbaru = [];
$foto_profil = '';

$stmt = mysqli_prepare($conn, "SELECT profile_photo FROM users WHERE id = ? AND role = 'siswa' LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    $u = mysqli_fetch_assoc($r);
    $foto_profil = $u['profile_photo'] ?? '';
    mysqli_stmt_close($stmt);
}

// Pengumuman masih mengikuti sistem JSON yang sudah dipakai admin.
$data_file_notif = __DIR__ . "/../data/pengumuman.json";
$pengumuman_notif = [];
if (file_exists($data_file_notif)) {
    $pengumuman_notif = json_decode(file_get_contents($data_file_notif), true);
    if (!is_array($pengumuman_notif)) $pengumuman_notif = [];
}
$announcement_ids = [];
foreach ($pengumuman_notif as $item) {
    $announcement_ids[] = (string)($item['id'] ?? '');
}
if ($announcement_ids) {
    $read_ids = [];
    $stmt = mysqli_prepare($conn, "SELECT announcement_id FROM announcement_reads WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $r = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($r)) $read_ids[(string)$row['announcement_id']] = true;
        mysqli_stmt_close($stmt);
        foreach ($announcement_ids as $aid) if ($aid !== '' && !isset($read_ids[$aid])) $unread_pengumuman++;
    }
}

$stmt = mysqli_prepare($conn, "SELECT user_id, nama_pengirim, pesan, DATE_FORMAT(created_at,'%H:%i') waktu FROM chat_kelas ORDER BY id DESC LIMIT 6");
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) $chat_terbaru[] = $row;
    mysqli_stmt_close($stmt);
    $chat_terbaru = array_reverse($chat_terbaru);
}
$foto_profil_url = $foto_profil ? '../uploads/profile/' . rawurlencode($foto_profil) : '';

?>
<!DOCTYPE html>
<html lang="id">
<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard Siswa | XI TJKT 2</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fb;
            color: #172033;
            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;
        }

        a {
            text-decoration: none;
        }

        /* =========================
           TOPBAR
        ========================= */

        .topbar {
            height: 76px;
            background: #ffffff;
            border-bottom: 1px solid #e8edf5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 34px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-logo {
            width: 45px;
            height: 45px;
            border-radius: 13px;
            object-fit: cover;
            background: #eef2f7;
        }

        .brand-title {
            font-size: 17px;
            font-weight: 800;
            color: #111827;
            margin: 0;
        }

        .brand-subtitle {
            font-size: 12px;
            color: #8791a3;
            margin: 2px 0 0;
        }

        .top-user {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #172033;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .top-user-name {
            font-size: 14px;
            font-weight: 700;
        }

        .top-user-role {
            font-size: 11px;
            color: #8b95a7;
        }

        /* =========================
           LAYOUT
        ========================= */

        .page {
            max-width: 1400px;
            margin: auto;
            padding: 30px 34px 50px;
        }

        /* =========================
           WELCOME
        ========================= */

        .welcome {
            background: #172033;
            border-radius: 24px;
            padding: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
            overflow: hidden;
            position: relative;
        }

        .welcome::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 50%;
            right: -60px;
            top: -80px;
        }

        .welcome-text {
            position: relative;
            z-index: 2;
        }

        .welcome small {
            color: #aeb8c9;
            font-size: 13px;
        }

        .welcome h1 {
            margin: 7px 0 5px;
            font-size: 29px;
            font-weight: 800;
        }

        .welcome p {
            margin: 0;
            color: #bbc4d3;
            font-size: 14px;
        }

        .date-box {
            position: relative;
            z-index: 2;
            background: rgba(255,255,255,.09);
            border: 1px solid rgba(255,255,255,.08);
            padding: 14px 18px;
            border-radius: 15px;
            text-align: right;
            min-width: 150px;
        }

        .date-box strong {
            display: block;
            font-size: 14px;
        }

        .date-box span {
            color: #b9c2d1;
            font-size: 12px;
        }

        /* =========================
           STAT CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 20px;
            padding: 21px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f4f8;
            color: #172033;
            font-size: 19px;
        }

        .stat-label {
            color: #8a94a5;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
        }

        /* =========================
           QUICK MENU
        ========================= */

        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 28px 0 13px;
        }

        .section-title h2 {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
        }

        .section-title a {
            color: #687386;
            font-size: 12px;
        }

        .quick-menu {
            display: grid;
            grid-template-columns:
                repeat(4, 1fr);
            gap: 14px;
        }

        .quick-card {
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 18px;
            padding: 19px;
            color: #172033;
            transition: .2s;
        }

        .quick-card:hover {
            transform: translateY(-3px);
            border-color: #cfd7e4;
            box-shadow:
                0 10px 25px rgba(25,35,55,.07);
        }

        .quick-icon {
            width: 43px;
            height: 43px;
            border-radius: 13px;
            background: #f0f3f7;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 13px;
        }

        .quick-card strong {
            display: block;
            font-size: 14px;
        }

        .quick-card span {
            display: block;
            color: #8b95a6;
            font-size: 11px;
            margin-top: 4px;
        }

        /* =========================
           CONTENT GRID
        ========================= */

        .content-grid {
            display: grid;
            grid-template-columns:
                1.4fr
                .8fr;
            gap: 20px;
            margin-top: 24px;
        }

        .panel {
            background: white;
            border: 1px solid #e8edf5;
            border-radius: 20px;
            overflow: hidden;
        }

        .panel-head {
            padding: 19px 21px;
            border-bottom: 1px solid #edf0f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-head h3 {
            margin: 0;
            font-size: 15px;
            font-weight: 800;
        }

        .panel-head a {
            font-size: 11px;
            color: #7a8495;
        }

        .panel-body {
            padding: 18px 21px;
        }

        /* =========================
           JADWAL
        ========================= */

        .schedule-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f2f6;
        }

        .schedule-item:last-child {
            border-bottom: 0;
        }

        .period {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: #172033;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .subject {
            font-size: 13px;
            font-weight: 750;
        }

        .teacher {
            color: #8b95a5;
            font-size: 11px;
            margin-top: 3px;
        }

        /* =========================
           PIKET
        ========================= */

        .piket-box {
            background: #f6f8fb;
            border-radius: 15px;
            padding: 16px;
        }

        .piket-box .piket-day {
            font-size: 11px;
            color: #8b95a5;
            margin-bottom: 10px;
        }

        .piket-name {
            font-size: 13px;
            font-weight: 700;
            padding: 8px 0;
            border-bottom: 1px solid #e9edf3;
        }

        .piket-name:last-child {
            border-bottom: 0;
        }

        /* =========================
           TUGAS
        ========================= */

        .task-item {
            padding: 13px 0;
            border-bottom: 1px solid #edf0f5;
        }

        .task-item:last-child {
            border-bottom: 0;
        }

        .task-title {
            font-weight: 750;
            font-size: 13px;
        }

        .task-teacher {
            font-size: 11px;
            color: #8b95a5;
            margin-top: 3px;
        }

        .deadline {
            display: inline-block;
            margin-top: 7px;
            font-size: 10px;
            background: #f0f3f7;
            padding: 5px 8px;
            border-radius: 7px;
            color: #667085;
        }

        /* =========================
           GALERI
        ========================= */

        .gallery-grid {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 12px;
        }

        .gallery-item {
            position: relative;
            height: 150px;
            border-radius: 14px;
            overflow: hidden;
            background: #edf1f5;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .3s;
        }

        .gallery-item:hover img {
            transform: scale(1.05);
        }

        .gallery-caption {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 30px 10px 9px;
            background:
                linear-gradient(
                    transparent,
                    rgba(0,0,0,.72)
                );
            color: white;
            font-size: 11px;
        }

        .empty {
            text-align: center;
            color: #98a1b1;
            padding: 25px 10px;
            font-size: 12px;
        }

        /* =========================
           PROFILE
        ========================= */

        .profile-box {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 17px;
            border-bottom: 1px solid #edf0f5;
        }

        .profile-avatar {
            width: 53px;
            height: 53px;
            border-radius: 16px;
            background: #172033;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            font-weight: 800;
        }

        .profile-name {
            font-size: 14px;
            font-weight: 800;
        }

        .profile-nis {
            color: #8b95a5;
            font-size: 11px;
            margin-top: 3px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid #f0f2f5;
            font-size: 12px;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-label {
            color: #8b95a5;
        }

        .info-value {
            font-weight: 700;
        }

        /* =========================
           FOOTER
        ========================= */

        footer {
            text-align: center;
            color: #9aa3b2;
            font-size: 11px;
            padding-top: 35px;
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 900px) {

            .topbar {
                padding: 0 18px;
            }

            .page {
                padding: 20px 18px 40px;
            }

            .stats {
                grid-template-columns:
                    1fr;
            }

            .quick-menu {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .content-grid {
                grid-template-columns:
                    1fr;
            }

        }

        @media (max-width: 600px) {

            .topbar {
                height: 68px;
            }

            .brand-title {
                font-size: 14px;
            }

            .brand-subtitle {
                font-size: 10px;
            }

            .brand-logo {
                width: 39px;
                height: 39px;
            }

            .top-user-name,
            .top-user-role {
                display: none;
            }

            .avatar {
                width: 38px;
                height: 38px;
            }

            .welcome {
                padding: 23px;
                border-radius: 19px;
                align-items: flex-start;
                flex-direction: column;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .date-box {
                text-align: left;
                width: 100%;
            }

            .quick-menu {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .quick-card {
                padding: 15px;
            }

            .gallery-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

            .gallery-item {
                height: 125px;
            }

        }

    </style>

</head>

<body>

<!-- =========================
     TOPBAR
========================= -->

<header class="topbar">

    <div class="brand">

        <img
            src="../assets/images/logo.png"
            alt="Logo XI TJKT 2"
            class="brand-logo"
        >

        <div>

            <div class="brand-title">
                XI TJKT 2
            </div>

            <div class="brand-subtitle">
                Student Portal
            </div>

        </div>

    </div>

    <div class="top-user">

        <div>

            <div class="top-user-name">
                <?= htmlspecialchars($nama) ?>
            </div>

            <div class="top-user-role">
                Siswa
            </div>

        </div>

        <div class="avatar" style="overflow:hidden;">
            <?php if ($foto_profil_url): ?>
                <img src="<?= htmlspecialchars($foto_profil_url) ?>" alt="Foto profil" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= strtoupper(substr($nama, 0, 1)) ?>
            <?php endif; ?>
        </div>

    </div>

</header>


<main class="page">

    <!-- =========================
         WELCOME
    ========================= -->

    <section class="welcome">

        <div class="welcome-text">

            <small>
                <?= htmlspecialchars($hari) ?>
            </small>

            <h1>
                Halo, <?= htmlspecialchars($nama) ?> 👋
            </h1>

            <p>
                Selamat datang di portal siswa XI TJKT 2.
            </p>

        </div>

        <div class="date-box">

            <strong>
                <?= htmlspecialchars($tanggal) ?>
            </strong>

            <span>
                Tahun Pelajaran 2026/2027
            </span>

        </div>

    </section>


    <!-- =========================
         STATISTIK
    ========================= -->

    <section class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <div>

                <div class="stat-label">
                    Absensi Hari Ini
                </div>

                <div class="stat-value">

                    <?php if ($absensi): ?>

                        <?= htmlspecialchars($absensi['status']) ?>

                    <?php else: ?>

                        Belum Absen

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>

                <div class="stat-label">
                    Waktu Absensi
                </div>

                <div class="stat-value">

                    <?php if ($absensi): ?>

                        <?= htmlspecialchars(
                            date(
                                'H:i',
                                strtotime($absensi['waktu'])
                            )
                        ) ?>

                    <?php else: ?>

                        —

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-book-open"></i>
            </div>

            <div>

                <div class="stat-label">
                    Jadwal Hari Ini
                </div>

                <div class="stat-value">
                    <?= count($jadwal) ?> Pelajaran
                </div>

            </div>

        </div>

    </section>


    <!-- =========================
         MENU CEPAT
    ========================= -->

    <div class="section-title">

        <h2>
            Menu Cepat
        </h2>

    </div>


    <section class="quick-menu">

        <a
            href="../absensi/scan.php"
            class="quick-card"
        >

            <div class="quick-icon">
                <i class="fa-solid fa-qrcode"></i>
            </div>

            <strong>
                Scan Absensi
            </strong>

            <span>
                Scan QR dari wali kelas
            </span>

        </a>


        <a
            href="galeri.php"
            class="quick-card"
        >

            <div class="quick-icon">
                <i class="fa-solid fa-images"></i>
            </div>

            <strong>
                Galeri XI TJKT 2
            </strong>

            <span>
                Lihat dokumentasi kelas
            </span>

        </a>


        <a
            href="../admin/jadwal.php"
            class="quick-card"
        >

            <div class="quick-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>

            <strong>
                Jadwal Pelajaran
            </strong>

            <span>
                Lihat jadwal kelas
            </span>

        </a>

        <a
            href="../logout.php"
            class="quick-card"
        >

            <div class="quick-icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </div>

            <strong>
                Keluar
            </strong>

            <span>
                Keluar dari akun
            </span>

        </a>

    </section>


    <!-- =========================
         FITUR BARU
    ========================= -->

    <div class="section-title">
        <h2>Fitur Siswa</h2>
    </div>

    <section class="quick-menu">

        <a href="pengaturan.php" class="quick-card">
            <div class="quick-icon"><i class="fa-solid fa-user-gear"></i></div>
            <strong>Pengaturan Akun</strong>
            <span>Ganti password & foto profil</span>
        </a>

        <a href="pengumuman.php" class="quick-card" style="position:relative;">
            <div class="quick-icon"><i class="fa-solid fa-bell"></i></div>
            <strong>Inbox Pengumuman</strong>
            <span>Lihat informasi dari wali kelas</span>
            <?php if ($unread_pengumuman > 0): ?>
                <span style="position:absolute;right:14px;top:14px;background:#172033;color:#fff;padding:4px 7px;border-radius:99px;font-size:10px;font-weight:800;">+<?= $unread_pengumuman ?></span>
            <?php endif; ?>
        </a>

        <a href="chat.php" class="quick-card">
            <div class="quick-icon"><i class="fa-regular fa-comments"></i></div>
            <strong>Chat Kelas</strong>
            <span>Ngobrol bersama teman sekelas</span>
        </a>

    </section>


    <!-- =========================
         CHAT KELAS
    ========================= -->

    <div class="section-title">
        <h2>Chat Kelas</h2>
        <a href="chat.php">Buka semua <i class="fa-solid fa-arrow-right"></i></a>
    </div>

    <section class="panel">
        <div class="panel-body">
            <div id="dashboardChatList">
                <?php if (empty($chat_terbaru)): ?>
                    <div class="empty">Belum ada chat kelas.</div>
                <?php else: ?>
                    <?php foreach ($chat_terbaru as $cm): ?>
                        <div style="padding:10px 0;border-bottom:1px solid #f0f2f6;">
                            <div style="display:flex;justify-content:space-between;gap:10px;">
                                <strong style="font-size:12px;"><?= htmlspecialchars($cm['nama_pengirim']) ?></strong>
                                <small style="color:#8993a4;font-size:10px;"><?= htmlspecialchars($cm['waktu']) ?></small>
                            </div>
                            <div style="font-size:12px;color:#687386;margin-top:4px;white-space:pre-wrap;word-break:break-word;"><?= htmlspecialchars($cm['pesan']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form id="dashboardChatForm" style="display:flex;gap:9px;margin-top:13px;">
                <input id="dashboardChatInput" type="text" maxlength="500" placeholder="Tulis pesan ke kelas..." required style="flex:1;border:1px solid #dfe4ec;border-radius:10px;padding:10px 12px;outline:0;font-size:12px;">
                <button type="submit" style="border:0;background:#172033;color:#fff;border-radius:10px;padding:0 14px;"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </section>


    <!-- =========================
         CONTENT
    ========================= -->

    <section class="content-grid">


        <!-- JADWAL -->

        <div class="panel">

            <div class="panel-head">

                <h3>
                    <i class="fa-solid fa-calendar-day"></i>
                    Jadwal <?= htmlspecialchars($hari) ?>
                </h3>

            </div>

            <div class="panel-body">

                <?php if (count($jadwal) > 0): ?>

                    <?php foreach ($jadwal as $item): ?>

                        <div class="schedule-item">

                            <div class="period">
                                <?= htmlspecialchars(
                                    $item['jam_ke']
                                ) ?>
                            </div>

                            <div>

                                <div class="subject">
                                    <?= htmlspecialchars(
                                        $item['mata_pelajaran']
                                    ) ?>
                                </div>

                                <div class="teacher">
                                    <?= htmlspecialchars(
                                        $item['guru']
                                    ) ?>
                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty">
                        Tidak ada jadwal untuk hari ini.
                    </div>

                <?php endif; ?>

            </div>

        </div>


        <!-- PROFILE + PIKET -->

        <div>

            <div class="panel">

                <div class="panel-head">

                    <h3>
                        Profil Saya
                    </h3>

                </div>

                <div class="panel-body">

                    <div class="profile-box">

                        <div class="profile-avatar">
                            <?= strtoupper(
                                substr($nama, 0, 1)
                            ) ?>
                        </div>

                        <div>

                            <div class="profile-name">
                                <?= htmlspecialchars($nama) ?>
                            </div>

                            <div class="profile-nis">
                                NIS: <?= htmlspecialchars($nis) ?>
                            </div>

                        </div>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Kelas
                        </span>

                        <span class="info-value">
                            XI TJKT 2
                        </span>

                    </div>


                    <div class="info-row">

                        <span class="info-label">
                            Jenis Kelamin
                        </span>

                        <span class="info-value">
                            <?= $siswa['jenis_kelamin'] === 'L'
                                ? 'Laki-laki'
                                : 'Perempuan'
                            ?>
                        </span>

                    </div>

                </div>

            </div>


            <div class="panel mt-4">

                <div class="panel-head">

                    <h3>
                        <i class="fa-solid fa-broom"></i>
                        Piket Hari Ini
                    </h3>

                </div>

                <div class="panel-body">

                    <div class="piket-box">

                        <div class="piket-day">
                            <?= htmlspecialchars($hari) ?>
                        </div>

                        <?php if (count($piket) > 0): ?>

                            <?php foreach ($piket as $namaPiket): ?>

                                <div class="piket-name">

                                    <?= htmlspecialchars(
                                        $namaPiket
                                    ) ?>

                                    <?php if (
                                        $namaPiket === $nama
                                    ): ?>

                                        <small>
                                            • Kamu
                                        </small>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <div class="empty">
                                Belum ada data piket.
                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================
         TUGAS
    ========================= -->

    <section>

        <div class="section-title">

            <h2>
                Tugas Terbaru
            </h2>

        </div>

        <div class="panel">

            <div class="panel-body">

                <?php if (count($tugas) > 0): ?>

                    <?php foreach ($tugas as $item): ?>

                        <div class="task-item">

                            <div class="task-title">
                                <?= htmlspecialchars(
                                    $item['judul']
                                ) ?>
                            </div>

                            <div class="task-teacher">

                                Guru:
                                <?= htmlspecialchars(
                                    $item['guru'] ?: '-'
                                ) ?>

                            </div>

                            <?php if (
                                !empty($item['deadline'])
                            ): ?>

                                <span class="deadline">

                                    <i class="fa-regular fa-clock"></i>

                                    Deadline:
                                    <?= htmlspecialchars(
                                        date(
                                            'd/m/Y',
                                            strtotime(
                                                $item['deadline']
                                            )
                                        )
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="empty">
                        Belum ada tugas.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- =========================
         GALERI
    ========================= -->

    <section>

        <div class="section-title">

            <h2>
                Dokumentasi XI TJKT 2
            </h2>

            <a href="galeri.php">
                Lihat semua
                <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>


        <div class="panel">

            <div class="panel-body">

                <?php if (count($galeri) > 0): ?>

                    <div class="gallery-grid">

                        <?php foreach ($galeri as $foto): ?>

                            <a
                                href="galeri.php"
                                class="gallery-item"
                            >

                                <img
                                    src="../assets/images/gallery/<?= htmlspecialchars(
                                        basename(
                                            $foto['gambar']
                                        )
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $foto['judul'] ?: 'Galeri'
                                    ) ?>"
                                    onerror="this.style.display='none';"
                                >

                                <div class="gallery-caption">

                                    <?= htmlspecialchars(
                                        $foto['judul']
                                            ?: 'Dokumentasi XI TJKT 2'
                                    ) ?>

                                </div>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty">

                        <i
                            class="fa-regular fa-images"
                            style="font-size:30px;margin-bottom:10px;"
                        ></i>

                        <br>

                        Belum ada foto di galeri kelas.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <footer>

        XI TJKT 2 · Student Portal
        <br>
        SMK PGRI Subang · 2026/2027

    </footer>

</main>


<script>
(function(){
    const form=document.getElementById('dashboardChatForm');
    const input=document.getElementById('dashboardChatInput');
    const list=document.getElementById('dashboardChatList');
    if(!form)return;
    function esc(v){return String(v).replace(/[&<>\"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;'}[c]));}
    async function refresh(){
        try{
            const r=await fetch('chat_api.php?limit=6',{cache:'no-store'}),d=await r.json();
            if(!d.ok)return;
            if(!d.messages.length){list.innerHTML='<div class="empty">Belum ada chat kelas.</div>';return;}
            list.innerHTML=d.messages.map(m=>'<div style="padding:10px 0;border-bottom:1px solid #f0f2f6;"><div style="display:flex;justify-content:space-between;gap:10px;"><strong style="font-size:12px;">'+esc(m.nama_pengirim)+'</strong><small style="color:#8993a4;font-size:10px;">'+esc(m.waktu)+'</small></div><div style="font-size:12px;color:#687386;margin-top:4px;white-space:pre-wrap;word-break:break-word;">'+esc(m.pesan)+'</div></div>').join('');
        }catch(e){}
    }
    form.addEventListener('submit',async function(e){e.preventDefault();if(!input.value.trim())return;const fd=new FormData();fd.append('pesan',input.value.trim());const r=await fetch('chat_api.php',{method:'POST',body:fd});const d=await r.json();if(d.ok){input.value='';refresh();}else alert(d.message||'Gagal mengirim pesan.');});
    setInterval(refresh,4000);
})();
</script>
</body>
</html>