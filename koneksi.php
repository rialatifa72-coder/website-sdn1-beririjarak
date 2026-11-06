<?php
// Konfigurasi koneksi database SDN 1 Beririjarak.
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak_baru";

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$connected = @mysqli_real_connect($mysqli, $host, $user, $password, $database);

if ($connected) {
    $koneksi = $mysqli;
    mysqli_set_charset($koneksi, "utf8mb4");
    $koneksi_error = "";
} else {
    $koneksi = false;
    $koneksi_error = mysqli_connect_error() ?: "Koneksi database gagal.";
}
