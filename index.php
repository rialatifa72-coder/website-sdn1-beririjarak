<?php
require_once __DIR__ . "/koneksi.php";

$daftarGuru = false;
if (isset($koneksi) && $koneksi) {
    // Pastikan kolom gelar tersedia meskipun halaman admin belum pernah dibuka.
    $cekKolomGelarGuru = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_guru LIKE 'gelar'");
    if ($cekKolomGelarGuru && mysqli_num_rows($cekKolomGelarGuru) === 0) {
        mysqli_query($koneksi, "ALTER TABLE tb_guru ADD COLUMN gelar VARCHAR(100) NULL AFTER nama_guru");
    }
    $queryGuru = mysqli_query(
        $koneksi,
        "SELECT nama_guru, gelar, jabatan, foto
         FROM tb_guru
         ORDER BY id ASC"
    );
    if ($queryGuru) {
        $daftarGuru = $queryGuru;
    }
}

$daftarFasilitas = [];
if (isset($koneksi) && $koneksi) {
    $cekTabelFasilitas = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_fasilitas'");
    if ($cekTabelFasilitas && mysqli_num_rows($cekTabelFasilitas) > 0) {
        $queryFasilitas = mysqli_query($koneksi, "SELECT nama_fasilitas, deskripsi, foto FROM tb_fasilitas ORDER BY nama_fasilitas ASC, id ASC");
        if ($queryFasilitas) {
            while ($barisFasilitas = mysqli_fetch_assoc($queryFasilitas)) {
                $kunciFasilitas = function_exists("mb_strtolower") ? mb_strtolower(trim($barisFasilitas["nama_fasilitas"]), "UTF-8") : strtolower(trim($barisFasilitas["nama_fasilitas"]));
                if (!isset($daftarFasilitas[$kunciFasilitas])) {
                    $daftarFasilitas[$kunciFasilitas] = ["nama_fasilitas" => $barisFasilitas["nama_fasilitas"], "deskripsi" => $barisFasilitas["deskripsi"], "foto" => []];
                }
                $daftarFasilitas[$kunciFasilitas]["foto"][] = $barisFasilitas["foto"];
                if (empty($daftarFasilitas[$kunciFasilitas]["deskripsi"]) && !empty($barisFasilitas["deskripsi"])) $daftarFasilitas[$kunciFasilitas]["deskripsi"] = $barisFasilitas["deskripsi"];
            }
        }
    }
}

$daftarGaleri = false;
if (isset($koneksi) && $koneksi) {
    $queryGaleri = mysqli_query(
        $koneksi,
        "SELECT judul, deskripsi, nama_file, tanggal_kegiatan
         FROM tb_galeri
         ORDER BY tanggal_kegiatan DESC, id DESC
         LIMIT 12"
    );
    if ($queryGaleri) {
        $daftarGaleri = $queryGaleri;
    }
}

$daftarEkstrakurikuler = [];
if (isset($koneksi) && $koneksi) {
    // Kelompokkan beberapa foto berdasarkan nama kegiatan agar satu ekskul menjadi satu kartu.
    $queryEkstrakurikuler = mysqli_query(
        $koneksi,
        "SELECT nama_kegiatan, deskripsi, foto
         FROM tb_ekstrakurikuler
         ORDER BY nama_kegiatan ASC, id ASC"
    );
    if ($queryEkstrakurikuler) {
        while ($barisEkskul = mysqli_fetch_assoc($queryEkstrakurikuler)) {
            $kunciEkskul = function_exists("mb_strtolower")
                ? mb_strtolower(trim($barisEkskul["nama_kegiatan"]), "UTF-8")
                : strtolower(trim($barisEkskul["nama_kegiatan"]));
            if (!isset($daftarEkstrakurikuler[$kunciEkskul])) {
                $daftarEkstrakurikuler[$kunciEkskul] = [
                    "nama_kegiatan" => $barisEkskul["nama_kegiatan"],
                    "deskripsi" => $barisEkskul["deskripsi"],
                    "foto" => []
                ];
            }
            $daftarEkstrakurikuler[$kunciEkskul]["foto"][] = $barisEkskul["foto"];
            if (empty($daftarEkstrakurikuler[$kunciEkskul]["deskripsi"]) && !empty($barisEkskul["deskripsi"])) {
                $daftarEkstrakurikuler[$kunciEkskul]["deskripsi"] = $barisEkskul["deskripsi"];
            }
        }
    }
}

// Kartu Pencak Silat tetap tampil meskipun dokumentasi belum diunggah.
$kunciPencakSilat = function_exists("mb_strtolower")
    ? mb_strtolower("Pencak Silat", "UTF-8")
    : strtolower("Pencak Silat");
if (!isset($daftarEkstrakurikuler[$kunciPencakSilat])) {
    $daftarEkstrakurikuler[$kunciPencakSilat] = [
        "nama_kegiatan" => "Pencak Silat",
        "deskripsi" => "Melatih kedisiplinan, keberanian, ketangkasan, serta membentuk karakter dan sikap sportif peserta didik.",
        "foto" => []
    ];
}

function aman($nilai) {
    return htmlspecialchars((string)($nilai ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SDN 1 Beririjarak | Website Resmi Sekolah</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
:root {
    --biru: #232cac;
    --gelap: #1c2c7c;
    --muda: #e8f5e9;
    --teks: #26364a;
}

* {
    scroll-behavior: smooth;
    scroll-margin-top: 85px;
}

body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    color: var(--teks);
    margin: 0;
}

/* NAVBAR DIBERI Z-INDEX TINGGI AGAR TIDAK TERTUTUP ELEMEN LAIN */
.navbar {
    background: var(--gelap);
    color: white;
    padding: 14px 0;
    position: sticky;
    top: 0;
    z-index: 1050; 
}

.footer {
    background: var(--gelap);
    color: white;
}

.navbar-brand {
    color: white !important;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
}

.logo-sekolah {
    width: 58px;
    height: 58px;
    object-fit: contain;
    background: white;
    border: 2px solid rgba(255,255,255,.85);
    border-radius: 50%;
    padding: 3px;
    flex-shrink: 0;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.25;
}

.brand-text small {
    display: block;
    font-size: 11px;
    font-weight: 400;
}

.footer-logo {
    width: 42px;
    height: 42px;
    object-fit: contain;
    background: white;
    border-radius: 50%;
    padding: 3px;
    vertical-align: middle;
}

.navbar .nav-link {
    color: white !important;
    font-size: 14px;
}

.navbar .nav-link:hover {
    color: #292baf !important;
}

.dropdown-menu {
    border: 0;
    box-shadow: 0 8px 25px rgba(0,0,0,.12);
}

.hero {
    min-height: 600px;
    display: flex;
    align-items: center;
    color: #fff;
    position: relative;
    isolation: isolate;
    overflow: hidden;
    background:
        linear-gradient(90deg, rgba(24, 29, 30, .48) 0%, rgba(28, 32, 32, .20) 48%, rgba(28, 32, 32, .05) 100%),
        linear-gradient(0deg, rgba(20, 24, 22, .10), rgba(20, 24, 22, .02)),
        url("guru.jpeg")
        center 42%/cover no-repeat;
}
.hero::before {
    content: "";
    position: absolute;
    z-index: -1;
    width: 440px;
    height: 440px;
    right: -130px;
    bottom: -210px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 50%;
    box-shadow: 0 0 0 45px rgba(255,255,255,.045), 0 0 0 90px rgba(255,255,255,.035);
    pointer-events: none;
}

.hero-content {
    padding: 100px 0;
}

.hero-label {
    display: inline-block;
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 30px;
    padding: 8px 16px;
    margin-bottom: 20px;
}

.hero h1 {
    font-size: clamp(26px, 3.2vw, 40px);
    font-weight: 800;
    line-height: 1.2;
    max-width: 620px;
}

/* ANIMASI JUDUL MUNCUL KATA DEMI KATA */
.hero-title-animate {
    color: #ffffff;
    -webkit-text-fill-color: #ffffff;
}
.hero-title-animate .hero-word {
    display: inline-block;
    color: #ffffff;
    -webkit-text-fill-color: #ffffff;
    opacity: 0;
    margin-right: .22em;
    animation: heroWordReveal .8s ease-out forwards;
    animation-delay: var(--word-delay);
}
@keyframes heroWordReveal {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (prefers-reduced-motion: reduce) {
    .hero-title-animate .hero-word {
        opacity: 1;
        animation: none;
        transform: none;
    }
}

.hero p {
    max-width: 650px;
    line-height: 1.8;
    color: #e5edfa;
}

.btn-utama {
    background: var(--biru);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    border: 0;
}

.btn-utama:hover {
    background: #1b4297;
    color: white;
}

.btn-outline-light-custom {
    border: 1px solid white;
    color: white;
    padding: 11px 24px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}

.btn-outline-light-custom:hover {
    background: white;
    color: var(--gelap);
}

.stats {
    margin-top: -38px;
    position: relative;
    z-index: 2;
}

.stat-card {
    height: 100%;
    text-align: center;
    padding: 25px 15px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 28px rgba(20,83,45,.10);
}

.stat-card i {
    color: var(--biru);
    font-size: 30px;
}

.stat-card h3 {
    font-size: 27px;
    font-weight: 800;
    color: var(--gelap);
    margin-top: 10px;
}

.stat-card p {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 0;
}

/* Kombinasi warna lembut untuk setiap kartu statistik */
.stats .row > div:nth-child(1) .stat-card { background: linear-gradient(135deg, #e3f2fd, #bbdefb); }
.stats .row > div:nth-child(2) .stat-card { background: linear-gradient(135deg, #e0f2f1, #b2dfdb); }
.stats .row > div:nth-child(3) .stat-card { background: linear-gradient(135deg, #ede7f6, #d1c4e9); }
.stats .row > div:nth-child(4) .stat-card { background: linear-gradient(135deg, #e1f5fe, #b3e5fc); }
.stats .row > div:nth-child(5) .stat-card { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); }
.stats .row > div:nth-child(6) .stat-card { background: linear-gradient(135deg, #fff3e0, #ffe0b2); }
.stats .row > div:nth-child(7) .stat-card { background: linear-gradient(135deg, #fce4ec, #f8bbd0); }
.stats .stat-card i { color: #174a7e; }
.stats .stat-card h3 { color: #173b63; }
.stats .stat-card p { color: #40566b; }

/* KELAS ANIMASI KLIK STATISTIK */
.interactive-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
}
.interactive-card:active, .interactive-card.clicked {
    transform: scale(0.92) translateY(5px);
    background-color: #f0f7f4;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.2);
}

/* TAMBAHAN TRANSISI SLOW-MOTION MUNCUL SAAT DI-SCROLL */
.stat-card-animate {
    opacity: 0;
    transform: translateY(50px);
    transition: opacity 1.5s cubic-bezier(0.16, 1, 0.3, 1), 
                transform 1.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.stat-card-animate.muncul {
    opacity: 1;
    transform: translateY(0);
}

.stat-card-animate:nth-child(1) { transition-delay: 0.15s; }
.stat-card-animate:nth-child(2) { transition-delay: 0.3s; }
.stat-card-animate:nth-child(3) { transition-delay: 0.45s; }
.stat-card-animate:nth-child(4) { transition-delay: 0.6s; }
.stat-card-animate:nth-child(5) { transition-delay: 0.75s; }
.stat-card-animate:nth-child(6) { transition-delay: 0.9s; }

.section {
    padding: 85px 0;
}

.section-light {
    background: #f5f8fd;
}

.section-title {
    color: var(--gelap);
    font-weight: 800;
    margin-bottom: 15px;
}

.section-subtitle {
    max-width: 700px;
    margin: 0 auto 40px;
    color: #6c757d;
    line-height: 1.8;
}

.section-label {
    text-transform: uppercase;
    color: var(--biru);
    font-weight: 700;
    letter-spacing: 2px;
    font-size: 12px;
}

.profile-visual {
    position: relative;
    padding: 0 18px 22px 0;
}
.profile-visual::before {
    content: "";
    position: absolute;
    left: -12px;
    top: -12px;
    width: 46%;
    height: 42%;
    border-top: 5px solid #0d6efd;
    border-left: 5px solid #0d6efd;
    border-radius: 18px 0 0 0;
    opacity: .65;
}
.profile-image {
    position: relative;
    z-index: 1;
    display: block;
    width: 100%;
    height: 410px;
    object-fit: cover;
    border-radius: 22px;
    box-shadow: 0 18px 42px rgba(8,43,89,.16);
}
.profile-photo-caption {
    position: absolute;
    z-index: 2;
    left: 34px;
    bottom: 42px;
    max-width: calc(100% - 68px);
    padding: 14px 18px;
    border-radius: 14px;
    color: #fff;
    background: rgba(8,43,89,.88);
    backdrop-filter: blur(8px);
    box-shadow: 0 8px 24px rgba(0,0,0,.16);
}
.profile-photo-caption strong { display:block; font-size:15px; }
.profile-photo-caption span { font-size:12px; color:#dceaff; }
.profile-intro {
    color:#5e6d80;
    line-height:1.9;
    font-size:15px;
}
.profile-points {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
    margin:22px 0 26px;
}
.profile-point {
    display:flex;
    align-items:center;
    gap:10px;
    padding:12px;
    border:1px solid #e5edf8;
    border-radius:13px;
    background:#f8fbff;
    color:#193b65;
    font-size:13px;
    font-weight:700;
}
.profile-point i {
    display:flex;
    align-items:center;
    justify-content:center;
    width:34px;
    height:34px;
    min-width:34px;
    border-radius:10px;
    background:#e5f0ff;
    color:#0d6efd;
    font-size:17px;
}
@media (max-width:575px) {
    .profile-image { height:280px; }
    .profile-points { grid-template-columns:1fr; }
    .profile-photo-caption { left:24px; bottom:34px; }
}

.info-card, .learning-card {
    background: white;
    border: 1px solid #e7edf6;
    padding: 28px;
    border-radius: 14px;
    height: 100%;
    transition: .25s;
}

.info-card:hover, .learning-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(20,83,45,.10);
}

.info-card h5 {
    color: var(--gelap);
    font-weight: 700;
}

.info-card p, .learning-card p {
    color: #6c757d;
    line-height: 1.8;
}

.feature-icon {
    width: 55px;
    height: 55px;
    min-width: 55px;
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: var(--muda);
    color: var(--biru);
    font-size: 25px;
    margin-bottom: 18px;
}

.visi-card {
    background: linear-gradient(135deg, #14532d, #198754);
    color: white;
    padding: 45px 30px;
    border-radius: 18px;
}

.visi-card h3 {
    font-weight: 800;
}

.misi-number {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--muda);
    color: var(--biru);
    font-weight: 800;
}

/* DESAIN BARU VISI DAN MISI */
.vm-vision {
    position: relative;
    overflow: hidden;
    padding: 42px;
    border-radius: 26px;
    color: #fff;
    background: linear-gradient(120deg, #082b59 0%, #0d6efd 62%, #3b91f5 100%);
    box-shadow: 0 18px 45px rgba(8, 43, 89, .18);
}
.vm-vision::after {
    content: "CIKAL";
    position: absolute;
    right: 22px;
    bottom: -32px;
    font-size: clamp(70px, 12vw, 150px);
    line-height: 1;
    font-weight: 900;
    color: rgba(255,255,255,.08);
    pointer-events: none;
}
.vm-vision-content { position: relative; z-index: 1; max-width: 850px; }
.vm-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    border-radius: 30px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.3px;
    text-transform: uppercase;
}
.vm-vision h3 { font-size: 30px; font-weight: 800; margin: 18px 0 12px; }
.vm-vision p { max-width: 760px; font-size: 18px; line-height: 1.8; color: #edf5ff; margin: 0; }
.vm-values { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 24px; }
.vm-value {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 30px;
    color: #fff;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.24);
    font-size: 13px;
    font-weight: 600;
}
.vm-mission-heading { display:flex; align-items:center; justify-content:center; gap:12px; margin: 46px 0 24px; }
.vm-mission-heading .line { width: 42px; height: 2px; background: #0d6efd; opacity: .35; }
.vm-mission-heading h3 { margin:0; font-weight:800; color:#082b59; }
.vm-mission-card {
    display:flex;
    gap:18px;
    height:100%;
    padding:24px;
    border:1px solid #e5edf8;
    border-radius:20px;
    background:#fff;
    box-shadow:0 8px 25px rgba(8,43,89,.055);
    transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.vm-mission-card:hover { transform:translateY(-6px); border-color:#b9d5ff; box-shadow:0 16px 32px rgba(8,43,89,.11); }
.vm-mission-icon {
    width:52px; height:52px; min-width:52px;
    display:flex; align-items:center; justify-content:center;
    border-radius:16px;
    color:#0d6efd; background:#eaf3ff; font-size:23px;
}
.vm-mission-card h5 { color:#082b59; font-weight:800; font-size:17px; margin:2px 0 9px; }
.vm-mission-card p { color:#68788d; line-height:1.75; margin:0; font-size:14px; }

/* TAMPILAN VISI MISI + ANIMASI MUNCUL BERURUTAN */
.vm-vision {
    background:
        radial-gradient(circle at 88% 18%, rgba(255,255,255,.18), transparent 26%),
        linear-gradient(130deg, #071f46 0%, #0b56ad 58%, #1684dc 100%);
    border: 1px solid rgba(255,255,255,.2);
}
.vm-vision-content { max-width: 100%; }
.vm-vision h3 { font-size: clamp(27px, 4vw, 38px); letter-spacing: -.6px; }
.vm-vision p { max-width: 850px; }
.vm-values { gap: 12px; }
.vm-value {
    padding: 11px 16px;
    background: rgba(255,255,255,.13);
    backdrop-filter: blur(5px);
    transition: background .25s ease, transform .25s ease;
}
.vm-value:hover { background: rgba(255,255,255,.25); transform: translateY(-4px); }
.vm-value-button {
    border: 1px solid rgba(255,255,255,.24);
    font: inherit;
    cursor: pointer;
}
.vm-value-button:focus-visible {
    outline: 3px solid #fff;
    outline-offset: 4px;
}
.vm-value-button.active {
    color: #082b59;
    background: #fff;
    border-color: #fff;
    box-shadow: 0 7px 20px rgba(0,0,0,.16);
}
.vm-value-detail {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    max-width: 850px;
    margin-top: 22px;
    padding: 20px 22px;
    color: #17365e;
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(255,255,255,.7);
    border-radius: 18px;
    box-shadow: 0 12px 30px rgba(0,0,0,.13);
    animation: vmDetailIn .35s ease both;
}
.vm-value-detail[hidden] { display: none !important; }
.vm-value-detail-icon {
    display: grid;
    place-items: center;
    width: 46px;
    height: 46px;
    min-width: 46px;
    color: #0d6efd;
    background: #eaf3ff;
    border-radius: 14px;
    font-size: 21px;
}
.vm-value-detail h4 { margin: 1px 0 6px; font-size: 18px; font-weight: 800; color: #082b59; }
.vm-value-detail p { margin: 0; color: #53677f; font-size: 14px; line-height: 1.75; }
@keyframes vmDetailIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.vm-mission-heading { margin-top: 54px; }
.vm-mission-heading .line { width: clamp(35px, 8vw, 90px); background: linear-gradient(90deg, transparent, #0d6efd); }
.vm-mission-heading .line:last-child { transform: scaleX(-1); }
.vm-mission-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #e2ebf8;
    box-shadow: 0 12px 30px rgba(8,43,89,.07);
}
.vm-mission-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: linear-gradient(180deg, #0d6efd, #59b5ff);
}
.vm-mission-icon {
    border-radius: 17px;
    background: linear-gradient(145deg, #eaf3ff, #d5e8ff);
    box-shadow: inset 0 0 0 1px rgba(13,110,253,.06);
}
.vm-mission-card h5 { line-height: 1.45; }
.vm-reveal {
    opacity: 0;
    transform: translateY(32px) scale(.98);
    filter: blur(4px);
    transition: opacity .75s ease, transform .75s cubic-bezier(.2,.75,.25,1), filter .75s ease;
}
.vm-reveal.muncul {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: blur(0);
}
.vm-values .vm-reveal:nth-child(2), .row .vm-reveal:nth-child(2) { transition-delay: .12s; }
.vm-values .vm-reveal:nth-child(3), .row .vm-reveal:nth-child(3) { transition-delay: .24s; }
.vm-values .vm-reveal:nth-child(4), .row .vm-reveal:nth-child(4) { transition-delay: .36s; }
.vm-values .vm-reveal:nth-child(5), .row .vm-reveal:nth-child(5) { transition-delay: .48s; }
.vm-values .vm-reveal:nth-child(6) { transition-delay: .60s; }
@media (prefers-reduced-motion: reduce) {
    .vm-reveal { opacity: 1; transform: none; filter: none; transition: none; }
}
@media (max-width:575px) {
    .vm-vision { padding:30px 22px; border-radius:20px; }
    .vm-vision h3 { font-size:25px; }
    .vm-vision p { font-size:16px; }
    .vm-mission-card { padding:20px; }
}
 
/* SLIDER GURU */
.guru-slider {
    position: relative;
    padding: 0 48px;
}

.guru-track-wrapper {
    width: 100%;
    overflow: hidden;
}

.guru-track {
    display: flex;
    gap: 22px;
    transition: transform .45s ease;
    will-change: transform;
}

.guru-item {
    flex: 0 0 calc((100% - 44px) / 3);
    min-width: 0;
    padding: 20px;
    text-align: center;
    background: white;
    border: 1px solid #e7edf6;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(20,83,45,.10);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.guru-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(20,83,45,.15);
}

.guru-foto-placeholder {
    width: 100%;
    height: 290px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    border: 3px solid #ffc107;
    background: var(--muda);
    color: #1c2c7c;
    font-size: 5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}

.guru-item img {
    display: block;
    width: 100%;
    height: 290px;
    object-fit: cover;
    object-position: center top;
    border-radius: 14px;
    border: 3px solid #ffc107;
    background: var(--muda);
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
}

.guru-item h5 {
    color: #1c2c7c;
    font-size: 16px;
    font-weight: 700;
    margin: 16px 0 4px;
}

.guru-item .jabatan-guru {
    color: #f59e0b;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 8px;
}

.guru-item .quote-guru {
    color: #6c757d;
    font-size: 12px;
    font-style: italic;
    line-height: 1.5;
    margin-bottom: 0;
}

.guru-arrow {
    position: absolute;
    z-index: 5;
    top: 42%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 50%;
    background: var(--biru);
    color: white;
    font-size: 21px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.guru-arrow:hover {
    background: var(--gelap);
}

.guru-arrow:disabled {
    opacity: .35;
    cursor: not-allowed;
}

.guru-prev {
    left: 0;
}

.guru-next {
    right: 0;
}

/* DESAIN SAMPUL KARTU EKSTRAKURIKULER */
.ekskul-card {
    display:block; overflow:hidden; border-radius:20px; background:#fff;
    color:var(--teks); cursor:pointer; font:inherit;
    box-shadow:0 8px 25px rgba(16,42,85,.09);
    transition:transform .3s ease, box-shadow .3s ease;
}
.ekskul-card:hover, .ekskul-card:focus-visible {
    transform:translateY(-8px); box-shadow:0 18px 38px rgba(16,42,85,.18);
    outline:3px solid rgba(35,44,172,.18); outline-offset:3px;
}
.ekskul-cover { height:235px; position:relative; overflow:hidden; background:linear-gradient(135deg,#1c2c7c,#2674d9 58%,#5bc0eb); }
.ekskul-cover img { width:100%; height:100%; display:block; object-fit:cover; transition:transform .6s ease; }
.ekskul-card:hover .ekskul-cover img { transform:scale(1.09); }
.ekskul-cover-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,.9); }
.ekskul-cover-placeholder i { font-size:76px; filter:drop-shadow(0 5px 10px rgba(0,0,0,.18)); }
.ekskul-cover-shade { position:absolute; inset:0; background:linear-gradient(180deg,rgba(5,20,55,.08) 15%,rgba(5,20,55,.82) 100%); }
.ekskul-photo-count { position:absolute; top:15px; right:15px; padding:7px 12px; border-radius:30px; color:#fff; background:rgba(8,25,65,.65); border:1px solid rgba(255,255,255,.35); font-size:12px; font-weight:700; backdrop-filter:blur(8px); }
.ekskul-cover-title { position:absolute; left:22px; right:18px; bottom:19px; color:#fff; }
.ekskul-kicker { display:inline-block; font-size:10px; letter-spacing:2px; font-weight:800; color:#d7edff; margin-bottom:7px; }
.ekskul-cover-title h3 { font-size:25px; font-weight:800; margin:0; text-shadow:0 2px 8px rgba(0,0,0,.25); }
.ekskul-card-body { padding:20px 22px 22px; }
.ekskul-card-body p { color:#667085; font-size:14px; line-height:1.7; min-height:48px; margin:0 0 15px; }
.ekskul-open-link { display:flex; align-items:center; justify-content:space-between; color:#232cac; font-size:13px; font-weight:800; }
.ekskul-open-link i { font-size:19px; transition:transform .25s ease; }
.ekskul-card:hover .ekskul-open-link i { transform:translateX(5px); }

/* GALERI */
.gallery-image {
    width: 100%;
    height: 230px;
    object-fit: cover;
    border-radius: 12px;
    transition: .25s;
}

.gallery-image:hover {
    transform: scale(1.02);
}

/* BERITA */
.news-card img {
    height: 210px;
    width: 100%;
    object-fit: cover;
    border-radius: 12px 12px 0 0;
}

.news-card .card-body {
    padding: 23px;
}

/* MEDIA PEMBELAJARAN */
.learning-card > i {
    font-size: 35px;
    color: var(--biru);
}

/* LOKASI */
.location-box {
    background: var(--muda);
    padding: 35px;
    border-radius: 16px;
    height: 100%;
}

/* FOOTER */
.footer {
    padding: 50px 0 20px;
}

.footer a {
    color: #d1fae5;
    text-decoration: none;
}

.footer a:hover {
    color: white;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,.15);
    margin-top: 30px;
    padding-top: 20px;
    color: #d1fae5;
    font-size: 13px;
}

/* RESPONSIVE */
@media (max-width: 991px) {
    .guru-item {
        flex-basis: calc((100% - 22px) / 2);
    }
    .guru-item img {
        height: 250px;
    }
}

@media (max-width: 768px) {
    .hero {
        min-height: 500px;
    }
    .hero-content {
        padding: 75px 0;
    }
    .section {
        padding: 60px 0;
    }
    .stats {
        margin-top: 20px;
    }
    .profile-image {
        height: 280px;
    }
}

@media (max-width: 575px) {
    .guru-slider {
        padding: 0 35px;
    }
    .guru-track {
        gap: 14px;
    }
    .guru-item {
        flex-basis: 100%;
    }
    .guru-item img {
        height: 300px;
    }
    .guru-arrow {
        width: 35px;
        height: 35px;
        font-size: 17px;
    }
    .location-box {
        padding: 25px;
    }
}

/* WARNA BOOTSTRAP DISESUAIKAN MENJADI HIJAU */
.text-primary { color: #198754 !important; }
.btn-primary { background-color: #198754 !important; border-color: #198754 !important; }
.btn-primary:hover { background-color: #146c43 !important; border-color: #146c43 !important; }
/* Tombol Login navbar tetap biru, bukan hijau */
.navbar .btn-primary {
    background-color: #1769d2 !important;
    border-color: #1769d2 !important;
    color: #fff !important;
}
.navbar .btn-primary:hover,
.navbar .btn-primary:focus {
    background-color: #124fa3 !important;
    border-color: #124fa3 !important;
}
.btn-outline-primary { color: #198754 !important; border-color: #198754 !important; }
.btn-outline-primary:hover { background-color: #198754 !important; border-color: #198754 !important; color: #fff !important; }

/* ===== PROFIL SEKOLAH MODERN ===== */
.profil-modern{position:relative;padding:95px 0;background:linear-gradient(135deg,#f3f7ff 0%,#fff 68%);overflow:hidden}
.profil-modern:before{content:"";position:absolute;width:420px;height:420px;right:-170px;top:-210px;border-radius:50%;background:rgba(13,110,253,.055)}
.profil-modern>.container{position:relative;z-index:1}
.profil-visual{position:relative;padding:14px 18px 24px 0}
.profil-decoration{position:absolute;left:-12px;top:-10px;width:48%;height:45%;border-top:5px solid #438cff;border-left:5px solid #438cff;border-radius:22px 0 0;opacity:.8}
.profil-main-image{position:relative;z-index:1;display:block;width:100%;height:440px;object-fit:cover;border-radius:25px;box-shadow:0 24px 55px rgba(15,47,90,.18)}
.profil-photo-overlay{position:absolute;z-index:2;left:22px;right:42px;bottom:42px;display:flex;align-items:center;gap:14px;padding:18px;color:#fff;background:rgba(7,43,91,.93);border-radius:16px;box-shadow:0 12px 30px rgba(0,0,0,.16)}
.profil-overlay-icon{width:48px;height:48px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15);border-radius:13px;font-size:22px}
.profil-photo-overlay h4{margin:0 0 5px;font-size:18px;font-weight:800}
.profil-photo-overlay p{margin:0;color:#dceaff;font-size:12px}
.profil-floating{position:absolute;z-index:3;top:30px;right:-3px;display:flex;align-items:center;gap:9px;padding:12px 16px;background:#fff;color:#123d76;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.1);font-size:11px;font-weight:800}
.profil-floating i{color:#f3a623;font-size:18px}
.profil-information{padding:10px 0 10px 15px}
.profil-eyebrow{display:flex;align-items:center;gap:10px;color:#1265d8;font-size:12px;font-weight:800;letter-spacing:2px;margin-bottom:20px}
.profil-eyebrow span{width:28px;height:3px;background:#1265d8;border-radius:10px}
.profil-information h2{color:#102d50;font-size:clamp(30px,3.3vw,43px);line-height:1.2;font-weight:850;margin:0 0 22px}
.profil-information h2 span{display:block;color:#1265d8}
.profil-intro{color:#455b75;font-size:16px;line-height:1.9;font-weight:600;margin-bottom:14px}
.profil-detail{color:#718096;font-size:14px;line-height:1.9;margin-bottom:24px}
.profil-button{display:inline-flex;align-items:center;gap:13px;padding:13px 21px;color:#fff;background:#1265d8;border-radius:11px;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 8px 20px rgba(18,101,216,.2);transition:.3s}
.profil-button:hover{color:#fff;background:#082b59;transform:translateY(-3px)}
.profil-commitment{margin-top:85px}
.commitment-heading{text-align:center;margin-bottom:32px}
.commitment-heading span{color:#1265d8;font-size:11px;letter-spacing:2px;font-weight:800}
.commitment-heading h3{color:#102d50;font-size:clamp(23px,3vw,31px);font-weight:850;margin:10px 0}
.commitment-heading p{color:#718096;font-size:14px;margin:0}
.commitment-card{height:100%;min-height:205px;padding:25px 22px;background:#fff;border:1px solid #e5edf8;border-radius:18px;box-shadow:0 8px 25px rgba(16,45,80,.045);transition:all .3s}
.commitment-card:hover{transform:translateY(-8px);border-color:#8bbaff;box-shadow:0 18px 35px rgba(16,45,80,.11)}
.commitment-icon{width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:14px;font-size:22px;margin-bottom:17px}
.commitment-icon.blue{color:#1265d8;background:#e7f0ff}.commitment-icon.orange{color:#e99a20;background:#fff3df}.commitment-icon.red{color:#e44c65;background:#ffe9ed}.commitment-icon.green{color:#239b67;background:#e3f8ed}
.commitment-card h4{color:#102d50;font-size:15px;font-weight:800;margin-bottom:10px}
.commitment-card p{color:#718096;font-size:13px;line-height:1.75;margin:0}
@media(max-width:991px){.profil-modern{padding:70px 0}.profil-main-image{height:370px}.profil-information{padding:10px 0}.profil-commitment{margin-top:60px}}
@media(max-width:575px){.profil-modern{padding:55px 0}.profil-visual{padding:12px 8px 15px 0}.profil-main-image{height:290px;border-radius:18px}.profil-photo-overlay{left:10px;right:15px;bottom:28px;padding:13px}.profil-photo-overlay h4{font-size:16px}.profil-floating{top:18px;right:0;font-size:9px}.profil-information h2{font-size:30px}.profil-commitment{margin-top:48px}.commitment-card{min-height:0}}


/* KATA PENGANTAR KEPALA SEKOLAH */
.sambutan-section{position:relative;overflow:hidden;background:linear-gradient(145deg,#f3f7ff,#fff 70%)}
.sambutan-section .section-subtitle{max-width:650px;margin:12px auto 0;color:#718096;line-height:1.8}
.sambutan-card{position:relative;display:grid;grid-template-columns:280px minmax(0,1fr);align-items:start;gap:32px;padding:34px;background:#fff;border:1px solid #e4edf9;border-radius:22px;box-shadow:0 18px 50px rgba(16,45,80,.08)}
.sambutan-photo-column{text-align:center}
.sambutan-photo{cursor:zoom-in;transition:box-shadow .3s ease,border-color .3s ease}
.sambutan-photo:hover{box-shadow:0 16px 38px rgba(16,45,80,.24);border-color:#9bc4ff}
.foto-kepsek-modal .modal-content{background:transparent;border:0}
.foto-kepsek-modal .modal-body{padding:0;text-align:center}
.foto-kepsek-modal img{max-height:85vh;max-width:100%;object-fit:contain;border-radius:14px;box-shadow:0 18px 55px rgba(0,0,0,.35)}
.foto-kepsek-modal .btn-close{position:absolute;right:0;top:-42px;filter:invert(1);opacity:1}
.sambutan-photo{display:block;width:100%;height:350px;object-fit:cover;object-position:center 18%;border-radius:17px;border:4px solid #e8f1ff;box-shadow:0 10px 28px rgba(16,45,80,.12)}
.sambutan-photo-animate{opacity:0;transform:translateY(45px) scale(.94);filter:blur(3px);transition:opacity .9s ease,transform .9s cubic-bezier(.2,.75,.25,1),filter .9s ease;will-change:opacity,transform}
.sambutan-photo-animate.muncul{opacity:1;transform:translateY(0) scale(1);filter:blur(0);}
@media(prefers-reduced-motion:reduce){.sambutan-photo-animate,.sambutan-photo-animate.muncul{transition:none;transform:none;filter:none;opacity:1}}
.sambutan-photo-caption{margin-top:15px;color:#718096;font-size:13px;line-height:1.5}
.sambutan-photo-caption strong{display:block;color:#123d76;font-size:16px}
.sambutan-content{min-width:0}
.sambutan-mark{width:48px;height:48px;display:flex;align-items:center;justify-content:center;margin-bottom:15px;border-radius:14px;background:#e8f1ff;color:#1265d8;font-size:31px}
.sambutan-body{color:#5c6e83;font-size:15px;line-height:1.95}
.sambutan-body p{margin-bottom:17px;text-align:justify}
.sambutan-body .sambutan-greeting{color:#123d76;font-weight:800}
.sambutan-signature{display:flex;flex-direction:column;gap:4px;margin-top:24px;padding-top:18px;border-top:1px solid #e8eef7;color:#718096;font-size:14px}
.sambutan-signature strong{color:#123d76;font-size:15px}
@media(max-width:767px){.sambutan-card{grid-template-columns:1fr;padding:24px 20px;gap:22px}.sambutan-photo-column{max-width:280px;margin:0 auto}.sambutan-photo{height:330px}.sambutan-body{font-size:14px}}


/* ===== LATAR HALAMAN ELEGANT NATURAL ===== */
body {
    background-color: #faf9f4;
}
.section {
    background-color: #f7f8f5;
}
.section-light {
    background: linear-gradient(135deg, #fffdf8 0%, #f5f6f1 55%, #edf2ed 100%);
}
.profil-modern {
    background: linear-gradient(120deg, #faf9f4 0%, #f1f4ed 58%, #edf2f1 100%);
}
.sambutan-section {
    background: linear-gradient(120deg, #fffdf8 0%, #f4f5ef 55%, #eef2ef 100%);
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container">

<a class="navbar-brand" href="#beranda">
    <img src="logo.jpeg" alt="" class="logo-sekolah">
    <span class="brand-text">
        <strong>SDN 1 BERIRIJARAK</strong>
        <small>Sekolah Dasar Negeri</small>
    </span>
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="menuUtama">
<ul class="navbar-nav ms-auto align-items-lg-center">

<li class="nav-item">
    <a class="nav-link" href="#beranda">Beranda</a>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#profil" data-bs-toggle="dropdown">Profil Sekolah</a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#profil">Tentang Kami</a></li>
        <li><a class="dropdown-item" href="#sambutan">Sambutan Kepala Sekolah</a></li>
        <li><a class="dropdown-item" href="#visi-misi">Visi dan Misi</a></li>
        <li><a class="dropdown-item" href="#fasilitas">Fasilitas</a></li>
    </ul>
</li>

<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#akademik" data-bs-toggle="dropdown">Akademik</a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#akademik">Program Pendidikan</a></li>
        <li><a class="dropdown-item" href="#ekstrakurikuler">Ekstrakurikuler</a></li>
    </ul>
</li>

<li class="nav-item"><a class="nav-link" href="#guru">Guru</a></li>
<li class="nav-item"><a class="nav-link" href="#galeri">Galeri</a></li>
<li class="nav-item"><a class="nav-link" href="#prestasi">Prestasi</a></li>
<li class="nav-item"><a class="nav-link" href="#media-pembelajaran">Media Pembelajaran</a></li>
<li class="nav-item"><a class="nav-link" href="#lokasi">Lokasi</a></li>

<li class="nav-item ms-lg-2">
    <a class="btn btn-primary btn-sm px-3 mt-2 mt-lg-0" href="login.php">
        <i class="bi bi-box-arrow-in-right me-1"></i>Login
    </a>
</li>

</ul>
</div>
</div>
</nav>

<!-- BERANDA -->
<header class="hero" id="beranda">
<div class="container">
<div class="hero-content">



<h1 class="hero-title-animate"><span class="hero-word" style="--word-delay: 0.00s">MENCETAK</span> <span class="hero-word" style="--word-delay: 0.55s">GENERASI</span> <span class="hero-word" style="--word-delay: 1.10s">CIKAL,</span> <span class="hero-word" style="--word-delay: 1.65s">CERDAS,</span> <span class="hero-word" style="--word-delay: 2.20s">BERIMAN,</span> <span class="hero-word" style="--word-delay: 2.75s">KREATIF,</span> <span class="hero-word" style="--word-delay: 3.30s">AMANAH,</span> <span class="hero-word" style="--word-delay: 3.85s">DAN</span> <span class="hero-word" style="--word-delay: 4.40s">PEDULI</span> <span class="hero-word" style="--word-delay: 4.95s">LINGKUNGAN</span></h1>


<p class="mt-4 mb-4">
    Selamat datang di website resmi SDN 1 Beririjarak.
    Mari bersama membangun lingkungan belajar yang nyaman,
    menyenangkan, berkarakter, dan mendukung prestasi setiap siswa.
</p>

<a href="#profil" class="btn-utama me-2 mb-2">
    Kenali Sekolah Kami <i class="bi bi-arrow-right ms-2"></i>
</a>



</div>
</div>
</header>

<!-- STATISTIK -->
<section class="stats">
<div class="container">
<div class="row g-3">

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-upc-scan"></i>
        <h3>50203014</h3>
        <p>NPSN Sekolah</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-collection-fill"></i>
        <h3>11</h3>
        <p>Rombongan Belajar</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-patch-check-fill"></i>
        <h3>B</h3>
        <p>Akreditasi</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-calendar-check-fill"></i>
        <h3>1968</h3>
        <p>Tahun Berdiri</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-person-workspace"></i>
        <h3>15</h3>
        <p>Guru & Tenaga Kependidikan</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-people-fill"></i>
        <h3>211</h3>
        <p>Peserta Didik</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-building"></i>
        <h3>6</h3>
        <p>Ruang Kelas</p>
    </div>
</div>

</div>
</div>
</section>

<!-- PROFIL SEKOLAH -->
<section class="profil-modern" id="profil">
<div class="container">
    <div class="row align-items-center g-5">
        <div class="col-lg-6">
            <div class="profil-visual">
                <div class="profil-decoration"></div>
                <img class="profil-main-image"
                     src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1200&q=90"
                     alt="Ilustrasi lingkungan belajar di sekolah">
                <div class="profil-photo-overlay">
                    <div class="profil-overlay-icon"><i class="bi bi-mortarboard-fill"></i></div>
                    <div>
                        <h4>SDN 1 Beririjarak</h4>
                        <p>Tempat tumbuh, belajar, dan meraih cita-cita</p>
                    </div>
                </div>
                <div class="profil-floating">
                    <i class="bi bi-stars"></i>
                    <span>Pendidikan untuk Masa Depan</span>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="profil-information">
                <div class="profil-eyebrow"><span></span> PROFIL SEKOLAH</div>
                <h2>Mewujudkan Generasi <span>CIKAL</span> di SDN 1 Beririjarak</h2>
                <p class="profil-intro">
                    SD Negeri 1 Beririjarak merupakan satuan pendidikan dasar negeri yang berlokasi di Desa Beririjarak, Kecamatan Wanasaba, Kabupaten Lombok Timur, Nusa Tenggara Barat. Berdiri sejak tahun 1968, sekolah ini terus berkomitmen memberikan layanan pendidikan yang berkualitas bagi peserta didik.
                </p>
                <p class="profil-detail">
                    Dengan dukungan 15 guru dan tenaga kependidikan, 11 rombongan belajar, serta fasilitas pembelajaran yang tersedia, SD Negeri 1 Beririjarak berupaya membentuk lulusan CIKAL: Cerdas, beriman dan bertakwa, Kreatif, Amanah, serta peduli terhadap lingkungan.
                </p>
                <a href="#visi-misi" class="profil-button">
                    Jelajahi Visi dan Misi <i class="bi bi-arrow-up-right"></i>
                </a>
            </div>
        </div>
    </div>

</div>
</section>

<!-- SAMBUTAN KEPALA SEKOLAH -->
<section class="section section-light sambutan-section" id="sambutan">
<div class="container">
    <div class="text-center mb-5">
        <span class="section-label">Sambutan</span>
        <h2 class="section-title">Sambutan Kepala Sekolah</h2>
        <p class="section-subtitle">Pesan dan harapan Kepala Sekolah untuk seluruh warga sekolah.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="sambutan-card">
                <div class="sambutan-photo-column">
                    <img src="kepsek.jpeg" alt="Foto Kepala SD Negeri 1 Beririjarak" class="sambutan-photo sambutan-photo-animate" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#fotoKepsekModal" aria-label="Perbesar foto Kepala Sekolah" title="Klik untuk memperbesar foto">
                    <div class="sambutan-photo-caption">
                        <span>Hormat kami,</span>
                        <strong>PAUZANUDDIN, S.Pd.</strong>
                        <span class="d-block mt-1 fw-semibold" style="color:#123d76;font-size:15px;">Kepala SD NEGERI 1 BERIRIJARAK</span>
                    </div>
                </div>
                <div class="sambutan-content">
                    <div class="sambutan-mark"><i class="bi bi-quote"></i></div>
                    <div class="sambutan-body">                    <p>Dengan penuh rasa syukur, website resmi SD NEGERI 1 BERIRIJARAK dapat hadir sebagai jendela informasi dan komunikasi bagi seluruh warga sekolah dan masyarakat luas. Di era digital ini, keberadaan website sekolah bukan lagi sekadar pelengkap, melainkan sebuah kebutuhan. Website ini kami hadirkan sebagai sarana untuk menyampaikan informasi secara transparan, cepat dan akurat mengenai profil sekolah, program pendidikan, prestasi siswa, karya guru, serta berbagai kegiatan sekolah. Kami menyadari bahwa pendidikan adalah tanggung jawab bersama antara sekolah, orang tua, dan masyarakat. Oleh karena itu, melalui website ini kami berharap terjalin komunikasi yang lebih baik, sehingga dapat mendukung terciptanya lingkungan belajar yang kondusif, kreatif, dan berprestasi. Terima kasih kepada seluruh tim yang telah berkontribusi dalam pembuatan website ini. Semoga website ini memberikan manfaat dan menjadi media inspirasi bagi kita semua dalam memajukan dunia pendidikan.</p>

                    <p class="sambutan-greeting mb-0">Wassalamu'alaikum Warahmatullahi Wabarakatuh.</p>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</section>

<!-- VISI DAN MISI -->
<section class="section" id="visi-misi">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Arah dan Tujuan</span>
    <h2 class="section-title">Visi dan Misi Sekolah</h2>
    <p class="section-subtitle">
        Menjadi pedoman bersama dalam menyelenggarakan pendidikan
        dan mengembangkan potensi peserta didik.
    </p>
</div>

<div class="vm-vision mb-4">
    <div class="vm-vision-content">
        <span class="vm-eyebrow vm-reveal"><i class="bi bi-eye-fill"></i> Visi Sekolah</span>
        <h3 class="vm-reveal">Mewujudkan Generasi CIKAL</h3>
        <p class="vm-reveal">Menjadi sekolah yang menghasilkan lulusan <strong>CIKAL</strong>:
        Cerdas, Iman dan Taqwa, Kreatif, Amanah, dan peduli Lingkungan.</p>
        <div class="vm-values" aria-label="Pilih nilai CIKAL untuk melihat penjelasan">
            <button type="button" class="vm-value vm-reveal vm-value-button" data-title="Cerdas" data-icon="bi-lightbulb-fill" data-description="Mengembangkan kemampuan berpikir, menambah pengetahuan, serta membiasakan siswa untuk belajar aktif, kritis, dan mampu memecahkan masalah."><i class="bi bi-lightbulb-fill"></i> Cerdas</button>
            <button type="button" class="vm-value vm-reveal vm-value-button" data-title="Iman dan Taqwa" data-icon="bi-heart-fill" data-description="Menanamkan keimanan dan ketakwaan kepada Tuhan Yang Maha Esa melalui pembiasaan beribadah, berakhlak mulia, serta menghormati sesama."><i class="bi bi-heart-fill"></i> Iman dan Taqwa</button>
            <button type="button" class="vm-value vm-reveal vm-value-button" data-title="Kreatif" data-icon="bi-palette-fill" data-description="Mendorong siswa untuk mengembangkan bakat, imajinasi, dan ide-ide baru serta berani menghasilkan karya yang positif dan bermanfaat."><i class="bi bi-palette-fill"></i> Kreatif</button>
            <button type="button" class="vm-value vm-reveal vm-value-button" data-title="Amanah" data-icon="bi-shield-check" data-description="Membentuk pribadi yang jujur, disiplin, bertanggung jawab, dapat dipercaya, dan konsisten melaksanakan tugas serta kewajibannya."><i class="bi bi-shield-check"></i> Amanah</button>
            <button type="button" class="vm-value vm-reveal vm-value-button" data-title="Peduli Lingkungan" data-icon="bi-tree-fill" data-description="Membiasakan siswa menjaga kebersihan, merawat lingkungan sekolah, menghemat sumber daya, dan ikut menciptakan lingkungan yang sehat dan nyaman."><i class="bi bi-tree-fill"></i> Peduli Lingkungan</button>
        </div>
        <div class="vm-value-detail" id="vmValueDetail" aria-live="polite" hidden>
            <div class="vm-value-detail-icon"><i class="bi bi-stars" id="vmValueDetailIcon"></i></div>
            <div>
                <h4 id="vmValueDetailTitle"></h4>
                <p id="vmValueDetailText"></p>
            </div>
        </div>
    </div>
</div>

<div class="vm-mission-heading">
    <span class="line"></span>
    <h3 class="vm-reveal"><i class="bi bi-bullseye me-2 text-primary"></i>B. Misi</h3>
    <span class="line"></span>
</div>

<p class="text-center text-secondary mb-4 vm-reveal" style="line-height:1.8">
    Dalam upaya mengimplementasikan visi sekolah, SDN 1 Beririjarak menjabarkan misi sekolah sebagai berikut:
</p>

<div class="row g-4">
    <div class="col-md-6">
        <div class="vm-mission-card vm-reveal">
            <div class="vm-mission-icon"><i class="bi bi-moon-stars-fill"></i></div>
            <div><h5>01. Membina Imtaq dan Akhlak Mulia</h5>
            <p>Membina warga sekolah dalam kegiatan-kegiatan peningkatan Imtaq dan berakhlak mulia.</p></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="vm-mission-card vm-reveal">
            <div class="vm-mission-icon"><i class="bi bi-trophy-fill"></i></div>
            <div><h5>02. Memfasilitasi Peningkatan Prestasi</h5>
            <p>Memfasilitasi peningkatan prestasi siswa sesuai bakat dan minat pada bidang akademik dan nonakademik.</p></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="vm-mission-card vm-reveal">
            <div class="vm-mission-icon"><i class="bi bi-lightbulb-fill"></i></div>
            <div><h5>03. Mendorong Siswa Kreatif Berkarya</h5>
            <p>Merancang dan melaksanakan pembelajaran yang mendorong siswa kreatif berkarya.</p></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="vm-mission-card vm-reveal">
            <div class="vm-mission-icon"><i class="bi bi-people-fill"></i></div>
            <div><h5>04. Menumbuhkan Perilaku Positif</h5>
            <p>Menumbuhkan perilaku disiplin, santun dan saling menghargai dalam berbagai situasi baik di sekolah maupun di rumah.</p></div>
        </div>
    </div>
    <div class="col-md-6 mx-auto">
        <div class="vm-mission-card vm-reveal">
            <div class="vm-mission-icon"><i class="bi bi-tree-fill"></i></div>
            <div><h5>05. Melatih Kepedulian Lingkungan</h5>
            <p>Melatih siswa peduli lingkungan.</p></div>
        </div>
    </div>
</div>

</div>
</section>

<!-- FASILITAS -->
<section class="section section-light" id="fasilitas">
<div class="container">
    <div class="text-center mb-5">
        <span class="section-label">Fasilitas Sekolah</span>
        <h2 class="section-title">Fasilitas Pendukung Pembelajaran</h2>
        <p class="section-subtitle">Lihat berbagai fasilitas sekolah beserta dokumentasi fotonya.</p>
    </div>
    <div class="row g-4">
        <?php if (!empty($daftarFasilitas)): ?>
            <?php $nomorFasilitas = 0; foreach ($daftarFasilitas as $fasilitas): $nomorFasilitas++; $modalFasilitasId = "modalFasilitas" . $nomorFasilitas; ?>
            <div class="col-md-6 col-lg-4">
                <button type="button" class="ekskul-card w-100 p-0 border-0 text-start" data-bs-toggle="modal" data-bs-target="#<?= aman($modalFasilitasId) ?>" aria-label="Lihat foto <?= aman($fasilitas["nama_fasilitas"]) ?>">
                    <div class="ekskul-cover">
                        <?php $fotoSampulFasilitas = !empty($fasilitas["foto"][0]) ? $fasilitas["foto"][0] : ""; ?>
                        <?php if ($fotoSampulFasilitas && is_file(__DIR__ . "/" . $fotoSampulFasilitas)): ?>
                            <img src="<?= aman($fotoSampulFasilitas) ?>" alt="<?= aman($fasilitas["nama_fasilitas"]) ?>" loading="lazy">
                        <?php else: ?><div class="ekskul-cover-placeholder"><i class="bi bi-building"></i></div><?php endif; ?>
                        <div class="ekskul-cover-shade"></div>
                        <span class="ekskul-photo-count"><i class="bi bi-images me-1"></i><?= count($fasilitas["foto"]) ?> Foto</span>
                        <div class="ekskul-cover-title"><span class="ekskul-kicker">FASILITAS SEKOLAH</span><h3><?= aman($fasilitas["nama_fasilitas"]) ?></h3></div>
                    </div>
                    <div class="ekskul-card-body">
                        <p><?= !empty($fasilitas["deskripsi"]) ? nl2br(aman($fasilitas["deskripsi"])) : "Dokumentasi " . aman($fasilitas["nama_fasilitas"]) . "." ?></p>
                        <span class="ekskul-open-link">Lihat fasilitas <i class="bi bi-arrow-right-circle-fill"></i></span>
                    </div>
                </button>
            </div>
            <div class="modal fade" id="<?= aman($modalFasilitasId) ?>" tabindex="-1" aria-labelledby="<?= aman($modalFasilitasId) ?>Label" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable"><div class="modal-content border-0 rounded-4">
                    <div class="modal-header text-white" style="background:var(--gelap)">
                        <div><h5 class="modal-title fw-bold" id="<?= aman($modalFasilitasId) ?>Label"><?= aman($fasilitas["nama_fasilitas"]) ?></h5><small class="text-white-50">Dokumentasi fasilitas sekolah</small></div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-4">
                        <?php if (!empty($fasilitas["deskripsi"])): ?><p class="text-secondary mb-4"><?= nl2br(aman($fasilitas["deskripsi"])) ?></p><?php endif; ?>
                        <div class="row g-3">
                            <?php $jumlahFotoFasilitasValid = 0; foreach ($fasilitas["foto"] as $fotoFasilitas): ?>
                                <?php if (!empty($fotoFasilitas) && is_file(__DIR__ . "/" . $fotoFasilitas)): $jumlahFotoFasilitasValid++; ?>
                                <div class="col-sm-6 col-lg-4"><img src="<?= aman($fotoFasilitas) ?>" alt="Foto <?= aman($fasilitas["nama_fasilitas"]) ?>" class="w-100 rounded-3 shadow-sm" style="height:230px;object-fit:cover" loading="lazy"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($jumlahFotoFasilitasValid === 0): ?><div class="col-12 text-center py-4"><i class="bi bi-camera text-primary" style="font-size:2.5rem"></i><p class="text-secondary mt-2 mb-0">Foto fasilitas belum tersedia.</p></div><?php endif; ?>
                        </div>
                    </div>
                </div></div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><div class="text-center text-muted py-4"><i class="bi bi-building fs-1 d-block mb-2"></i>Dokumentasi fasilitas sekolah akan ditampilkan setelah admin mengunggah foto.</div></div>
        <?php endif; ?>
    </div>
</div>
</section>

<!-- AKADEMIK -->
<section class="section" id="akademik">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Akademik</span>
    <h2 class="section-title">Program Pendidikan</h2>
    <p class="section-subtitle">
        Kegiatan pendidikan yang mendukung pengetahuan,
        keterampilan, dan karakter siswa.
    </p>
</div>

<div class="row g-4">

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-journal-text"></i></div>
    <h5>Pembelajaran di Kelas</h5>
    <p>Kegiatan pembelajaran sesuai kurikulum yang diterapkan sekolah.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-lightbulb-fill"></i></div>
    <h5>Pengembangan Potensi</h5>
    <p>Mengembangkan kemampuan, kreativitas, dan kemandirian siswa.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-heart-fill"></i></div>
    <h5>Pendidikan Karakter</h5>
    <p>Membiasakan sikap disiplin, tanggung jawab, santun, dan peduli.</p>
</div>
</div>

</div>
</div>
</section>

<!-- EKSTRAKURIKULER -->
<section class="section section-light" id="ekstrakurikuler">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Pengembangan Diri</span>
    <h2 class="section-title">Kegiatan Ekstrakurikuler</h2>
    <p class="section-subtitle">
        Sesuaikan dengan kegiatan ekstrakurikuler yang tersedia di sekolah.
    </p>
</div>

<div class="row g-4">

<?php if (!empty($daftarEkstrakurikuler)): ?>
    <?php $nomorEkskul = 0; foreach ($daftarEkstrakurikuler as $ekskul): $nomorEkskul++; $modalEkskulId = "modalEkskul" . $nomorEkskul; ?>
    <div class="col-md-6 col-lg-4">
        <button type="button" class="ekskul-card w-100 p-0 border-0 text-start"
            data-bs-toggle="modal" data-bs-target="#<?= aman($modalEkskulId) ?>"
            aria-label="Lihat foto kegiatan <?= aman($ekskul["nama_kegiatan"]) ?>">
            <div class="ekskul-cover">
                <?php $fotoSampulEkskul = !empty($ekskul["foto"][0]) ? $ekskul["foto"][0] : ""; ?>
                <?php if ($fotoSampulEkskul && is_file(__DIR__ . "/" . $fotoSampulEkskul)): ?>
                    <img src="<?= aman($fotoSampulEkskul) ?>" alt="Sampul <?= aman($ekskul["nama_kegiatan"]) ?>" loading="lazy">
                <?php else: ?>
                    <div class="ekskul-cover-placeholder"><i class="bi bi-trophy-fill"></i></div>
                <?php endif; ?>
                <div class="ekskul-cover-shade"></div>
                <span class="ekskul-photo-count"><i class="bi bi-images me-1"></i><?= count($ekskul["foto"]) ?> Foto</span>
                <div class="ekskul-cover-title">
                    <span class="ekskul-kicker">EKSTRAKURIKULER</span>
                    <h3><?= aman($ekskul["nama_kegiatan"]) ?></h3>
                </div>
            </div>
            <div class="ekskul-card-body">
                <p><?= !empty($ekskul["deskripsi"]) ? nl2br(aman($ekskul["deskripsi"])) : "Dokumentasi kegiatan " . aman($ekskul["nama_kegiatan"]) . "." ?></p>
                <span class="ekskul-open-link">Jelajahi kegiatan <i class="bi bi-arrow-right-circle-fill"></i></span>
            </div>
        </button>
    </div>

    <div class="modal fade" id="<?= aman($modalEkskulId) ?>" tabindex="-1" aria-labelledby="<?= aman($modalEkskulId) ?>Label" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header text-white" style="background:var(--gelap)">
                    <div><h5 class="modal-title fw-bold" id="<?= aman($modalEkskulId) ?>Label"><?= aman($ekskul["nama_kegiatan"]) ?></h5>
                    <small class="text-white-50">Dokumentasi kegiatan ekstrakurikuler</small></div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <?php if (!empty($ekskul["deskripsi"])): ?><p class="text-secondary mb-4"><?= nl2br(aman($ekskul["deskripsi"])) ?></p><?php endif; ?>
                    <div class="row g-3">
                        <?php $jumlahFotoValid = 0; ?>
                        <?php foreach ($ekskul["foto"] as $fotoEkskul): ?>
                            <?php if (!empty($fotoEkskul) && is_file(__DIR__ . "/" . $fotoEkskul)): ?>
                            <?php $jumlahFotoValid++; ?>
                            <div class="col-sm-6 col-lg-4">
                                <img src="<?= aman($fotoEkskul) ?>" alt="Dokumentasi <?= aman($ekskul["nama_kegiatan"]) ?>"
                                    class="w-100 rounded-3 shadow-sm" style="height:230px;object-fit:cover" loading="lazy">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($jumlahFotoValid === 0): ?>
                            <div class="col-12 text-center py-4">
                                <i class="bi bi-camera text-primary" style="font-size:2.5rem"></i>
                                <p class="text-secondary mt-2 mb-0">Foto kegiatan akan ditampilkan di sini setelah admin mengunggah dokumentasinya.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-12"><div class="text-center py-5 px-3 rounded-4 bg-white border">
        <i class="bi bi-images text-primary" style="font-size:2.5rem"></i>
        <h5 class="fw-bold mt-3">Belum ada kegiatan ekstrakurikuler</h5>
        <p class="text-secondary mb-0">Informasi dan foto kegiatan akan ditampilkan setelah admin menambahkannya.</p>
    </div></div>
<?php endif; ?>

</div>
</div>
</section>

<!-- GURU -->
<section class="section" id="guru">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Tenaga Pendidik</span>
    <h2 class="section-title">Guru dan Tenaga Kependidikan</h2>
    <p class="section-subtitle">
        Kenali guru dan tenaga pendidik SDN 1 Beririjarak.
    </p>
</div>

<?php if ($daftarGuru && mysqli_num_rows($daftarGuru) > 0): ?>
<div class="guru-slider">

<button type="button" class="guru-arrow guru-prev" id="guruPrev" onclick="geserGuru(-1)" aria-label="Guru sebelumnya">
    <i class="bi bi-chevron-left"></i>
</button>

<div class="guru-track-wrapper">
<div class="guru-track" id="guruTrack">
    <?php while ($guru = mysqli_fetch_assoc($daftarGuru)): ?>
    <article class="guru-item">
        <?php if (!empty($guru["foto"]) && is_file(__DIR__ . "/" . $guru["foto"])): ?>
            <img src="<?= aman($guru["foto"]) ?>" alt="Foto <?= aman($guru["nama_guru"]) ?>" loading="lazy">
        <?php else: ?>
            <div class="guru-foto-placeholder" role="img" aria-label="Foto belum tersedia">
                <i class="bi bi-person-fill"></i>
            </div>
        <?php endif; ?>
        <h5><?= aman($guru["nama_guru"]) ?><?php if (!empty($guru["gelar"])): ?>, <?= aman($guru["gelar"]) ?><?php endif; ?></h5>
        <?php if (!empty($guru["jabatan"])): ?>
            <div class="jabatan-guru"><?= aman($guru["jabatan"]) ?></div>
        <?php endif; ?>
    </article>
    <?php endwhile; ?>
</div>
</div>

<button type="button" class="guru-arrow guru-next" id="guruNext" onclick="geserGuru(1)" aria-label="Guru berikutnya">
    <i class="bi bi-chevron-right"></i>
</button>

</div>
<div class="text-center mt-4">
    <span class="text-secondary small" id="guruCounter"></span>
</div>
<?php else: ?>
<div class="text-center py-5 px-3 rounded-4 bg-light">
    <i class="bi bi-people text-primary" style="font-size:3rem"></i>
    <h5 class="mt-3 fw-bold">Data guru belum tersedia</h5>
    <p class="text-secondary mb-0">Informasi guru dan tenaga kependidikan akan ditampilkan setelah admin menambahkan data melalui dashboard.</p>
</div>
<?php endif; ?>

</div>
</section>

<!-- GALERI -->
<section class="section section-light" id="galeri">
<div class="container">
<div class="text-center mb-5">
    <span class="section-label">Dokumentasi</span>
    <h2 class="section-title">Galeri Sekolah</h2>
    <p class="section-subtitle">Dokumentasi kegiatan belajar dan aktivitas sekolah.</p>
</div>
<div class="row g-4">
<?php if ($daftarGaleri && mysqli_num_rows($daftarGaleri) > 0): ?>
    <?php while ($galeri = mysqli_fetch_assoc($daftarGaleri)): ?>
    <div class="col-md-4">
        <article class="info-card h-100 p-3">
            <img class="gallery-image" src="<?= aman($galeri["nama_file"]) ?>" alt="<?= aman($galeri["judul"]) ?>" loading="lazy">
            <h5 class="fw-bold mt-3 mb-1"><?= aman($galeri["judul"]) ?></h5>
            <?php if (!empty($galeri["tanggal_kegiatan"])): ?>
                <small class="text-primary"><?= aman(date("d F Y", strtotime($galeri["tanggal_kegiatan"]))) ?></small>
            <?php endif; ?>
            <?php if (!empty($galeri["deskripsi"])): ?>
                <p class="text-secondary mt-2 mb-0"><?= aman($galeri["deskripsi"]) ?></p>
            <?php endif; ?>
        </article>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-12">
        <div class="text-center py-5 px-3 rounded-4 bg-white">
            <i class="bi bi-images text-primary" style="font-size:3rem"></i>
            <h5 class="mt-3 fw-bold">Belum ada foto galeri</h5>
            <p class="text-secondary mb-0">Dokumentasi kegiatan sekolah akan ditampilkan di sini setelah admin menambahkan foto.</p>
        </div>
    </div>
<?php endif; ?>
</div>
</div>
</section>

<?php
$daftarBerita = false;
if (isset($koneksi) && $koneksi) {
    $queryBerita = mysqli_query($koneksi, "SELECT judul, isi, gambar, tanggal_publikasi FROM tb_berita WHERE status='Terbit' AND tanggal_publikasi <= NOW() ORDER BY tanggal_publikasi DESC, id DESC LIMIT 6");
    if ($queryBerita) $daftarBerita = $queryBerita;
}
?>
<!-- PRESTASI SEKOLAH -->
<section class="section" id="prestasi">
<div class="container">
<div class="text-center mb-5">
    <span class="section-label">Pencapaian Sekolah</span>
    <h2 class="section-title">Prestasi Sekolah</h2>
    <p class="section-subtitle">Informasi pencapaian dan penghargaan yang diraih oleh peserta didik serta sekolah.</p>
</div>
<div class="row g-4">
<?php if ($daftarBerita && mysqli_num_rows($daftarBerita) > 0): ?>
    <?php while ($berita = mysqli_fetch_assoc($daftarBerita)): ?>
    <div class="col-md-4">
        <article class="card news-card h-100 border-0 shadow-sm">
            <?php if (!empty($berita["gambar"]) && is_file(__DIR__ . "/" . $berita["gambar"])): ?>
                <img src="<?= aman($berita["gambar"]) ?>" alt="<?= aman($berita["judul"]) ?>" style="width:100%;height:220px;object-fit:cover">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-light text-primary" style="height:220px"><i class="bi bi-newspaper" style="font-size:3rem"></i></div>
            <?php endif; ?>
            <div class="card-body">
                <small class="text-primary"><?= aman(date("d F Y", strtotime($berita["tanggal_publikasi"]))) ?></small>
                <h5 class="fw-bold mt-2"><?= aman($berita["judul"]) ?></h5>
                <p class="text-secondary mb-0" style="white-space:pre-line"><?= aman(mb_strimwidth($berita["isi"], 0, 180, "...", "UTF-8")) ?></p>
            </div>
        </article>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-12"><div class="text-center py-5 px-3 rounded-4 bg-light">
        <i class="bi bi-trophy-fill text-primary" style="font-size:3rem"></i>
        <h5 class="mt-3 fw-bold">Belum ada prestasi yang ditampilkan</h5>
        <p class="text-secondary mb-0">Prestasi sekolah akan ditampilkan di sini setelah admin menambahkannya.</p>
    </div></div>
<?php endif; ?>
</div>
</div>
</section>

<!-- MEDIA PEMBELAJARAN -->
<section class="section section-light" id="media-pembelajaran">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Belajar Digital</span>
    <h2 class="section-title">Media Pembelajaran</h2>
    <p class="section-subtitle">
        Sarana digital untuk membantu siswa memahami materi pelajaran.
    </p>
</div>

<div class="row g-4">

<div class="col-md-4">
<div class="learning-card">
    <i class="bi bi-play-circle-fill"></i>
    <h5 class="fw-bold mt-3">Video Pembelajaran</h5>
    <p>Video untuk membantu siswa memahami pelajaran.</p>
    <a href="#" class="btn btn-outline-primary mt-3">Lihat Materi</a>
</div>
</div>

<div class="col-md-4">
<div class="learning-card">
    <i class="bi bi-journal-bookmark-fill"></i>
    <h5 class="fw-bold mt-3">Materi Pelajaran</h5>
    <p>Bahan bacaan untuk mendukung pembelajaran di kelas maupun di rumah.</p>
    <a href="#" class="btn btn-outline-primary mt-3">Lihat Materi</a>
</div>
</div>

<div class="col-md-4">
<div class="learning-card">
    <i class="bi bi-patch-question-fill"></i>
    <h5 class="fw-bold mt-3">Latihan Soal</h5>
    <p>Latihan untuk mengulang materi dan mengukur pemahaman.</p>
    <a href="#" class="btn btn-outline-primary mt-3">Lihat Latihan</a>
</div>
</div>

</div>
</div>
</section>

<!-- LOKASI -->
<section class="section" id="lokasi">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Temukan Kami</span>
    <h2 class="section-title">Lokasi Sekolah</h2>
    <p class="section-subtitle">Informasi alamat dan lokasi SDN 1 Beririjarak.</p>
</div>

<div class="row g-4">

<div class="col-lg-5">
<div class="location-box">
    <h4 class="fw-bold mb-4">SDN 1 Beririjarak</h4>

    <p>
        <i class="bi bi-geo-alt-fill text-primary me-2"></i>
        <strong>Alamat:</strong><br>
        Desa Karang Baru, Kec. Wanasaba, Kabupaten Lombok Timur, Nusa Tenggara Barat.
    </p>

    <p>
        <i class="bi bi-building text-primary me-2"></i>
        <strong>Nama Sekolah:</strong><br>
        SDN 1 Beririjarak
    </p>

    <p class="mb-0">
        Sesuaikan alamat dengan alamat resmi sekolah.
    </p>
</div>
</div>

<div class="col-lg-7">
<div class="rounded-4 overflow-hidden h-100" style="min-height:320px">
   <iframe 
    src="https://maps.google.com/maps?q=SDN+1+BERIRIJARAK,+Desa+Beririjarak&z=17&output=embed" 
    width="100%" 
    height="400" 
    style="border:0;" 
    allowfullscreen="" 
    loading="lazy">
</iframe>
</div>
</div>

</div>
</div>
</section>

<!-- FOOTER -->
<footer class="footer">
<div class="container">

<div class="row g-4">

<div class="col-lg-5">
    <h5 class="fw-bold d-flex align-items-center gap-2">
        <img src="logo.jpeg" alt="Logo Sekolah" class="footer-logo">
        <span>SDN 1 BERIRIJARAK</span>
    </h5>

    <p class="mt-3" style="color:#d1fae5;line-height:1.8">
        Website resmi sebagai sarana informasi sekolah,
        kegiatan pendidikan, dan media pembelajaran.
    </p>
</div>

<div class="col-6 col-lg-3">
    <h6 class="fw-bold mb-3">Menu Utama</h6>
    <p><a href="#beranda">Beranda</a></p>
    <p><a href="#profil">Profil Sekolah</a></p>
    <p><a href="#visi-misi">Visi dan Misi</a></p>
    <p><a href="#akademik">Akademik</a></p>
</div>

<div class="col-6 col-lg-4">
    <h6 class="fw-bold mb-3">Informasi</h6>
    <p><a href="#berita">Berita Sekolah</a></p>
    <p><a href="#media-pembelajaran">Media Pembelajaran</a></p>
    <p><a href="#lokasi">Lokasi Sekolah</a></p>
    <p><a href="login.php">Login</a></p>
    <p><i class="bi bi-envelope me-2"></i><a href="mailto:sdn1beririjarak661@gmail.com">sdn1beririjarak661@gmail.com</a></p>
</div>

</div>

<div class="footer-bottom text-center">
    &copy; 2026 KKN Desa Beririjarak 28 Universitas Hamzanwadi. Semua hak dilindungi.
</div>

</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// SLIDER GURU
const guruTrack = document.getElementById("guruTrack");
const guruCounter = document.getElementById("guruCounter");
const guruPrev = document.getElementById("guruPrev");
const guruNext = document.getElementById("guruNext");

let posisiGuru = 0;

function jumlahGuruTerlihat() {
    if (window.innerWidth <= 575) return 1;
    if (window.innerWidth <= 991) return 2;
    return 3;
}

function geserGuru(arah) {
    if (!guruTrack || !guruCounter || !guruPrev || !guruNext) return;

    const semuaGuru = guruTrack.querySelectorAll(".guru-item");
    const terlihat = jumlahGuruTerlihat();

    if (semuaGuru.length === 0) return;

    const maksimal = Math.max(0, semuaGuru.length - terlihat);
    posisiGuru += arah;
    posisiGuru = Math.max(0, Math.min(posisiGuru, maksimal));

    const lebarItem = semuaGuru[0].getBoundingClientRect().width;
    const gayaTrack = window.getComputedStyle(guruTrack);
    const jarak = parseFloat(gayaTrack.columnGap) || 0;

    guruTrack.style.transform = `translateX(-${posisiGuru * (lebarItem + jarak)}px)`;
    guruCounter.textContent =
        `${posisiGuru + 1}–${Math.min(posisiGuru + terlihat, semuaGuru.length)} dari ${semuaGuru.length} guru`;

    guruPrev.disabled = posisiGuru === 0;
    guruNext.disabled = posisiGuru === maksimal;
}

window.addEventListener("resize", function () {
    posisiGuru = 0;
    geserGuru(0);
});

// TUTUP NAVBAR HP SETELAH MEMILIH MENU
document.querySelectorAll("#menuUtama .nav-link:not(.dropdown-toggle)")
.forEach(function (link) {
    link.addEventListener("click", function () {
        const menu = document.getElementById("menuUtama");

        if (window.innerWidth < 992 && menu.classList.contains("show")) {
            bootstrap.Collapse.getOrCreateInstance(menu).hide();
        }
    });
});

// FUNGSI ANIMASI KLIK PADA KOTAK STATISTIK
function cardClick(element) {
    element.classList.add('clicked');
    setTimeout(() => {
        element.classList.remove('clicked');
    }, 250);
}

// FUNGSI SLOW-MOTION IN KETIKA DI-SCROLL
const observerOptions = {
    root: null,
    rootMargin: '0px',
    threshold: 0.15
};

const observer = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('muncul');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

document.querySelectorAll('.stat-card-animate').forEach(card => {
    observer.observe(card);
});

document.querySelectorAll('.sambutan-photo-animate').forEach(photo => {
    observer.observe(photo);
});

// Animasi visi dan misi: setiap bagian muncul satu per satu saat digulir.
document.querySelectorAll('#visi-misi .vm-reveal').forEach(item => {
    observer.observe(item);
});

// Klik setiap nilai CIKAL untuk menampilkan penjelasannya.
const vmValueButtons = document.querySelectorAll('.vm-value-button');
const vmValueDetail = document.getElementById('vmValueDetail');
const vmValueDetailTitle = document.getElementById('vmValueDetailTitle');
const vmValueDetailText = document.getElementById('vmValueDetailText');
const vmValueDetailIcon = document.getElementById('vmValueDetailIcon');

vmValueButtons.forEach(button => {
    button.addEventListener('click', () => {
        vmValueButtons.forEach(item => {
            item.classList.remove('active');
            item.setAttribute('aria-pressed', 'false');
        });

        button.classList.add('active');
        button.setAttribute('aria-pressed', 'true');
        vmValueDetailTitle.textContent = button.dataset.title;
        vmValueDetailText.textContent = button.dataset.description;
        vmValueDetailIcon.className = 'bi ' + button.dataset.icon;

        // Ulangi animasi panel setiap nilai dipilih.
        vmValueDetail.hidden = true;
        void vmValueDetail.offsetWidth;
        vmValueDetail.hidden = false;
    });
});

geserGuru(0);
</script>

<!-- MODAL PERBESAR FOTO KEPALA SEKOLAH -->
<div class="modal fade foto-kepsek-modal" id="fotoKepsekModal" tabindex="-1" aria-labelledby="fotoKepsekModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-body position-relative">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                <img src="kepsek.jpeg" alt="Foto Kepala SD Negeri 1 Beririjarak ukuran besar">
            </div>
        </div>
    </div>
</div>

</body>
</html>