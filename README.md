# Website SDN 1 Beririjarak

Website sekolah berbasis PHP dan MySQL untuk dijalankan melalui XAMPP.

## Menjalankan di XAMPP
1. Letakkan folder proyek di `C:\xampp\htdocs\web sekolah`.
2. Jalankan Apache dan MySQL dari XAMPP.
3. Buka phpMyAdmin, lalu impor file `database.sql`.
4. Buka `http://localhost/web%20sekolah/`.

## Login administrator
- Username: `admin`
- Password: `admin123`

Jika ingin mengganti password, simpan password baru dalam bentuk hash menggunakan PHP `password_hash()`.

## Catatan
Koneksi database lokal memakai host `localhost`, user `root`, dan password kosong sesuai konfigurasi XAMPP standar. Ubah `koneksi.php` jika pengaturan MySQL berbeda.
