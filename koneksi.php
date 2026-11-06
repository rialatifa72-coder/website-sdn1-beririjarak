<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak";

// Nonaktifkan exception mysqli agar kegagalan koneksi dapat ditangani oleh halaman.
mysqli_report(MYSQLI_REPORT_OFF);

// Membuat koneksi ke database.
$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    // Simpan pesan kesalahan; jangan hentikan halaman login.
    $koneksi_error = mysqli_connect_error();
} else {
    $koneksi_error = "";
}

// Mengatur karakter database.
if ($koneksi) {
    mysqli_set_charset($koneksi, "utf8mb4");
}

// Jangan tambahkan tag penutup PHP agar tidak ada output tak sengaja
// yang dapat mengganggu header redirect dan session.
