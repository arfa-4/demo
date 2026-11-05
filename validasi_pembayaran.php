<?php
session_start();
// Proteksi halaman: Hanya untuk pengajar/admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.html");
    exit();
}

$koneksi = mysqli_connect("localhost", "root", "", "lms_demo");

// ====== LOGIKA PROSES PERSETUJUAN (APPROVE) ======
if (isset($_GET['action']) && $_GET['action'] == 'setujui' && isset($_GET['enrollment_id'])) {
    $enrollment_id = intval($_GET['enrollment_id']);
    
    // Update status dari pending menjadi active
    $query_approve = "UPDATE enrollments SET status = 'active' WHERE id = $enrollment_id";
    
    if (mysqli_query($koneksi, $query_approve)) {
        echo "<script>alert('Pembayaran berhasil disetujui! Kelas siswa telah aktif.'); window.location.href='validasi_pembayaran.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Validasi Pembayaran Manual - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #28a745; color: white; }
        .btn-approve { background: #28a745; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-approve:hover { background: #218838; }
        .badge-pending { background: #ffc107; color: #212529; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2>💳 Persetujuan Pembayaran Manual Kelas Premium</h2>
    <p>Berikut adalah daftar siswa yang menunggu verifikasi pembayaran untuk masuk ke kelas berbayar:</p>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Siswa</th>
                <th>Kelas Premium</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Ambil data enrollment yang pending, gabungkan dengan data user dan course
            $query = "SELECT e.id AS enroll_id, u.nama AS student_name, c.title AS course_title, e.status 
                      FROM enrollments e
                      JOIN users u ON e.student_id = u.id
                      JOIN courses c ON e.course_id = c.id
                      WHERE e.status = 'pending'
                      ORDER BY e.id DESC";
                      
            $result = mysqli_query($koneksi, $query);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['enroll_id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                    echo "<td><strong>" . htmlspecialchars($row['course_title']) . "</strong></td>";
                    echo "<td><span class='badge-pending'>" . $row['status'] . "</span></td>";
                    echo "<td>
                            <a href='validasi_pembayaran.php?action=setujui&enrollment_id=" . $row['enroll_id'] . "' class='btn-approve' onclick='return confirm(\"Setujui pembayaran siswa ini?\")'>✓ Setujui</a>
                          </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='text-align:center; color:#999;'>Alhamdulillah, tidak ada pembayaran pending saat ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <a href="dashboard_guru.php" style="color: #666; text-decoration: none;">← Kembali ke Dashboard Guru</a>
</div>

</body>
</html>