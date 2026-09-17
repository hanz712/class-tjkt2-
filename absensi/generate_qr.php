<?php

date_default_timezone_set('Asia/Jakarta');

// ===============================
// ANTI CACHE
// ===============================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ===============================
// AMBIL JADWAL ID
// ===============================
$jadwal_id = isset($_GET['jadwal_id'])
    ? (int) $_GET['jadwal_id']
    : 0;

if ($jadwal_id <= 0) {
    die("Jadwal ID tidak valid.");
}

// ===============================
// TOKEN CONFIG
// ===============================
$expire = 600; // 10 menit

$secret = "TJKT2-PERBAIKAN-ABSENSI-2026";

// Bucket waktu
$bucket = floor(time() / $expire);

// Data yang ditandatangani
$data = $jadwal_id . "|" . $bucket;

// Signature HMAC
$signature = hash_hmac(
    "sha256",
    $data,
    $secret
);

// Token lengkap
$token = $jadwal_id . "." . $bucket . "." . $signature;

// ===============================
// URL SCANNER
// ===============================
$scan_url =
    "https://xi-tjkt2.byethost33.com/absensi/scan.php?token=" .
    urlencode($token);

// ===============================
// QR SERVER
// ===============================
$qr_url =
    "https://api.qrserver.com/v1/create-qr-code/" .
    "?size=400x400&data=" .
    urlencode($scan_url);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>QR Absensi</title>

    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            text-align: center;
        }

        .container {
            max-width: 500px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,.1);
        }

        h1 {
            margin-bottom: 10px;
        }

        .qr {
            margin: 20px auto;
        }

        .qr img {
            width: 400px;
            max-width: 100%;
            height: auto;
        }

        .token {
            margin-top: 20px;
            padding: 12px;
            background: #f1f1f1;
            border-radius: 8px;
            word-break: break-all;
            font-size: 13px;
        }

        .url {
            margin-top: 15px;
            padding: 12px;
            background: #eef6ff;
            border-radius: 8px;
            word-break: break-all;
            font-size: 13px;
        }

        .success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>📷 QR Absensi</h1>

    <p>
        Jadwal ID:
        <strong><?= htmlspecialchars($jadwal_id) ?></strong>
    </p>

    <p class="success">
        ✅ QR Token Aktif
    </p>

    <div class="qr">
        <img
            src="<?= htmlspecialchars($qr_url) ?>"
            alt="QR Code Absensi"
        >
    </div>

    <div class="token">
        <strong>Token:</strong><br>
        <?= htmlspecialchars($token) ?>
    </div>

    <div class="url">
        <strong>Isi QR:</strong><br>
        <?= htmlspecialchars($scan_url) ?>
    </div>

    <p>
        Token berlaku selama
        <strong>10 menit</strong>.
    </p>

</div>

</body>
</html>