<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";

wajib_login(['wali_kelas']);

$pesan = '';
$error = '';

$hari_list = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat'
];

/*
|--------------------------------------------------------------------------
| TAMBAH
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'tambah'
) {

    $hari = $_POST['hari'] ?? '';
    $jam_ke = (int) ($_POST['jam_ke'] ?? 0);
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $guru = trim($_POST['guru'] ?? '');

    if (
        !in_array($hari, $hari_list, true) ||
        $jam_ke < 1 ||
        $mata_pelajaran === '' ||
        $guru === ''
    ) {

        $error = "Semua data jadwal wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO jadwal
            (hari, jam_ke, mata_pelajaran, guru)
            VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "siss",
            $hari,
            $jam_ke,
            $mata_pelajaran,
            $guru
        );

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Jadwal berhasil ditambahkan.";
        } else {
            $error = "Gagal menambahkan jadwal.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'edit'
) {

    $id = (int) ($_POST['id'] ?? 0);
    $hari = $_POST['hari'] ?? '';
    $jam_ke = (int) ($_POST['jam_ke'] ?? 0);
    $mata_pelajaran = trim($_POST['mata_pelajaran'] ?? '');
    $guru = trim($_POST['guru'] ?? '');

    if (
        $id <= 0 ||
        !in_array($hari, $hari_list, true) ||
        $jam_ke < 1 ||
        $mata_pelajaran === '' ||
        $guru === ''
    ) {

        $error = "Data jadwal tidak lengkap.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE jadwal
             SET hari = ?,
                 jam_ke = ?,
                 mata_pelajaran = ?,
                 guru = ?
             WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sissi",
            $hari,
            $jam_ke,
            $mata_pelajaran,
            $guru,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Jadwal berhasil diperbarui.";
        } else {
            $error = "Gagal memperbarui jadwal.";
        }
    }
}


/*
|--------------------------------------------------------------------------
| HAPUS
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    $stmt = mysqli_prepare(
        $conn,
        "DELETE FROM jadwal WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        $pesan = "Jadwal berhasil dihapus.";
    } else {
        $error = "Gagal menghapus jadwal.";
    }
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM jadwal
     ORDER BY
     FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat'),
     jam_ke ASC"
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$jadwal = [];

while ($row = mysqli_fetch_assoc($result)) {
    $jadwal[] = $row;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Jadwal Pelajaran - XI TJKT 2</title>

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
    max-width:1200px;
    margin:auto;
    padding:25px 15px 50px;
}

.card-custom {
    background:#111827;
    border:1px solid #1f2937;
    border-radius:18px;
    padding:20px;
}

.topbar {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:25px;
}

.title {
    font-size:26px;
    font-weight:800;
}

.subtitle {
    color:#94a3b8;
    margin-top:5px;
}

.form-control,
.form-select {
    background:#0f172a;
    border-color:#334155;
    color:#fff;
}

.form-control:focus,
.form-select:focus {
    background:#0f172a;
    color:#fff;
    border-color:#6366f1;
    box-shadow:none;
}

.form-select option {
    background:#0f172a;
}

.table {
    color:#fff;
    vertical-align:middle;
}

.table td,
.table th {
    border-color:#273244;
}

</style>

</head>

<body>

<div class="container-main">

<div class="topbar">

<div>

<div class="title">
<i class="fa-solid fa-calendar-days"></i>
Jadwal Pelajaran
</div>

<div class="subtitle">
XI TJKT 2
</div>

</div>

<div class="d-flex gap-2">

<a
href="index.php"
class="btn btn-secondary"
>
<i class="fa-solid fa-arrow-left"></i>
Dashboard
</a>

<button
class="btn btn-primary"
data-bs-toggle="modal"
data-bs-target="#modalTambah"
>
<i class="fa-solid fa-plus"></i>
Tambah Jadwal
</button>

</div>

</div>


<?php if ($pesan): ?>

<div class="alert alert-success">
<?= htmlspecialchars($pesan) ?>
</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<div class="card-custom">

<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>#</th>
<th>Hari</th>
<th>Jam Ke</th>
<th>Mata Pelajaran</th>
<th>Guru</th>
<th>Aksi</th>

</tr>

</thead>

<tbody>

<?php foreach ($jadwal as $i => $j): ?>

<tr>

<td><?= $i + 1 ?></td>

<td>
<strong><?= htmlspecialchars($j['hari']) ?></strong>
</td>

<td>
<?= htmlspecialchars($j['jam_ke']) ?>
</td>

<td>
<?= htmlspecialchars($j['mata_pelajaran']) ?>
</td>

<td>
<?= htmlspecialchars($j['guru']) ?>
</td>

<td>

<button
class="btn btn-warning btn-sm"
data-bs-toggle="modal"
data-bs-target="#edit<?= $j['id'] ?>"
>
<i class="fa-solid fa-pen"></i>
</button>

<a
href="?hapus=<?= $j['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Hapus jadwal ini?')"
>
<i class="fa-solid fa-trash"></i>
</a>

</td>

</tr>


<!-- MODAL EDIT -->

<div
class="modal fade"
id="edit<?= $j['id'] ?>"
tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content bg-dark text-white">

<form method="POST">

<div class="modal-header">

<h5 class="modal-title">
Edit Jadwal
</h5>

<button
type="button"
class="btn-close btn-close-white"
data-bs-dismiss="modal"
></button>

</div>

<div class="modal-body">

<input
type="hidden"
name="aksi"
value="edit"
>

<input
type="hidden"
name="id"
value="<?= $j['id'] ?>"
>

<div class="mb-3">

<label>Hari</label>

<select
name="hari"
class="form-select"
required
>

<?php foreach ($hari_list as $hari): ?>

<option
value="<?= $hari ?>"
<?= $j['hari'] === $hari ? 'selected' : '' ?>
>
<?= $hari ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Jam Ke</label>

<input
type="number"
name="jam_ke"
class="form-control"
min="1"
value="<?= $j['jam_ke'] ?>"
required
>

</div>

<div class="mb-3">

<label>Mata Pelajaran</label>

<input
type="text"
name="mata_pelajaran"
class="form-control"
value="<?= htmlspecialchars($j['mata_pelajaran']) ?>"
required
>

</div>

<div class="mb-3">

<label>Guru</label>

<input
type="text"
name="guru"
class="form-control"
value="<?= htmlspecialchars($j['guru']) ?>"
required
>

</div>

</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal"
>
Batal
</button>

<button
class="btn btn-primary"
>
Simpan
</button>

</div>

</form>

</div>

</div>

</div>

<?php endforeach; ?>

</tbody>

</table>

</div>

</div>

</div>


<!-- MODAL TAMBAH -->

<div
class="modal fade"
id="modalTambah"
tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content bg-dark text-white">

<form method="POST">

<div class="modal-header">

<h5 class="modal-title">
<i class="fa-solid fa-plus"></i>
Tambah Jadwal
</h5>

<button
type="button"
class="btn-close btn-close-white"
data-bs-dismiss="modal"
></button>

</div>

<div class="modal-body">

<input
type="hidden"
name="aksi"
value="tambah"
>

<div class="mb-3">

<label>Hari</label>

<select
name="hari"
class="form-select"
required
>

<option value="">
-- Pilih Hari --
</option>

<?php foreach ($hari_list as $hari): ?>

<option value="<?= $hari ?>">
<?= $hari ?>
</option>

<?php endforeach; ?>

</select>

</div>

<div class="mb-3">

<label>Jam Ke</label>

<input
type="number"
name="jam_ke"
class="form-control"
min="1"
max="20"
required
>

</div>

<div class="mb-3">

<label>Mata Pelajaran</label>

<input
type="text"
name="mata_pelajaran"
class="form-control"
placeholder="Contoh: Bahasa Indonesia"
required
>

</div>

<div class="mb-3">

<label>Guru</label>

<input
type="text"
name="guru"
class="form-control"
placeholder="Nama guru"
required
>

</div>

</div>

<div class="modal-footer">

<button
type="button"
class="btn btn-secondary"
data-bs-dismiss="modal"
>
Batal
</button>

<button
class="btn btn-primary"
>
Tambah
</button>

</div>

</form>

</div>

</div>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>