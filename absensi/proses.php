<?php

require_once "../config/auth.php";
require_once "../config/database.php";
require_once "../config/qr.php";

header("Content-Type: application/json; charset=UTF-8");

wajib_login(['siswa']);

date_default_timezone_set('Asia/Jakarta');

/*
|--------------------------------------------------------------------------
| Fungsi response JSON
|--------------------------------------------------------------------------
*/
function response_json($success, $message, $extra = [])
{
    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $extra
        ),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Pastikan koneksi database tersedia
|--------------------------------------------------------------------------
*/
if (!isset($conn) || !$conn) {
    response_json(
        false,
        "Koneksi database gagal."
    );
}

/*
|--------------------------------------------------------------------------
| Ambil token QR
|--------------------------------------------------------------------------
*/
$token = $_GET['token'] ?? '';

$token = trim($token);

if ($token === '') {
    response_json(
        false,
        "Token QR tidak ditemukan."
    );
}

/*
|--------------------------------------------------------------------------
| Validasi QR
|--------------------------------------------------------------------------
*/
if (!validasi_token_qr($token)) {
    response_json(
        false,
        "QR absensi sudah tidak berlaku. Silakan scan QR terbaru dari guru."
    );
}

/*
|--------------------------------------------------------------------------
| Ambil identitas siswa dari session
|--------------------------------------------------------------------------
|
| Saat siswa login:
| $_SESSION['username'] = NIS siswa
|
*/
$nis = trim($_SESSION['username'] ?? '');

if ($nis === '') {
    response_json(
        false,
        "Session siswa tidak ditemukan. Silakan login ulang."
    );
}

/*
|--------------------------------------------------------------------------
| Cari siswa berdasarkan NIS
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, nis, nama
     FROM siswa
     WHERE nis = ?
     LIMIT 1"
);

if (!$stmt) {
    response_json(
        false,
        "Gagal menyiapkan pencarian siswa."
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $nis
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    response_json(
        false,
        "Gagal mencari data siswa."
    );
}

$result = mysqli_stmt_get_result($stmt);

$siswa = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$siswa) {
    response_json(
        false,
        "Data siswa dengan NIS tersebut tidak ditemukan."
    );
}

$siswa_id = (int) $siswa['id'];
$nama_siswa = $siswa['nama'];

/*
|--------------------------------------------------------------------------
| Tanggal dan waktu absensi
|--------------------------------------------------------------------------
*/
$tanggal = date("Y-m-d");
$waktu   = date("H:i:s");

/*
|--------------------------------------------------------------------------
| Cek apakah siswa sudah absen hari ini
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, waktu, status, metode
     FROM absensi
     WHERE siswa_id = ?
       AND tanggal = ?
     LIMIT 1"
);

if (!$stmt) {
    response_json(
        false,
        "Gagal memeriksa absensi hari ini."
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "is",
    $siswa_id,
    $tanggal
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    response_json(
        false,
        "Gagal memeriksa data absensi."
    );
}

$result = mysqli_stmt_get_result($stmt);

$absensi_lama = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Jika sudah absen
|--------------------------------------------------------------------------
*/
if ($absensi_lama) {

    response_json(
        true,
        "Kamu sudah melakukan absensi hari ini.",
        [
            "nama" => $nama_siswa,
            "status" => $absensi_lama['status'],
            "waktu" => $absensi_lama['waktu'],
            "already_attended" => true
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Simpan absensi
|--------------------------------------------------------------------------
*/
$status = "Hadir";
$metode = "QR_GURU";

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO absensi
        (siswa_id, tanggal, waktu, status, metode)
     VALUES
        (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    response_json(
        false,
        "Gagal menyiapkan penyimpanan absensi."
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "issss",
    $siswa_id,
    $tanggal,
    $waktu,
    $status,
    $metode
);

$berhasil = mysqli_stmt_execute($stmt);

$error_number = mysqli_errno($conn);
$error_message = mysqli_error($conn);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Jika INSERT gagal
|--------------------------------------------------------------------------
*/
if (!$berhasil) {

    /*
     * 1062 = duplicate entry.
     * Ini bisa terjadi kalau dua request scan masuk hampir bersamaan.
     */
    if ($error_number === 1062) {

        response_json(
            true,
            "Absensi kamu sudah tercatat.",
            [
                "nama" => $nama_siswa,
                "status" => "Hadir",
                "already_attended" => true
            ]
        );
    }

    response_json(
        false,
        "Absensi gagal disimpan ke database.",
        [
            "error_code" => $error_number
        ]
    );
}

/*
|--------------------------------------------------------------------------
| Pastikan data benar-benar tersimpan
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, tanggal, waktu, status, metode
     FROM absensi
     WHERE siswa_id = ?
       AND tanggal = ?
     LIMIT 1"
);

if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "is",
        $siswa_id,
        $tanggal
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $data_absensi = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

} else {

    $data_absensi = null;
}

/*
|--------------------------------------------------------------------------
| Berhasil
|--------------------------------------------------------------------------
*/
response_json(
    true,
    "Absensi berhasil dicatat.",
    [
        "nama" => $nama_siswa,
        "status" => "Hadir",
        "tanggal" => $tanggal,
        "waktu" => $data_absensi['waktu'] ?? $waktu,
        "metode" => "QR_GURU",
        "already_attended" => false
    ]
);