<?php
session_start();

// 1. Proteksi halaman: pastikan hanya teacher yang bisa memproses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

// 2. Koneksi ke Database lms_demo
$host     = "localhost";
$username = "root";
$password = "";
$database = "lms_demo";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// 3. Ambil dan validasi data dari form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id      = intval($_POST['course_id']);
    $question       = trim($_POST['question']);
    $option_a       = trim($_POST['option_a']);
    $option_b       = trim($_POST['option_b']);
    $option_c       = trim($_POST['option_c']);
    $option_d       = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];

    // Pastikan data penting tidak kosong
    if (empty($course_id) || empty($question) || empty($correct_answer)) {
        echo "<script>alert('Semua data wajib diisi!'); window.history.back();</script>";
        exit();
    }

    // 4. Kueri Simpan Data Menggunakan Prepared Statement (Aman dari SQL Injection)
    $query = "INSERT INTO quizzes (course_id, question, option_a, option_b, option_c, option_d, correct_answer) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
              
    $stmt = mysqli_prepare($koneksi, $query);
    
    if ($stmt) {
        // "issssss" berarti: i = integer, s = string
        mysqli_stmt_bind_param($stmt, "issssss", $course_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_answer);
        
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, arahkan kembali ke dashboard guru dengan pesan sukses
            echo "<script>alert('Kuis otomatis berhasil dipublikasikan!'); window.location.href='dashboard_guru.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan kuis: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Gagal menyiapkan sistem database.'); window.history.back();</script>";
    }
} else {
    // Jika diakses langsung tanpa POST, kembalikan ke dashboard
    header("Location: dashboard_guru.php");
    exit();
}

mysqli_close($koneksi);
?>