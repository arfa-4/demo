<?php
session_start();

// 1. Proteksi halaman: pastikan hanya student yang bisa mengakses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
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

$student_id = $_SESSION['id'];
$pesan_skor = "";

// 3. LOGIKA PROSES JAWABAN (Saat siswa klik Kirim Jawaban)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['jawab_kuis'])) {
    $quiz_id        = intval($_POST['quiz_id']);
    $jawaban_siswa  = $_POST['jawaban'];
    $course_id      = intval($_POST['course_id']);

    // Ambil kunci jawaban asli dari database
    $q_kunci = "SELECT correct_answer FROM quizzes WHERE id = ?";
    $stmt_kunci = mysqli_prepare($koneksi, $q_kunci);
    mysqli_stmt_bind_param($stmt_kunci, "i", $quiz_id);
    mysqli_stmt_execute($stmt_kunci);
    $res_kunci = mysqli_stmt_get_result($stmt_kunci);
    $data_kunci = mysqli_fetch_assoc($res_kunci);

    if ($data_kunci) {
        // Hitung skor otomatis (Jika benar score 100, salah 0 karena berbasis per soal)
        // Catatan: Jika ingin kuis multi-soal sekaligus, logikanya tinggal menggunakan perulangan (loop).
        $skor = ($jawaban_siswa === $data_kunci['correct_answer']) ? 100 : 0;
        
        // Simpan hasil ke tabel submissions (Diset sebagai penugasan kuis/evaluasi otomatis)
        // Kita berasumsi kuis ini masuk ke pelaporan kinerja utama Anda
        $status_tugas = "Kuis Otomatis (ID: $quiz_id) - Jawaban Anda: $jawaban_siswa";
        
        // Cek apakah siswa sudah pernah mengerjakan kuis ini sebelumnya di submissions
        $q_simpan = "INSERT INTO submissions (student_id, assignment_id, grade, feedback) VALUES (?, ?, ?, ?)";
        $stmt_simpan = mysqli_prepare($koneksi, $q_simpan);
        
        // Menggunakan assignment_id default/dummy untuk penanda Kuis (misal: ID 999 atau disesuaikan)
        $quiz_assignment_marker = 999; 
        mysqli_stmt_bind_param($stmt_simpan, "iiis", $student_id, $quiz_assignment_marker, $skor, $status_tugas);
        
        if (mysqli_stmt_execute($stmt_simpan)) {
            if ($skor === 100) {
                $pesan_skor = "<div class='alert alert-success'>🎉 <strong>Luar Biasa!</strong> Jawaban Anda BENAR. Skor Anda: <strong>100</strong>. Nilai otomatis disimpan ke sistem.</div>";
            } else {
                $pesan_skor = "<div class='alert alert-danger'>❌ <strong>Kurang Tepat!</strong> Jawaban Anda SALAH (Kunci yang benar: ".$data_kunci['correct_answer']."). Skor Anda: <strong>0</strong>. Tetap semangat!</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Ujian Kuis - LMS Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #007bff; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        .quiz-question { font-size: 16px; font-weight: bold; margin-bottom: 15px; background: #f8f9fa; padding: 15px; border-left: 5px solid #007bff; border-radius: 4px; }
        .option-radio { margin-bottom: 12px; display: flex; align-items: center; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
        .option-radio:hover { background: #f1f3f5; }
        .option-radio input { margin-right: 10px; transform: scale(1.2); }
        .btn-submit { background: #28a745; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; font-size: 15px; font-weight: bold; width: 100%; }
        .btn-submit:hover { background: #218838; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 15px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .meta-info { font-size: 12px; color: #666; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>LMS Demo - Menu Evaluasi Siswa</h2>
    <div>
        <span>Siswa: <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong></span>
    </div>
</div>

<div class="container">
    
    <!-- Menampilkan Hasil Koreksi Instan -->
    <?php echo $pesan_skor; ?>

    <div class="card">
        <h3>📝 Kuis Pilihan Ganda Hari Ini</h3>
        
        <?php
        // Ambil kuis terbaru yang ada di database lms_demo
        $query_quiz = "SELECT q.*, c.title AS course_title FROM quizzes q 
                       JOIN courses c ON q.course_id = c.id 
                       ORDER BY q.id DESC LIMIT 1";
        $res_quiz = mysqli_query($koneksi, $query_quiz);

        if (mysqli_num_rows($res_quiz) > 0) {
            $quiz = mysqli_fetch_assoc($res_quiz);
            ?>
            <div class="meta-info">Mata Pelajaran: <strong><?php echo htmlspecialchars($quiz['course_title']); ?></strong> | Dipublikasikan pada: <?php echo $quiz['created_at']; ?></div>
            
            <form action="" method="POST">
                <!-- Kirim data tersembunyi -->
                <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                <input type="hidden" name="course_id" value="<?php echo $quiz['course_id']; ?>">

                <div class="quiz-question">
                    <?php echo nl2br(htmlspecialchars($quiz['question'])); ?>
                </div>

                <label class="option-radio">
                    <input type="radio" name="jawaban" value="A" required>
                    <strong>A.</strong> &nbsp; <?php echo htmlspecialchars($quiz['option_a']); ?>
                </label>

                <label class="option-radio">
                    <input type="radio" name="jawaban" value="B">
                    <strong>B.</strong> &nbsp; <?php echo htmlspecialchars($quiz['option_b']); ?>
                </label>

                <label class="option-radio">
                    <input type="radio" name="jawaban" value="C">
                    <strong>C.</strong> &nbsp; <?php echo htmlspecialchars($quiz['option_c']); ?>
                </label>

                <label class="option-radio">
                    <input type="radio" name="jawaban" value="D">
                    <strong>D.</strong> &nbsp; <?php echo htmlspecialchars($quiz['option_d']); ?>
                </label>

                <br>
                <button type="submit" name="jawab_kuis" class="btn-submit">Kirim & Periksa Jawaban Saya</button>
            </form>
            <?php
        } else {
            echo '<p style="text-align:center; color:#999; padding: 20px 0;">Belum ada kuis otomatis yang diterbitkan oleh guru saat ini.</p>';
        }
        ?>
    </div>
</div>

</body>
</html>