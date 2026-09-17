<?php

require_once __DIR__ . "/../config/auth.php";

wajib_login(['wali_kelas']);

$wali = [
    'nama' => 'Deden Nur Rahayu Mamad, S.Pd.',
    'kelas' => 'XI TJKT 2',
    'tahun' => '2026 - 2027',
    'jurusan' => 'Teknik Jaringan Komputer dan Telekomunikasi'
];

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Tentang Wali Kelas - XI TJKT 2</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

body {
    background:#0b1020;
    color:#fff;
    font-family:Arial,sans-serif;
}

.container-main {
    max-width:900px;
    margin:auto;
    padding:25px 15px 50px;
}

.topbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
    margin-bottom:25px;
}

.title {
    font-size:26px;
    font-weight:800;
}

.subtitle {
    color:#94a3b8;
}

.profile-card {
    background:#111827;
    border:1px solid #1f2937;
    border-radius:22px;
    padding:35px 25px;
    text-align:center;
}

.avatar {
    width:100px;
    height:100px;
    margin:0 auto 20px;
    border-radius:50%;
    background:#1e293b;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:40px;
}

.info {
    margin-top:25px;
    text-align:left;
}

.info-item {
    background:#0f172a;
    border-radius:14px;
    padding:15px;
    margin-bottom:10px;
}

.label {
    color:#94a3b8;
    font-size:12px;
}

.value {
    font-weight:700;
    margin-top:4px;
}

</style>

</head>

<body>

<div class="container-main">

<div class="topbar">

<div>

<div class="title">
<i class="fa-solid fa-chalkboard-user"></i>
Tentang Wali Kelas
</div>

<div class="subtitle">
Informasi wali kelas XI TJKT 2
</div>

</div>

<a
    href="index.php"
    class="btn btn-secondary"
>
<i class="fa-solid fa-arrow-left"></i>
Dashboard
</a>

</div>


<div class="profile-card">

<div class="avatar">

<i class="fa-solid fa-user-tie"></i>

</div>

<h3>

<?= htmlspecialchars($wali['nama']) ?>

</h3>

<p class="text-secondary">

Wali Kelas XI TJKT 2

</p>


<div class="info">

<div class="info-item">

<div class="label">
Kelas
</div>

<div class="value">
<?= htmlspecialchars($wali['kelas']) ?>
</div>

</div>


<div class="info-item">

<div class="label">
Jurusan
</div>

<div class="value">
<?= htmlspecialchars($wali['jurusan']) ?>
</div>

</div>


<div class="info-item">

<div class="label">
Tahun Pelajaran
</div>

<div class="value">
<?= htmlspecialchars($wali['tahun']) ?>
</div>

</div>

</div>

</div>

</div>

</body>

</html>