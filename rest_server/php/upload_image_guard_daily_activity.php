<?php

// Pastikan path ke global_path.php sudah benar
require_once 'global_path.php'; 

// --- 1. Ambil data POST dari Android ---
// 'nama_file' dikirim dari Android (misal: DA20251214001.jpeg)
$nama_file = $_POST['nama_file'] ?? null;
// 'foto' adalah Base64 String
$image_base64 = $_POST['foto'] ?? null;

// Dekode Base64
$image = $image_base64 ? base64_decode($image_base64) : false;

// --- 2. Tentukan Direktori Target ---
// Ganti path ini sesuai dengan struktur server lu untuk Daily Activity
// Disarankan: /rest_server/image/daily_activity/
$target_directory_base = "/var/www/html/dev/ess-api-android-guard/rest_server/image/daily_activity/";

// Final Target Path: (misal: .../daily_activity/DA20251214001.jpeg)
$target_dir = $target_directory_base . $nama_file;


// --- 3. Validasi dan Proses Upload ---

// A. Pengecekan nama file dan Base64 data
if (empty($nama_file) || empty($image_base64)) {
    echo json_encode(array('response' => 'Error', 'message' => 'Data POST (nama_file atau foto) kosong.'));
    exit;
}

// B. Pengecekan apakah direktori bisa di-write
if (!is_writable(dirname($target_dir))) {
    // Lu mungkin perlu membuat direktori 'daily_activity' jika belum ada
    if (!is_dir(dirname($target_dir))) {
         // Coba buat direktori jika belum ada
         if (!mkdir(dirname($target_dir), 0777, true)) {
             echo json_encode(array('response' => 'Error', 'message' => 'Gagal membuat direktori: ' . dirname($target_dir)));
             exit;
         }
    }
    
    // Pengecekan ulang writeable setelah coba buat
    if (!is_writable(dirname($target_dir))) {
        echo json_encode(array('response' => 'Error', 'message' => 'Directory is not writable: ' . dirname($target_dir)));
        exit;
    }
}

// C. Pengecekan apakah base64 image valid
if ($image === false) {
    echo json_encode(array('response' => 'Error', 'message' => 'Invalid base64 image data or corrupted.'));
    exit;
}

// D. Menyimpan File
if (file_put_contents($target_dir, $image)) {
    // Sukses: Kirim response "Success"
    echo json_encode(array('response' => 'Success', 'message' => 'Image uploaded successfully.'));
} else {
    // Gagal menyimpan
    $error = error_get_last();
    echo json_encode(array("response" => "Error", "message" => "Image not uploaded", "error_detail" => $error['message']));
}

?>