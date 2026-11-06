
<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_sdn1beririjarak";

// Membuat koneksi ke database
$koneksi = mysqli_connect(
    $host,
    $user,
    $password,
    $database
);

// Memeriksa koneksi
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Mengatur karakter database
mysqli_set_charset($koneksi, "utf8mb4");
?>
```
