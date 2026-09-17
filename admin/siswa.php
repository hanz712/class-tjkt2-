<?php

require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../config/auth.php";

wajib_login(['wali_kelas']);

$pesan = '';
$error = '';

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    // Ambil NIS terlebih dahulu
    $stmt = mysqli_prepare(
        $conn,
        "SELECT nis FROM siswa WHERE id = ? LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $siswa = mysqli_fetch_assoc($result);

    if ($siswa) {

        $nis = $siswa['nis'];

        // Hapus akun login siswa
        $delete_user = mysqli_prepare(
            $conn,
            "DELETE FROM users
             WHERE username = ?
             AND role = 'siswa'"
        );

        mysqli_stmt_bind_param(
            $delete_user,
            "s",
            $nis
        );

        mysqli_stmt_execute($delete_user);

        // Hapus siswa
        $delete_siswa = mysqli_prepare(
            $conn,
            "DELETE FROM siswa WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $delete_siswa,
            "i",
            $id
        );

        if (mysqli_stmt_execute($delete_siswa)) {
            $pesan = "Data siswa berhasil dihapus.";
        } else {
            $error = "Gagal menghapus data siswa.";
        }

    } else {
        $error = "Data siswa tidak ditemukan.";
    }
}


/*
|--------------------------------------------------------------------------
| TAMBAH SISWA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'tambah'
) {

    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';

    if (
        $nis === '' ||
        $nama === '' ||
        !in_array($jenis_kelamin, ['L', 'P'], true)
    ) {

        $error = "Semua data siswa wajib diisi.";

    } else {

        // Cek NIS
        $cek = mysqli_prepare(
            $conn,
            "SELECT id FROM siswa WHERE nis = ? LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $cek,
            "s",
            $nis
        );

        mysqli_stmt_execute($cek);

        $hasil = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasil) > 0) {

            $error = "NIS tersebut sudah terdaftar.";

        } else {

            // QR siswa
            $qr_code = "TJKT2-" . strtoupper(
                bin2hex(random_bytes(6))
            );

            // Simpan siswa
            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO siswa
                (nis, nama, jenis_kelamin, qr_code)
                VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $insert,
                "ssss",
                $nis,
                $nama,
                $jenis_kelamin,
                $qr_code
            );

            if (mysqli_stmt_execute($insert)) {

                /*
                |--------------------------------------------------------------------------
                | BUAT AKUN LOGIN SISWA
                |--------------------------------------------------------------------------
                */

                $password_hash = password_hash(
                    $nis,
                    PASSWORD_DEFAULT
                );

                $user = mysqli_prepare(
                    $conn,
                    "INSERT INTO users
                    (nama, username, password, role)
                    VALUES (?, ?, ?, 'siswa')"
                );

                mysqli_stmt_bind_param(
                    $user,
                    "sss",
                    $nama,
                    $nis,
                    $password_hash
                );

                if (mysqli_stmt_execute($user)) {

                    $pesan =
                        "Siswa berhasil ditambahkan. " .
                        "Akun login otomatis dibuat.";

                } else {

                    // Jika akun gagal dibuat, hapus siswa
                    $delete = mysqli_prepare(
                        $conn,
                        "DELETE FROM siswa WHERE nis = ?"
                    );

                    mysqli_stmt_bind_param(
                        $delete,
                        "s",
                        $nis
                    );

                    mysqli_stmt_execute($delete);

                    $error =
                        "Siswa gagal dibuat karena akun login " .
                        "tidak berhasil dibuat.";
                }

            } else {

                $error =
                    "Gagal menyimpan siswa: " .
                    mysqli_error($conn);
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT SISWA
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['aksi'] ?? '') === 'edit'
) {

    $id = (int) ($_POST['id'] ?? 0);
    $nis = trim($_POST['nis'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? '';

    if (
        $id <= 0 ||
        $nis === '' ||
        $nama === '' ||
        !in_array($jenis_kelamin, ['L', 'P'], true)
    ) {

        $error = "Data edit tidak lengkap.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | Ambil NIS lama
        |--------------------------------------------------------------------------
        */

        $old = mysqli_prepare(
            $conn,
            "SELECT nis FROM siswa
             WHERE id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $old,
            "i",
            $id
        );

        mysqli_stmt_execute($old);

        $old_result = mysqli_stmt_get_result($old);
        $old_data = mysqli_fetch_assoc($old_result);

        if (!$old_data) {

            $error = "Data siswa tidak ditemukan.";

        } else {

            $nis_lama = $old_data['nis'];

            /*
            |--------------------------------------------------------------------------
            | Cek apakah NIS baru dipakai siswa lain
            |--------------------------------------------------------------------------
            */

            $cek = mysqli_prepare(
                $conn,
                "SELECT id FROM siswa
                 WHERE nis = ?
                 AND id != ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param(
                $cek,
                "si",
                $nis,
                $id
            );

            mysqli_stmt_execute($cek);

            $cek_result = mysqli_stmt_get_result($cek);

            if (mysqli_num_rows($cek_result) > 0) {

                $error = "NIS tersebut sudah digunakan siswa lain.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | UPDATE SISWA
                |--------------------------------------------------------------------------
                */

                $update = mysqli_prepare(
                    $conn,
                    "UPDATE siswa
                     SET nis = ?, nama = ?, jenis_kelamin = ?
                     WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $update,
                    "sssi",
                    $nis,
                    $nama,
                    $jenis_kelamin,
                    $id
                );

                if (mysqli_stmt_execute($update)) {

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE AKUN LOGIN
                    |--------------------------------------------------------------------------
                    |
                    | Username siswa mengikuti NIS.
                    |
                    */

                    $update_user = mysqli_prepare(
                        $conn,
                        "UPDATE users
                         SET username = ?, nama = ?
                         WHERE username = ?
                         AND role = 'siswa'"
                    );

                    mysqli_stmt_bind_param(
                        $update_user,
                        "sss",
                        $nis,
                        $nama,
                        $nis_lama
                    );

                    mysqli_stmt_execute($update_user);

                    $pesan = "Data siswa berhasil diperbarui.";

                } else {

                    $error =
                        "Gagal memperbarui siswa: " .
                        mysqli_error($conn);
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $keyword = "%" . $search . "%";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM siswa
         WHERE nis LIKE ?
         OR nama LIKE ?
         ORDER BY nama ASC"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $keyword,
        $keyword
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM siswa
         ORDER BY nama ASC"
    );
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$siswa_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    $siswa_list[] = $row;
}


/*
|--------------------------------------------------------------------------
| TOTAL SISWA
|--------------------------------------------------------------------------
*/

$total_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM siswa"
);

$total_data = mysqli_fetch_assoc($total_query);

$total_siswa = (int) $total_data['total'];

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Daftar Siswa - XI TJKT 2</title>

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
            background: #0b1020;
            color: #fff;
            font-family: Arial, sans-serif;
        }

        .container-main {
            max-width: 1200px;
            margin: auto;
            padding: 25px 15px 50px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .title {
            font-size: 26px;
            font-weight: 800;
        }

        .subtitle {
            color: #94a3b8;
            margin-top: 5px;
        }

        .card-custom {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 18px;
            padding: 20px;
        }

        .search-box {
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
        }

        .search-box:focus {
            background: #0f172a;
            color: #fff;
            border-color: #6366f1;
            box-shadow: none;
        }

        .student-card {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 18px;
            padding: 18px;
            height: 100%;
        }

        .student-card:hover {
            border-color: #475569;
            transform: translateY(-2px);
            transition: .2s;
        }

        .avatar {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1e293b;
            font-size: 20px;
            font-weight: 800;
        }

        .student-name {
            font-size: 17px;
            font-weight: 700;
        }

        .student-nis {
            color: #94a3b8;
            font-size: 13px;
        }

        .gender {
            color: #cbd5e1;
            font-size: 13px;
        }

        .btn-dark-custom {
            background: #1f2937;
            color: #fff;
            border: none;
        }

        .btn-dark-custom:hover {
            background: #374151;
            color: #fff;
        }

        .qr-code {
            display: flex;
            justify-content: center;
            padding: 15px;
        }

        .modal-content {
            background: #111827;
            color: #fff;
            border: 1px solid #334155;
            border-radius: 18px;
        }

        .form-control,
        .form-select {
            background: #0f172a;
            border-color: #334155;
            color: #fff;
        }

        .form-control:focus,
        .form-select:focus {
            background: #0f172a;
            color: #fff;
            border-color: #6366f1;
            box-shadow: none;
        }

        .form-select option {
            background: #0f172a;
        }

        .btn-close {
            filter: invert(1);
        }

    </style>

</head>

<body>

<div class="container-main">

    <!-- HEADER -->

    <div class="topbar">

        <div>

            <div class="title">
                <i class="fa-solid fa-users"></i>
                Daftar Siswa
            </div>

            <div class="subtitle">
                XI TJKT 2 • <?= $total_siswa ?> siswa
            </div>

        </div>

        <div class="d-flex gap-2">

            <a
                href="index.php"
                class="btn btn-dark-custom"
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


    <!-- PESAN -->

    <?php if ($pesan): ?>

        <div class="alert alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($pesan) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- SEARCH -->

    <div class="card-custom mb-4">

        <form method="GET">

            <div class="input-group">

                <span class="input-group-text bg-dark border-secondary text-secondary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    class="form-control search-box"
                    placeholder="Cari nama atau NIS..."
                >

                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    Cari
                </button>

            </div>

        </form>

    </div>


    <!-- LIST SISWA -->

    <div class="row g-3">

        <?php if (empty($siswa_list)): ?>

            <div class="col-12">

                <div class="card-custom text-center py-5">

                    <i
                        class="fa-solid fa-user-slash fa-2x mb-3 text-secondary"
                    ></i>

                    <h5>
                        Siswa tidak ditemukan
                    </h5>

                    <p class="text-secondary mb-0">
                        Coba gunakan nama atau NIS yang berbeda.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <?php foreach ($siswa_list as $siswa): ?>

            <?php

            $initial = strtoupper(
                substr($siswa['nama'], 0, 1)
            );

            ?>

            <div class="col-md-6 col-lg-4">

                <div class="student-card">

                    <div class="d-flex align-items-center gap-3 mb-3">

                        <div class="avatar">
                            <?= htmlspecialchars($initial) ?>
                        </div>

                        <div>

                            <div class="student-name">
                                <?= htmlspecialchars($siswa['nama']) ?>
                            </div>

                            <div class="student-nis">
                                NIS:
                                <?= htmlspecialchars($siswa['nis']) ?>
                            </div>

                        </div>

                    </div>


                    <div class="gender mb-3">

                        <i class="fa-solid fa-venus-mars"></i>

                        <?= $siswa['jenis_kelamin'] === 'L'
                            ? 'Laki-laki'
                            : 'Perempuan' ?>

                    </div>


                    <div class="d-flex gap-2">

                        <!-- QR -->

                        <button
                            class="btn btn-primary btn-sm flex-fill"
                            data-bs-toggle="modal"
                            data-bs-target="#qr<?= $siswa['id'] ?>"
                        >
                            <i class="fa-solid fa-qrcode"></i>
                            QR
                        </button>


                        <!-- EDIT -->

                        <button
                            class="btn btn-warning btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#edit<?= $siswa['id'] ?>"
                        >
                            <i class="fa-solid fa-pen"></i>
                        </button>


                        <!-- HAPUS -->

                        <a
                            href="?hapus=<?= $siswa['id'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm(
                                'Yakin ingin menghapus <?= htmlspecialchars(
                                    $siswa['nama'],
                                    ENT_QUOTES
                                ) ?>?'
                            )"
                        >
                            <i class="fa-solid fa-trash"></i>
                        </a>

                    </div>

                </div>

            </div>


            <!-- MODAL QR -->

            <div
                class="modal fade"
                id="qr<?= $siswa['id'] ?>"
                tabindex="-1"
            >

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content">

                        <div class="modal-header">

                            <h5 class="modal-title">
                                <i class="fa-solid fa-qrcode"></i>
                                QR Siswa
                            </h5>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>

                        </div>

                        <div class="modal-body text-center">

                            <h5>
                                <?= htmlspecialchars($siswa['nama']) ?>
                            </h5>

                            <p class="text-secondary">
                                NIS:
                                <?= htmlspecialchars($siswa['nis']) ?>
                            </p>

                            <div class="qr-code">

                                <div
                                    id="qr-code-<?= $siswa['id'] ?>"
                                ></div>

                            </div>

                            <small class="text-secondary">
                                <?= htmlspecialchars($siswa['qr_code']) ?>
                            </small>

                        </div>

                    </div>

                </div>

            </div>


            <!-- MODAL EDIT -->

            <div
                class="modal fade"
                id="edit<?= $siswa['id'] ?>"
                tabindex="-1"
            >

                <div class="modal-dialog modal-dialog-centered">

                    <div class="modal-content">

                        <form method="POST">

                            <div class="modal-header">

                                <h5 class="modal-title">
                                    <i class="fa-solid fa-pen"></i>
                                    Edit Siswa
                                </h5>

                                <button
                                    type="button"
                                    class="btn-close"
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
                                    value="<?= $siswa['id'] ?>"
                                >


                                <div class="mb-3">

                                    <label class="form-label">
                                        NIS
                                    </label>

                                    <input
                                        type="text"
                                        name="nis"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $siswa['nis']
                                        ) ?>"
                                        required
                                    >

                                </div>


                                <div class="mb-3">

                                    <label class="form-label">
                                        Nama Lengkap
                                    </label>

                                    <input
                                        type="text"
                                        name="nama"
                                        class="form-control"
                                        value="<?= htmlspecialchars(
                                            $siswa['nama']
                                        ) ?>"
                                        required
                                    >

                                </div>


                                <div class="mb-3">

                                    <label class="form-label">
                                        Jenis Kelamin
                                    </label>

                                    <select
                                        name="jenis_kelamin"
                                        class="form-select"
                                        required
                                    >

                                        <option
                                            value="L"
                                            <?= $siswa['jenis_kelamin'] === 'L'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Laki-laki
                                        </option>

                                        <option
                                            value="P"
                                            <?= $siswa['jenis_kelamin'] === 'P'
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            Perempuan
                                        </option>

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

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    <i class="fa-solid fa-save"></i>
                                    Simpan
                                </button>

                            </div>

                        </form>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>


<!-- MODAL TAMBAH -->

<div
    class="modal fade"
    id="modalTambah"
    tabindex="-1"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <form method="POST">

                <div class="modal-header">

                    <h5 class="modal-title">
                        <i class="fa-solid fa-user-plus"></i>
                        Tambah Siswa
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
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

                        <label class="form-label">
                            NIS
                        </label>

                        <input
                            type="text"
                            name="nis"
                            class="form-control"
                            placeholder="Contoh: 001"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Nama Lengkap
                        </label>

                        <input
                            type="text"
                            name="nama"
                            class="form-control"
                            placeholder="Nama siswa"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Jenis Kelamin
                        </label>

                        <select
                            name="jenis_kelamin"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih --
                            </option>

                            <option value="L">
                                Laki-laki
                            </option>

                            <option value="P">
                                Perempuan
                            </option>

                        </select>

                    </div>


                    <div class="alert alert-info mb-0">

                        <i class="fa-solid fa-circle-info"></i>

                        Akun login siswa akan otomatis dibuat.

                        <br>

                        <strong>
                            Username:
                        </strong>
                        NIS

                        <br>

                        <strong>
                            Password awal:
                        </strong>
                        NIS

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
                        class="btn btn-primary"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Tambahkan
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script
    src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
></script>


<script>

<?php foreach ($siswa_list as $siswa): ?>

new QRCode(
    document.getElementById(
        "qr-code-<?= $siswa['id'] ?>"
    ),
    {
        text: <?= json_encode($siswa['qr_code']) ?>,
        width: 220,
        height: 220
    }
);

<?php endforeach; ?>

</script>

</body>

</html>