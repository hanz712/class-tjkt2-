<?php

require_once "../config/auth.php";

wajib_login(['wali_kelas']);

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Preview PDF - XI TJKT 2
    </title>

    <style>

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

    </style>

</head>

<body>

<iframe
    src="export_pdf.php?tanggal=<?= urlencode($tanggal) ?>">
</iframe>

</body>

</html>