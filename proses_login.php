<?php
// 1. Memulai session untuk menyimpan data login user
session_start();

// 2. Konfigurasi koneksi ke database lms_demo
$host     = "localhost";
$username = "root";
$password = "";
$database = "lms_demo";

$koneksi = mysqli_connect($host, $username, $password, $database);

// Periksa apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// 3. Memproses data saat tombol "Masuk Sekarang" diklik
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Mengambil data dari form login.html dan mengamankannya dari SQL Injection
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    // 4. Query untuk mencari user berdasarkan email dan password
    $query  = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($koneksi, $query);

    // Periksa apakah user ditemukan
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Menyimpan data user ke dalam session
        $_SESSION['id']    = $user['id'];
        $_SESSION['nama']  = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role']  = $user['role'];

        // 5. Pengalihan halaman (Redirect) berdasarkan Role
        if ($user['role'] == 'admin') {
            header("Location: dashboard_admin.php");
        } elseif ($user['role'] == 'teacher') {
            header("Location: dashboard_guru.php");
        } elseif ($user['role'] == 'student') {
            header("Location: dashboard_siswa.php");
        } else {
            // Jika role tidak dikenal, arahkan ke halaman utama/default
            header("Location: index.php");
        }
        exit();
    } else {
        // Jika login gagal, tampilkan notifikasi dan kembali ke login.html
        echo "<script>
                alert('Email atau Password salah! Silakan coba lagi.');
                window.location.href = 'login.html';
              </script>";
    }
}
?>