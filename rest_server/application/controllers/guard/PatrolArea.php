<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';

class PatrolArea extends REST_Controller
{
    // URL UPLOADER PHP (UBAH SESUAI PATH LU)
    const PHP_UPLOADER_URL = 'http://31.97.106.123/dev/ess-api-android-guard/rest_server/php/upload_image_ci_patroli.php';

    public function __construct()
    {
        parent::__construct();
        $this->db_guard = $this->load->database('db_guard', TRUE);
        $this->load->model('guard/M_PatrolArea');
    }

    /**
     * Endpoint: POST /api/guard/PatrolArea/submit_patroli
     */
    public function submit_patroli_post()
    {
        // 1. Ambil Data dari Android (Sudah JSON Encoded)
        $id_master_room = $this->post('id_master_room');
        $id_user_submit = $this->post('id_user_submit');
        $keterangan_patroli = $this->post('keterangan_patroli');
        $nama_room_posted = $this->post('nama_master_room'); // <<< AMBIL DARI POST BARU

        // Data JSON dari Android (String)
        $foto_base64_list_json = $this->post('foto_base64_list');
        $nama_foto_list_json = $this->post('nama_foto_list');
        $nama_folder = $this->post('nama_folder') ?? 'patroli_area'; // default folder

        // 2. Validasi Input
        if (empty($id_master_room) || empty($id_user_submit) || empty($keterangan_patroli) || empty($foto_base64_list_json)) {
            $this->response(['status' => false, 'message' => 'Parameter tidak lengkap: Room ID, User ID, Keterangan, atau Foto wajib diisi.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // 3. Decode JSON List Foto
        $base64_list = json_decode($foto_base64_list_json, true);
        $nama_file_list = json_decode($nama_foto_list_json, true);

        if (!is_array($base64_list) || count($base64_list) == 0 || !is_array($nama_file_list) || count($base64_list) != count($nama_file_list)) {
             $this->response(['status' => false, 'message' => 'Format data foto Base64 atau Nama file tidak valid/kosong.'], REST_Controller::HTTP_BAD_REQUEST);
             return;
        }


        // --- START TRANSACTION ---
        $this->db_guard->trans_start();

        // 4. Insert ke guard_patrol_detail (Header)
        $id_patrol_detail = $this->M_PatrolArea->generateIdPatrolDetail();
        $datetime_patrol = date('Y-m-d H:i:s');

        $data_detail = [
            'id_guard_patrol_detail' => $id_patrol_detail,
            'id_master_room'         => $id_master_room,
            'nama_room'              => $nama_room_posted,
            'user_create'            => $id_user_submit,
            'datetime_patrol'        => $datetime_patrol,
            'status'                 => 1, // Aktif
            'keterangan'             => $keterangan_patroli,
            'timestamp_create'       => $datetime_patrol,
        ];

        $this->M_PatrolArea->createPatrolDetail($data_detail);
        
        $insert_photo_batch = [];
        $base_url_image = 'http://31.97.106.123/dev/ess-api-android-guard/rest_server/image/patroli_area/'; // GANTI SESUAI PATH LU

        // 5. Looping Upload Foto dan Siapkan Data Insert Batch
        foreach ($base64_list as $index => $base64_data) {
            $nama_file = $nama_file_list[$index];
            $full_photo_url = $base_url_image . $nama_file;
            
            // Panggil PHP Uploader menggunakan cURL (Internal Request)
            $upload_success = $this->callPhpUploader($nama_file, $base64_data, $nama_folder);

            if ($upload_success) {
                // Jika upload ke folder sukses, siapkan data untuk database
                $insert_photo_batch[] = [
                    'id_guard_patrol_detail' => $id_patrol_detail,
                    'id_master_room'         => $id_master_room,
                    'id_user_submit'         => $id_user_submit,
                    'tanggal_create'         => $datetime_patrol,
                    // Keterangan setiap foto bisa disamakan atau di-modifikasi jika diperlukan
                    'keterangan_patroli'     => $keterangan_patroli . " (Foto ke-" . ($index + 1) . ")", 
                    'url_photo'              => $full_photo_url,
                ];
            } else {
                // Jika salah satu foto gagal upload, batalkan semua transaksi
                $this->db_guard->trans_rollback();
                $this->response(['status' => false, 'message' => 'Gagal mengupload salah satu foto. Transaksi dibatalkan.'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }
        }

        // 6. Insert Semua URL Foto ke guard_patrol_photo (Detail)
        if (!empty($insert_photo_batch)) {
            $this->M_PatrolArea->insertPatrolPhotos($insert_photo_batch);
        }

        // --- END TRANSACTION ---
        if ($this->db_guard->trans_status() === FALSE) {
            $this->db_guard->trans_rollback();
            $this->response(['status' => false, 'message' => 'Gagal menyimpan ke database (Transaction Rollback).'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $this->db_guard->trans_commit();
            $this->response(['status' => true, 'message' => 'Laporan Patroli Area berhasil disimpan!'], REST_Controller::HTTP_CREATED);
        }
    }

    /**
     * Fungsi Internal: Memanggil PHP Uploader melalui cURL
     */
    private function callPhpUploader($nama_file, $base64_data, $nama_folder)
    {
        $ch = curl_init();

        $post_data = [
            'nama_file' => $nama_file,
            'foto'      => $base64_data,
            'nama_folder' => $nama_folder // Kirim nama folder agar PHP Uploader bisa dinamis
        ];

        curl_setopt($ch, CURLOPT_URL, self::PHP_UPLOADER_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch); // <<< Tambahkan ini

        curl_close($ch);

        if ($http_code != 200 || empty($response)) {
            log_message('error', 'Curl failed or HTTP code non-200 for ' . $nama_file . ': Response: ' . $response);
            return false;
        }

        // Jika ada error cURL (misal: gagal konek)
        if ($curl_error) { 
            log_message('error', 'Curl Connection ERROR for ' . $nama_file . ': ' . $curl_error);
            return false;
        }

        $json_res = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || (isset($json_res['response']) && $json_res['response'] != 'Success')) {
            log_message('error', 'PHP Uploader responded FAILED for ' . $nama_file . ': ' . $response);
            return false;
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'PHP Uploader responded NON-JSON for ' . $nama_file . ': ' . $response);
            return false;
        }

        if (!isset($json_res['response']) || $json_res['response'] != 'Success') {
            log_message('error', 'PHP Uploader responded FAILED for ' . $nama_file . ': ' . print_r($json_res, true));
            return false;
        }

        return true;
    }


    public function get_history_get() {
        // Otentikasi
        // if (!$this->rest->api_key_check()) {
        //     $this->response(['status' => false, 'message' => 'Unauthorized Access.'], REST_Controller::HTTP_UNAUTHORIZED);
        //     return;
        // }

        // Ambil Filter (GET parameters)
        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');
        $id_user = $this->get('id_user'); // Tambahkan filter jika lu mau spesifik user

        // Panggil Model
        $history_data = $this->M_PatrolArea->getPatrolHistory($start_date, $end_date, $id_user);

        if ($history_data) {
            $this->response([
                'status' => true,
                'message' => 'Data riwayat patroli berhasil dimuat.',
                'total_data' => count($history_data),
                'data' => $history_data
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Riwayat patroli tidak ditemukan atau database kosong.'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }


    public function get_room_get() {
        // Otentikasi Wajib (sesuai permintaan lu)
        // if (!$this->rest->api_key_check()) {
        //     $this->response(['status' => false, 'message' => 'Unauthorized Access.'], REST_Controller::HTTP_UNAUTHORIZED);
        //     return;
        // }

        $id_master_room = $this->get('id_master_room');

        if (!$id_master_room) {
            $this->response(['status' => false, 'message' => 'ID Master Room diperlukan.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Panggil Model
        $room_data = $this->M_PatrolArea->getSingleMasterRoom($id_master_room);

        if ($room_data) {
            $this->response([
                'status' => true,
                'message' => 'Data ruangan berhasil dimuat.',
                'data' => $room_data
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Ruangan dengan ID ' . $id_master_room . ' tidak ditemukan atau status tidak aktif.'
            ], REST_Controller::HTTP_NOT_FOUND);
        }
    }

}