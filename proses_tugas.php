<?php
session_start();

// 1. Proteksi halaman: pastikan hanya teacher yang bisa mengakses proses ini
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

// 3. Proses data ketika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id   = intval($_POST['course_id']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date']; // Mengambil data datetime-local

    // Validasi input sederhana agar tidak ada field yang kosong
    if (empty($course_id) || empty($title) || empty($description) || empty($due_date)) {
        echo "<script>alert('Semua data penugasan wajib diisi!'); window.history.back();</script>";
        exit();
    }

    // 4. Otomatis buat tabel 'assignments' jika belum ada di database
    $sql_create_table = "CREATE TABLE IF NOT EXISTS assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        due_date DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )";
    mysqli_query($koneksi, $sql_create_table);

    // FIX: Cek apakah kolom 'description' sudah ada, jika belum, tambahkan secara otomatis
    $check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM assignments LIKE 'description'");
    if (mysqli_num_rows($check_column) == 0) {
        mysqli_query($koneksi, "ALTER TABLE assignments ADD COLUMN description TEXT NOT NULL AFTER title");
    }

    // 5. Insert data tugas baru menggunakan Prepared Statement
    $query_insert = "INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query_insert);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $course_id, $title, $description, $due_date);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Tugas / Ujian baru berhasil dipublikasikan!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menyimpan tugas: " . mysqli_stmt_error($stmt) . "'); window.history.back();</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('Gagal menyiapkan sistem database: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
}

// Tutup koneksi database
mysqli_close($koneksi);
?>