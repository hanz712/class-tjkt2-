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
| Data absensi
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

$persentase = $total > 0
    ? round(($hadir / $total) * 100)
    : 0;

/*
|--------------------------------------------------------------------------
| Tanggal
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
        Export PDF Absensi - XI TJKT 2
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 25px;
            color: #111;
        }

        .toolbar {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            gap: 10px;
        }

        button {
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .print {
            background: #111827;
            color: white;
        }

        .back {
            background: #e5e7eb;
            color: #111;
        }

        .paper {
            background: white;
            max-width: 900px;
            margin: auto;
            padding: 35px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }

        .header p {
            margin: 3px;
            color: #555;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 20px;
        }

        .summary div {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .summary strong {
            display: block;
            font-size: 20px;
            margin-top: 4px;
        }

        .percentage {
            margin-bottom: 20px;
        }

        .percentage-bar {
            height: 10px;
            background: #e5e7eb;
            margin-top: 8px;
        }

        .percentage-fill {
            height: 10px;
            background: #111827;
            width: <?= $persentase ?>%;
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
            background: #f3f4f6;
        }

        td {
            font-size: 13px;
        }

        .center {
            text-align: center;
        }

        .signature {
            width: 280px;
            margin-left: auto;
            text-align: center;
            margin-top: 50px;
        }

        .signature-space {
            height: 65px;
        }

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .toolbar {
                display: none;
            }

            .paper {
                max-width: none;
                padding: 0;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }

        }

    </style>

</head>

<body>


<div class="toolbar">

    <button
        class="print"
        onclick="window.print()">

        🖨️ Cetak / Simpan PDF

    </button>


    <button
        class="back"
        onclick="history.back()">

        ← Kembali

    </button>

</div>


<div class="paper">


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

            Tanggal:
            <strong>
                <?= htmlspecialchars($tanggalTampil) ?>
            </strong>

        </p>

    </div>


    <div class="summary">

        <div>
            Total
            <strong><?= $total ?></strong>
        </div>

        <div>
            Hadir
            <strong><?= $hadir ?></strong>
        </div>

        <div>
            Sakit
            <strong><?= $sakit ?></strong>
        </div>

        <div>
            Izin
            <strong><?= $izin ?></strong>
        </div>

        <div>
            Alpa
            <strong><?= $alpa ?></strong>
        </div>

    </div>


    <div class="percentage">

        <strong>
            Persentase Kehadiran: <?= $persentase ?>%
        </strong>

        <div class="percentage-bar">

            <div class="percentage-fill"></div>

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
            __________________________
        </strong>

    </div>


</div>

</body>

</html>