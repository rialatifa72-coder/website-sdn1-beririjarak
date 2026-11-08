<?php
require_once __DIR__ . "/koneksi.php";

$daftarGuru = false;
if (isset($koneksi) && $koneksi) {
    $queryGuru = mysqli_query(
        $koneksi,
        "SELECT nama_guru, jabatan, pendidikan, foto
         FROM tb_guru
         ORDER BY id ASC"
    );
    if ($queryGuru) {
        $daftarGuru = $queryGuru;
    }
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
    width: 50px;
    height: 50px;
    object-fit: contain;
    background: white;
    border-radius: 50%;
    padding: 4px;
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
    min-height: 570px;
    display: flex;
    align-items: center;
    color: white;
    background:
        linear-gradient(90deg, rgba(10,45,25,.90), rgba(10,45,25,.48)),
        url("https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=1800&q=85")
        center/cover no-repeat;
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
    font-size: clamp(36px, 5vw, 58px);
    font-weight: 800;
    line-height: 1.2;
    max-width: 750px;
}

/* ANIMASI JUDUL UTAMA BERANDA */
.hero-title-animate {
    background-image: linear-gradient(100deg, #ffffff 0%, #ffffff 35%, #bde8ff 50%, #ffffff 65%, #ffffff 100%);
    background-size: 220% 100%;
    background-position: 100% 50%;
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    -webkit-text-fill-color: transparent;
    animation:
        heroTitleEntrance 1.1s cubic-bezier(.2,.75,.25,1) both,
        heroTitleShimmer 6s ease-in-out 1.1s infinite;
    will-change: transform, opacity, background-position;
}

@keyframes heroTitleEntrance {
    from { opacity: 0; transform: translateY(35px) scale(.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes heroTitleShimmer {
    0%, 100% { background-position: 100% 50%; }
    50% { background-position: 0% 50%; }
}

@media (prefers-reduced-motion: reduce) {
    .hero-title-animate {
        animation: none;
        background: none;
        color: #fff;
        -webkit-text-fill-color: #fff;
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

.profile-image {
    width: 100%;
    height: 380px;
    object-fit: cover;
    border-radius: 16px;
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
.btn-outline-primary { color: #198754 !important; border-color: #198754 !important; }
.btn-outline-primary:hover { background-color: #198754 !important; border-color: #198754 !important; color: #fff !important; }
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container">

<a class="navbar-brand" href="#beranda">
    <img src="logo.jpeg" alt="Logo SDN 1 Beririjarak" class="logo-sekolah">
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
<li class="nav-item"><a class="nav-link" href="#berita">Berita</a></li>
<li class="nav-item"><a class="nav-link" href="#media-pembelajaran">Media Belajar</a></li>
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

<span class="hero-label">
    <i class="bi bi-mortarboard me-2"></i>
    Website Resmi Sekolah
</span>

<h1 class="hero-title-animate">MASA DEPAN CERDAS DIMULAI DI SINI</h1>

<p class="mt-4 mb-4">
    Selamat datang di website resmi SDN 1 Beririjarak.
    Mari bersama membangun lingkungan belajar yang nyaman,
    menyenangkan, berkarakter, dan mendukung prestasi setiap siswa.
</p>

<a href="#profil" class="btn-utama me-2 mb-2">
    Kenali Sekolah Kami <i class="bi bi-arrow-right ms-2"></i>
</a>

<a href="#akademik" class="btn-outline-light-custom mb-2">
    Program Pendidikan
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
        <i class="bi bi-patch-check-fill"></i>
        <h3>B</h3>
        <p>Akreditasi</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-calendar-check-fill"></i>
        <h3>1984</h3>
        <p>Tahun Berdiri</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-person-workspace"></i>
        <h3>12</h3>
        <p>Guru & Tenaga</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-mortarboard-fill"></i>
        <h3>1000+</h3>
        <p>Alumni</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-people-fill"></i>
        <h3>150+</h3>
        <p>Siswa Aktif</p>
    </div>
</div>

<div class="col-6 col-md-4 col-lg-2 stat-card-animate">
    <div class="stat-card interactive-card" onclick="cardClick(this)">
        <i class="bi bi-trophy-fill"></i>
        <h3>25+</h3>
        <p>Prestasi</p>
    </div>
</div>

</div>
</div>
</section>

<!-- PROFIL SEKOLAH -->
<section class="section" id="profil">
<div class="container">
<div class="row align-items-center g-5">

<div class="col-lg-6">
    <img class="profile-image"
         src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=1000&q=85"
         alt="Ilustrasi lingkungan sekolah">
</div>

<div class="col-lg-6">
    <span class="section-label">Profil Sekolah</span>
    <h2 class="section-title mt-2">Mengenal SDN 1 Beririjarak</h2>

    <p class="text-secondary" style="line-height:1.9">
        SDN 1 Beririjarak hadir sebagai tempat belajar dan bertumbuh
        bagi generasi muda. Kami berkomitmen memberikan pendidikan
        yang mendorong peserta didik untuk meraih prestasi,
        mengembangkan keterampilan, serta membentuk karakter yang baik.
    </p>

    <p class="text-secondary" style="line-height:1.9">
        Melalui lingkungan sekolah yang aman, nyaman, dan kondusif,
        kami mengajak setiap peserta didik untuk aktif belajar,
        percaya diri, berperilaku positif, dan mengembangkan potensi
        diri agar siap menghadapi masa depan.
    </p>

    <a href="#visi-misi" class="btn-utama">Lihat Visi dan Misi</a>
</div>

</div>
</div>
</section>

<!-- SAMBUTAN KEPALA SEKOLAH -->
<section class="section section-light" id="sambutan">
<div class="container">

<div class="text-center mb-5">
    <span class="section-label">Sambutan</span>
    <h2 class="section-title">Sambutan Kepala Sekolah</h2>
</div>

<div class="row justify-content-center">
<div class="col-lg-9">
<div class="info-card text-center">

<div class="feature-icon mx-auto">
    <i class="bi bi-chat-quote-fill"></i>
</div>

<p class="mb-3">
    Selamat datang di website resmi SDN 1 Beririjarak.
    Website ini diharapkan menjadi sarana informasi mengenai
    kegiatan sekolah, program pendidikan, prestasi siswa,
    dan berbagai informasi lainnya.
</p>

<p class="mb-0">
    Mari bekerja sama menciptakan pendidikan berkualitas
    serta membentuk peserta didik yang berkarakter,
    mandiri, dan berprestasi.
</p>

<h6 class="fw-bold text-primary mt-4 mb-0">
    Kepala SDN 1 Beririjarak
</h6>

<small class="text-muted">
    Nama kepala sekolah dapat ditambahkan di sini
</small>

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

<div class="visi-card mb-5 text-center">
    <i class="bi bi-eye-fill fs-1"></i>
    <h3 class="mt-3 mb-4">Visi Sekolah</h3>
    <p class="fs-5 mb-0">
        Menjadi sekolah yang menghasilkan lulusan
        <strong>CIKAL</strong>: Cerdas, Iman dan Taqwa,
        Kreatif, Amanah, dan peduli Lingkungan.
    </p>
</div>

<div class="text-center mb-4">
    <h3 class="section-title">Misi Sekolah</h3>
</div>

<div class="row g-4">

<div class="col-md-6">
<div class="info-card d-flex gap-3">
    <div class="misi-number">1</div>
    <div>
        <h5>Meningkatkan Iman dan Taqwa</h5>
        <p>Membina warga sekolah dalam peningkatan imtaq dan akhlak mulia.</p>
    </div>
</div>
</div>

<div class="col-md-6">
<div class="info-card d-flex gap-3">
    <div class="misi-number">2</div>
    <div>
        <h5>Mengembangkan Prestasi</h5>
        <p>Mendukung prestasi siswa sesuai bakat dan minat akademik maupun nonakademik.</p>
    </div>
</div>
</div>

<div class="col-md-6">
<div class="info-card d-flex gap-3">
    <div class="misi-number">3</div>
    <div>
        <h5>Mendorong Kreativitas</h5>
        <p>Mendorong siswa agar kreatif, aktif, dan berani berkarya.</p>
    </div>
</div>
</div>

<div class="col-md-6">
<div class="info-card d-flex gap-3">
    <div class="misi-number">4</div>
    <div>
        <h5>Membentuk Karakter Positif</h5>
        <p>Menumbuhkan disiplin, kesantunan, dan sikap saling menghargai.</p>
    </div>
</div>
</div>

<div class="col-md-6 mx-auto">
<div class="info-card d-flex gap-3">
    <div class="misi-number">5</div>
    <div>
        <h5>Peduli Lingkungan</h5>
        <p>Melatih siswa agar peduli terhadap kebersihan dan kelestarian lingkungan.</p>
    </div>
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
    <p class="section-subtitle">
        Fasilitas sekolah dapat disesuaikan dengan kondisi nyata sekolah.
    </p>
</div>

<div class="row g-4">

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-book-half"></i></div>
    <h5>Ruang Kelas</h5>
    <p>Tempat berlangsungnya kegiatan belajar mengajar siswa dan guru.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
    <h5>Perpustakaan</h5>
    <p>Sarana untuk mendukung minat baca dan menambah pengetahuan siswa.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card">
    <div class="feature-icon"><i class="bi bi-tree-fill"></i></div>
    <h5>Lingkungan Sekolah</h5>
    <p>Lingkungan yang mendukung kenyamanan, kebersihan, dan aktivitas siswa.</p>
</div>
</div>

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

<div class="col-md-4">
<div class="info-card text-center">
    <div class="feature-icon mx-auto"><i class="bi bi-flag-fill"></i></div>
    <h5>Pramuka</h5>
    <p>Mendukung kedisiplinan, kemandirian, dan kerja sama.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card text-center">
    <div class="feature-icon mx-auto"><i class="bi bi-music-note-beamed"></i></div>
    <h5>Seni dan Kreativitas</h5>
    <p>Mengembangkan bakat dan kreativitas siswa.</p>
</div>
</div>

<div class="col-md-4">
<div class="info-card text-center">
    <div class="feature-icon mx-auto"><i class="bi bi-trophy-fill"></i></div>
    <h5>Olahraga</h5>
    <p>Mendorong kebugaran, sportivitas, dan kerja sama tim.</p>
</div>
</div>

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
        <h5><?= aman($guru["nama_guru"]) ?></h5>
        <?php if (!empty($guru["jabatan"])): ?>
            <div class="jabatan-guru"><?= aman($guru["jabatan"]) ?></div>
        <?php endif; ?>
        <?php if (!empty($guru["pendidikan"])): ?>
            <p class="quote-guru"><?= aman($guru["pendidikan"]) ?></p>
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

<div class="col-md-4">
    <img class="gallery-image" src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=800&q=80" alt="Kegiatan belajar">
</div>

<div class="col-md-4">
    <img class="gallery-image" src="https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80" alt="Aktivitas pendidikan">
</div>

<div class="col-md-4">
    <img class="gallery-image" src="https://images.unsplash.com/photo-1580582932707-520aed937b7b?auto=format&fit=crop&w=800&q=80" alt="Gedung sekolah">
</div>

</div>

<p class="text-center text-muted small mt-4">
    Foto ilustrasi. Ganti dengan dokumentasi asli sekolah.
</p>

</div>
</section>

<?php
$daftarBerita = false;
if (isset($koneksi) && $koneksi) {
    $queryBerita = mysqli_query($koneksi, "SELECT judul, isi, gambar, tanggal_publikasi FROM tb_berita WHERE status='Terbit' AND tanggal_publikasi <= NOW() ORDER BY tanggal_publikasi DESC, id DESC LIMIT 6");
    if ($queryBerita) $daftarBerita = $queryBerita;
}
?>
<!-- BERITA -->
<section class="section" id="berita">
<div class="container">
<div class="text-center mb-5">
    <span class="section-label">Informasi Terbaru</span>
    <h2 class="section-title">Berita dan Kegiatan Sekolah</h2>
    <p class="section-subtitle">Informasi kegiatan dan pengumuman sekolah.</p>
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
        <i class="bi bi-newspaper text-primary" style="font-size:3rem"></i>
        <h5 class="mt-3 fw-bold">Belum ada berita yang diterbitkan</h5>
        <p class="text-secondary mb-0">Berita sekolah akan ditampilkan di sini setelah admin menerbitkannya.</p>
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
    src="https://maps.google.com/maps?q=SDN+2+Karang+Baru,+Desa+Beririjarak&t=&z=17&ie=UTF8&iwloc=&output=embed" 
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
</div>

</div>

<div class="footer-bottom text-center">
    &copy; KKN Desa Beririjarak 28 Universitas Hamzanwadi. Semua hak cipta dilindungi.
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

geserGuru(0);
</script>

</body>
</html>