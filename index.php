<?php

require_once __DIR__ . "/config/auth.php";

if (!sudah_login()) {
    header("Location: login.php");
    exit;
}

redirect_role();

$nama_wali = $_SESSION['nama'] ?? 'Wali Kelas';

/* Total siswa */
$query_siswa = mysqli_query($conn, "SELECT COUNT(*) AS total FROM siswa");
$total_siswa = mysqli_fetch_assoc($query_siswa)['total'];

/* Absensi hari ini */
$hari_ini = date('Y-m-d');

$query_hadir = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM absensi 
     WHERE tanggal = '$hari_ini' 
     AND status = 'Hadir'"
);

$total_hadir = mysqli_fetch_assoc($query_hadir)['total'];

$query_sakit = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM absensi 
     WHERE tanggal = '$hari_ini' 
     AND status = 'Sakit'"
);

$total_sakit = mysqli_fetch_assoc($query_sakit)['total'];

$query_izin = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM absensi 
     WHERE tanggal = '$hari_ini' 
     AND status = 'Izin'"
);

$total_izin = mysqli_fetch_assoc($query_izin)['total'];

$query_alpa = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total 
     FROM absensi 
     WHERE tanggal = '$hari_ini' 
     AND status = 'Alpa'"
);

$total_alpa = mysqli_fetch_assoc($query_alpa)['total'];

$belum_absen = max(
    0,
    $total_siswa - $total_hadir - $total_sakit - $total_izin - $total_alpa
);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TJKT 2 HUB</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #172033;
        }

        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            font-size: 22px;
            font-weight: 800;
        }

        .brand span {
            color: #6366f1;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #6366f1;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .container-dashboard {
            max-width: 1250px;
            margin: auto;
            padding: 35px 20px;
        }

        .hero {
            background: linear-gradient(135deg, #111827, #312e81);
            color: white;
            border-radius: 24px;
            padding: 35px;
            margin-bottom: 25px;
        }

        .hero small {
            opacity: .7;
        }

        .hero h1 {
            font-size: 32px;
            font-weight: 800;
            margin-top: 8px;
        }

        .hero p {
            opacity: .8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eef2ff;
            color: #4f46e5;
            margin-bottom: 12px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
        }

        .stat-label {
            color: #6b7280;
            font-size: 13px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 15px;
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .quick-card {
            background: white;
            border-radius: 18px;
            padding: 25px;
            text-decoration: none;
            color: #172033;
            border: 1px solid #e5e7eb;
            transition: .2s;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }

        .quick-icon {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: #eef2ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            margin-bottom: 15px;
        }

        .quick-card h5 {
            font-weight: 800;
            margin-bottom: 5px;
        }

        .quick-card p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            border-radius: 20px;
            padding: 25px;
            border: 1px solid #e5e7eb;
        }

        .attendance-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .attendance-row:last-child {
            border-bottom: none;
        }

        .logout {
            color: #dc2626;
            text-decoration: none;
            font-size: 14px;
        }

        @media(max-width: 900px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .bottom-grid {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width: 600px) {

            .topbar {
                padding: 15px;
            }

            .container-dashboard {
                padding: 20px 15px;
            }

            .hero {
                padding: 25px;
            }

            .hero h1 {
                font-size: 25px;
            }

            .stats,
            .quick-grid {
                grid-template-columns: 1fr;
            }
        }

    </style>
</head>

<body>

<!-- TOPBAR -->
<header class="topbar">

    <div class="brand">
        TJKT <span>2 HUB</span>
    </div>

    <div class="profile">

        <div>
            <strong><?= htmlspecialchars($nama_wali) ?></strong><br>
            <small>Wali Kelas</small>
        </div>

        <div class="avatar">
            <?= strtoupper(substr($nama_wali, 0, 1)) ?>
        </div>

        <a href="../logout.php" class="logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>

    </div>

</header>


<main class="container-dashboard">

    <!-- HERO -->

    <section class="hero">

        <small>XI TJKT 2 • 2026/2027</small>

        <h1>
            Halo, <?= htmlspecialchars($nama_wali) ?> 👋
        </h1>

        <p>
            Pantau aktivitas dan kehadiran siswa XI TJKT 2
            dalam satu tempat.
        </p>

    </section>


    <!-- STATISTIK -->

    <div class="stats">

        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <div class="stat-number">
                <?= $total_siswa ?>
            </div>

            <div class="stat-label">
                Total Siswa
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div class="stat-number">
                <?= $total_hadir ?>
            </div>

            <div class="stat-label">
                Hadir Hari Ini
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div class="stat-number">
                <?= $belum_absen ?>
            </div>

            <div class="stat-label">
                Belum Absen
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-notes-medical"></i>
            </div>

            <div class="stat-number">
                <?= $total_sakit ?>
            </div>

            <div class="stat-label">
                Sakit
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                <i class="fa-solid fa-envelope"></i>
            </div>

            <div class="stat-number">
                <?= $total_izin ?>
            </div>

            <div class="stat-label">
                Izin
            </div>

        </div>

    </div>


    <!-- QUICK ACCESS -->

    <div class="section-title">
        Quick Access
    </div>

    <div class="quick-grid">

        <a href="../absensi/index.php" class="quick-card">

            <div class="quick-icon">
                <i class="fa-solid fa-qrcode"></i>
            </div>

            <h5>Absensi QR</h5>

            <p>
                Scan QR siswa
            </p>

        </a>


        <a href="siswa.php" class="quick-card">

            <div class="quick-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <h5>Daftar Siswa</h5>

            <p>
                Kelola data siswa
            </p>

        </a>


        <a href="jadwal.php" class="quick-card">

            <div class="quick-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>

            <h5>Jadwal</h5>

            <p>
                Jadwal pelajaran
            </p>

        </a>


        <a href="piket.php" class="quick-card">

            <div class="quick-icon">
                <i class="fa-solid fa-broom"></i>
            </div>

            <h5>Jadwal Piket</h5>

            <p>
                Atur piket kelas
            </p>

        </a>

    </div>


    <!-- BOTTOM -->

    <div class="bottom-grid">

        <div class="panel">

            <div class="section-title">
                Kehadiran Hari Ini
            </div>

            <div class="attendance-row">
                <span>Hadir</span>
                <strong><?= $total_hadir ?></strong>
            </div>

            <div class="attendance-row">
                <span>Sakit</span>
                <strong><?= $total_sakit ?></strong>
            </div>

            <div class="attendance-row">
                <span>Izin</span>
                <strong><?= $total_izin ?></strong>
            </div>

            <div class="attendance-row">
                <span>Alpa</span>
                <strong><?= $total_alpa ?></strong>
            </div>

            <div class="attendance-row">
                <span>Belum Absen</span>
                <strong><?= $belum_absen ?></strong>
            </div>

        </div>


        <div class="panel">

            <div class="section-title">
                Menu Kelas
            </div>

            <p>
                Gunakan menu berikut untuk mengelola
                aktivitas XI TJKT 2.
            </p>

            <div class="d-grid gap-2">

                <a href="../index.php"
                   class="btn btn-outline-dark">
                    <i class="fa-solid fa-house"></i>
                    Portal Kelas
                </a>

                <a href="../absensi/rekap.php"
                   class="btn btn-outline-dark">
                    <i class="fa-solid fa-chart-column"></i>
                    Rekap Absensi
                </a>

            </div>

        </div>

    </div>

</main>

</body>
</html>