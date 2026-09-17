<?php

require_once "../config/auth.php";
require_once "../config/database.php";

wajib_login(['wali_kelas']);

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$dateObj = DateTime::createFromFormat('Y-m-d', $tanggal);

if (!$dateObj || $dateObj->format('Y-m-d') !== $tanggal) {
    $tanggal = date('Y-m-d');
}

/*
|--------------------------------------------------------------------------
| Ambil data absensi
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        s.id,
        s.nis,
        s.nama,
        s.jenis_kelamin,
        a.id AS absensi_id,
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
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $tanggal);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Statistik
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
    }
}

/*
|--------------------------------------------------------------------------
| Format tanggal
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
    date('d', $timestamp) . ' ' .
    $bulan[(int)date('n', $timestamp)] . ' ' .
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
        Cetak Absensi - XI TJKT 2
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            color: #111;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header h2 {
            margin: 6px 0;
            font-size: 18px;
        }

        .header p {
            margin: 4px 0;
            color: #555;
        }

        .info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .stat strong {
            display: block;
            font-size: 20px;
            margin-top: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #222;
            padding: 8px;
        }

        th {
            background: #eee;
            text-align: center;
        }

        td {
            font-size: 13px;
        }

        .center {
            text-align: center;
        }

        .signature {
            margin-top: 60px;
            width: 300px;
            margin-left: auto;
            text-align: center;
        }

        .signature-space {
            height: 70px;
        }

        .no-print {
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 16px;
            border: 0;
            background: #111827;
            color: white;
            cursor: pointer;
            border-radius: 6px;
        }

        @media print {

            body {
                margin: 10mm;
            }

            .no-print {
                display: none;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

        }

    </style>

</head>

<body>

<div class="no-print">

    <button
        class="btn"
        onclick="window.print()">

        🖨️ Cetak

    </button>

</div>


<div class="header">

    <h1>
        LAPORAN ABSENSI SISWA
    </h1>

    <h2>
        XI TJKT 2
    </h2>

    <p>
        SMK PGRI Subang
    </p>

    <p>
        Tanggal: <strong><?= htmlspecialchars($tanggalTampil) ?></strong>
    </p>

</div>


<div class="stats">

    <div class="stat">
        Total
        <strong><?= $total ?></strong>
    </div>

    <div class="stat">
        Hadir
        <strong><?= $hadir ?></strong>
    </div>

    <div class="stat">
        Sakit
        <strong><?= $sakit ?></strong>
    </div>

    <div class="stat">
        Izin
        <strong><?= $izin ?></strong>
    </div>

    <div class="stat">
        Alpa
        <strong><?= $alpa ?></strong>
    </div>

</div>


<table>

    <thead>

        <tr>

            <th width="40">
                No
            </th>

            <th>
                NIS
            </th>

            <th>
                Nama Siswa
            </th>

            <th>
                L/P
            </th>

            <th>
                Status
            </th>

            <th>
                Waktu
            </th>

            <th>
                Metode
            </th>

        </tr>

    </thead>

    <tbody>

    <?php foreach ($data as $index => $siswa): ?>

        <tr>

            <td class="center">
                <?= $index + 1 ?>
            </td>

            <td>
                <?= htmlspecialchars($siswa['nis']) ?>
            </td>

            <td>
                <?= htmlspecialchars($siswa['nama']) ?>
            </td>

            <td class="center">
                <?= htmlspecialchars($siswa['jenis_kelamin']) ?>
            </td>

            <td class="center">

                <?php if (empty($siswa['absensi_id'])): ?>

                    Belum

                <?php else: ?>

                    <?= htmlspecialchars($siswa['status']) ?>

                <?php endif; ?>

            </td>

            <td class="center">

                <?= !empty($siswa['waktu'])
                    ? htmlspecialchars($siswa['waktu'])
                    : '-'
                ?>

            </td>

            <td class="center">

                <?= !empty($siswa['metode'])
                    ? htmlspecialchars($siswa['metode'])
                    : '-'
                ?>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>


<div class="signature">

    Wali Kelas XI TJKT 2

    <div class="signature-space"></div>

    <strong>
        ______________________________
    </strong>

</div>


<script>

    window.onload = function () {

        setTimeout(function () {
            window.print();
        }, 500);

    };

</script>

</body>

</html>