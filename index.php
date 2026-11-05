<?php
session_start();
include 'koneksi.php';

// Ambil daftar kursus yang tersedia untuk dijual dari database
$ambil_kelas = mysqli_query($conn, "SELECT * FROM courses");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - LMS Demo</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f4f9; color: #333; margin: 0; padding: 0; }
        
        /* Menu Bar Atas */
        .nav-top { background: #2c3e50; padding: 15px 30px; text-align: right; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .nav-brand { color: white; font-weight: bold; text-decoration: none; font-size: 16px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; font-size: 14px; }
        .nav-top .btn-reg { background: #18bc9c; padding: 8px 15px; border-radius: 4px; transition: 0.3s; }
        .nav-top .btn-reg:hover { background: #149a80; }

        /* Banner Hero */
        .hero { background: linear-gradient(135deg, #2c3e50, #18bc9c); color: white; text-align: center; padding: 50px 20px; }
        .hero h1 { margin: 0; font-size: 32px; }
        .hero p { font-size: 16px; opacity: 0.9; margin-top: 10px; }
        
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        .section-title { text-align: center; margin-bottom: 40px; color: #2c3e50; }
        
        /* Status Login Siswa */
        .login-status { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; margin-bottom: 40px; }
        
        /* Grid Kartu Kelas */
        .course-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .course-card { background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s; }
        .course-card:hover { transform: translateY(-5px); }
        .course-badge { background-color: #e67e22; color: white; padding: 5px 10px; font-size: 12px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-bottom: 10px; border-radius: 3px; }
        .course-body { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .course-title { margin: 0 0 10px 0; color: #2c3e50; font-size: 22px; }
        .course-desc { color: #7f8c8d; font-size: 15px; line-height: 1.5; flex-grow: 1; }
        
        /* Bagian Harga & Tombol Aksi */
        .course-footer { border-top: 1px solid #f1f2f6; padding: 20px 25px; background-color: #fafbfc; display: flex; align-items: center; justify-content: space-between; }
        .price { font-size: 20px; font-weight: bold; color: #27ae60; }
        .btn { display: inline-block; padding: 10px 20px; color: white; background-color: #007bff; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; transition: 0.3s; }
        .btn:hover { background-color: #0056b3; }
        .btn-beli { background-color: #2980b9; }
        .btn-beli:hover { background-color: #3498db; }
    </style>
</head>
<body>

<!-- Menu Navigasi Dinamis -->
<div class="nav-top">
    <a href="index.php" class="nav-brand">🏠 LMS Demo</a>
    <div class="nav-links">
        <?php if(isset($_SESSION['role'])): ?>
            <span style="color: #18bc9c; font-weight: bold; margin-right: 15px;">Role: <?php echo strtoupper($_SESSION['role']); ?></span>
            <a href="logout.php">❌ Keluar (Logout)</a>
        <?php else: ?>
            <a href="login_siswa.php">🔑 Login Siswa</a>
            <a href="login_guru.php">👨‍🏫 Login Guru</a>
            <a href="login_admin.php">🛠️ Admin</a>
            <a href="daftar_kursus.php" class="btn-reg">📝 Daftar Kursus Baru</a>
        <?php endif; ?>
    </div>
</div>

<!-- Banner Selamat Datang -->
<div class="hero">
    <h1>Selamat Datang di LMS Demo</h1>
    <p>Platform Pembelajaran Digital Praktis & Kelas Kursus Premium.</p>
</div>

<div class="container">

    <!-- Blok Deteksi Status Login Multirole -->
    <?php if(isset($_SESSION['role'])): ?>
        <div class="login-status">
            <?php
                // Tentukan nama tampilan berdasarkan data session yang tersedia
                $nama_user = $_SESSION['siswa_nama'] ?? $_SESSION['nama'] ?? 'User';
                
                // Menentukan link file tujuan dashboard
                $role = strtolower($_SESSION['role']);
                if ($role === 'teacher' || $role === 'guru') {
                    $dashboard_link = 'dashboard_guru.php';
                    $role_text = 'Guru';
                } elseif ($role === 'admin') {
                    $dashboard_link = 'dashboard_admin.php';
                    $role_text = 'Administrator';
                } else {
                    $dashboard_link = 'dashboard_siswa.php';
                    $role_text = 'Siswa';
                }
            ?>
            <p style="margin: 0 0 15px 0; font-size: 16px;">
                Anda sudah masuk sebagai <strong><?php echo htmlspecialchars($nama_user); ?></strong> (<?php echo $role_text; ?>)
            </p>
            <a href="<?php echo $dashboard_link; ?>" class="btn">Ke Dashboard Utama &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- Katalog Penjualan Kelas -->
    <h2 class="section-title">Katalog Kelas Kursus Terbaik Kami</h2>
    
    <div class="course-grid">
        <?php 
        if ($ambil_kelas && mysqli_num_rows($ambil_kelas) > 0):
            while($kelas = mysqli_fetch_assoc($ambil_kelas)): 
                // Mengantisipasi perbedaan nama kolom di DB (course_name atau title)
                $nama_kelas = $kelas['course_name'] ?? $kelas['title'] ?? 'Kelas Tanpa Nama';
                $level_kelas = $kelas['level'] ?? 'Umum';
                $deskripsi_kelas = $kelas['description'] ?? 'Tidak ada deskripsi.';
        ?>
            <!-- Kartu Informasi Kelas -->
            <div class="course-card">
                <div class="course-body">
                    <span class="course-badge">⭐ <?php echo htmlspecialchars($level_kelas); ?></span>
                    <h3 class="course-title"><?php echo htmlspecialchars($nama_kelas); ?></h3>
                    <p class="course-desc"><?php echo htmlspecialchars($deskripsi_kelas); ?></p>
                </div>
                <div class="course-footer">
                    <span class="price">Rp 250.000</span>
                    <a href="daftar_kursus.php?pilih=<?php echo $kelas['id']; ?>" class="btn btn-beli">Daftar Kelas &rarr;</a>
                </div>
            </div>
        <?php 
            endwhile;
        else:
        ?>
            <p style="text-align: center; grid-column: 1/-1; color: #7f8c8d; background: white; padding: 30px; border-radius: 8px;">
                Maaf, saat ini belum ada data kelas kursus yang tersedia di database.
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>