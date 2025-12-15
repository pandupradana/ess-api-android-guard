<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_PatrolArea extends CI_Model {

    // Asumsi koneksi database guard di $this->db_guard
    public function __construct() {
        parent::__construct();
        // Pastikan $this->db_guard sudah didefinisikan di Controller atau koneksi default
    }

    /**
     * Generate ID Patrol Detail (Contoh: PD20251215001)
     */
    public function generateIdPatrolDetail() {
        $today = date('Ymd');
        $prefix = "PD" . $today;

        $this->db_guard->select('id_guard_patrol_detail');
        $this->db_guard->like('id_guard_patrol_detail', $prefix, 'after');
        $this->db_guard->order_by('id_guard_patrol_detail', 'DESC');
        $this->db_guard->limit(1);
        $query = $this->db_guard->get('guard_patrol_detail');
        
        $last_number = 0;
        
        if ($query->num_rows() > 0) {
            $last_id = $query->row()->id_guard_patrol_detail;
            $last_number = (int) substr($last_id, -3);
        }

        $new_number = $last_number + 1;
        return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menyimpan data header Patroli Area
     */
    public function createPatrolDetail($data) {
        $this->db_guard->insert('guard_patrol_detail', $data);
        return $this->db_guard->affected_rows();
    }

    /**
     * Menyimpan data foto Patroli Area (Multiple Rows)
     * @param array $data_batch Array of photo data
     */
    public function insertPatrolPhotos($data_batch) {
        if (!empty($data_batch)) {
            // Gunakan insert_batch untuk performa cepat
            $this->db_guard->insert_batch('guard_patrol_photo', $data_batch);
            return $this->db_guard->affected_rows();
        }
        return 0;
    }


    public function getPatrolHistory($start_date = null, $end_date = null, $id_user = null) {
        $this->db_guard->select('id_guard_patrol_detail, id_master_room, nama_room, user_create, datetime_patrol, keterangan');
        $this->db_guard->from('guard_patrol_detail');

        // Filter Tanggal
        if ($start_date && $end_date) {
            $this->db_guard->where('DATE(datetime_patrol) >=', $start_date);
            $this->db_guard->where('DATE(datetime_patrol) <=', $end_date);
        }
        
        // Filter User (Opsional, jika user hanya boleh lihat riwayatnya sendiri)
        if ($id_user) {
            $this->db_guard->where('user_create', $id_user);
        }

        $this->db_guard->order_by('datetime_patrol', 'DESC');
        $query = $this->db_guard->get();
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }


    public function getSingleMasterRoom($id_master_room) {
        $this->db_guard->select('id_master_room, nama_master_room, lokasi, lantai, keterangan');
        $this->db_guard->from('guard_master_room');
        $this->db_guard->where('id_master_room', $id_master_room);
        $this->db_guard->where('status_aktif', 1); // Hanya ambil yang aktif
        
        $query = $this->db_guard->get();
        
        if ($query->num_rows() == 1) {
            return $query->row_array(); // Ambil sebagai single array
        }
        return false;
    }


    public function getPatrolPhotos($id_guard_patrol_detail) {
        $this->db_guard->select('keterangan_patroli, url_photo'); // Hanya ambil kolom yang dibutuhkan Android
        $this->db_guard->from('guard_patrol_photo');
        $this->db_guard->where('id_guard_patrol_detail', $id_guard_patrol_detail);
        $this->db_guard->order_by('tanggal_create', 'ASC');
        
        $query = $this->db_guard->get();
        
        if ($query->num_rows() > 0) {
            return $query->result_array();
        }
        return false;
    }
}