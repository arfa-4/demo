<?php
session_start();

// 1. Proteksi halaman: pastikan hanya teacher yang bisa mengakses
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

// 3. Otomatis buat tabel 'courses' dengan kolom pendukung fitur berbayar jika belum ada
$sql_create_courses = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    price INT DEFAULT 0,
    is_paid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($koneksi, $sql_create_courses);


// ====== LOGIKA PROSES TAMBAH KURSUS ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_kursus'])) {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $is_paid     = intval($_POST['is_paid']);
    // Jika tipenya gratis (0), paksa harga jadi 0 meskipun user mengisi kolom harga
    $price       = ($is_paid === 1) ? intval($_POST['price']) : 0;

    if (!empty($title)) {
        $query_insert = "INSERT INTO courses (title, description, is_paid, price) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query_insert);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssii", $title, $description, $is_paid, $price);
            if (mysqli_stmt_execute($stmt)) {
                echo "<script>alert('Kursus baru berhasil ditambahkan!'); window.location.href='kelola_kursus.php';</script>";
                exit();
            } else {
                echo "<script>alert('Gagal menambah kursus: " . mysqli_error($koneksi) . "');</script>";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        echo "<script>alert('Nama Kursus tidak boleh kosong!');</script>";
    }
}


// ====== LOGIKA PROSES HAPUS KURSUS ======
if (isset($_GET['hapus'])) {
    $id_hapus = intval($_GET['hapus']);
    
    $query_delete = "DELETE FROM courses WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query_delete);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_hapus);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Kursus berhasil dihapus!'); window.location.href='kelola_kursus.php';</script>";
            exit();
        } else {
            echo "<script>alert('Gagal menghapus kursus: " . mysqli_error($koneksi) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kursus - Ruang Guru</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #28a745; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .btn-back { color: white; text-decoration: none; font-weight: bold; background: #218838; padding: 6px 12px; border-radius: 4px; }
        .btn-back:hover { background: #1e7e34; }
        
        .container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 25px; }
        .card h3 { margin-top: 0; color: #28a745; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        
        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 600px) { .row-grid { grid-template-columns: 1fr; } }

        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-submit:hover { background: #218838; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background: #f1f3f5; color: #555; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; display: inline-block; }
        .badge-free { background-color: #d4edda; color: #155724; }
        .badge-paid { background-color: #fff3cd; color: #856404; }

        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; }
        .btn-delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🛠️ Kelola Struktur Kursus / Mata Pelajaran</h2>
    <a href="dashboard_guru.php" class="btn-back">⬅️ Kembali ke Dashboard</a>
</div>

<div class="container">
    
    <!-- FORM TAMBAH KURSUS BARU -->
    <div class="card">
        <h3>➕ Tambah Kursus / Mata Pelajaran Baru</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label>Nama Kursus / Mata Pelajaran:</label>
                <input type="text" name="title" placeholder="Contoh: Pemrograman Web Berbasis PHP" required>
            </div>
            
            <div class="row-grid">
                <div class="form-group">
                    <label>Tipe Akses Kelas:</label>
                    <select name="is_paid" id="is_paid" onchange="togglePriceField()" required>
                        <option value="0">Gratis (Free)</option>
                        <option value="1">Berbayar (Premium)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Harga Kelas (Rupiah):</label>
                    <input type="number" name="price" id="price" placeholder="Contoh: 150000" value="0" min="0" disabled>
                </div>
            </div>

            <div class="form-group">
                <label>Deskripsi Singkat Kelas:</label>
                <textarea name="description" rows="3" placeholder="Tulis deskripsi atau capaian pembelajaran kursus ini..."></textarea>
            </div>
            
            <button type="submit" name="tambah_kursus" class="btn-submit">Simpan Kursus Baru</button>
        </form>
    </div>

    <!-- DAFTAR KURSUS YANG TERSEDIA -->
    <div class="card">
        <h3>📋 Daftar Kursus Saat Ini</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Kursus / Mata Pelajaran</th>
                    <th>Tipe Kelas</th>
                    <th>Harga</th>
                    <th>Deskripsi</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query_select = "SELECT * FROM courses ORDER BY id DESC";
                $result = mysqli_query($koneksi, $query_select);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $is_paid_status = isset($row['is_paid']) ? $row['is_paid'] : 0;
                        $course_price   = isset($row['price']) ? $row['price'] : 0;

                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                        
                        // Menampilkan Badge Status Akses Kelas
                        if ($is_paid_status == 1) {
                            echo "<td><span class='badge badge-paid'>Berbayar</span></td>";
                            echo "<td><strong>Rp " . number_format($course_price, 0, ',', '.') . "</strong></td>";
                        } else {
                            echo "<td><span class='badge badge-free'>Gratis</span></td>";
                            echo "<td><i style='color:#999;'>Free</i></td>";
                        }

                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                        echo "<td style='text-align: center;'>
                                <a href='kelola_kursus.php?hapus=" . $row['id'] . "' class='btn-delete' onclick='return confirm(\"Apakah Anda yakin ingin menghapus kursus ini? Semua materi terkait juga akan terhapus!\")'>Hapus</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; color:#999;'>Belum ada kursus yang dibuat. Silakan tambah di atas.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// Fungsi JavaScript untuk mengaktifkan/menonaktifkan input harga secara real-time
function togglePriceField() {
    var isPaidSelect = document.getElementById('is_paid');
    var priceInput = document.getElementById('price');
    
    if (isPaidSelect.value == "1") {
        priceInput.disabled = false;
        priceInput.required = true;
        if(priceInput.value == "0") {
            priceInput.value = "";
        }
        priceInput.focus();
    } else {
        priceInput.disabled = true;
        priceInput.required = false;
        priceInput.value = "0";
    }
}
</script>

</body>
</html>
<?php
mysqli_close($koneksi);
?>