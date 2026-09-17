<?php

require_once __DIR__ . "/../config/auth.php";
require_once __DIR__ . "/../config/database.php";

wajib_login(['wali_kelas']);

$message = "";
$error = "";

/*
|--------------------------------------------------------------------------
| BUAT FOLDER GALLERY
|--------------------------------------------------------------------------
*/

$folder = __DIR__ . "/../assets/images/gallery/";

if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}


/*
|--------------------------------------------------------------------------
| UPLOAD FOTO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["tambah_galeri"])) {

    $judul = trim($_POST["judul"] ?? "");
    $deskripsi = trim($_POST["deskripsi"] ?? "");

    if (
        !isset($_FILES["gambar"]) ||
        $_FILES["gambar"]["error"] !== UPLOAD_ERR_OK
    ) {

        $error = "Foto wajib dipilih.";

    } else {

        $file = $_FILES["gambar"];

        if ($file["size"] > 5 * 1024 * 1024) {

            $error = "Ukuran foto maksimal 5 MB.";

        } else {

            $allowed = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            $mime = mime_content_type($file["tmp_name"]);

            if (!isset($allowed[$mime])) {

                $error = "Format foto harus JPG, PNG, atau WEBP.";

            } else {

                $extension = $allowed[$mime];

                $nama_file =
                    "gallery_" .
                    bin2hex(random_bytes(8)) .
                    "." .
                    $extension;

                $tujuan = $folder . $nama_file;

                if (move_uploaded_file(
                    $file["tmp_name"],
                    $tujuan
                )) {

                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO galeri
                        (judul, gambar, deskripsi)
                        VALUES (?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sss",
                        $judul,
                        $nama_file,
                        $deskripsi
                    );

                    if (mysqli_stmt_execute($stmt)) {

                        $message = "Foto berhasil ditambahkan.";

                    } else {

                        if (file_exists($tujuan)) {
                            unlink($tujuan);
                        }

                        $error = "Gagal menyimpan data galeri.";
                    }

                    mysqli_stmt_close($stmt);

                } else {

                    $error = "Gagal mengupload foto.";
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| HAPUS FOTO
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["hapus_id"])) {

    $id = (int) $_POST["hapus_id"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT gambar
         FROM galeri
         WHERE id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $foto = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($foto) {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM galeri
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {

            $file = $folder . basename($foto["gambar"]);

            if (file_exists($file)) {
                unlink($file);
            }

            $message = "Foto berhasil dihapus.";

        } else {

            $error = "Foto gagal dihapus.";
        }

        mysqli_stmt_close($stmt);
    }
}


/*
|--------------------------------------------------------------------------
| DATA GALERI
|--------------------------------------------------------------------------
*/

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM galeri
     ORDER BY created_at DESC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Galeri TJKT 2</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
rel="stylesheet">

<style>

body {
    background:#f4f6f8;
    color:#111827;
}

.gallery-card {
    background:#fff;
    border:0;
    border-radius:20px;
    overflow:hidden;
    height:100%;
    box-shadow:0 7px 25px rgba(0,0,0,.05);
}

.gallery-image {
    width:100%;
    height:210px;
    object-fit:cover;
}

.gallery-body {
    padding:18px;
}

.empty {
    background:white;
    border-radius:20px;
    padding:50px;
    text-align:center;
}

</style>

</head>

<body>

<nav class="navbar navbar-dark bg-dark">

<div class="container">

<a
href="index.php"
class="navbar-brand fw-bold"
>
TJKT 2 HUB
</a>

<a
href="index.php"
class="btn btn-outline-light btn-sm"
>
Dashboard
</a>

</div>

</nav>


<div class="container py-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="fw-bold mb-1">
<i class="fa-solid fa-images"></i>
Galeri TJKT 2
</h2>

<p class="text-muted mb-0">
Kelola dokumentasi kelas XI TJKT 2.
</p>

</div>

<button
class="btn btn-dark"
data-bs-toggle="modal"
data-bs-target="#modalTambah"
>

<i class="fa-solid fa-plus me-2"></i>
Tambah Foto

</button>

</div>


<?php if ($message): ?>

<div class="alert alert-success">
<?= htmlspecialchars($message) ?>
</div>

<?php endif; ?>


<?php if ($error): ?>

<div class="alert alert-danger">
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<div class="row g-4">

<?php while ($row = mysqli_fetch_assoc($result)): ?>

<div class="col-md-6 col-lg-4">

<div class="gallery-card">

<img
src="../assets/images/gallery/<?= htmlspecialchars($row["gambar"]) ?>"
class="gallery-image"
alt="Galeri"
>

<div class="gallery-body">

<h5 class="fw-bold">
<?= htmlspecialchars($row["judul"] ?: "Dokumentasi TJKT 2") ?>
</h5>

<?php if (!empty($row["deskripsi"])): ?>

<p class="text-muted small">
<?= nl2br(htmlspecialchars($row["deskripsi"])) ?>
</p>

<?php endif; ?>


<form method="POST">

<input
type="hidden"
name="hapus_id"
value="<?= (int)$row["id"] ?>"
>

<button
type="submit"
class="btn btn-outline-danger btn-sm"
onclick="return confirm('Hapus foto ini?')"
>

<i class="fa-solid fa-trash"></i>
Hapus

</button>

</form>

</div>

</div>

</div>

<?php endwhile; ?>

</div>

</div>


<!-- MODAL TAMBAH -->

<div
class="modal fade"
id="modalTambah"
tabindex="-1"
>

<div class="modal-dialog">

<div class="modal-content">

<form
method="POST"
enctype="multipart/form-data"
>

<div class="modal-header">

<h5 class="modal-title">
Tambah Foto
</h5>

<button
type="button"
class="btn-close"
data-bs-dismiss="modal"
></button>

</div>


<div class="modal-body">

<div class="mb-3">

<label class="form-label">
Judul
</label>

<input
type="text"
name="judul"
class="form-control"
placeholder="Contoh: Praktik Jaringan"
>

</div>


<div class="mb-3">

<label class="form-label">
Foto
</label>

<input
type="file"
name="gambar"
class="form-control"
accept="image/jpeg,image/png,image/webp"
required
>

<div class="form-text">
Maksimal 5 MB.
</div>

</div>


<div class="mb-3">

<label class="form-label">
Deskripsi
</label>

<textarea
name="deskripsi"
class="form-control"
rows="4"
placeholder="Keterangan foto..."
></textarea>

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
type="submit"
name="tambah_galeri"
class="btn btn-dark"
>
Simpan Foto
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