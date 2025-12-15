<?php

// Pastikan path ke global_path.php sudah benar (jika dipakai)
// require_once 'global_path.php'; // Hapus/sesuaikan jika tidak diperlukan

// --- 1. Ambil data POST dari CI Controller ---
$nama_file = $_POST['nama_file'] ?? null;     // Contoh: RM001_101_1_12345.jpeg
$image_base64 = $_POST['foto'] ?? null;       // Base64 String
$nama_folder = $_POST['nama_folder'] ?? 'default_upload'; // Contoh: patroli_area

// Dekode Base64
$image = $image_base64 ? base64_decode($image_base64) : false;

// --- 2. Tentukan Direktori Target DYNAMIC ---
// Pastikan path ini benar di server Linux lu
$base_dir = "/var/www/html/dev/ess-api-android-guard/rest_server/image/"; 
$target_directory_full = $base_dir . $nama_folder . "/"; 
$target_dir = $target_directory_full . $nama_file;


// --- 3. Validasi dan Proses Upload ---

// A. Pengecekan nama file dan Base64 data
if (empty($nama_file) || empty($image_base64)) {
    echo json_encode(array('response' => 'Error', 'message' => 'Data POST (nama_file atau foto) kosong.'));
    exit;
}

// B. Pengecekan apakah direktori bisa di-write (dan coba buat jika belum ada)
if (!is_dir($target_directory_full)) {
    if (!mkdir($target_directory_full, 0777, true)) {
        echo json_encode(array('response' => 'Error', 'message' => 'Gagal membuat direktori: ' . $target_directory_full));
        exit;
    }
}

// Pengecekan ulang writeable
if (!is_writable($target_directory_full)) {
    echo json_encode(array('response' => 'Error', 'message' => 'Directory is not writable: ' . $target_directory_full));
    exit;
}

// C. Pengecekan apakah base64 image valid
if ($image === false) {
    echo json_encode(array('response' => 'Error', 'message' => 'Invalid base64 image data or corrupted.'));
    exit;
}

// D. Menyimpan File
if (file_put_contents($target_dir, $image)) {
    echo json_encode(array('response' => 'Success', 'message' => 'Image uploaded successfully.'));
} else {
    // Tambahin error detail ketika gagal menyimpan gambar
    $error = error_get_last();
    echo json_encode(array("response" => "Error", "message" => "Image not uploaded", "error_detail" => $error['message']));
}

?>