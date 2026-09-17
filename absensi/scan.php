<?php
require_once "../config/auth.php";

wajib_login(['siswa']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Scan Absensi - XI TJKT 2</title>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #050816;
            color: white;
            min-height: 100vh;
            padding: 20px;
        }

        .box {
            max-width: 500px;
            margin: auto;
            background: #0b1025;
            border-radius: 20px;
            padding: 20px;
        }

        h1 {
            text-align: center;
        }

        #reader {
            width: 100%;
            margin-top: 20px;
        }

        #hasil {
            margin-top: 20px;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            display: none;
        }

        .success {
            background: #063d25;
            color: #7fffb8;
        }

        .error {
            background: #45111b;
            color: #ff9ca8;
        }

        .loading {
            background: #17203d;
            color: #b9c7ff;
        }

        a {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #9daeff;
        }
    </style>
</head>

<body>

<div class="box">

    <h1>📷 Scan Absensi</h1>

    <p style="text-align:center;">
        Arahkan kamera ke QR Absensi Guru
    </p>

    <div id="reader"></div>

    <div id="hasil"></div>

    <a href="../siswa/index.php">
        ← Kembali ke Dashboard
    </a>

</div>

<script>

let sudahDiproses = false;
let scanner = null;

function tampilkanPesan(pesan, tipe) {

    const hasil = document.getElementById("hasil");

    hasil.style.display = "block";
    hasil.className = tipe;
    hasil.innerHTML = pesan;
}

async function prosesQR(teksQR) {

    if (sudahDiproses) {
        return;
    }

    sudahDiproses = true;

    tampilkanPesan(
        "⏳ Memproses absensi...",
        "loading"
    );

    try {

        let url;

        try {
            url = new URL(teksQR);
        } catch (e) {

            throw new Error(
                "QR yang discan bukan QR absensi yang valid."
            );
        }

        const token = url.searchParams.get("token");

        if (!token) {
            throw new Error(
                "Token absensi tidak ditemukan."
            );
        }

        if (scanner) {
            try {
                await scanner.stop();
            } catch (e) {}
        }

        const response = await fetch(
            "proses.php?token=" +
            encodeURIComponent(token),
            {
                method: "GET",
                credentials: "same-origin",
                cache: "no-store"
            }
        );

        const text = await response.text();

        let data;

        try {
            data = JSON.parse(text);
        } catch (e) {

            console.log("RESPON SERVER:", text);

            throw new Error(
                "Server tidak mengirim respon JSON yang valid."
            );
        }

        if (data.success) {

            tampilkanPesan(
                "✅ " + data.message,
                "success"
            );

            setTimeout(() => {
                window.location.href =
                    "../siswa/index.php";
            }, 1500);

        } else {

            tampilkanPesan(
                "❌ " + (data.message || "Absensi gagal."),
                "error"
            );

            // Izinkan scan ulang
            setTimeout(() => {
                sudahDiproses = false;

                mulaiScanner();

            }, 2500);
        }

    } catch (error) {

        console.error(error);

        tampilkanPesan(
            "❌ " + error.message,
            "error"
        );

        setTimeout(() => {

            sudahDiproses = false;

            mulaiScanner();

        }, 2500);
    }
}

function mulaiScanner() {

    scanner = new Html5Qrcode("reader");

    scanner.start(
        {
            facingMode: "environment"
        },
        {
            fps: 10,
            qrbox: {
                width: 250,
                height: 250
            }
        },
        (decodedText) => {

            prosesQR(decodedText);

        },
        (errorMessage) => {

            // Error pembacaan kamera biasa.
            // Tidak perlu ditampilkan ke user.

        }
    ).catch((err) => {

        tampilkanPesan(
            "❌ Kamera tidak dapat digunakan.<br><br>" +
            "Pastikan izin kamera sudah diberikan.",
            "error"
        );

        console.error(err);
    });
}

mulaiScanner();

</script>

</body>
</html>
 