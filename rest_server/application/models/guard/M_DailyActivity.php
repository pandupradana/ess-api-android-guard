<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_DailyActivity extends CI_Model {

    // Asumsi koneksi database guard di $this->db_guard
    public function __construct() {
        parent::__construct();
    }

    /**
     * Generate ID Daily Activity (Contoh: DA20251213001)
     */
    public function generateIdDailyActivity() {
        // Format Tanggal: YYYYMMDD
        $today = date('Ymd');
        $prefix = "DA" . $today;

        // Query untuk mencari nomor urut terakhir hari ini
        $this->db_guard->select('id_daily_activity');
        $this->db_guard->like('id_daily_activity', $prefix, 'after');
        $this->db_guard->order_by('id_daily_activity', 'DESC');
        $this->db_guard->limit(1);
        $query = $this->db_guard->get('guard_daily_activity');
        
        $last_number = 0;
        
        if ($query->num_rows() > 0) {
            $last_id = $query->row()->id_daily_activity; // DA20251213001
            // Ambil 3 digit terakhir (001)
            $last_number = (int) substr($last_id, -3);
        }

        // Nomor urut baru
        $new_number = $last_number + 1;
        
        // Format nomor urut (padding 001, 002, dst.)
        return $prefix . str_pad($new_number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menyimpan data Daily Activity baru ke database
     */
    public function createDailyActivity($data) {
        $this->db_guard->insert('guard_daily_activity', $data);
        return $this->db_guard->affected_rows();
    }


    /**
	 * Ambil history daily activity
	 * Bisa difilter tanggal (optional)
	 */
	public function getDailyActivityHistory($start_date = null, $end_date = null)
	{
	    $this->db_guard->select("
	        id,
	        id_daily_activity,
	        datetime_activity,
	        title_activity,
	        keterangan_activity,
	        foto_url_activity,
	        user_create,
	        status
	    ");
	    $this->db_guard->from('guard_daily_activity');
	    $this->db_guard->where('status', 1);

	    if (!empty($start_date) && !empty($end_date)) {
	        $this->db_guard->where('DATE(datetime_activity) >=', $start_date);
	        $this->db_guard->where('DATE(datetime_activity) <=', $end_date);
	    }

	    $this->db_guard->order_by('datetime_activity', 'DESC');

	    return $this->db_guard->get()->result_array();
	}

}
