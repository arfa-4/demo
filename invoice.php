<?php
session_start();

// Proteksi halaman: pastikan user sudah login
if (!isset($_SESSION['role'])) {
    header("Location: login.html");
    exit();
}

// Koneksi ke Database
$host     = "localhost";
$username = "root";
$password = "";
$database = "lms_demo";

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil ID pendaftaran (enrollment_id) dari URL
if (!isset($_GET['enrollment_id'])) {
    die("ID Pendaftaran tidak ditemukan.");
}

$enrollment_id = intval($_GET['enrollment_id']);

// == PERBAIKAN DI SINI ==
// Kolom tanggal (created_at / enrollment_date) dihapus dari query agar tidak memicu error database
$query = "SELECT e.id AS enroll_id, e.status, 
                 u.nama AS student_name, u.email AS student_email, 
                 c.title AS course_title, c.price 
          FROM enrollments e
          JOIN users u ON e.student_id = u.id
          JOIN courses c ON e.course_id = c.id
          WHERE e.id = $enrollment_id";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($koneksi));
}

if (mysqli_num_rows($result) === 0) {
    die("Data invoice tidak ditemukan.");
}

$invoice = mysqli_fetch_assoc($result);

// Default harga jika di database tidak ada atau bernilai 0 (contoh kelas premium standard)
$harga_kelas = ($invoice['price'] > 0) ? $invoice['price'] : 250000; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #LMS-<?php echo $invoice['enroll_id']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8f9fa; color: #333; margin: 0; padding: 4px; }
        .invoice-box { max-width: 800px; margin: 30px auto; padding: 30px; background: white; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .invoice-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #28a745; padding-bottom: 20px; margin-bottom: 20px; }
        .invoice-header h2 { margin: 0; color: #28a745; font-size: 28px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .status-active { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-block h4 { margin: 0 0 8px 0; color: #666; font-size: 14px; text-transform: uppercase; }
        .info-block p { margin: 0; font-size: 15px; line-height: 1.5; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table th { background-color: #f8f9fa; color: #555; font-weight: bold; padding: 12px; border-bottom: 2px solid #ddd; text-align: left; }
        table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        
        .total-section { display: flex; justify-content: flex-end; font-size: 18px; font-weight: bold; margin-bottom: 30px; }
        .total-box { border-top: 2px solid #333; padding-top: 10px; width: 250px; display: flex; justify-content: space-between; }
        
        .payment-instructions { background: #f1f3f5; padding: 15px; border-radius: 6px; border-left: 4px solid #17a2b8; margin-bottom: 20px; }
        .payment-instructions h5 { margin: 0 0 8px 0; color: #17a2b8; font-size: 15px; }
        .payment-instructions p { margin: 0; font-size: 13px; color: #555; line-height: 1.6; }
        
        .actions { display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 10px 20px; border-radius: 4px; font-weight: bold; text-decoration: none; cursor: pointer; border: none; font-size: 14px; }
        .btn-print { background-color: #6c757d; color: white; }
        .btn-print:hover { background-color: #5a6268; }
        .btn-back { background-color: #007bff; color: white; }
        .btn-back:hover { background-color: #0069d9; }

        @media print {
            body { background: white; }
            .invoice-box { box-shadow: none; border: none; margin: 0; padding: 0; }
            .actions, .payment-instructions { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-box">
    <!-- Header Invoice -->
    <div class="invoice-header">
        <div>
            <h2>INVOICE</h2>
            <small style="color:#777;">Nomor Nota: #LMS-<?php echo $invoice['enroll_id']; ?></small>
        </div>
        <div>
            <?php if ($invoice['status'] === 'active'): ?>
                <span class="status-badge status-active">Lunas / Aktif</span>
            <?php else: ?>
                <span class="status-badge status-pending">Menunggu Pembayaran</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Pelanggan & Transaksi -->
    <div class="info-grid">
        <div class="info-block">
            <h4>Diterbitkan Untuk:</h4>
            <p><strong><?php echo htmlspecialchars($invoice['student_name']); ?></strong></p>
            <p><?php echo htmlspecialchars($invoice['student_email']); ?></p>
            <p>Peran: Siswa / Student</p>
        </div>
        <div class="info-block" style="text-align: right;">
            <h4>Detail Tanggal:</h4>
            <p><strong>Tanggal Cetak:</strong> 
                <?php 
                // Langsung menggunakan waktu sekarang agar tidak bergantung pada kolom database
                echo date('d M Y H:i'); 
                ?>
            </p>
            <p><strong>Metode:</strong> Transfer Bank (Manual)</p>
        </div>
    </div>

    <!-- Tabel Item Kelas -->
    <table>
        <thead>
            <tr>
                <th>Deskripsi Kelas Premium</th>
                <th style="text-align: right;">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Akses Kelas Premium: <?php echo htmlspecialchars($invoice['course_title']); ?></strong><br>
                    <small style="color:#777;">Akses penuh ke seluruh materi, kuis otomatis, penugasan, dan umpan balik langsung dari guru.</small>
                </td>
                <td style="text-align: right; font-weight: bold;">
                    Rp <?php echo number_format($harga_kelas, 0, ',', '.'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Bagian Total Tagihan -->
    <div class="total-section">
        <div class="total-box">
            <span>Total Bayar:</span>
            <span style="color: #28a745;">Rp <?php echo number_format($harga_kelas, 0, ',', '.'); ?></span>
        </div>
    </div>

    <!-- Instruksi Pembayaran (Hanya tampil jika status masih pending) -->
    <?php if ($invoice['status'] === 'pending'): ?>
        <div class="payment-instructions">
            <h5>⚠️ Instruksi Langkah Pembayaran Manual:</h5>
            <p>1. Silakan transfer tepat sebesar <strong>Rp <?php echo number_format($harga_kelas, 0, ',', '.'); ?></strong> ke rekening berikut:</p>
            <p style="margin-left: 15px; margin-top: 5px;"><strong>Bank BCA:</strong> 123-4567-890 (a.n. Administrator LMS Demo)</p>
            <p>2. Setelah transfer berhasil dilakukan, harap konfirmasi dan tunjukkan bukti nota ini ke Guru/Pengajar Anda.</p>
            <p>3. Akses kelas premium akan otomatis aktif setelah disetujui melalui menu <em>Persetujuan Pembayaran Manual</em> di Dashboard Guru.</p>
        </div>
    <?php else: ?>
        <div class="payment-instructions" style="border-left-color: #28a745; background: #eafaf1;">
            <h5 style="color: #28a745;">✓ Pembayaran Terverifikasi</h5>
            <p style="color: #155724;">Terima kasih! Pembayaran Anda telah disetujui secara manual oleh instruktur/guru. Anda sekarang memiliki akses penuh ke kelas ini.</p>
        </div>
    <?php endif; ?>

    <!-- Tombol Aksi -->
    <div class="actions">
        <button onclick="window.history.back();" class="btn btn-back">← Kembali</button>
        <button onclick="window.print();" class="btn btn-print">🖨️ Cetak Invoice</button>
    </div>
</div>

</body>
</html>
<?php mysqli_close($koneksi); ?>