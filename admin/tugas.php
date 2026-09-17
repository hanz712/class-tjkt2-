<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";

wajib_login(['wali_kelas']);

$pesan = '';
$error = '';

/*
|--------------------------------------------------------------------------
| TAMBAH TUGAS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'tambah'
) {

    $judul = trim($_POST['judul'] ?? '');
    $guru = trim($_POST['guru'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    if ($judul === '') {

        $error = "Judul tugas wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO tugas
            (judul, guru, deskripsi, deadline)
            VALUES (?, ?, ?, NULLIF(?, ''))"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $judul,
            $guru,
            $deskripsi,
            $deadline
        );

        if (mysqli_stmt_execute($stmt)) {
            $pesan = "Tugas berhasil ditambahkan.";
        } else {
            $error = "Gagal menambahkan tugas.";
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
        "DELETE FROM tugas WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    if (mysqli_stmt_execute($stmt)) {
        $pesan = "Tugas berhasil dihapus.";
    } else {
        $error = "Gagal menghapus tugas.";
    }
}


/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM tugas
     ORDER BY
        deadline IS NULL,
        deadline ASC,
        created_at DESC"
);

$tugas = [];

while ($row = mysqli_fetch_assoc($result)) {
    $tugas[] = $row;
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

<title>Tugas - XI TJKT 2</title>

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
    max-width:1100px;
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

.card-custom {
    background:#111827;
    border:1px solid #1f2937;
    border-radius:18px;
    padding:20px;
}

.task-card {
    background:#0f172a;
    border:1px solid #1f2937;
    border-radius:16px;
    padding:20px;
    height:100%;
}

.task-title {
    font-size:18px;
    font-weight:700;
}

.task-description {
    color:#94a3b8;
    white-space:pre-line;
}

.form-control {
    background:#0f172a;
    color:#fff;
    border-color:#334155;
}

.form-control:focus {
    background:#0f172a;
    color:#fff;
    border-color:#6366f1;
    box-shadow:none;
}

</style>

</head>

<body>

<div class="container-main">

<div class="topbar">

<div>

<div class="title">
<i class="fa-solid fa-clipboard-list"></i>
Tugas
</div>

<div class="subtitle">
Tugas dari guru untuk XI TJKT 2
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
Tambah Tugas
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


<div class="row g-3">

<?php if (empty($tugas)): ?>

<div class="col-12">

<div class="card-custom text-center">

<i class="fa-solid fa-clipboard fa-2x text-secondary mb-3"></i>

<h5>Belum ada tugas</h5>

<p class="text-secondary mb-0">
Tugas yang ditambahkan akan muncul di sini.
</p>

</div>

</div>

<?php endif; ?>


<?php foreach ($tugas as $t): ?>

<div class="col-md-6 col-lg-4">

<div class="task-card">

<div class="task-title mb-2">

<?= htmlspecialchars($t['judul']) ?>

</div>

<?php if ($t['guru']): ?>

<div class="small text-secondary mb-3">

<i class="fa-solid fa-user"></i>

<?= htmlspecialchars($t['guru']) ?>

</div>

<?php endif; ?>


<?php if ($t['deskripsi']): ?>

<div class="task-description mb-3">

<?= htmlspecialchars($t['deskripsi']) ?>

</div>

<?php endif; ?>


<?php if ($t['deadline']): ?>

<div class="mb-3">

<span class="badge bg-warning text-dark">

<i class="fa-solid fa-calendar"></i>

Deadline:
<?= date(
    'd M Y',
    strtotime($t['deadline'])
) ?>

</span>

</div>

<?php endif; ?>


<a
href="?hapus=<?= $t['id'] ?>"
class="btn btn-outline-danger btn-sm"
onclick="return confirm('Hapus tugas ini?')"
>

<i class="fa-solid fa-trash"></i>

Hapus

</a>

</div>

</div>

<?php endforeach; ?>

</div>

</div>


<!-- MODAL -->

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
Tambah Tugas
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

<label>Judul Tugas</label>

<input
type="text"
name="judul"
class="form-control"
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
>

</div>


<div class="mb-3">

<label>Deskripsi</label>

<textarea
name="deskripsi"
class="form-control"
rows="5"
placeholder="Instruksi tugas..."
></textarea>

</div>


<div class="mb-3">

<label>Deadline</label>

<input
type="date"
name="deadline"
class="form-control"
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

<button class="btn btn-primary">
Tambah Tugas
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