<?php
$koneksi = mysqli_connect("localhost", "root", "", "db_sekolah"); // sesuaikan nama database
if ($koneksi) {
    echo "Koneksi Berhasil!";
} else {
    echo "Koneksi Gagal: " . mysqli_connect_error();
}
?>