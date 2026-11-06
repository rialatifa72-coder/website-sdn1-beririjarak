<?php
// Konfigurasi koneksi database SDN 1 Beririjarak.
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak";

$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal. Periksa konfigurasi database di koneksi.php.");
}

mysqli_set_charset($koneksi, "utf8mb4");
