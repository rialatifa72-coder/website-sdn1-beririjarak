<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak";

// Nonaktifkan exception mysqli agar kegagalan koneksi dapat ditangani oleh halaman.
mysqli_report(MYSQLI_REPORT_OFF);

// Membatasi waktu tunggu koneksi agar halaman login tidak terus loading.
$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

// Membuat koneksi ke database.
$koneksi = @mysqli_real_connect($mysqli, $host, $user, $password, $database);

if (!$koneksi) {
    // Simpan pesan kesalahan; jangan hentikan halaman login.
    $koneksi_error = mysqli_connect_error() ?: mysqli_error($mysqli);
    $koneksi = false;
} else {
    $koneksi = $mysqli;
    $koneksi_error = "";
}

// Mengatur karakter database.
if ($koneksi) {
    mysqli_set_charset($koneksi, "utf8mb4");
}

// Jangan tambahkan tag penutup PHP agar tidak ada output tak sengaja
// yang dapat mengganggu header redirect dan session.
