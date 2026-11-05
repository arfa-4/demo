<?php
session_start();

// 1. Proteksi halaman: jika bukan student, kembalikan ke login
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

// [PERBAIKAN UTAMA] Pastikan tabel forum_messages sudah ada saat halaman diakses (GET / POST)
$check_forum = mysqli_query($koneksi, "SHOW TABLES LIKE 'forum_messages'");
if (mysqli_num_rows($check_forum) == 0) {
    $create_table_query = "CREATE TABLE forum_messages (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT, 
        nama VARCHAR(100), 
        pesan TEXT, 
        created_at DATETIME
    )";
    mysqli_query($koneksi, $create_table_query);
}

// Ambil data siswa dari session
$student_id   = intval($_SESSION['id']); 
$student_name = $_SESSION['nama'];

// 3. PROSES KIRIM DISKUSI FORUM
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_kirim_diskusi'])) {
    $pesan_diskusi = mysqli_real_escape_string($koneksi, $_POST['pesan_diskusi']);
    
    if (!empty(trim($pesan_diskusi))) {
        mysqli_query($koneksi, "INSERT INTO forum_messages (user_id, nama, pesan, created_at) VALUES ($student_id, '$student_name', '$pesan_diskusi', NOW())");
    }
    
    // Refresh halaman agar chat langsung muncul dan form bersih
    header("Location: forum.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Diskusi - LMS Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #17a2b8; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .btn-back { color: white; text-decoration: none; font-weight: bold; background: #138496; padding: 6px 12px; border-radius: 4px; }
        .btn-back:hover { background: #117a8b; }
        
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .forum-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .forum-card h3 { margin-top: 0; color: #17a2b8; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        
        /* Box Chat Room */
        .forum-box { height: 400px; border: 1px solid #ddd; border-radius: 6px; overflow-y: auto; padding: 15px; background: #fdfdfd; margin-bottom: 20px; display: flex; flex-direction: column; gap: 12px; }
        
        /* Balon Chat */
        .chat-container { display: flex; flex-direction: column; max-width: 75%; }
        .chat-container.self { align-self: flex-end; }
        .chat-container.others { align-self: flex-start; }

        .chat-meta { font-size: 11px; color: #777; margin-bottom: 2px; padding: 0 5px; }
        .chat-container.self .chat-meta { text-align: right; }

        .chat-msg { padding: 10px 14px; border-radius: 12px; font-size: 14.5px; line-height: 1.4; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
        .chat-container.others .chat-msg { background: #f1f3f5; color: #333; border-top-left-radius: 2px; }
        .chat-container.self .chat-msg { background: #e3f2fd; color: #0d47a1; border-top-right-radius: 2px; }
        
        /* Form Input */
        .input-group { display: flex; gap: 10px; }
        .input-group input { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; }
        .input-group input:focus { border-color: #17a2b8; }
        .btn-send { background: #28a745; color: white; border: none; padding: 0 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn-send:hover { background: #218838; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🗣️ Ruang Diskusi Siswa</h2>
    <a href="dashboard_siswa.php" class="btn-back">← Kembali ke Dashboard</a>
</div>

<div class="container">
    <div class="forum-card">
        <h3>Pusat Bantuan & Tanya Jawab</h3>
        
        <!-- Area Riwayat Obrolan -->
        <div class="forum-box" id="forumBox">
            <div class="chat-container others">
                <span class="chat-meta">Sistem Notifikasi</span>
                <div class="chat-msg">
                    Selamat datang di forum diskusi utama. Silakan tanyakan kendala belajar Anda di sini untuk ditanggapi oleh pengajar atau rekan sekelas.
                </div>
            </div>

            <?php
            // Ambil data chat dari database
            $get_chat = mysqli_query($koneksi, "SELECT * FROM forum_messages ORDER BY id ASC LIMIT 100");
            if ($get_chat && mysqli_num_rows($get_chat) > 0) {
                while($chat = mysqli_fetch_assoc($get_chat)) {
                    // Cek apakah pesan dikirim oleh diri sendiri
                    $is_me = ($chat['user_id'] == $student_id) ? 'self' : 'others';
                    $waktu = date('H:i', strtotime($chat['created_at']));
                    ?>
                    <div class="chat-container <?php echo $is_me; ?>">
                        <span class="chat-meta"><?php echo htmlspecialchars($chat['nama']); ?> • <?php echo $waktu; ?></span>
                        <div class="chat-msg">
                            <?php echo nl2br(htmlspecialchars($chat['pesan'])); ?>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        
        <!-- Form Pengiriman Pesan -->
        <form action="forum.php" method="POST">
            <div class="input-group">
                <input type="text" name="pesan_diskusi" placeholder="Tulis pertanyaan atau pesan diskusi Anda di sini..." required autocomplete="off">
                <button type="submit" name="action_kirim_diskusi" class="btn-send">Kirim Pesan</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Otomatis scroll chat box ke posisi paling bawah agar pesan terbaru langsung terlihat
    const forumBox = document.getElementById('forumBox');
    forumBox.scrollTop = forumBox.scrollHeight;
</script>

</body>
</html>