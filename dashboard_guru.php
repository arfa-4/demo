<?php
session_start();

// 1. Proteksi halaman: jika bukan teacher, tendang kembali ke login
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

// Ambil ID guru yang sedang login
$teacher_id = $_SESSION['id'];

// ====== LOGIKA PROSES SIMPAN MATERI BARU ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_materi'])) {
    $course_id   = intval($_POST['course_id']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $file_path   = trim($_POST['file_path']);

    $query_insert = "INSERT INTO materials (course_id, title, description, file_path) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query_insert);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $course_id, $title, $description, $file_path);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Materi baru berhasil disimpan!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menyimpan materi: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// ====== LOGIKA PROSES SIMPAN PENUGASAN BARU ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_tugas'])) {
    $course_id   = intval($_POST['course_id']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date    = $_POST['due_date'];

    $query_insert = "INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query_insert);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $course_id, $title, $description, $due_date);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Penugasan berhasil dipublish!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal mempublish penugasan: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// ====== LOGIKA PROSES SIMPAN KUIS BARU ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_kuis'])) {
    $course_id      = intval($_POST['course_id']);
    $question       = trim($_POST['question']);
    $option_a       = trim($_POST['option_a']);
    $option_b       = trim($_POST['option_b']);
    $option_c       = trim($_POST['option_c']);
    $option_d       = trim($_POST['option_d']);
    $correct_answer = $_POST['correct_answer'];

    // Pastikan tabel kuis ada
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS quizzes (id INT AUTO_INCREMENT PRIMARY KEY, course_id INT, question TEXT, option_a VARCHAR(255), option_b VARCHAR(255), option_c VARCHAR(255), option_d VARCHAR(255), correct_answer CHAR(1))");

    $query_insert = "INSERT INTO quizzes (course_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query_insert);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "issssss", $course_id, $question, $option_a, $option_b, $option_c, $option_d, $correct_answer);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Kuis otomatis berhasil diterbitkan!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menerbitkan kuis: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// ====== LOGIKA PROSES SIMPAN FEEDBACK & NILAI ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_feedback'])) {
    $submission_id = intval($_POST['submission_id']);
    $grade         = intval($_POST['grade']);
    $feedback      = trim($_POST['feedback']);

    $query_update = "UPDATE submissions SET grade = ?, feedback = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query_update);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isi", $grade, $feedback, $submission_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Nilai & Feedback berhasil disimpan!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menyimpan penilaian: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// ====== LOGIKA PROSES UPDATE MATERI (EDIT) ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_materi'])) {
    $materi_id   = intval($_POST['materi_id']);
    $course_id   = intval($_POST['course_id']);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $file_path   = trim($_POST['file_path']);

    $query_update_materi = "UPDATE materials SET course_id = ?, title = ?, description = ?, file_path = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query_update_materi);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssi", $course_id, $title, $description, $file_path, $materi_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Materi berhasil diperbarui!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal memperbarui materi!');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// ====== LOGIKA PROSES HAPUS DATA VIA GET ======
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_target = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'hapus_materi') {
        mysqli_query($koneksi, "DELETE FROM materials WHERE id = $id_target");
        echo "<script>alert('Materi berhasil dihapus!'); window.location.href='dashboard_guru.php';</script>";
    } elseif ($action === 'hapus_tugas') {
        mysqli_query($koneksi, "DELETE FROM assignments WHERE id = $id_target");
        echo "<script>alert('Penugasan berhasil dihapus!'); window.location.href='dashboard_guru.php';</script>";
    } elseif ($action === 'hapus_kuis') {
        mysqli_query($koneksi, "DELETE FROM quizzes WHERE id = $id_target"); 
        echo "<script>alert('Kuis berhasil dihapus!'); window.location.href='dashboard_guru.php';</script>";
    }
}

// ====== LOGIKA VALIDASI PEMBAYARAN MANUAL ======
if (isset($_GET['action']) && $_GET['action'] === 'setujui' && isset($_GET['enrollment_id'])) {
    $enroll_id = intval($_GET['enrollment_id']);
    $query_approve = "UPDATE enrollments SET status = 'active' WHERE id = $enroll_id";
    
    if (mysqli_query($koneksi, $query_approve)) {
        echo "<script>alert('Pembayaran sukses disetujui! Akses kelas siswa telah aktif.'); window.location.href='dashboard_guru.php';</script>";
        exit();
    } else {
        echo "<script>alert('Gagal menyetujui pembayaran: " . mysqli_error($koneksi) . "');</script>";
    }
}

// ====== LOGIKA PROSES KIRIM NOTIFIKASI / PENGUMUMAN MASSAL ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kirim_pengumuman'])) {
    $course_id = mysqli_real_escape_string($koneksi, $_POST['course_id']);
    $pesan     = trim($_POST['pesan']);

    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id VARCHAR(50) NOT NULL,
        teacher_id INT NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $query_announcement = "INSERT INTO announcements (course_id, teacher_id, message) VALUES (?, ?, ?)";
    $stmt_ann = mysqli_prepare($koneksi, $query_announcement);
    if ($stmt_ann) {
        mysqli_stmt_bind_param($stmt_ann, "sis", $course_id, $teacher_id, $pesan);
        if (mysqli_stmt_execute($stmt_ann)) {
            echo "<script>alert('Notifikasi Blast berhasil dikirim ke seluruh siswa!'); window.location.href='dashboard_guru.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal mengirim pengumuman: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt_ann);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - LMS Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #28a745; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .logout { color: white; text-decoration: none; font-weight: bold; background: #218838; padding: 6px 12px; border-radius: 4px; }
        .logout:hover { background: #1e7e34; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; }
        @media (max-width: 992px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #28a745; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .option-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 5px; }
        
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn-submit:hover { background: #218838; }
        .btn-accent { background: #17a2b8; }
        .btn-accent:hover { background: #138496; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-purple { background: #6f42c1; color: white; }
        .btn-purple:hover { background: #5a32a3; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        table th { background: #f1f3f5; color: #555; }
        
        .badge { padding: 3px 6px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        
        .analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; }
        .analytics-box { background: #f8f9fa; padding: 12px; border-radius: 6px; border-left: 4px solid #28a745; }
        .analytics-box h4 { margin: 0; font-size: 13px; color: #666; }
        .analytics-box p { margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: #333; }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 8px; width: 100%; max-width: 500px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #28a745; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
        .btn-close { background: #6c757d; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        
        .action-link { font-weight: bold; text-decoration: none; font-size: 12px; margin-right: 5px; }
        .action-edit { color: #17a2b8; }
        .action-delete { color: #dc3545; }
        .btn-approve-sm { background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 11px; }
        .btn-approve-sm:hover { background: #218838; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>LMS Demo - Ruang Guru</h2>
    <div>
        <span style="margin-right: 15px;">Selamat mengajar, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong></span>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    
    <!-- KOLOM KIRI (INPUT DAN MANAJEMEN DATA) -->
    <div>
        <!-- 1. MANAJEMEN KURSUS -->
        <div class="card">
            <h3>🛠️ 1. Manajemen Kursus & Struktur Materi</h3>
            <form action="dashboard_guru.php" method="POST">
                <div class="form-group">
                    <label>Pilih Kursus / Mata Pelajaran:</label>
                    <select name="course_id" required>
                        <option value="">-- Pilih Kursus --</option>
                        <?php
                        $query_c = "SELECT * FROM courses";
                        $res_c = mysqli_query($koneksi, $query_c);
                        while($row_c = mysqli_fetch_assoc($res_c)) {
                            echo "<option value='".$row_c['id']."'>".htmlspecialchars($row_c['title'])."</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Judul Materi Pembelajaran:</label>
                    <input type="text" name="title" placeholder="Contoh: Bab 2 - Mengenal Pemrograman Objek" required>
                </div>
                <div class="form-group">
                    <label>Struktur Deskripsi / Konten Teks:</label>
                    <textarea name="description" rows="3" placeholder="Tulis rangkuman, instruksi teks, atau link video materi di sini..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Nama Berkas / Path File Lampiran:</label>
                    <input type="text" name="file_path" placeholder="Contoh: bab2_oop.pdf" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="simpan_materi" class="btn-submit">Simpan Materi</button>
                    <a href="kelola_kursus.php" class="btn-submit btn-accent" style="text-align:center;">Edit Struktur Kursus</a>
                </div>
            </form>
        </div>

        <!-- LIST MONITORING & EDIT MATERI -->
        <div class="card">
            <h3>📋 Daftar & Kelola Materi Tersimpan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mata Pelajaran</th>
                        <th>Judul Materi</th>
                        <th>Lampiran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_mat = "SELECT m.*, c.title AS course_title FROM materials m JOIN courses c ON m.course_id = c.id ORDER BY m.id DESC";
                    $r_mat = mysqli_query($koneksi, $q_mat);
                    if ($r_mat && mysqli_num_rows($r_mat) > 0) {
                        while ($mat = mysqli_fetch_assoc($r_mat)) {
                            ?>
                            <tr>
                                <td><small><strong><?php echo htmlspecialchars($mat['course_title']); ?></strong></small></td>
                                <td><?php echo htmlspecialchars($mat['title']); ?></td>
                                <td><code style="background:#eef;padding:2px 4px;"><?php echo htmlspecialchars($mat['file_path']); ?></code></td>
                                <td>
                                    <button onclick="openEditMateriModal(<?php echo $mat['id']; ?>, <?php echo $mat['course_id']; ?>, '<?php echo htmlspecialchars($mat['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mat['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($mat['file_path'], ENT_QUOTES); ?>')", class="action-link action-edit" style="background:none;border:none;cursor:pointer;padding:0;">Edit</button> | 
                                    <a href="dashboard_guru.php?action=hapus_materi&id=<?php echo $mat['id']; ?>" class="action-link action-delete" onclick="return confirm('Hapus materi ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;color:#999;'>Belum ada materi terbit.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- 3. BUAT TUGAS BARU -->
        <div class="card">
            <h3>📅 Buat Penugasan & Ujian Baru</h3>
            <form action="dashboard_guru.php" method="POST">
                <div class="form-group">
                    <label>Pilih Kursus:</label>
                    <select name="course_id" required>
                        <option value="">-- Pilih Kursus --</option>
                        <?php
                        if(mysqli_num_rows($res_c) > 0) {
                            mysqli_data_seek($res_c, 0);
                            while($row_c = mysqli_fetch_assoc($res_c)) {
                                echo "<option value='".$row_c['id']."'>".htmlspecialchars($row_c['title'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Judul Tugas / Ujian:</label>
                    <input type="text" name="title" placeholder="Contoh: Ujian Tengah Semester (UTS)" required>
                </div>
                <div class="form-group">
                    <label>Instruksi Pengerjaan:</label>
                    <textarea name="description" rows="2" placeholder="Tulis instruksi evaluasi di sini..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Batas Waktu Pengumpulan (Deadline):</label>
                    <input type="datetime-local" name="due_date" required>
                </div>
                <button type="submit" name="simpan_tugas" class="btn-submit btn-accent">Publish Penugasan</button>
            </form>
        </div>

        <!-- BUAT KUIS OTOMATIS -->
        <div class="card">
            <h3>📝 Pembuat Kuis Otomatis (Pilihan Ganda)</h3>
            <form action="dashboard_guru.php" method="POST">
                <div class="form-group">
                    <label>Pilih Kursus:</label>
                    <select name="course_id" required>
                        <option value="">-- Pilih Kursus --</option>
                        <?php
                        if(mysqli_num_rows($res_c) > 0) {
                            mysqli_data_seek($res_c, 0);
                            while($row_c = mysqli_fetch_assoc($res_c)) {
                                echo "<option value='".$row_c['id']."'>".htmlspecialchars($row_c['title'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pertanyaan / Soal:</label>
                    <textarea name="question" rows="2" placeholder="Tulis pertanyaan kuis di sini..." required></textarea>
                </div>
                <div class="form-group">
                    <label>Pilihan Jawaban:</label>
                    <div class="option-group">
                        <input type="text" name="option_a" placeholder="Pilihan A" required>
                        <input type="text" name="option_b" placeholder="Pilihan B" required>
                        <input type="text" name="option_c" placeholder="Pilihan C" required>
                        <input type="text" name="option_d" placeholder="Pilihan D" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kunci Jawaban Benar:</label>
                    <select name="correct_answer" required>
                        <option value="">-- Pilih Kunci Jawaban --</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <button type="submit" name="simpan_kuis" class="btn-submit btn-purple">Publish Kuis Otomatis</button>
            </form>
        </div>
    </div>

    <!-- KOLOM KANAN (MONITORING DATA & HASIL SISWA) -->
    <div>
        <!-- 💳 VALIDASI PEMBAYARAN MANUAL KELAS PREMIUM -->
        <div class="card" style="border: 2px solid #28a745;">
            <h3>💳 Validasi Pembayaran Manual Kelas Premium</h3>
            <table>
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas Premium</th>
                        <th>Status</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_pending = "SELECT e.id AS enroll_id, u.nama AS student_name, c.title AS course_title, e.status 
                                      FROM enrollments e
                                      JOIN users u ON e.student_id = u.id
                                      JOIN courses c ON e.course_id = c.id
                                      WHERE e.status = 'pending'
                                      ORDER BY e.id DESC";
                    $res_pending = mysqli_query($koneksi, $query_pending);

                    if ($res_pending && mysqli_num_rows($res_pending) > 0) {
                        while ($pnd = mysqli_fetch_assoc($res_pending)) {
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($pnd['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($pnd['course_title']); ?></td>
                                <td><span class="badge badge-warning"><?php echo $pnd['status']; ?></span></td>
                                <td>
                                    <a href="dashboard_guru.php?action=setujui&enrollment_id=<?php echo $pnd['enroll_id']; ?>" class="btn-approve-sm" onclick="return confirm('Konfirmasi bahwa siswa ini telah membayar transfer?')">✓ Setujui</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; color:#777; padding: 15px;'>Alhamdulillah, tidak ada antrean pembayaran saat ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- MONITORING TUGAS & KUIS TERBIT -->
        <div class="card">
            <h3>📌 Pemantauan Penugasan & Kuis Terbit</h3>
            <div style="margin-bottom:15px;">
                <strong>Daftar Tugas Saat Ini:</strong>
                <table>
                    <tbody>
                        <?php
                        $q_asg = "SELECT a.*, c.title AS c_title FROM assignments a JOIN courses c ON a.course_id = c.id ORDER BY a.id DESC";
                        $r_asg = mysqli_query($koneksi, $q_asg);
                        if(mysqli_num_rows($r_asg) > 0){
                            while($asg = mysqli_fetch_assoc($r_asg)){
                                echo "<tr><td><strong>[".$asg['c_title']."]</strong> ".$asg['title']."</td>
                                      <td><a href='dashboard_guru.php?action=hapus_tugas&id=".$asg['id']."' class='action-delete' onclick='return confirm(\"Hapus penugasan ini?\")'>Hapus</a></td></tr>";
                            }
                        } else { echo "<tr><td style='color:#999;text-align:center;'>Belum ada tugas dibuat.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div>
                <strong>Daftar Kuis Aktif:</strong>
                <table>
                    <tbody>
                        <?php
                        $q_qz = "SELECT q.*, c.title AS c_title FROM quizzes q JOIN courses c ON q.course_id = c.id ORDER BY q.id DESC";
                        $r_qz = mysqli_query($koneksi, $q_qz);
                        if(mysqli_num_rows($r_qz) > 0){
                            while($qz = mysqli_fetch_assoc($r_qz)){
                                echo "<tr><td><strong>[".$qz['c_title']."]</strong> ".substr(htmlspecialchars($qz['question']), 0, 60)."...</td>
                                      <td><a href='dashboard_guru.php?action=hapus_kuis&id=".$qz['id']."' class='action-delete' onclick='return confirm(\"Hapus kuis ini?\")'>Hapus</a></td></tr>";
                            }
                        } else { echo "<tr><td style='color:#999;text-align:center;'>Belum ada kuis dibuat.</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. LAPORAN KINERJA -->
        <div class="card">
            <h3>📊 Laporan Kinerja & Keterlibatan</h3>
            <div class="analytics-grid">
                <div class="analytics-box">
                    <h4>Total Peserta Aktif</h4>
                    <p>
                        <?php
                        $q_total_mhs = "SELECT COUNT(*) as total FROM users WHERE role='student'";
                        $r_total_mhs = mysqli_query($koneksi, $q_total_mhs);
                        $d_total_mhs = mysqli_fetch_assoc($r_total_mhs);
                        echo $d_total_mhs['total'] . " Siswa";
                        ?>
                    </p>
                </div>
                <div class="analytics-box" style="border-left-color: #17a2b8;">
                    <h4>Rasio Kelulusan / Evaluasi</h4>
                    <p>
                        <?php
                        $check_column = mysqli_query($koneksi, "SHOW COLUMNS FROM submissions LIKE 'grade'");
                        if(mysqli_num_rows($check_column) > 0) {
                            $q_grad = "SELECT COUNT(*) as total, SUM(CASE WHEN grade >= 75 THEN 1 ELSE 0 END) as lulus FROM submissions";
                            $r_grad = mysqli_query($koneksi, $q_grad);
                            $d_grad = mysqli_fetch_assoc($r_grad);
                            if($d_grad['total'] > 0){
                                echo round(($d_grad['lulus'] / $d_grad['total']) * 100) . "% Lulus";
                            } else { echo "100% Stabil"; }
                        } else { echo "Belum Ada Data"; }
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- REVISI FORM PENGUMUMAN MASSAL -->
        <div class="card">
            <h3>📢 Pengumuman & Notifikasi Massal</h3>
            <form action="dashboard_guru.php" method="POST">
                <div class="form-group">
                    <label>Kirim Ke Kelas:</label>
                    <select name="course_id" required>
                        <option value="all">Semua Peserta Terdaftar</option>
                        <?php
                        if(mysqli_num_rows($res_c) > 0) {
                            mysqli_data_seek($res_c, 0);
                            while($row_c = mysqli_fetch_assoc($res_c)) {
                                echo "<option value='".$row_c['id']."'>Kelas: ".htmlspecialchars($row_c['title'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Isi Pesan Pengumuman:</label>
                    <textarea name="pesan" rows="2" placeholder="Tulis pesan blast yang akan tampil di dashboard semua siswa..." required></textarea>
                </div>
                <button type="submit" name="kirim_pengumuman" class="btn-submit btn-warning">Kirim Notifikasi Blast</button>
            </form>
        </div>

        <!-- 2. MANAJEMEN PESERTA -->
        <div class="card">
            <h3>👥 2. Manajemen Peserta & Pemantauan</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Email Akun</th>
                        <th>Status Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_user = "SELECT * FROM users WHERE role='student' ORDER BY nama ASC";
                    $res_user = mysqli_query($koneksi, $query_user);
                    if ($res_user && mysqli_num_rows($res_user) > 0) {
                        while ($student = mysqli_fetch_assoc($res_user)) {
                            echo "<tr>";
                            echo "<td><strong>" . htmlspecialchars($student['nama']) . "</strong></td>";
                            echo "<td>" . htmlspecialchars($student['email']) . "</td>";
                            echo "<td><span class='badge badge-success'>Online / Aktif</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center; color:#999;'>Belum ada siswa terdaftar.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- 3. PUSAT PENILAIAN & UMPAN BALIK -->
        <div class="card">
            <h3>📝 3. Pusat Penilaian & Umpan Balik</h3>
            <table>
                <thead>
                    <tr>
                        <th>Siswa / Tugas</th>
                        <th>Nilai</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_s = "SELECT s.*, a.title AS task_title, u.nama AS student_name FROM submissions s
                                JOIN assignments a ON s.assignment_id = a.id
                                JOIN users u ON s.student_id = u.id
                                ORDER BY s.id DESC";
                    $res_s = mysqli_query($koneksi, $query_s);

                    if ($res_s && mysqli_num_rows($res_s) > 0) {
                        while ($sub = mysqli_fetch_assoc($res_s)) {
                            $sub_grade = isset($sub['grade']) ? $sub['grade'] : null;
                            $sub_feedback = isset($sub['feedback']) ? $sub['feedback'] : '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($sub['student_name']); ?></strong><br>
                                    <span style="font-size:11px; color:#666;"><?php echo htmlspecialchars($sub['task_title']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    if ($sub_grade !== null) {
                                        echo "<span class='badge badge-success'>Score: ".$sub_grade."</span>";
                                    } else { echo "<span class='badge badge-warning'>Belum Dinilai</span>"; }
                                    ?>
                                </td>
                                <td>
                                    <button onclick="openFeedbackModal(<?php echo $sub['id']; ?>, '<?php echo htmlspecialchars($sub['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($sub['task_title'], ENT_QUOTES); ?>', '<?php echo $sub_grade; ?>', '<?php echo htmlspecialchars($sub_feedback, ENT_QUOTES); ?>')" style="color:#17a2b8; font-weight:bold; background:none; border:none; cursor:pointer; font-size:12px; padding:0;">+ Beri Feedback</button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else { echo '<tr><td colspan="3" style="text-align:center; color:#999;">Belum ada jawaban siswa terkumpul.</td></tr>'; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ====== POP-UP MODAL EDIT MATERI ====== -->
<div id="editMateriModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Edit Materi Pembelajaran</div>
        <form action="dashboard_guru.php" method="POST">
            <input type="hidden" name="materi_id" id="edit_materi_id">
            <div class="form-group">
                <label>Pilih Kursus:</label>
                <select name="course_id" id="edit_course_id" required>
                    <?php
                    mysqli_data_seek($res_c, 0);
                    while($row_c = mysqli_fetch_assoc($res_c)) {
                        echo "<option value='".$row_c['id']."'>".htmlspecialchars($row_c['title'])."</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Judul Materi:</label>
                <input type="text" name="title" id="edit_materi_title" required>
            </div>
            <div class="form-group">
                <label>Deskripsi Materi:</label>
                <textarea name="description" id="edit_materi_desc" rows="3" required></textarea>
            </div>
            <div class="form-group">
                <label>Nama/Path File Berkas:</label>
                <input type="text" name="file_path" id="edit_materi_file" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close" onclick="closeEditMateriModal()">Batal</button>
                <button type="submit" name="update_materi" class="btn-submit">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- ====== WIDGET POP-UP MODAL FORM FEEDBACK ====== -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">Pusat Penilaian & Feedback</div>
        <form action="dashboard_guru.php" method="POST">
            <input type="hidden" name="submission_id" id="modal_sub_id">
            <div class="form-group">
                <label>Nama Siswa:</label>
                <input type="text" id="modal_student_name" disabled>
            </div>
            <div class="form-group">
                <label>Tugas / Ujian:</label>
                <input type="text" id="modal_task_title" disabled>
            </div>
            <div class="form-group">
                <label>Input Nilai (0 - 100):</label>
                <input type="number" name="grade" id="modal_grade" min="0" max="100" required>
            </div>
            <div class="form-group">
                <label>Umpan Balik / Catatan Guru:</label>
                <textarea name="feedback" id="modal_feedback" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close" onclick="closeFeedbackModal()">Batal</button>
                <button type="submit" name="simpan_feedback" class="btn-submit">Simpan Nilai</button>
            </div>
        </form>
    </div>
</div>

<script>
function openFeedbackModal(id, student, task, grade, feedback) {
    document.getElementById('modal_sub_id').value = id;
    document.getElementById('modal_student_name').value = student;
    document.getElementById('modal_task_title').value = task;
    document.getElementById('modal_grade').value = (grade !== 'null' && grade !== '' && grade !== 'data kosong') ? grade : '';
    document.getElementById('modal_feedback').value = feedback;
    document.getElementById('feedbackModal').style.display = 'flex';
}
function closeFeedbackModal() { document.getElementById('feedbackModal').style.display = 'none'; }

function openEditMateriModal(id, course_id, title, desc, file) {
    document.getElementById('edit_materi_id').value = id;
    document.getElementById('edit_course_id').value = course_id;
    document.getElementById('edit_materi_title').value = title;
    document.getElementById('edit_materi_desc').value = desc;
    document.getElementById('edit_materi_file').value = file;
    document.getElementById('editMateriModal').style.display = 'flex';
}
function closeEditMateriModal() { document.getElementById('editMateriModal').style.display = 'none'; }
</script>

</body>
</html>
<?php mysqli_close($koneksi); ?>