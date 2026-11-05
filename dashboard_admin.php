<?php
session_start();

// 1. Proteksi halaman: jika bukan admin, tendang kembali ke login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LMS Demo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }
        .navbar { background: #343a40; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar h2 { margin: 0; font-size: 20px; }
        .logout { color: #ff4d4d; text-decoration: none; font-weight: bold; background: #454d55; padding: 6px 12px; border-radius: 4px; }
        .logout:hover { background: #5a6268; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .container { grid-template-columns: 1fr; } }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #343a40; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; width: 100%; }
        .btn-submit:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        table th { background: #f1f3f5; color: #555; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-admin { background: #e2e3e5; color: #383d41; }
        .badge-teacher { background: #d4edda; color: #155724; }
        .badge-student { background: #d1ecf1; color: #0c5460; }
        .btn-delete { color: #dc3545; text-decoration: none; font-weight: bold; }
        .btn-delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>LMS Demo - Admin Panel</h2>
    <div>
        <span style="margin-right: 15px;">Halo, <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong> (Admin)</span>
        <a href="logout.php" class="logout">Logout</a>
    </div>
</div>

<div class="container">
    
    <!-- KOLOM KIRI: DAFTAR PENGGUNA -->
    <div>
        <div class="card">
            <h3>👥 Manajemen Pengguna</h3>
            <p style="font-size: 14px; color: #666;">Berikut daftar seluruh akun yang terdaftar di database <code>lms_demo</code>:</p>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Hak Akses (Role)</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mengambil seluruh data user dari tabel users
                    $query_users = "SELECT * FROM users ORDER BY id ASC";
                    $result_users = mysqli_query($koneksi, $query_users);

                    if (mysqli_num_rows($result_users) > 0) {
                        while ($user = mysqli_fetch_assoc($result_users)) {
                            // Menentukan warna badge berdasarkan role
                            $badge_class = 'badge-student';
                            if ($user['role'] === 'admin') $badge_class = 'badge-admin';
                            if ($user['role'] === 'teacher') $badge_class = 'badge-teacher';
                            ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['nama']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                <td>
                                    <!-- Parameter ID dibawa untuk proses hapus -->
                                    <a href="hapus_user.php?id=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="5" style="text-align:center; color:#999;">Tidak ada data pengguna ditemukan.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KOLOM KANAN: FORM REGISTRASI USER BARU -->
    <div>
        <div class="card">
            <h3>➕ Tambah Akun Baru</h3>
            <form action="proses_tambah_user.php" method="POST">
                <div class="form-group">
                    <label>Nama Lengkap:</label>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
                </div>
                <div class="form-group">
                    <label>Alamat Email:</label>
                    <input type="email" name="email" placeholder="contoh: user@lms.com" required>
                </div>
                <div class="form-group">
                    <label>Kata Sandi (Password):</label>
                    <input type="password" name="password" placeholder="Masukkan password baru" required>
                </div>
                <div class="form-group">
                    <label>Hak Akses (Role):</label>
                    <select name="role" required>
                        <option value="student">Student (Siswa)</option>
                        <option value="teacher">Teacher (Guru)</option>
                        <option value="admin">Admin (Pengelola)</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Daftarkan Pengguna</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>