<?php
require_once "../config/auth.php";
wajib_login(['wali_kelas']);

$data_dir  = __DIR__ . "/../data";
$data_file = $data_dir . "/pengumuman.json";

if (!is_dir($data_dir)) {
    @mkdir($data_dir, 0755, true);
}

if (!file_exists($data_file)) {
    file_put_contents($data_file, "[]", LOCK_EX);
}

$data = json_decode(file_get_contents($data_file), true);

if (!is_array($data)) {
    $data = [];
}

/* =========================
   TAMBAH PENGUMUMAN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'tambah') {

    $judul = trim($_POST['judul'] ?? '');
    $isi   = trim($_POST['isi'] ?? '');

    if ($judul !== '' && $isi !== '') {

        $data[] = [
            'id' => uniqid(),
            'judul' => $judul,
            'isi' => $isi,
            'tanggal' => date('Y-m-d H:i:s')
        ];

        file_put_contents(
            $data_file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    header("Location: pengumuman.php");
    exit;
}

/* =========================
   HAPUS
========================= */
if (isset($_GET['hapus'])) {

    $id = $_GET['hapus'];

    $data = array_values(array_filter(
        $data,
        function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }
    ));

    file_put_contents(
        $data_file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    header("Location: pengumuman.php");
    exit;
}

/* Terbaru di atas */
$data = array_reverse($data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pengumuman - XI TJKT 2</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f7fb;
    font-family:Arial,sans-serif;
}

.topbar{
    background:#111827;
    color:white;
    padding:18px 25px;
}

.container-main{
    max-width:1100px;
    margin:30px auto;
    padding:0 15px;
}

.card-box{
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,.06);
    margin-bottom:25px;
}

.announcement{
    border:1px solid #e5e7eb;
    border-radius:15px;
    padding:20px;
    margin-bottom:15px;
}

.announcement h5{
    margin-bottom:8px;
    font-weight:700;
}

.date{
    color:#6b7280;
    font-size:13px;
}

.isi{
    white-space:pre-line;
    margin-top:15px;
    color:#374151;
}

.btn-back{
    text-decoration:none;
    color:white;
}
</style>
</head>

<body>

<div class="topbar">
    <div class="container">
        <h4 class="mb-1">📢 Pengumuman</h4>
        <small>Kelola pengumuman kelas XI TJKT 2</small>
    </div>
</div>

<div class="container-main">

    <a href="index.php" class="btn btn-secondary mb-3">
        ← Kembali ke Dashboard
    </a>

    <!-- FORM TAMBAH -->
    <div class="card-box">

        <h5 class="mb-3">➕ Tambah Pengumuman</h5>

        <form method="POST">

            <input type="hidden" name="aksi" value="tambah">

            <div class="mb-3">
                <label class="form-label">Judul Pengumuman</label>
                <input
                    type="text"
                    name="judul"
                    class="form-control"
                    placeholder="Contoh: Pengumpulan Tugas"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Isi Pengumuman</label>
                <textarea
                    name="isi"
                    class="form-control"
                    rows="5"
                    placeholder="Tulis isi pengumuman..."
                    required
                ></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                📢 Publikasikan
            </button>

        </form>

    </div>

    <!-- DAFTAR -->
    <div class="card-box">

        <h5 class="mb-4">📋 Daftar Pengumuman</h5>

        <?php if (empty($data)): ?>

            <div class="text-center text-muted py-5">
                Belum ada pengumuman.
            </div>

        <?php else: ?>

            <?php foreach ($data as $item): ?>

                <div class="announcement">

                    <div class="d-flex justify-content-between gap-3">

                        <div>
                            <h5>
                                <?= htmlspecialchars($item['judul'] ?? '') ?>
                            </h5>

                            <div class="date">
                                📅
                                <?= htmlspecialchars($item['tanggal'] ?? '') ?>
                            </div>
                        </div>

                        <div>
                            <a
                                href="?hapus=<?= urlencode($item['id']) ?>"
                                class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Hapus pengumuman ini?')"
                            >
                                🗑️
                            </a>
                        </div>

                    </div>

                    <div class="isi">
                        <?= htmlspecialchars($item['isi'] ?? '') ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>