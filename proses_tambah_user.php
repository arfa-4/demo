<?php
session_start();

// 1. Koneksi ke Database lms_demo
$host     = "localhost";
$username = "root";
$password = "";
$database = "lms_demo";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 2. Memastikan data dikirim melalui metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Mengambil dan membersihkan data inputan untuk mencegah SQL Injection awal
    $nama  = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    $email = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    $pass  = trim($_POST['password']);
    $role  = mysqli_real_escape_string($koneksi, trim($_POST['role'])); // e.g., 'student' atau 'teacher'

    // Validation minimal: Pastikan semua data terisi
    if (empty($nama) || empty($email) || empty($pass) || empty($role)) {
        echo "<script>alert('Semua data wajib diisi!'); window.history.back();</script>";
        exit();
    }

    // 3. Cek apakah email sudah terdaftar sebelumnya
    $cek_email = "SELECT id FROM users WHERE email = ?";
    $stmt_cek  = mysqli_prepare($koneksi, $cek_email);
    
    if ($stmt_cek) {
        mysqli_stmt_bind_param($stmt_cek, "s", $email);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);
        
        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            echo "<script>alert('Email sudah terdaftar! Gunakan email lain.'); window.history.back();</script>";
            mysqli_stmt_close($stmt_cek);
            exit();
        }
        mysqli_stmt_close($stmt_cek);
    }

    // 4. Enkripsi password demi keamanan (Best Practice)
    $password_aman = password_hash($pass, PASSWORD_BCRYPT);

    // 5. Query Insert menggunakan Prepared Statements
    $query_insert = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt_insert  = mysqli_prepare($koneksi, $query_insert);

    if ($stmt_insert) {
        mysqli_stmt_bind_param($stmt_insert, "ssss", $nama, $email, $password_aman, $role);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            // Jika berhasil, arahkan ke halaman manajemen user atau dashboard
            echo "<script>alert('User baru berhasil ditambahkan!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menambahkan user: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        mysqli_stmt_close($stmt_insert);
    } else {
        echo "<script>alert('Gagal menyiapkan query template.'); window.history.back();</script>";
    }

} else {
    // Jika file diakses langsung tanpa method POST, kembalikan ke dashboard
    header("Location: dashboard_guru.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>