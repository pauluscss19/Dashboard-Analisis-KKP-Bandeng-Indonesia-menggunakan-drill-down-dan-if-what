<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kkp_bandeng');

// Fungsi koneksi database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Fungsi untuk format angka
function formatNumber($number) {
    return number_format($number, 2, ',', '.');
}

// Fungsi untuk format rupiah
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>
    