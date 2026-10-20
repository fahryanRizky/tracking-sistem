# Tracking Sistem - Project Claim Verification

Sistem verifikasi klaim tagihan proyek konstruksi yang dirancang untuk mencegah kerugian akibat ketidaksesuaian antara klaim Vendor dan realisasi lapangan.

## 📖 Latar Belakang (Studi Kasus)
PT Jaya (Owner) menerima tagihan dari PT Lampu (Vendor) untuk pemasangan 10 lampu di area kerja. Karena tidak bisa memantau lapangan setiap hari, Owner mengalami kesulitan memverifikasi apakah tagihan tersebut sesuai dengan yang benar-benar terpasang. 

**Tracking Sistem** hadir sebagai solusi "meja pertemuan" digital. Sistem ini membandingkan data klaim dari Vendor dengan data realisasi lapangan yang diinput oleh Admin (Site Manager), sehingga Owner dapat mengambil keputusan (Approve/Reject) berdasarkan data yang akurat, lengkap dengan bukti foto dan rekam jejak.

## ✨ Fitur Utama
- **Multi-Role Access:** Vendor (Klaim), Admin (Verifikasi Lapangan), Owner (Approval).
- **Perbandingan Otomatis:** Menghitung selisih kuantitas dan nominal secara *real-time* (Highlight merah jika terjadi kekurangan).
- **Bukti Foto:** Admin dapat mengunggah foto lapangan sebagai bukti verifikasi.
- **Kolom Diskusi:** Fitur *chat* di dalam klaim untuk memfasilitasi komunikasi antar pihak.
- **Sistem Revisi (Versioning):** Jika klaim ditolak, Vendor dapat merevisi, dan riwayat versi lama tetap tersimpan.
- **Notifikasi Email Otomatis:** Routing email (Vendor -> Admin -> Owner) menggunakan *Queue Worker*.
- **Export PDF Formal:** Laporan klaim siap cetak dengan kop surat dan kolom tanda tangan.
- **Pencarian & Filter:** Memudahkan pencarian klaim berdasarkan nama vendor, status, dan tanggal.

## 🛠️ Teknologi yang Digunakan
- **Backend:** Laravel 12, PHP 8.3+
- **Database:** MySQL
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Library Tambahan:** `barryvdh/laravel-dompdf`, Laravel Breeze (Auth)
- **Testing Email:** Mailtrap (SMTP)

## 🚀 Cara Menjalankan Project Secara Lokal

1. Clone repository ini:
   ```bash
   git clone https://github.com/username-kamu/tracking-sistem.git
   cd tracking-sistem