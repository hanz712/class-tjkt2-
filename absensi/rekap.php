<?php

date_default_timezone_set('Asia/Jakarta');

require_once "../config/auth.php";
require_once "../config/database.php";

wajib_login(['wali_kelas']);

/*
|--------------------------------------------------------------------------
| TANGGAL YANG DIPILIH
|--------------------------------------------------------------------------
*/

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

/*
|--------------------------------------------------------------------------
| VALIDASI TANGGAL
|--------------------------------------------------------------------------
*/

$dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);

if (
    !$dateObj ||
    $dateObj->format('Y-m-d') !== $tanggal
) {
    $tanggal = date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| AMBIL SEMUA SISWA + DATA ABSENSI
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.id AS siswa_id,
        s.nis,
        s.nama,
        s.jenis_kelamin,

        a.id AS absensi_id,
        a.tanggal AS absensi_tanggal,
        a.waktu,
        a.status,
        a.metode

    FROM siswa s

    LEFT JOIN absensi a
        ON a.siswa_id = s.id
        AND a.tanggal = ?

    ORDER BY s.nama ASC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        "Query gagal dipersiapkan: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $tanggal
);

if (!mysqli_stmt_execute($stmt)) {
    die(
        "Query gagal dijalankan: " .
        htmlspecialchars(mysqli_stmt_error($stmt))
    );
}

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die(
        "Gagal mengambil data: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| HITUNG STATISTIK
|--------------------------------------------------------------------------
*/

$total = count($data);

$hadir = 0;
$sakit = 0;
$izin = 0;
$alpa = 0;
$belum = 0;

foreach ($data as $siswa) {

    if (empty($siswa['absensi_id'])) {

        $belum++;

        continue;
    }

    switch ($siswa['status']) {

        case 'Hadir':
            $hadir++;
            break;

        case 'Sakit':
            $sakit++;
            break;

        case 'Izin':
            $izin++;
            break;

        case 'Alpa':
            $alpa++;
            break;

        default:
            $belum++;
            break;
    }
}

/*
|--------------------------------------------------------------------------
| PERSENTASE
|--------------------------------------------------------------------------
*/

$persentase = $total > 0
    ? round(($hadir / $total) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| FORMAT TANGGAL INDONESIA
|--------------------------------------------------------------------------
*/

$bulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$timestamp = strtotime($tanggal);

$tanggalTampil =
    date('d', $timestamp) .
    ' ' .
    $bulan[(int) date('n', $timestamp)] .
    ' ' .
    date('Y', $timestamp);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Pantau Absensi - XI TJKT 2
    </title>

    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            color: #111827;
        }

        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            background: #111827;
            color: white;
            padding: 18px 0;
        }

        .container-main {
            max-width: 1200px;
            margin: auto;
            padding: 25px 15px;
        }

        .topbar .container-main {
            padding-top: 0;
            padding-bottom: 0;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            opacity: .9;
        }

        .back-btn:hover {
            color: white;
            opacity: 1;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .attendance-card {
            background: white;
            border-radius: 18px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .06);

            overflow: hidden;
        }

        /* =====================================================
           STAT CARD
        ===================================================== */

        .stat-card {
            background: white;

            border-radius: 16px;

            padding: 20px;

            box-shadow:
                0 8px 25px rgba(0, 0, 0, .06);

            height: 100%;
        }

        .stat-icon {
            width: 45px;
            height: 45px;

            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #f1f5f9;

            font-size: 20px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin-top: 10px;
        }

        /* =====================================================
           STATUS
        ===================================================== */

        .status {
            display: inline-block;

            padding: 6px 11px;

            border-radius: 999px;

            font-size: 12px;

            font-weight: 600;
        }

        .status-hadir {
            background: #dcfce7;
            color: #166534;
        }

        .status-sakit {
            background: #fef3c7;
            color: #92400e;
        }

        .status-izin {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-alpa {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-belum {
            background: #e5e7eb;
            color: #374151;
        }

        /* =====================================================
           AVATAR
        ===================================================== */

        .avatar {
            width: 42px;
            height: 42px;

            min-width: 42px;

            border-radius: 50%;

            background: #e5e7eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-weight: bold;
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .table td,
        .table th {
            vertical-align: middle;
        }

        .table tbody tr {
            transition: .15s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* =====================================================
           BUTTON
        ===================================================== */

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        @media(max-width: 768px) {

            .container-main {
                padding: 18px 12px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-number {
                font-size: 24px;
            }

            .table-responsive {
                font-size: 13px;
            }

            .hide-mobile {
                display: none;
            }

            .action-buttons {
                width: 100%;
            }

            .action-buttons .btn {
                flex: 1;
            }

        }

    </style>

</head>

<body>


<!-- =========================================================
     TOPBAR
========================================================= -->

<div class="topbar">

    <div class="container-main">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <div class="fw-bold">

                    <i
                        class="fa-solid fa-chart-column me-2">
                    </i>

                    Pantau Absensi

                </div>

                <small class="text-white-50">

                    XI TJKT 2

                </small>

            </div>


            <a
                href="index.php"
                class="back-btn">

                <i
                    class="fa-solid fa-arrow-left me-1">
                </i>

                Dashboard

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="container-main">


    <!-- =====================================================
         FILTER + ACTION
    ====================================================== -->

    <div class="attendance-card p-3 mb-4">

        <form
            method="GET"
            class="row g-3 align-items-end">

            <div class="col-md-4">

                <label
                    class="form-label fw-semibold">

                    Tanggal Absensi

                </label>

                <input
                    type="date"
                    name="tanggal"
                    value="<?= htmlspecialchars($tanggal) ?>"
                    class="form-control">

            </div>


            <div class="col-md-auto">

                <button
                    type="submit"
                    class="btn btn-dark">

                    <i
                        class="fa-solid fa-filter me-1">
                    </i>

                    Tampilkan

                </button>

            </div>


            <div class="col-md-auto">

                <div class="action-buttons">


                    <!-- EXPORT PDF -->

                    <a
                        href="pdf.php?tanggal=<?= urlencode($tanggal) ?>"
                        target="_blank"
                        class="btn btn-danger">

                        <i
                            class="fa-solid fa-file-pdf me-1">
                        </i>

                        Export PDF

                    </a>


                    <!-- CETAK -->

                    <a
                        href="cetak.php?tanggal=<?= urlencode($tanggal) ?>"
                        target="_blank"
                        class="btn btn-secondary">

                        <i
                            class="fa-solid fa-print me-1">
                        </i>

                        Cetak

                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- =====================================================
         INFO TANGGAL
    ====================================================== -->

    <div class="mb-3">

        <h5 class="mb-1 fw-bold">

            Rekap Kehadiran

        </h5>

        <div class="text-muted">

            <i
                class="fa-regular fa-calendar me-1">
            </i>

            <?= htmlspecialchars($tanggalTampil) ?>

        </div>

    </div>


    <!-- =====================================================
         STATISTIK
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-users">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Total Siswa

                </div>

                <div class="stat-number">

                    <?= $total ?>

                </div>

            </div>

        </div>


        <!-- HADIR -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-circle-check">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Hadir

                </div>

                <div class="stat-number">

                    <?= $hadir ?>

                </div>

            </div>

        </div>


        <!-- SAKIT -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-bed">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Sakit

                </div>

                <div class="stat-number">

                    <?= $sakit ?>

                </div>

            </div>

        </div>


        <!-- IZIN -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-file-circle-exclamation">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Izin

                </div>

                <div class="stat-number">

                    <?= $izin ?>

                </div>

            </div>

        </div>


        <!-- ALPA -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-circle-xmark">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Alpa

                </div>

                <div class="stat-number">

                    <?= $alpa ?>

                </div>

            </div>

        </div>


        <!-- BELUM -->

        <div class="col-6 col-md">

            <div class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-hourglass-half">
                    </i>

                </div>

                <div class="text-muted mt-2">

                    Belum

                </div>

                <div class="stat-number">

                    <?= $belum ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         PERSENTASE
    ====================================================== -->

    <div class="attendance-card p-4 mb-4">

        <div
            class="d-flex justify-content-between align-items-center mb-2">

            <strong>

                Kehadiran

            </strong>

            <strong>

                <?= $persentase ?>%

            </strong>

        </div>


        <div
            class="progress"
            style="height: 10px;">

            <div
                class="progress-bar"
                role="progressbar"
                style="width: <?= $persentase ?>%;">

            </div>

        </div>

        <div class="small text-muted mt-2">

            <?= $hadir ?> dari <?= $total ?> siswa hadir

        </div>

    </div>


    <!-- =====================================================
         TABEL ABSENSI
    ====================================================== -->

    <div class="attendance-card">


        <div class="p-4 border-bottom">

            <h5 class="mb-1">

                Daftar Kehadiran

            </h5>

            <div class="text-muted small">

                Data absensi tanggal
                <?= htmlspecialchars($tanggalTampil) ?>

            </div>

        </div>


        <div class="table-responsive">

            <table
                class="table table-hover mb-0">


                <thead class="table-light">

                    <tr>

                        <th width="60">
                            #
                        </th>

                        <th>
                            Siswa
                        </th>

                        <th class="hide-mobile">
                            NIS
                        </th>

                        <th>
                            Status
                        </th>

                        <th class="hide-mobile">
                            Waktu
                        </th>

                        <th class="hide-mobile">
                            Metode
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (empty($data)): ?>

                    <tr>

                        <td
                            colspan="6"
                            class="text-center py-5">

                            <i
                                class="fa-solid fa-users-slash fs-1 text-muted mb-3">
                            </i>

                            <div
                                class="text-muted">

                                Belum ada data siswa.

                            </div>

                        </td>

                    </tr>

                <?php else: ?>


                    <?php foreach ($data as $index => $siswa): ?>


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | STATUS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            empty(
                                $siswa['absensi_id']
                            )
                        ) {

                            $statusText =
                                'Belum';

                            $statusClass =
                                'status-belum';

                        } else {

                            $statusText =
                                $siswa['status'];

                            switch (
                                $siswa['status']
                            ) {

                                case 'Hadir':

                                    $statusClass =
                                        'status-hadir';

                                    break;

                                case 'Sakit':

                                    $statusClass =
                                        'status-sakit';

                                    break;

                                case 'Izin':

                                    $statusClass =
                                        'status-izin';

                                    break;

                                case 'Alpa':

                                    $statusClass =
                                        'status-alpa';

                                    break;

                                default:

                                    $statusText =
                                        'Belum';

                                    $statusClass =
                                        'status-belum';

                                    break;
                            }
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | AVATAR
                        |--------------------------------------------------------------------------
                        */

                        $nama =
                            trim(
                                $siswa['nama'] ?? ''
                            );

                        $huruf =
                            $nama !== ''
                            ? strtoupper(
                                mb_substr(
                                    $nama,
                                    0,
                                    1,
                                    'UTF-8'
                                )
                            )
                            : '?';

                        ?>


                        <tr>


                            <!-- NOMOR -->

                            <td>

                                <?= $index + 1 ?>

                            </td>


                            <!-- SISWA -->

                            <td>

                                <div
                                    class="d-flex align-items-center gap-3">

                                    <div class="avatar">

                                        <?= htmlspecialchars(
                                            $huruf
                                        ) ?>

                                    </div>


                                    <div>

                                        <div
                                            class="fw-semibold">

                                            <?= htmlspecialchars(
                                                $siswa['nama']
                                            ) ?>

                                        </div>

                                        <small
                                            class="text-muted">

                                            <?= $siswa['jenis_kelamin'] === 'L'
                                                ? 'Laki-laki'
                                                : 'Perempuan'
                                            ?>

                                        </small>

                                    </div>

                                </div>

                            </td>


                            <!-- NIS -->

                            <td class="hide-mobile">

                                <?= htmlspecialchars(
                                    $siswa['nis']
                                ) ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                    class="status <?= htmlspecialchars($statusClass) ?>">

                                    <?= htmlspecialchars(
                                        $statusText
                                    ) ?>

                                </span>

                            </td>


                            <!-- WAKTU -->

                            <td class="hide-mobile">

                                <?php if (
                                    !empty(
                                        $siswa['waktu']
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        $siswa['waktu']
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <!-- METODE -->

                            <td class="hide-mobile">

                                <?php if (
                                    !empty(
                                        $siswa['metode']
                                    )
                                ): ?>

                                    <?= htmlspecialchars(
                                        $siswa['metode']
                                    ) ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>