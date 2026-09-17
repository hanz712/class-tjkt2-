<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/qr.php';

wajib_login(['wali_kelas']);

$token = buat_token_qr();

$baseUrl =
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
        ? 'https'
        : 'http')
    . '://'
    . $_SERVER['HTTP_HOST'];

$scanUrl =
    $baseUrl .
    dirname(dirname($_SERVER['SCRIPT_NAME'])) .
    '/absensi/scan.php?token=' .
    urlencode($token);

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>QR Absensi • XI TJKT 2</title>

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet"
>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>

        body {
            margin: 0;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #070b14;

            color: white;

            font-family: Arial, sans-serif;

            padding: 20px;
        }

        .box {
            width: 100%;
            max-width: 600px;

            padding: 35px;

            text-align: center;

            border-radius: 25px;

            background: rgba(255,255,255,.06);

            border:
                1px solid rgba(255,255,255,.1);
        }

        #qrcode {
            width: 280px;
            height: 280px;

            background: white;

            padding: 15px;

            border-radius: 18px;

            margin: 25px auto;
        }

        #qrcode img {
            width: 100%;
            height: 100%;
        }

        .timer {
            font-size: 18px;
            color: #fbbf24;
        }

        .muted {
            color: #9ca3af;
        }

    </style>

</head>

<body>

<div class="box">

    <h2>
        <i class="fa-solid fa-qrcode"></i>
        QR Absensi
    </h2>

    <p class="muted">
        Tampilkan QR ini kepada siswa.
    </p>

    <div id="qrcode"></div>

    <div class="timer">

        QR berlaku sekitar
        <strong>5 menit</strong>

    </div>

    <p class="muted mt-3">

        Siswa login ke akun masing-masing,
        lalu scan QR ini.

    </p>

    <a
        href="../admin/index.php"
        class="btn btn-secondary mt-2"
    >
        Kembali ke Dashboard
    </a>

</div>


<script>

const scanUrl = <?= json_encode($scanUrl) ?>;

new QRCode(
    document.getElementById("qrcode"),
    {
        text: scanUrl,
        width: 250,
        height: 250
    }
);

</script>

</body>
</html>