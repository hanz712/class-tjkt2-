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
    $siswa_id = (int) ($_POST['siswa_id'] ?? 0);

    if (
        !in_array($hari, $hari_list, true) ||
        $siswa_id <= 0
    ) {

        $error = "Hari dan siswa wajib dipilih.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO piket (hari, siswa_id)
             VALUES (?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $hari,
            $siswa_id
        );

        if (mysqli_stmt_execute($stmt)) {

            $pesan = "Siswa berhasil dimasukkan ke jadwal piket.";

        } else {

            if (mysqli_errno($conn) == 1062) {
                $error = "Siswa tersebut sudah ada pada hari itu.";
            } else {
                $error = "Gagal menambahkan jadwal piket.";
            }
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
        "DELETE FROM piket WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    if (mysqli_stmt_execute($stmt)) {
        $pesan = "Jadwal piket berhasil dihapus.";
    } else {
        $error = "Gagal menghapus jadwal piket.";
    }
}


/*
|--------------------------------------------------------------------------
| DATA SISWA
|--------------------------------------------------------------------------
*/

$siswa_result = mysqli_query(
    $conn,
    "SELECT id, nis, nama
     FROM siswa
     ORDER BY nama ASC"
);

$siswa_list = [];

while ($row = mysqli_fetch_assoc($siswa_result)) {
    $siswa_list[] = $row;
}


/*
|--------------------------------------------------------------------------
| DATA PIKET
|--------------------------------------------------------------------------
*/

$piket_result = mysqli_query(
    $conn,
    "SELECT
        p.id,
        p.hari,
        s.nis,
        s.nama
     FROM piket p
     INNER JOIN siswa s
        ON p.siswa_id = s.id
     ORDER BY
        FIELD(
            p.hari,
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat'
        ),
        s.nama ASC"
);

$piket = [];

while ($row = mysqli_fetch_assoc($piket_result)) {
    $piket[] = $row;
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

<title>Jadwal Piket - XI TJKT 2</title>

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
}

.form-control,
.form-select {
    background:#0f172a;
    color:#fff;
    border-color:#334155;
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

.piket-item {
    background:#0f172a;
    border:1px solid #1f2937;
    border-radius:14px;
    padding:15px;
    margin-bottom:10px;
}

</style>

</head>

<body>

<div class="container-main">

<div class="topbar">

<div>

<div class="title">
<i class="fa-solid fa-broom"></i>
Jadwal Piket
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
Tambah
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

<div class="row g-3">

<?php foreach ($hari_list as $hari): ?>

<div class="col-md-6 col-lg-4">

<div class="piket-item">

<h5>

<i class="fa-solid fa-calendar-day"></i>

<?= $hari ?>

</h5>

<?php

$ada = false;

foreach ($piket as $p):

if ($p['hari'] !== $hari) {
continue;
}

$ada = true;

?>

<div class="d-flex justify-content-between align-items-center mt-3">

<div>

<strong>
<?= htmlspecialchars($p['nama']) ?>
</strong>

<br>

<small class="text-secondary">
NIS <?= htmlspecialchars($p['nis']) ?>
</small>

</div>

<a
href="?hapus=<?= $p['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Hapus dari jadwal piket?')"
>
<i class="fa-solid fa-trash"></i>
</a>

</div>

<?php endforeach; ?>


<?php if (!$ada): ?>

<div class="text-secondary mt-3">
Belum ada siswa.
</div>

<?php endif; ?>

</div>

</div>

<?php endforeach; ?>

</div>

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
Tambah Jadwal Piket
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

<label>Siswa</label>

<select
name="siswa_id"
class="form-select"
required
>

<option value="">
-- Pilih Siswa --
</option>

<?php foreach ($siswa_list as $s): ?>

<option value="<?= $s['id'] ?>">

<?= htmlspecialchars($s['nis']) ?>
-
<?= htmlspecialchars($s['nama']) ?>

</option>

<?php endforeach; ?>

</select>

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