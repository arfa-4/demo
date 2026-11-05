<?php
include 'koneksi.php';

$pesan = "";
$tipe_pesan = "";
$sukses_daftar = false; // Flag untuk menandai pendaftaran sukses
$data_invoice = []; // Menyimpan data bukti untuk pembeli

// Tangkap ID kelas dari parameter URL (jika diklik dari halaman katalog index.php)
$kelas_terpilih = isset($_GET['pilih']) ? intval($_GET['pilih']) : 0;

// Ambil daftar kursus yang tersedia dari database
$list_kelas = [];
$ambil_kelas = mysqli_query($conn, "SELECT * FROM courses");
if ($ambil_kelas && mysqli_num_rows($ambil_kelas) > 0) {
    while ($row = mysqli_fetch_assoc($ambil_kelas)) {
        $list_kelas[] = $row;
    }
}

// Proses Pendaftaran Akun Siswa Baru
if (isset($_POST['daftar'])) {
    $nama      = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

    if ($course_id == 0) {
        $pesan = "❌ Silakan pilih salah satu kelas yang ingin diikuti terlebih dahulu.";
        $tipe_pesan = "error";
    } else {
        // 1. Cek apakah email sudah terdaftar menggunakan Prepared Statement
        $query_cek = "SELECT id_daftar FROM pendaftaran_siswa WHERE email_siswa = ?";
        $stmt_cek = mysqli_prepare($conn, $query_cek);
        
        if ($stmt_cek) {
            mysqli_stmt_bind_param($stmt_cek, "s", $email);
            mysqli_stmt_execute($stmt_cek);
            mysqli_stmt_store_result($stmt_cek);
            
            if (mysqli_stmt_num_rows($stmt_cek) > 0) {
                $pesan = "❌ Email sudah terdaftar! Gunakan email lain.";
                $tipe_pesan = "error";
            } else {
                // 2. Memasukkan data pendaftaran baru
                $query_insert = "INSERT INTO pendaftaran_siswa (nama_lengkap, email_siswa, password_siswa, course_id, payment_status, status_akun) 
                                 VALUES (?, ?, ?, ?, 'pending', 'Nonaktif')";
                $stmt_ins = mysqli_prepare($conn, $query_insert);
                
                if ($stmt_ins) {
                    mysqli_stmt_bind_param($stmt_ins, "sssi", $nama, $email, $password, $course_id);
                    if (mysqli_stmt_execute($stmt_ins)) {
                        $pesan = "🎉 Pendaftaran Berhasil! Akun Anda telah dibuat dengan status **Pending**. Silakan lakukan pembayaran untuk aktivasi.";
                        $tipe_pesan = "sukses";
                        $sukses_daftar = true;

                        // Cari nama kursus yang dipilih atau gunakan fallback statis berdasarkan ID
                        $nama_kursus_terpilih = ($course_id == 1) ? "1. Website (Premium)" : "2. Database (Premium)";
                        foreach ($list_kelas as $k) {
                            if ($k['id'] == $course_id) {
                                $nama_kursus_terpilih = !empty($k['course_name']) ? $k['course_name'] : $nama_kursus_terpilih;
                                break;
                            }
                        }

                        $harga_pilihan = ($course_id == 1) ? "Rp 150.000" : "Rp 200.000";

                        // Simpan data invoice bukti pendaftaran
                        $data_invoice = [
                            'nomor_invoice' => 'INV-' . time() . '-' . $course_id,
                            'nama' => $nama,
                            'email' => $email,
                            'kelas' => $nama_kursus_terpilih,
                            'harga' => $harga_pilihan,
                            'tanggal' => date("d M Y, H:i") . ' WIB'
                        ];
                    } else {
                        $pesan = "❌ Terjadi kesalahan saat menyimpan data.";
                    }
                    mysqli_stmt_close($stmt_ins);
                }
            }
            mysqli_stmt_close($stmt_cek);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Kursus Premium & Akun Siswa</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2, h3 { color: #2c3e50; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; }
        .btn-back { display: inline-block; margin-bottom: 15px; color: #2980b9; text-decoration: none; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background-color: #27ae60; color: white; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; }
        input[type="text"], input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { background-color: #2980b9; color: white; padding: 12px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; font-weight: bold; }
        .btn:hover { background-color: #3498db; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .alert-sukses { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .notice-box { background-color: #fff3cd; color: #856404; border-left: 5px solid #ffc107; padding: 15px; border-radius: 4px; margin-top: 20px; }
        
        /* Desain Modern Radio Button Card */
        .radio-container { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
        .radio-card { border: 2px solid #ddd; border-radius: 6px; padding: 15px; display: flex; align-items: center; cursor: pointer; transition: all 0.2s ease; background-color: #fafafa; }
        .radio-card:hover { border-color: #2980b9; background-color: #f4f9fc; }
        .radio-card input[type="radio"] { width: 20px; height: 20px; margin-right: 15px; cursor: pointer; accent-color: #2980b9; }
        .radio-info { flex-grow: 1; }
        .radio-title { font-weight: bold; font-size: 16px; color: #2c3e50; margin-bottom: 4px; }
        .radio-meta { font-size: 13px; color: #7f8c8d; }
        .radio-price { font-weight: bold; color: #e74c3c; font-size: 16px; margin-left: 15px; text-align: right; }
        
        /* Aktif state ketika dichecked via JavaScript */
        .radio-card.active { border-color: #2980b9; background-color: #ebf5fb; box-shadow: 0 2px 6px rgba(41,128,185,0.1); }

        /* Style Invoice Bukti Pembeli */
        .invoice-box { background: #fff; border: 2px dashed #2980b9; padding: 20px; border-radius: 8px; margin-bottom: 30px; background-color: #fcfcfc; }
        .invoice-box h4 { margin-top: 0; color: #2980b9; border-bottom: 1px solid #ddd; padding-bottom: 8px; }
        .invoice-row { display: flex; justify-content: space-between; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px dotted #eee; }
        .invoice-label { font-weight: bold; color: #555; }
        .btn-print { background-color: #e67e22; margin-top: 10px; width: 100%; }
        .btn-print:hover { background-color: #d35400; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="btn-back">&larr; Kembali ke Beranda</a>
    <h2>🏫 Halaman Khusus Pendaftaran Kursus Premium</h2>

    <?php if ($pesan != ""): ?>
        <div class="alert alert-<?php echo $tipe_pesan; ?>"><?php echo $pesan; ?></div>
    <?php endif; ?>

    <!-- BUKTI PENDAFTARAN UNTUK PEMBELI (Muncul setelah klik daftar) -->
    <?php if ($sukses_daftar && !empty($data_invoice)): ?>
        <div class="invoice-box" id="area-bukti">
            <h4>🧾 BUKTI PENDAFTARAN SISWA BARU (INVOICE)</h4>
            <div class="invoice-row">
                <span class="invoice-label">Nomor Registrasi:</span>
                <span><strong><?php echo $data_invoice['nomor_invoice']; ?></strong></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Waktu Pendaftaran:</span>
                <span><?php echo $data_invoice['tanggal']; ?></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Nama Lengkap:</span>
                <span><?php echo htmlspecialchars($data_invoice['nama']); ?></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Email Siswa:</span>
                <span><?php echo htmlspecialchars($data_invoice['email']); ?></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Kelas Yang Diikuti:</span>
                <span><mark><strong><?php echo htmlspecialchars($data_invoice['kelas']); ?></strong></mark></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Total Biaya Administrasi:</span>
                <span style="color: #e74c3c; font-weight: bold;"><?php echo $data_invoice['harga']; ?></span>
            </div>
            <div class="invoice-row">
                <span class="invoice-label">Status Pembayaran:</span>
                <span style="color: #e67e22; font-weight: bold;">⏳ PENDING (Menunggu Validasi)</span>
            </div>
            <p style="font-size: 13px; color: #666; margin-top: 15px;">*Simpan atau download halaman ini sebagai bukti transfer yang sah ke instruktur.</p>
            <button onclick="window.print()" class="btn btn-print">🖨️ Cetak / Simpan ke PDF</button>
        </div>
    <?php endif; ?>

    <!-- 1. Daftar Kursus Tersedia -->
    <h3>1. Pilihan Kelas Kursus yang Tersedia</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Kursus</th>
                <th>Tingkat</th>
                <th>Deskripsi</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Inisialisasi data loop backup seandainya record database kosong/berbeda field
            $tampil_kelas = !empty($list_kelas) ? $list_kelas : [
                ['id' => 1, 'course_name' => '1. Website (Premium)', 'level' => 'Pemula', 'description' => 'Belajar HTML, CSS, dan Dasar-dasar JavaScript.'],
                ['id' => 2, 'course_name' => '2. Database (Premium)', 'level' => 'Menengah', 'description' => 'Pengenalan SQL, Normalisasi, dan Manajemen Data.']
            ];

            foreach ($tampil_kelas as $kelas): 
                $nama_kursus      = !empty($kelas['course_name']) ? $kelas['course_name'] : (($kelas['id'] == 1) ? '1. Website (Premium)' : '2. Database (Premium)');
                $level_kursus     = !empty($kelas['level']) ? $kelas['level'] : (($kelas['id'] == 1) ? 'Pemula' : 'Menengah');
                $deskripsi_kursus = $kelas['description'] ?? '-';
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($nama_kursus); ?></strong></td>
                <td><?php echo htmlspecialchars($level_kursus); ?></td>
                <td><?php echo htmlspecialchars($deskripsi_kursus); ?></td>
                <td><span style="color: green; font-weight: bold;">🟢 Buka</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 2. Formulir Akun Baru -->
    <h3>2. Formulir Pembuatan Akun Baru (Siswa)</h3>
    <form action="" method="POST">
        <div class="form-group">
            <label for="username">Nama Lengkap:</label>
            <input type="text" id="username" name="username" placeholder="Masukkan nama lengkap Anda" required>
        </div>
        <div class="form-group">
            <label for="email">Email Aktif:</label>
            <input type="email" id="email" name="email" placeholder="Masukkan email untuk login nanti" required>
        </div>
        <div class="form-group">
            <label for="password">Kata Sandi (Password):</label>
            <input type="password" id="password" name="password" placeholder="Buat kata sandi minimal 8 karakter" required>
        </div>
        
        <!-- MODIFIKASI RADIO BUTTON BULAT CARD BESERTA HARGA -->
        <div class="form-group">
            <label>Pilih Kelas yang Ingin Diikuti:</label>
            <div class="radio-container">
                <?php 
                foreach ($tampil_kelas as $kelas):
                    $id_kelas     = $kelas['id'];
                    $nama_kursus  = !empty($kelas['course_name']) ? $kelas['course_name'] : (($id_kelas == 1) ? '1. Website (Premium)' : '2. Database (Premium)');
                    $level_kursus = !empty($kelas['level']) ? $kelas['level'] : (($id_kelas == 1) ? 'Pemula' : 'Menengah');
                    
                    // Definisikan harga custom masing-masing kelas kursus
                    $harga_kursus = ($id_kelas == 1) ? 'Rp 150.000' : 'Rp 200.000';
                    
                    // Cek auto-select jika dilempar dari parameter halaman index
                    $checked = ($id_kelas == $kelas_terpilih || (empty($kelas_terpilih) && $id_kelas == 1)) ? 'checked' : '';
                    $active_class = ($checked != '') ? 'active' : '';
                ?>
                    <label class="radio-card <?php echo $active_class; ?>" onclick="updateRadioStyle(this)">
                        <input type="radio" name="course_id" value="<?php echo $id_kelas; ?>" <?php echo $checked; ?> required>
                        <div class="radio-info">
                            <div class="radio-title"><?php echo htmlspecialchars($nama_kursus); ?></div>
                            <div class="radio-meta">Tingkat: <?php echo htmlspecialchars($level_kursus); ?> | Status: Aktif</div>
                        </div>
                        <div class="radio-price"><?php echo $harga_kursus; ?></div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <button type="submit" name="daftar" class="btn">DAFTAR SEKARANG</button>
    </form>

    <!-- 3. Syarat Aktivasi -->
    <div class="notice-box">
        <h4>⚠️ Syarat & Prosedur Aktivasi Akun Siswa:</h4>
        <p>Setelah mengklik tombol daftar, status akun siswa Anda di sistem akan diset sebagai <strong>Pending / Nonaktif</strong>.</p>
        <ol>
            <li>Silakan lakukan pelunasan pembayaran administrasi kelas kursus.</li>
            <li>Data pendaftaran Anda secara otomatis akan masuk ke antrean validasi pengajar.</li>
            <li>Apabila pembayaran telah dinyatakan <strong>Lunas</strong> dan dikonfirmasi oleh pengajar di Dashboard Guru, status akun Anda akan langsung berubah menjadi <strong>Online / Aktif</strong> dan materi pelajaran siap diakses.</li>
        </ol>
    </div>
</div>

<script>
    // Efek JavaScript interaktif untuk mempercantik pilihan card bulat yang aktif
    function updateRadioStyle(element) {
        // Hapus class active dari semua card pilihan kelas
        const cards = document.querySelectorAll('.radio-card');
        cards.forEach(card => card.classList.remove('active'));
        
        // Tambahkan class active ke card bulat yang diklik pembeli
        element.classList.add('active');
    }
</script>
</body>
</html>