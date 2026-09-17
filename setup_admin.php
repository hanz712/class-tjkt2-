<?php

require_once __DIR__ . "/config/database.php";

$nama = "Deden Nur Rahayu Mamad, S.Pd.";
$username = "wali";
$password = "wali123";

$role = "wali_kelas";

$cek = mysqli_prepare(
    $conn,
    "SELECT id
     FROM users
     WHERE username = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $cek,
    "s",
    $username
);

mysqli_stmt_execute($cek);

$result = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($result) > 0) {

    die(
        "Akun wali kelas sudah ada. " .
        "Hapus setup_admin.php setelah selesai."
    );

}

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users
    (nama, username, password, role)
    VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $nama,
    $username,
    $password_hash,
    $role
);

if (mysqli_stmt_execute($stmt)) {

    echo "<h2>Akun wali kelas berhasil dibuat.</h2>";

    echo "<p>Username: <strong>" .
        htmlspecialchars($username) .
        "</strong></p>";

    echo "<p>Password: <strong>" .
        htmlspecialchars($password) .
        "</strong></p>";

    echo "<p>Setelah berhasil, <strong>hapus file setup_admin.php</strong>.</p>";

} else {

    echo "Gagal membuat akun: " .
        htmlspecialchars(mysqli_error($conn));

} 