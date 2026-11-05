<?php
session_start();

// 1. Proteksi halaman: jika bukan student, tendang kembali ke login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.html");
    exit();
}

// 2. Menggunakan file koneksi sentral agar selaras dengan file lainnya
include 'koneksi.php';

// Jika file 'koneksi.php' menggunakan variabel berbeda (misal $conn), selaraskan di sini:
if (isset($conn) && !isset($koneksi)) {
    $koneksi = $conn;
}

// Ambal data siswa dari session
$student_id   = intval($_SESSION['id']); 
$student_name = $_SESSION['nama'];

// ==========================================
// PROSES LOGIKA BEHIND-THE-SCENES (ACTION CONTROLLER)
// ==========================================

// 1. PROSES KIRIM JAWABAN TUGAS
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_kirim_jawaban'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $jawaban_teks  = mysqli_real_escape_string($koneksi, $_POST['jawaban_teks']);
    
    $cek_sub = mysqli_query($koneksi, "SELECT id FROM submissions WHERE assignment_id = $assignment_id AND student_id = $student_id");
    
    if (mysqli_num_rows($cek_sub) > 0) {
        $query_sub = "UPDATE submissions SET file_path = '$jawaban_teks', submitted_at = NOW() WHERE assignment_id = $assignment_id AND student_id = $student_id";
    } else {
        $query_sub = "INSERT INTO submissions (assignment_id, student_id, file_path, grade, feedback, submitted_at) VALUES ($assignment_id, $student_id, '$jawaban_teks', NULL, NULL, NOW())";
    }
    
    if (mysqli_query($koneksi, $query_sub)) {
        echo "<script>alert('Jawaban tugas berhasil dikirim!'); window.location.href='dashboard_siswa.php';</script>";
        exit();
    }
}

// 4. PROSES IKUTI KURSUS / DAFTAR KELAS
if (isset($_GET['action']) && $_GET['action'] == 'ikuti_kursus' && isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    
    // Ambil detail info kursus untuk mengecek field is_paid
    $query_check_course = mysqli_query($koneksi, "SELECT is_paid FROM courses WHERE id = $course_id");
    $is_paid_course = 0;
    if ($course_data = mysqli_fetch_assoc($query_check_course)) {
        $is_paid_course = intval($course_data['is_paid']);
    }

    // Pastikan tabel enrollments sudah ada beserta kolom status
    $check_enroll = mysqli_query($koneksi, "SHOW TABLES LIKE 'enrollments'");
    if (mysqli_num_rows($check_enroll) == 0) {
        mysqli_query($koneksi, "CREATE TABLE enrollments (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT, course_id INT, status ENUM('pending', 'active') DEFAULT 'pending', enrolled_at DATETIME)");
    }
    
    $cek_double = mysqli_query($koneksi, "SELECT id, status FROM enrollments WHERE student_id = $student_id AND course_id = $course_id");
    
    if (mysqli_num_rows($cek_double) == 0) {
        // Tentukan status default berdasarkan jenis kelas
        $status_awal = ($is_paid_course === 1) ? 'pending' : 'active';
        
        mysqli_query($koneksi, "INSERT INTO enrollments (student_id, course_id, status, enrolled_at) VALUES ($student_id, $course_id, '$status_awal', NOW())");
        $new_id = mysqli_insert_id($koneksi);

        if ($is_paid_course === 1) {
            header("Location: invoice.php?enrollment_id=" . $new_id);
            exit();
        } else {
            echo "<script>alert('Berhasil terdaftar! Kelas gratis ini sekarang aktif.'); window.location.href='dashboard_siswa.php';</script>";
            exit();
        }
    } else {
        $existing = mysqli_fetch_assoc($cek_double);
        if ($existing['status'] === 'pending') {
            header("Location: invoice.php?enrollment_id=" . $existing['id']);
            exit();
        }
    }
    header("Location: dashboard_siswa.php");
    exit();
}

// 3. PROSES KIRIM DISKUSI FORUM
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_kirim_diskusi'])) {
    $pesan_diskusi = mysqli_real_escape_string($koneksi, $_POST['pesan_diskusi']);
    
    $check_forum = mysqli_query($koneksi, "SHOW TABLES LIKE 'forum_messages'");
    if (mysqli_num_rows($check_forum) == 0) {
        mysqli_query($koneksi, "CREATE TABLE forum_messages (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, nama VARCHAR(100), pesan TEXT, created_at DATETIME)");
    }
    
    if (!empty(trim($pesan_diskusi))) {
        mysqli_query($koneksi, "INSERT INTO forum_messages (user_id, nama, pesan, created_at) VALUES ($student_id, '$student_name', '$pesan_diskusi', NOW())");
    }
    header("Location: forum.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - LMS Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #17a2b8; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .logout { color: white; text-decoration: none; font-weight: bold; background: #138496; padding: 6px 12px; border-radius: 4px; }
        .logout:hover { background: #117a8b; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 992px) { .container { grid-template-columns: 1fr; } }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #17a2b8; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        
        .item-list { background: #f8f9fa; padding: 12px; border-radius: 6px; margin-bottom: 12px; border-left: 4px solid #17a2b8; }
        .item-list h4 { margin: 0 0 5px 0; color: #333; }
        .item-list p { margin: 0; font-size: 14px; color: #666; }
        
        .btn-action { display: inline-block; background: #17a2b8; color: white; text-decoration: none; padding: 6px 12px; border-radius: 4px; font-size: 14px; margin-top: 8px; font-weight: bold; border: none; cursor: pointer; text-align: center;}
        .btn-action:hover { background: #138496; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; color: #212529; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background: #f1f3f5; color: #555; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-info { background: #e3f2fd; color: #0d47a1; }
        .badge-joined { background: #d4edda; color: #155724; font-size: 11px; padding: 4px 6px; border-radius: 4px; display: inline-block; font-weight: bold;}
        .badge-pending-text { background: #fff3cd; color: #856404; font-size: 11px; padding: 4px 6px; border-radius: 4px; display: inline-block; font-weight: bold; }
        .badge-price { background: #fff3cd; color: #856404; font-size: 11px; padding: 3px 6px; border-radius: 4px; font-weight: bold; margin-left: 5px; }
        
        .katalog-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
        @media (max-width: 576px) { .katalog-grid { grid-template-columns: 1fr; } }
        .katalog-item { border: 1px solid #dee2e6; padding: 12px; border-radius: 6px; background: #fff; }

        .modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-height: 85vh; overflow-y: auto; }
        .modal-header { font-size: 18px; font-weight: bold; color: #17a2b8; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; }
        #certificate-canvas { border: 4px double #17a2b8; background: #fff8f0; padding: 30px; text-align: center; font-family: 'Georgia', serif; color: #333; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>LMS Demo - Panel Siswa</h2>
    <div>
        <span style="margin-right: 15px;">Selamat belajar, <strong><?php echo htmlspecialchars($student_name); ?></strong></span>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    
    <div>
        <div class="card">
            <h3>🌐 1. Katalog Kursus (Semua Kursus Tersedia)</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Daftar semua kursus yang dibuka pada sistem LMS saat ini. Silakan ikuti kursus baru yang Anda minati:</p>
            <div class="katalog-grid">
                <?php
                $query_all_courses = "SELECT * FROM courses ORDER BY id DESC";
                $result_all_courses = mysqli_query($koneksi, $query_all_courses);

                if (mysqli_num_rows($result_all_courses) > 0) {
                    while ($course = mysqli_fetch_assoc($result_all_courses)) {
                        $c_id = $course['id'];
                        
                        // Cek pendaftaran dan statusnya (pending/active)
                        $cek_enrollment = mysqli_query($koneksi, "SELECT id, status FROM enrollments WHERE student_id = $student_id AND course_id = $c_id");
                        $has_record = (mysqli_num_rows($cek_enrollment) > 0);
                        $enroll_status = '';
                        $enroll_id = 0;
                        if ($has_record) {
                            $enroll_data = mysqli_fetch_assoc($cek_enrollment);
                            $enroll_status = $enroll_data['status'];
                            $enroll_id = $enroll_data['id'];
                        }
                        
                        $is_paid_course = isset($course['is_paid']) ? intval($course['is_paid']) : 0;
                        $price_course   = isset($course['price']) ? intval($course['price']) : 0;
                        ?>
                        <div class="katalog-item">
                            <!-- REVISI: Mengubah $course['title'] menjadi $course['course_name'] agar tidak eror -->
                            <strong style="font-size: 15px; color: #17a2b8;"><?php echo htmlspecialchars($course['course_name']); ?></strong>
                            
                            <?php if ($is_paid_course === 1): ?>
                                <span class="badge-price">Rp <?php echo number_format($price_course, 0, ',', '.'); ?></span>
                            <?php endif; ?>

                            <p style="font-size: 13px; color: #777; margin: 5px 0 10px 0;">
                                <?php echo !empty($course['description']) ? htmlspecialchars($course['description']) : 'Pelajari fondasi utama ilmu serta studi kasus nyata terintegrasi.'; ?>
                            </p>
                            
                            <?php if ($has_record): ?>
                                <?php if ($enroll_status === 'active'): ?>
                                    <span class="badge-joined">✓ Saya mengikuti kelas ini</span>
                                <?php else: ?>
                                    <div style="margin-top: 8px;">
                                        <span class="badge-pending-text">⏳ Menunggu Verifikasi</span>
                                        <a href="invoice.php?enrollment_id=<?php echo $enroll_id; ?>" class="btn-action btn-secondary" style="font-size:11px; padding:3px 6px; margin-top:0; margin-left:5px;">Lihat Invoice</a>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($is_paid_course === 1): ?>
                                    <a href="dashboard_siswa.php?action=ikuti_kursus&course_id=<?php echo $course['id']; ?>" class="btn-action btn-warning" style="font-size:12px; padding:4px 8px; margin-top:0;">💳 Bayar Sekarang</a>
                                <?php else: ?>
                                    <a href="dashboard_siswa.php?action=ikuti_kursus&course_id=<?php echo $course['id']; ?>" class="btn-action btn-success" style="font-size:12px; padding:4px 8px; margin-top:0;">+ Ikuti Kursus</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p style='color:#999;'>Belum ada kursus yang tersedia di katalog.</p>";
                }
                ?>
            </div>
        </div>

        <div class="card">
            <h3>📖 2. Modul Pembelajaran (Materi Teks, Video & Lampiran)</h3>
            <?php
            // Menampilkan modul hanya dari kelas aktif milik siswa
            $query_materi = "SELECT m.*, c.course_name AS course_title FROM materials m 
                             JOIN courses c ON m.course_id = c.id 
                             JOIN enrollments e ON c.id = e.course_id
                             WHERE e.student_id = $student_id AND e.status = 'active'
                             ORDER BY m.id DESC";
            $result_materi = mysqli_query($koneksi, $query_materi);

            if (mysqli_num_rows($result_materi) > 0) {
                while ($materi = mysqli_fetch_assoc($result_materi)) {
                    
                    $judul_lowercase = strtolower($materi['title']);
                    $video_url = "https://www.youtube.com/embed/bJ6bKHev6fI"; 

                    if (strpos($judul_lowercase, 'flexbox') !== false) {
                        $video_url = "https://www.youtube.com/embed/wsTv9y931o8"; 
                    } elseif (strpos($judul_lowercase, 'html') !== false || strpos($judul_lowercase, 'tag') !== false) {
                        $video_url = "https://www.youtube.com/embed/Q33KBiDriJY"; 
                    } elseif (strpos($judul_lowercase, 'database') !== false || strpos($judul_lowercase, 'basis data') !== false || strpos($judul_lowercase, 'relasional') !== false) {
                        $video_url = "https://www.youtube.com/embed/wzXpU77pco8"; 
                    }

                    $desc = isset($materi['description']) ? $materi['description'] : 'Baca petunjuk pengerjaan dan tonton video pendukung materi di bawah ini.';
                    $download_data = "Modul Penting: " . $materi['title'] . "\n\nDeskripsi Materi:\n" . $desc;
                    ?>
                    <div class="item-list">
                        <span class="badge badge-info"><?php echo htmlspecialchars($materi['course_title']); ?></span>
                        <h4 style="margin-top: 8px;"><?php echo htmlspecialchars($materi['title']); ?></h4>
                        
                        <p style="margin: 8px 0; font-size: 13.5px; line-height: 1.4;">
                            <?php echo htmlspecialchars($desc); ?>
                        </p>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="openVideoModal('<?php echo htmlspecialchars($materi['title'], ENT_QUOTES); ?>', '<?php echo $video_url; ?>')" class="btn-action btn-secondary" style="font-size: 12px; padding: 5px 10px;">▶️ Tonton Video Modul</button>
                            
                            <a href="data:text/plain;charset=utf-8,<?php echo rawurlencode($download_data); ?>" class="btn-action" style="font-size: 12px; padding: 5px 10px;" download="Lampiran_Modul_<?php echo $materi['id']; ?>.txt">⬇️ Unduh Lampiran Dokumen</a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<p style='color:#999;'>Belum ada modul pembelajaran yang tersedia. Silakan ikuti kelas aktif di katalog terlebih dahulu.</p>";
            }
            ?>
        </div>

        <div class="card">
            <h3>📝 3. Tugas & Jadwal Ujian Aktif</h3>
            <table>
                <thead>
                    <tr>
                        <th>Mata Pelajaran</th>
                        <th>Penugasan / Ujian</th>
                        <th>Batas Waktu (Deadline)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_tugas = "SELECT a.*, c.course_name AS course_title FROM assignments a
                                    JOIN courses c ON a.course_id = c.id
                                    JOIN enrollments e ON c.id = e.course_id
                                    WHERE e.student_id = $student_id AND e.status = 'active'
                                    ORDER BY a.due_date ASC";
                    $result_tugas = mysqli_query($koneksi, $query_tugas);

                    if (mysqli_num_rows($result_tugas) > 0) {
                        while ($tugas = mysqli_fetch_assoc($result_tugas)) {
                            $t_id = $tugas['id'];
                            $cek_status = mysqli_query($koneksi, "SELECT grade FROM submissions WHERE assignment_id = $t_id AND student_id = $student_id");
                            $is_submitted = (mysqli_num_rows($cek_status) > 0);
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tugas['course_title']); ?></strong></td>
                                <td>✏️ <?php echo htmlspecialchars($tugas['title']); ?></td>
                                <td><span style="color: #c00; font-weight: bold;"><?php echo date('d M Y, H:i', strtotime($tugas['due_date'])); ?></span></td>
                                <td>
                                    <button type="button" onclick="openJawabanModal(<?php echo $tugas['id']; ?>, '<?php echo htmlspecialchars($tugas['title'], ENT_QUOTES); ?>')" class="btn-action <?php echo $is_submitted ? 'btn-success' : ''; ?>" style="margin-top:0; font-size:12px; padding: 5px 10px;">
                                        <?php echo $is_submitted ? 'Edit Jawaban ✓' : 'Kirim Jawaban'; ?>
                                    </button>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center; color:#999;">Tidak ada tugas aktif dari kelas yang Anda ikuti saat ini.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card">
            <h3>🏫 Kelas Saya</h3>
            <p style="font-size: 13px; color: #666;">Akses cepat kelas aktif yang sedang Anda ikuti:</p>
            <?php
            $query_kelas = "SELECT c.* FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = $student_id AND e.status = 'active'";
            $result_kelas = mysqli_query($koneksi, $query_kelas);

            if (mysqli_num_rows($result_kelas) > 0) {
                while ($kelas = mysqli_fetch_assoc($result_kelas)) {
                    echo "<div style='padding: 10px; background:#f1f3f5; margin-bottom:8px; border-radius:4px; font-size:14px; border-left: 3px solid #28a745;'>";
                    echo "🛡️ <strong>" . htmlspecialchars($kelas['course_name']) . "</strong><br>";
                    echo "<a href='javascript:void(0);' onclick=\"openKelasModal('".htmlspecialchars($kelas['course_name'], ENT_QUOTES)."')\" style='font-size:11px; color:#17a2b8; text-decoration:none; font-weight:bold;'>Masuk Ruang Kelas →</a>";
                    echo "</div>";
                }
            } else {
                echo "<p style='color:#999;'>Belum ada kelas aktif. Silakan pilih kelas di katalog khusus.</p>";
            }
            ?>
        </div>

        <div class="card">
            <h3>🎓 Sertifikat Kelulusan</h3>
            <p style="font-size: 13px; color: #666;">Unduh sertifikat penghargaan otomatis setelah Anda menyelesaikan seluruh rangkaian materi kursus:</p>
            <button type="button" onclick="openCertificateModal('<?php echo htmlspecialchars($student_name, ENT_QUOTES); ?>')" class="btn-action btn-success" style="width: 100%; text-align: center; box-sizing: border-box; display: block;">🎖️ Klaim Sertifikat Otomatis</button>
        </div>

        <div class="card">
            <h3>💬 Pusat Bantuan & Forum Diskusi</h3>
            <p style="font-size: 13px; color: #666;">Punya kendala belajar? Masuk forum tanya jawab bersama mentor pengajar atau rekan sekelas Anda.</p>
            <a href="forum.php" class="btn-action btn-secondary" style="width: 100%; box-sizing: border-box; display: block;">🗣️ Masuk Forum Diskusi</a>
        </div>
    </div>

</div>

<!-- Modal Dialog forms -->
<div id="modalJawaban" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="jwb_header">Form Pengumpulan Tugas</div>
        <form action="dashboard_siswa.php" method="POST">
            <input type="hidden" name="assignment_id" id="jwb_assignment_id">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px; font-size:14px;">Tuliskan Tautan File / Esai Jawaban Anda:</label>
                <textarea name="jawaban_teks" rows="6" style="width:100%; box-sizing:border-box; padding:10px; border-radius:4px; border:1px solid #ccc;" placeholder="Tempel tautan Google Drive / Github, atau tulis jawaban esai Anda langsung di sini..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-action btn-secondary" onclick="closeModal('modalJawaban')">Batal</button>
                <button type="submit" name="action_kirim_jawaban" class="btn-action btn-success">Kirim Jawaban Sekarang</button>
            </div>
        </form>
    </div>
</div>

<div id="modalVideo" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="video_header">Video Tutorial Modul</div>
        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000;">
            <iframe id="video_frame" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-action btn-secondary" onclick="closeModal('modalVideo')">Tutup Pemutar Video</button>
        </div>
    </div>
</div>

<div id="modalSertifikat" class="modal">
    <div class="modal-content">
        <div class="modal-header">Sertifikat Kelulusan Resmi</div>
        <div id="certificate-canvas">
            <h2 style="margin: 0; font-size: 28px; color: #17a2b8;">SERTIFIKAT PENGHARGAAN</h2>
            <p style="font-size: 14px; font-style: italic; margin: 10px 0;">Diberikan secara terhormat kepada:</p>
            <h1 style="margin: 5px 0; font-size: 32px; text-decoration: underline; color: #333;" id="cert_student_name">Nama Siswa</h1>
            <p style="font-size: 14px; max-width: 450px; margin: 15px auto; line-height: 1.4;">
                Atas dedikasi luar biasa, penyelesaian komprehensif, dan pencapaian kompetensi akademik terbaik pada seluruh modul materi di platform <strong>LMS Demo System</strong>.
            </p>
            <div style="margin-top: 30px; display: flex; justify-content: space-between; padding: 0 40px; font-size: 12px;">
                <div>
                    <p style="margin:0;"><strong>7 Juli 2026</strong></p>
                    <p style="margin:0; border-top: 1px solid #777; padding-top:4px;">Tanggal Kelulusan</p>
                </div>
                <div>
                    <p style="margin:0; color: #17a2b8; font-family:'Courier New'; font-weight:bold;">[ VERIFIED ]</p>
                    <p style="margin:0; border-top: 1px solid #777; padding-top:4px;">LMS Demo Administrator</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-action btn-secondary" onclick="closeModal('modalSertifikat')">Kembali</button>
            <button type="button" onclick="window.print()" class="btn-action btn-success">🖨️ Cetak / Simpan PDF</button>
        </div>
    </div>
</div>

<div id="modalKelas" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="kelas_header">Ruang Kelas Aktif</div>
        <div style="text-align: center; padding: 20px 0;">
            <div style="font-size: 50px; margin-bottom: 15px;">🎒</div>
            <p style="font-size: 16px; font-weight: bold; margin: 0 0 10px 0;">Anda berhasil masuk ke interaksi ruang kelas virtual!</p>
            <p style="color:#666; font-size: 13px; line-height: 1.5;">Seluruh modul pembelajaran, ujian terjadwal, dan koordinasi kelompok untuk mata pelajaran ini disinkronisasikan secara langsung di panel kiri Anda.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-action btn-success" onclick="closeModal('modalKelas')">Mulai Belajar Aktif</button>
        </div>
    </div>
</div>

<script>
function openJawabanModal(id, title) {
    document.getElementById('jwb_assignment_id').value = id;
    document.getElementById('jwb_header').innerText = "Kirim Jawaban: " + title;
    document.getElementById('modalJawaban').style.display = 'flex';
}

function openVideoModal(title, embedUrl) {
    document.getElementById('video_header').innerText = "Memutar Modul: " + title;
    document.getElementById('video_frame').src = embedUrl;
    document.getElementById('modalVideo').style.display = 'flex';
}

function openCertificateModal(name) {
    document.getElementById('cert_student_name').innerText = name;
    document.getElementById('modalSertifikat').style.display = 'flex';
}

function openKelasModal(className) {
    document.getElementById('kelas_header').innerText = "Sesi Ruang Kelas: " + className;
    document.getElementById('modalKelas').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    if(modalId === 'modalVideo') {
        document.getElementById('video_frame').src = ""; 
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        if(event.target.id === 'modalVideo') {
            document.getElementById('video_frame').src = "";
        }
    }
}
</script>

</body>
</html>
<?php
mysqli_close($koneksi);
?>