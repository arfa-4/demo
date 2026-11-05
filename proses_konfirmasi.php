<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.html");
    exit();
}

$koneksi = mysqli_connect("localhost", "root", "", "lms_demo");

if (isset($_GET['course_id'])) {
    $student_id = $_SESSION['id'];
    $course_id  = intval($_GET['course_id']);

    // Cek apakah data pendaftaran ganda untuk menghindari submit berulang
    $cek = mysqli_query($koneksi, "SELECT id FROM enrollments WHERE student_id = $student_id AND course_id = $course_id");
    
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        // Jika sudah ada, langsung lempar ke invoice lama
        header("Location: invoice.php?enrollment_id=" . $data['id']);
        exit();
    }

    // Jalankan perintah INSERT dengan status 'pending' sesuai konsep langkah 1
    $query = "INSERT INTO enrollments (student_id, course_id, status, created_at) 
              VALUES ($student_id, $course_id, 'pending', NOW())";

    if (mysqli_query($koneksi, $query)) {
        // Ambil ID pendaftaran yang baru saja dimasukkan
        $new_enrollment_id = mysqli_insert_id($koneksi);
        
        // Alihkan ke invoice.php membawa ID pendaftarannya
        header("Location: invoice.php?enrollment_id=" . $new_enrollment_id);
        exit();
    } else {
        echo "Gagal memproses pendaftaran: " . mysqli_error($koneksi);
    }
} else {
    echo "Aksi tidak valid.";
}
?>