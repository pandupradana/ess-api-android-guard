<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Restserver\Libraries\REST_Controller;

require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/Format.php';

class Reimburse_bbm extends REST_Controller
{
   
    public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('default', TRUE);
		$this->db2 = $this->load->database('db2', TRUE);
		$this->db_absensi = $this->load->database('db_absensi', TRUE);

		$this->load->model('pengajuan/M_Reimburse_bbm');
	}

    public function insert_form_reimburse_bbm_post()
    {
        // Generate ID -> BBM_15112025_0001
        $date    = date('dmY');
        $prefix  = "BBM_" . $date . "_";

        $lastId = $this->M_Reimburse_bbm->get_last_id_today($date);

        $nextNumber = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        $id_reimburse = $prefix . $nextNumber;

        // Ambil parameter dari POST
        $data = [
            'id_reimburse_bbm' 	=> $id_reimburse,
		    'nip_pegawai'      	=> $this->post('nip_pegawai'),
		    'nama_pegawai'     	=> $this->post('nama_pegawai'),
		    'nomor_polisi'     	=> $this->post('nopol'),
		    'jenis_kendaraan'  	=> $this->post('jenis_kendaraan'),
		    'tanggal_permintaan'  	=> $this->post('tanggal_permintaan'),
		    'tanggal_db'       	=> date('Y-m-d H:i:s', strtotime($this->post('tanggal'))),
		    'km_awal'          	=> $this->post('km_awal'),
		    'km_akhir'         	=> $this->post('km_akhir'),
		    'keterangan'       	=> $this->post('keterangan'),
		    'foto_km_awal'     	=> $this->post('foto_km_awal'),
		    'foto_km_akhir'    	=> $this->post('foto_km_akhir'),
		    'url_foto_km_awal'  => $this->post('url_foto_km_awal'),
		    'url_foto_km_akhir' => $this->post('url_foto_km_akhir'), // sudah bener
		    'status'           	=> 0
        ];

        if ($this->M_Reimburse_bbm->insert_form_reimburse_bbm($data) > 0) {
            $this->response([
                'status' => true,
                'message' => 'Reimburse BBM berhasil dibuat',
                'id_reimburse_bbm' => $id_reimburse
            ], REST_Controller::HTTP_CREATED);
        } else {
            $this->response([
                'status' => false,
                'message' => 'Gagal membuat data reimburse BBM'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }


    public function get_reimburse_bbm_get()
	{
	    $nip = $this->get('nip_pegawai');

	    if (!$nip) {
	        return $this->response([
	            'status' => false,
	            'total_data' => 0,
	            'message' => 'Parameter nip_pegawai wajib diisi'
	        ], REST_Controller::HTTP_BAD_REQUEST);
	    }

	    $data = $this->M_Reimburse_bbm->get_reimburse_bbm_by_nip($nip);
	    $total = count($data);

	    if ($total > 0) {
	        $this->response([
	            'status' => true,
	            'total_data' => $total,
	            'data' => $data
	        ], REST_Controller::HTTP_OK);
	    } else {
	        $this->response([
	            'status' => false,
	            'total_data' => 0,
	            'data' => [],
	            'message' => 'Data tidak ditemukan'
	        ], REST_Controller::HTTP_OK);
	    }
	}

}