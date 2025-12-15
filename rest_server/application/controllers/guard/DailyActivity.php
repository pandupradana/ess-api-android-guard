<?php
defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */

// use namespace
use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';

/**
 * 
 */
class DailyActivity extends REST_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('default', TRUE);
		$this->db2 = $this->load->database('db2', TRUE);
		$this->db_absensi = $this->load->database('db_absensi', TRUE);
		$this->db_guard = $this->load->database('db_guard', TRUE);


		$this->load->model('guard/M_DailyActivity');
	}

	/**
     * Endpoint: POST /api/dailyactivity/index
     * Untuk menyimpan data aktivitas harian
     */
    public function index_post() {
        // Ambil data dari Android
        $title_activity = $this->post('title_activity');
        $keterangan_activity = $this->post('keterangan_activity');
        $user_create = $this->post('user_create'); // Wajib kirim ID/username user

        // Validasi
        if (empty($title_activity) || empty($keterangan_activity) || empty($user_create)) {
            $this->response([
                'status' => false,
                'message' => 'Parameter tidak lengkap (title, keterangan, user_create)'
            ], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        // Generate ID dan DateTime
        $id_daily_activity = $this->M_DailyActivity->generateIdDailyActivity();
        $datetime_activity = date('Y-m-d H:i:s');
        
        // Asumsi URL Foto akan di-update BELAKANGAN setelah upload foto sukses
        $foto_url_activity = ''; 

        $data_insert = [
            'id_daily_activity'     => $id_daily_activity,
            'datetime_activity'     => $datetime_activity,
            'title_activity'        => $title_activity,
            'keterangan_activity'   => $keterangan_activity,
            'foto_url_activity'     => $foto_url_activity, // Kosongkan dulu
            'user_create'           => $user_create,
            'status'                => 1, // Aktif
            'timestamp_create'      => $datetime_activity,
            'timestamp_update'      => $datetime_activity,
        ];

        if ($this->M_DailyActivity->createDailyActivity($data_insert) > 0) {
            
            // SUKSES: Kirim ID Aktivitas ke Android agar bisa digunakan untuk upload foto
            $this->response([
                'status' => true,
                'message' => 'Daily activity berhasil disimpan. Silakan upload foto.',
                'data' => [
                    'id_daily_activity' => $id_daily_activity,
                    'datetime_activity' => $datetime_activity
                ]
            ], REST_Controller::HTTP_CREATED);
            
        } else {
            // Gagal
            $this->response([
                'status' => false,
                'message' => 'Gagal menyimpan data ke database.'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    // Fungsi untuk update URL foto (Dipanggil setelah Android upload foto sukses)
    public function update_photo_post() {
        // UBAH $this->put() MENJADI $this->post()
        
        $id = $this->post('id_daily_activity');
        $url = $this->post('foto_url_activity');
        
        if (empty($id) || empty($url)) {
            $this->response(['status' => false, 'message' => 'ID dan URL wajib diisi.'], REST_Controller::HTTP_BAD_REQUEST);
            return;
        }

        $this->db_guard->where('id_daily_activity', $id);
        $this->db_guard->update('guard_daily_activity', ['foto_url_activity' => $url, 'timestamp_update' => date('Y-m-d H:i:s')]);
        
        if ($this->db_guard->affected_rows() > 0) {
            $this->response(['status' => true, 'message' => 'URL foto berhasil diperbarui.'], REST_Controller::HTTP_OK);
        } else {
            $this->response(['status' => false, 'message' => 'Gagal atau tidak ada perubahan pada URL foto.'], REST_Controller::HTTP_NOT_MODIFIED);
        }
    }


	 /**
	 * Endpoint: GET /api/dailyactivity/history
	 * Optional param: start_date, end_date
	 */
	public function history_get()
	{
	    $start_date = $this->get('start_date'); // yyyy-mm-dd
	    $end_date   = $this->get('end_date');   // yyyy-mm-dd

	    $list = $this->M_DailyActivity->getDailyActivityHistory($start_date, $end_date);

	    if (count($list) > 0) {
	        $this->response([
	            'status' => true,
	            'message' => 'Data daily activity berhasil diambil',
	            'total_data' => count($list),
	            'data' => $list
	        ], REST_Controller::HTTP_OK);
	    } else {
	        $this->response([
	            'status' => false,
	            'message' => 'Data daily activity tidak ditemukan',
	            'total_data' => 0,
	            'data' => []
	        ], REST_Controller::HTTP_OK);
	    }
	}

}
