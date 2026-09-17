<?php

require_once __DIR__ . "/../config/auth.php";

wajib_login(['wali_kelas']);

/*
|--------------------------------------------------------------------------
| FILE PENYIMPANAN
|--------------------------------------------------------------------------
| Data struktur disimpan di file JSON.
| Tidak perlu menambah tabel database.
*/

$data_dir  = __DIR__ . "/../data";
$data_file = $data_dir . "/struktur.json";

/* Buat folder data jika belum ada */
if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

/*
|--------------------------------------------------------------------------
| DATA DEFAULT
|--------------------------------------------------------------------------
*/

$struktur_default = [
    [
        'jabatan' => 'Wali Kelas',
        'nama'    => 'Deden Nur Rahayu Mamad, S.Pd.',
        'icon'    => 'fa-chalkboard-user'
    ],
    [
        'jabatan' => 'Ketua Kelas',
        'nama'    => 'Belum ditentukan',
        'icon'    => 'fa-user-tie'
    ],
    [
        'jabatan' => 'Wakil Ketua',
        'nama'    => 'Belum ditentukan',
        'icon'    => 'fa-user-group'
    ],
    [
        'jabatan' => 'Sekretaris',
        'nama'    => 'Belum ditentukan',
        'icon'    => 'fa-pen'
    ],
    [
        'jabatan' => 'Bendahara',
        'nama'    => 'Belum ditentukan',
        'icon'    => 'fa-wallet'
    ]
];

/*
|--------------------------------------------------------------------------
| BUAT FILE JSON PERTAMA KALI
|--------------------------------------------------------------------------
*/

if (!file_exists($data_file)) {

    file_put_contents(
        $data_file,
        json_encode(
            $struktur_default,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );
}

/*
|--------------------------------------------------------------------------
| BACA DATA
|--------------------------------------------------------------------------
*/

$json = @file_get_contents($data_file);

$struktur = json_decode($json, true);

if (!is_array($struktur) || empty($struktur)) {
    $struktur = $struktur_default;
}


/*
|--------------------------------------------------------------------------
| PROSES SIMPAN
|--------------------------------------------------------------------------
*/

$pesan = '';
$tipe_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data_baru = [];

    foreach ($struktur_default as $index => $default) {

        $nama = trim(
            $_POST['nama'][$index] ?? ''
        );

        /*
         * Kalau nama dikosongkan,
         * otomatis menjadi "Belum ditentukan".
         */
        if ($nama === '') {
            $nama = 'Belum ditentukan';
        }

        $data_baru[] = [
            'jabatan' => $default['jabatan'],
            'nama'    => $nama,
            'icon'    => $default['icon']
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN JSON
    |--------------------------------------------------------------------------
    */

    $hasil = file_put_contents(
        $data_file,
        json_encode(
            $data_baru,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ),
        LOCK_EX
    );


    if ($hasil !== false) {

        $struktur = $data_baru;

        $pesan = 'Struktur kelas berhasil diperbarui.';
        $tipe_pesan = 'success';

    } else {

        $pesan =
            'Gagal menyimpan data. Pastikan folder data dapat ditulis.';

        $tipe_pesan = 'danger';
    }
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

<title>Struktur Kelas - XI TJKT 2</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =========================
   DASAR
========================= */

* {
    box-sizing: border-box;
}

body {
    background: #0b1020;
    color: #fff;
    font-family: Arial, sans-serif;
    margin: 0;
}

.container-main {
    max-width: 1000px;
    margin: auto;
    padding: 25px 15px 50px;
}


/* =========================
   TOPBAR
========================= */

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.title {
    font-size: 26px;
    font-weight: 800;
}

.subtitle {
    color: #94a3b8;
    margin-top: 5px;
}


/* =========================
   HEADER KELAS
========================= */

.class-header {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 20px;
    text-align: center;
}

.class-header h2 {
    font-weight: 800;
}


/* =========================
   ALERT
========================= */

.alert-custom {
    border-radius: 14px;
    border: 0;
    margin-bottom: 20px;
}


/* =========================
   CARD
========================= */

.structure-card {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 20px;
    padding: 25px;
    text-align: center;
    height: 100%;

    transition: .2s;
}

.structure-card:hover {
    border-color: #334155;
    transform: translateY(-2px);
}


/* =========================
   ICON
========================= */

.icon-box {
    width: 60px;
    height: 60px;

    margin: 0 auto 15px;

    border-radius: 18px;

    background: #1e293b;

    display: flex;
    justify-content: center;
    align-items: center;

    font-size: 24px;
}


/* =========================
   TEXT
========================= */

.jabatan {
    color: #94a3b8;
    font-size: 13px;
    margin-bottom: 7px;
}

.nama {
    font-weight: 700;
    font-size: 16px;
}


/* =========================
   EDIT PANEL
========================= */

.edit-panel {
    background: #111827;
    border: 1px solid #1f2937;
    border-radius: 20px;

    padding: 25px;

    margin-top: 25px;
}

.edit-title {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 5px;
}

.edit-subtitle {
    color: #94a3b8;
    font-size: 12px;
    margin-bottom: 22px;
}


/* =========================
   FORM
========================= */

.form-label {
    color: #cbd5e1;
    font-size: 13px;
    font-weight: 700;
}

.form-control {
    background: #0b1020;
    border: 1px solid #334155;
    color: #fff;
    border-radius: 11px;
    padding: 11px 13px;
}

.form-control:focus {
    background: #0b1020;
    color: #fff;
    border-color: #64748b;
    box-shadow: none;
}

.form-control::placeholder {
    color: #64748b;
}


/* =========================
   BUTTON
========================= */

.btn-save {
    background: #fff;
    color: #0b1020;
    border: 0;

    border-radius: 11px;

    padding: 11px 18px;

    font-weight: 800;
}

.btn-save:hover {
    background: #e2e8f0;
    color: #0b1020;
}


/* =========================
   BADGE
========================= */

.edit-badge {
    display: inline-block;

    background: #1e293b;
    color: #94a3b8;

    font-size: 10px;

    padding: 5px 8px;

    border-radius: 7px;

    margin-bottom: 15px;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 600px) {

    .container-main {
        padding: 20px 12px 40px;
    }

    .title {
        font-size: 22px;
    }

    .class-header {
        padding: 20px;
    }

    .class-header h2 {
        font-size: 22px;
    }

    .structure-card {
        padding: 22px 15px;
    }

    .edit-panel {
        padding: 20px 15px;
    }

}

</style>

</head>


<body>


<div class="container-main">


<!-- =========================
     TOPBAR
========================= -->

<div class="topbar">

    <div>

        <div class="title">

            <i class="fa-solid fa-sitemap"></i>

            Struktur Kelas

        </div>


        <div class="subtitle">

            XI TJKT 2 • Tahun Pelajaran 2026/2027

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


<!-- =========================
     HEADER KELAS
========================= -->

<div class="class-header">

    <h2 class="mb-2">
        XI TJKT 2
    </h2>

    <p class="text-secondary mb-0">

        Teknik Jaringan Komputer dan Telekomunikasi

    </p>

</div>


<!-- =========================
     PESAN
========================= -->

<?php if ($pesan !== ''): ?>

    <div
        class="alert alert-<?= htmlspecialchars($tipe_pesan) ?> alert-custom"
    >

        <?php if ($tipe_pesan === 'success'): ?>

            <i class="fa-solid fa-circle-check"></i>

        <?php else: ?>

            <i class="fa-solid fa-circle-exclamation"></i>

        <?php endif; ?>


        <?= htmlspecialchars($pesan) ?>

    </div>

<?php endif; ?>


<!-- =========================
     PREVIEW STRUKTUR
========================= -->

<div class="row g-3">

<?php foreach ($struktur as $item): ?>

    <div class="col-md-6">

        <div class="structure-card">

            <div class="icon-box">

                <i class="fa-solid <?= htmlspecialchars($item['icon']) ?>"></i>

            </div>


            <div class="jabatan">

                <?= htmlspecialchars($item['jabatan']) ?>

            </div>


            <div class="nama">

                <?= htmlspecialchars($item['nama']) ?>

            </div>

        </div>

    </div>

<?php endforeach; ?>

</div>


<!-- =========================
     EDIT STRUKTUR
========================= -->

<div class="edit-panel">

    <div class="edit-badge">

        <i class="fa-solid fa-lock"></i>

        KHUSUS WALI KELAS

    </div>


    <div class="edit-title">

        Edit Struktur Kelas

    </div>


    <div class="edit-subtitle">

        Ubah nama pengurus kelas kemudian tekan tombol
        <b>Simpan Perubahan</b>.

    </div>


    <form
        method="POST"
        autocomplete="off"
    >

        <?php foreach ($struktur as $index => $item): ?>

            <div class="mb-3">

                <label
                    class="form-label"
                    for="nama<?= $index ?>"
                >

                    <?= htmlspecialchars(
                        $item['jabatan']
                    ) ?>

                </label>


                <input
                    type="text"
                    class="form-control"
                    id="nama<?= $index ?>"
                    name="nama[<?= $index ?>]"
                    value="<?= htmlspecialchars(
                        $item['nama']
                    ) ?>"
                    placeholder="Masukkan nama"
                >

            </div>

        <?php endforeach; ?>


        <button
            type="submit"
            class="btn btn-save"
        >

            <i class="fa-solid fa-floppy-disk"></i>

            Simpan Perubahan

        </button>

    </form>

</div>


</div>


</body>

</html>