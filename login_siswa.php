<?php
session_start();
include 'koneksi.php';

$pesan = "";
$tipe_pesan = "";

if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Ambil data siswa berdasarkan email di tabel pendaftaran_siswa
    $query  = "SELECT * FROM pendaftaran_siswa WHERE email_siswa = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $siswa = mysqli_fetch_assoc($result);

        // 1. Verifikasi Password
        if (password_verify($password, $password_siswa = $siswa['password_siswa'])) {
            
            // 2. CEK SYARAT UTAMA: Harus Lunas dan Aktif
            if ($siswa['payment_status'] == 'lunas' && $siswa['status_akun'] == 'Online / Aktif') {
                
                // Jika lunas & aktif, buat session dan izinkan masuk
                $_SESSION['siswa_id']   = $siswa['id_daftar'];
                $_SESSION['siswa_nama'] = $siswa['nama_lengkap'];
                $_SESSION['role']       = 'siswa';
                
                // Alihkan ke dashboard belajar siswa
                header("Location: dashboard_siswa.php");
                exit();

            } else {
                // Jika password benar tapi BELUM LUNAS / BELUM DIKONFIRMASI
                $pesan = "⚠️ Akun Anda belum aktif! Akses ditolak karena pembayaran Anda belum lunas atau belum dikonfirmasi oleh Pengajar. Silakan hubungi admin/pengajar.";
                $tipe_pesan = "peringatan";
            }

        } else {
            $pesan = "❌ Kata sandi yang Anda masukkan salah!";
            $tipe_pesan = "error";
        }
    } else {
        $pesan = "❌ Email tidak terdaftar di kelas kursus manapun!";
        $tipe_pesan = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Siswa - Kursus Premium</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { max-width: 400px; width: 100%; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #2c3e50; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="email"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background-color: #27ae60; color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; }
        .btn:hover { background-color: #219653; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; font-weight: bold; line-height: 1.5; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-peringatan { background-color: #fff3cd; color: #856404; border-left: 5px solid #ffc107; }
        .footer-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .footer-link a { color: #2980b9; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-box">
    <h2>🔑 Login Siswa Kursus</h2>

    <?php if ($pesan != ""): ?>
        <div class="alert alert-<?php echo $tipe_pesan; ?>"><?php echo $pesan; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="email">Email Terdaftar:</label>
            <input type="email" id="email" name="email" placeholder="contoh@email.com" required>
        </div>
        <div class="form-group">
            <label for="password">Kata Sandi:</label>
            <input type="password" id="password" name="password" placeholder="Masukkan kata sandi Anda" required>
        </div>
        <button type="submit" name="login" class="btn">MASUK KE KELAS</button>
    </form>

    <div class="footer-link">
        Belum punya akun? <a href="daftar_kursus.php">Daftar Kursus di Sini</a>
    </div>
</div>

</body>
</html>