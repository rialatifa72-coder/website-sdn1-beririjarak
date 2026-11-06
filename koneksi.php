<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak";

// Membuat koneksi ke database.
$koneksi = mysqli_connect($host, $user, $password, $database);

if (!$koneksi) {
    // Jangan tampilkan detail kredensial database kepada pengunjung.
    error_log("Koneksi database gagal: " . mysqli_connect_error());
    http_response_code(500);
    exit("Koneksi database gagal. Periksa konfigurasi database dan pastikan MySQL aktif.");
}

// Mengatur karakter database.
mysqli_set_charset($koneksi, "utf8mb4");

// Jangan tambahkan tag penutup PHP agar tidak ada output tak sengaja
// yang dapat mengganggu header redirect dan session.
