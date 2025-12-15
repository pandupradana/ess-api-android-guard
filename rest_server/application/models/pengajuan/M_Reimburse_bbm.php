<?php

class M_Reimburse_bbm extends CI_Model
{
    public function __construct()
    {
    }

    // Insert data
    public function insert_form_reimburse_bbm($data)
    {
        $this->db->insert('tbl_reimburse_bbm', $data);
        return $this->db->affected_rows();
    }

    // Ambil nomor urut terakhir per hari
    public function get_last_id_today($dateDMY)
    {
        // $dateDMY format = ddmmyyyy
        $this->db->like('id_reimburse_bbm', "BBM_" . $dateDMY . "_", 'after');
        $this->db->order_by('id_reimburse_bbm', 'DESC');
        $query = $this->db->get('tbl_reimburse_bbm')->row();

        if ($query) {
            $parts = explode("_", $query->id_reimburse_bbm);
            return intval($parts[2]);
        }

        return 0;
    }


    public function get_reimburse_bbm_by_nip($nip_pegawai = null)
	{
	    $sql = "SELECT 
	                id_reimburse_bbm,
	                nip_pegawai,
	                nama_pegawai,
	                nomor_polisi,
	                jenis_kendaraan,
	                tanggal_permintaan,
	                km_awal,
	                km_akhir,
	                keterangan,
	                foto_km_awal,
	                foto_km_akhir,
	                url_foto_km_awal,
	                url_foto_km_akhir,
	                status,
	                timestamp_submit
	            FROM tbl_reimburse_bbm
	            WHERE nip_pegawai = '$nip_pegawai'
	            ORDER BY timestamp_submit DESC";

	    $hasil = $this->db->query($sql);
	    return $hasil->result_array();
	}

}
