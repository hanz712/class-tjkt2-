<?php

$host = "sql204.byethost33.com";
$user = "b33_42840865";
$password = "hanzganz01";
$database = "b33_42840865_tjkt2";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");