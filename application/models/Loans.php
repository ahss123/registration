<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Loans extends CI_Model{

	public function create($data){
		date_default_timezone_set('Asia/Jakarta');
		$data['created_at'] = date('Y-m-d H:i:s');
		$this->db->insert('pengajuan_pinjaman',$data);
	}
}