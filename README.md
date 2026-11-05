# LMS Demo

Aplikasi Learning Management System (LMS) sederhana berbasis PHP dan MySQL/MariaDB. Aplikasi ini menyediakan katalog kursus, pendaftaran siswa, dashboard untuk siswa/guru/admin, materi dan tugas, kuis, forum diskusi, serta pengelolaan kursus dan pengguna.

## Fitur

- Menampilkan katalog kursus dan formulir pendaftaran siswa.
- Login siswa dan login berbasis peran melalui halaman login umum.
- Dashboard siswa untuk melihat kursus, materi, tugas, kuis, invoice, dan forum.
- Dashboard guru untuk mengelola materi, tugas, kuis, pengumuman, pendaftaran, dan nilai.
- Dashboard admin untuk melihat dan menambahkan pengguna.
- Pengelolaan kursus dan validasi pembayaran.

## Teknologi

- PHP dengan ekstensi `mysqli`
- MySQL atau MariaDB
- HTML, CSS, dan JavaScript

## Persiapan

1. Pasang lingkungan PHP dan MySQL, misalnya [Laragon](https://laragon.org/).
2. Salin atau clone repository ini ke folder web server, misalnya `C:\laragon\www\demo`.
3. Buat database bernama `lms_demo`.
4. Siapkan tabel dan kolom yang digunakan aplikasi. Dump/skema database belum disertakan dalam repository ini.
5. Periksa konfigurasi koneksi database di `koneksi.php` serta file PHP lain yang membuat koneksi secara langsung. Konfigurasi bawaan menggunakan `localhost`, pengguna `root`, tanpa kata sandi, dan database `lms_demo`.
6. Jalankan Apache dan MySQL dari Laragon, lalu buka <http://localhost/demo/>.

Tabel yang dirujuk oleh kode meliputi `users`, `courses`, `pendaftaran_siswa`, `enrollments`, `materials`, `assignments`, `quizzes`, `submissions`, `forum_messages`, dan `announcements`. Pastikan struktur tabel dan kolom sesuai dengan query di file PHP.

## Halaman utama

| File | Kegunaan |
| --- | --- |
| `index.php` | Beranda dan katalog kursus |
| `daftar_kursus.php` | Pendaftaran akun siswa dan kursus |
| `login.html`, `proses_login.php` | Login umum |
| `login_siswa.php` | Login siswa |
| `dashboard_siswa.php` | Dashboard siswa |
| `dashboard_guru.php` | Dashboard guru |
| `dashboard_admin.php` | Dashboard admin |
| `kelola_kursus.php` | Pengelolaan kursus |
| `kerjakan_kuis.php` | Halaman pengerjaan kuis |
| `forum.php` | Forum diskusi |
| `invoice.php` | Invoice pendaftaran kursus |
| `validasi_pembayaran.php`, `proses_konfirmasi.php` | Validasi/konfirmasi pembayaran |
| `proses_materi.php`, `proses_tugas.php`, `proses_kuis.php` | Pemrosesan materi, tugas, dan kuis |
| `proses_tambah_user.php` | Penambahan pengguna |
| `logout.php` | Mengakhiri sesi |

## Catatan

- Repository ini belum menyertakan dump database, sehingga database harus disiapkan terpisah sebelum aplikasi dapat digunakan.
- Beberapa tautan di halaman mengarah ke file yang belum tersedia di repository, termasuk `login_guru.php`, `login_admin.php`, dan `hapus_user.php`.
- Kredensial database ditulis langsung di beberapa file. Sesuaikan semuanya dengan konfigurasi lingkungan lokal sebelum menjalankan aplikasi.
