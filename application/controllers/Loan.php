<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use Spipu\Html2Pdf\Html2Pdf;

class Loan extends CI_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->model('loans');
		$this->load->library('dates');
	}

	public function index(){
		$this->load->view('layouts/header');
		$this->load->view('pages/loan');
		$this->load->view('layouts/footer');
	}

	public function loan_action(){
		$recaptcha = new \ReCaptcha\ReCaptcha(SECRET);
		$resp = $recaptcha->verify($this->input->post('g-recaptcha-response'), $this->input->server('REMOTE_ADDR'));
		if ($resp->isSuccess() === FALSE) {
			$this->session->set_userdata('notif', false);
        	redirect(base_url('permohonan-pinjaman'));
		}

		$this->load->library('form_validation');
		$this->form_validation->set_rules($this->generate_input_validation());
		if ($this->form_validation->run() == FALSE){
        	$this->session->set_userdata('notif', false);
            redirect(base_url('permohonan-pinjaman'));
        }
        else{
        	$data = $this->get_post_data();
        	$query = http_build_query($data);
        	$this->generate_pdf($query, $data['nik']);
        	$data['tanggal_lahir'] = $this->dates->change_format($data['tanggal_lahir']);
        	$this->loans->create($data);
			$this->load->library('sendmail');
			$this->sendmail->send_to(ADMIN_EMAIL, 'testing', 'testing bosq', base_url('assets/pdf/pinjaman-'.$data['nik'].'.pdf'));
			$this->sendmail->send_to($data['email'], 'testing 123', '123 bosq');
        	$this->session->set_userdata('notif', true);
			redirect(base_url('permohonan-pinjaman'));
        }
	}

	public function generate_input_validation(){
		$config = array(
	        array('field' => 'ktp','label' => 'KTP', 'rules' => 'required'),
	        array('field' => 'ktp_pasangan','label' => 'KTP Pasangan', 'rules' => 'required'),
	        array('field' => 'nama', 'label' => 'Nama', 'rules' => 'required'),
	        array('field' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'rules' => 'required'),
	        array('field' => 'alamat', 'label' => 'Alamat rumah', 'rules' => 'required'),
	        array('field' => 'alamat_pasangan', 'label' => 'Alamat rumah pasangan', 'rules' => 'required'),
	        array('field' => 'gender', 'label' => 'Jenis Kelamin', 'rules' => 'required'),
	        array('field' => 'pasangan', 'label' => 'Nama Pasangan', 'rules' => 'required'),
	        array('field' => 'unit', 'label' => 'Unit', 'rules' => 'required'),
			array('field' => 'nik','label' => 'NIK', 'rules' => 'required'),
	        array('field' => 'jabatan', 'label' => 'Jabatan', 'rules' => 'required'),
	        array('field' => 'golongan', 'label' => 'Grade', 'rules' => 'required'),
	        array('field' => 'pinjaman', 'label' => 'Uang Pinjaman', 'rules' => 'required'),
	        array('field' => 'pinjaman_deskripsi', 'label' => 'Uang Pinjaman (huruf)', 'rules' => 'required'),
	        array('field' => 'keperluan', 'label' => 'Keperluan', 'rules' => 'required'),
	        array('field' => 'policy', 'label' => 'Pernyataan', 'rules' => 'required'),
	        array('field' => 'email', 'label' => 'Email', 'rules' => 'required')
		);
		return $config;
	}

	public function generate_pdf($query, $nik){
		$html2pdf = new Html2Pdf('P', 'A4', 'en');
        $html2pdf->pdf->SetTitle('Pengajuan Pinjaman');
        $html2pdf->pdf->SetSubject('Permohonan Pegajuan Pinjaman Koperasi \"BUDI SETIA\"');
        $html2pdf->pdf->SetMargins(15, 10, 15);
        $html2pdf->pdf->SetFont('Times', '', 12);

        $pages = array(
        	'loan/persetujuan_credit?'.$query,
        	'loan/pengajuan_pinjaman?'.$query,
        	'loan/pernyataan?'.$query,
        	'loan/surat_kuasa?'.$query,
        	'loan/surat_kuasa_pemotongan_penghasilan?'.$query,
        	'loan/surat_persetujuan_pasangan?'.$query
        );

        foreach ($pages as $page) {
	        $html2pdf->pdf->AddPage();
	        $html2pdf->pdf->WriteHTML(file_get_contents(base_url($page)), true, false, true, false, '');
	        $html2pdf->pdf->lastPage();
        }
       	$html2pdf->output(SAVE_PDF.'pinjaman-'.$nik.'.pdf', 'F');
	}

	public function persetujuan_credit(){
		$data = $this->get_data();
		$this->load->view('loan/persetujuan_kredit', $data); 
	}

	public function pengajuan_pinjaman(){ 
		$data = $this->get_data();
		$this->load->view('loan/pengajuan_pinjaman', $data); 
	}

	public function pernyataan(){
	 	$data = $this->get_data();
		$this->load->view('loan/surat_pernyataan', $data); 
	}

	public function surat_kuasa(){ 
		$data = $this->get_data();
		$this->load->view('loan/surat_kuasa', $data); 
	}

	public function surat_kuasa_pemotongan_penghasilan(){ 
		$data = $this->get_data();
		$this->load->view('loan/surat_kuasa_pemotongan_gaji', $data); 
	}

	public function surat_persetujuan_pasangan(){ 
		$data = $this->get_data();
		$this->load->view('loan/surat_persetujuan_pasangan', $data); 
	}

	public function get_data(){
		return $data = array(
	    		'ktp' => $this->input->get('ktp'),
	    		'ktp_pasangan' => $this->input->get('ktp_pasangan'),
	    		'nama' => $this->input->get('nama'),
	    		'tanggal_lahir' => $this->input->get('tanggal_lahir'),
	    		'alamat' => $this->input->get('alamat'),
	    		'alamat_pasangan' => $this->input->get('alamat_pasangan'),
	    		'gender' => $this->input->get('gender'),
	    		'unit_kerja' => $this->input->get('unit_kerja'),
	    		'pasangan' => $this->input->get('pasangan'),
	    		'unit' => $this->input->get('unit'),
	    		'jabatan' => $this->input->get('jabatan'),
	    		'golongan' => $this->input->get('golongan'),
	    		'nik' => $this->input->get('nik'),
	    		'pinjaman' => $this->input->get('pinjaman'),
	    		'waktu' => $this->input->get('waktu'),
	    		'pinjaman_deskripsi' => $this->input->get('pinjaman_deskripsi'),
	    		'keperluan' => $this->input->get('keperluan'),
	    		'jenis_pinjaman' => $this->input->get('jenis_pinjaman')
	    	);
	}

	public function get_post_data(){
		return $data = array(
	        		'ktp' => $this->input->post('ktp'),
	        		'ktp_pasangan' => $this->input->post('ktp_pasangan'),
	        		'nama' => strtoupper($this->input->post('nama')),
	        		'tanggal_lahir' => $this->input->post('tanggal_lahir'),
	        		'alamat' => $this->input->post('alamat'),
	        		'alamat_pasangan' => $this->input->post('alamat_pasangan'),
	        		'gender' => $this->input->post('gender'),
	        		'pasangan' => $this->input->post('pasangan'),
	        		'unit_kerja' => $this->input->post('unit_kerja'),
	        		'unit' => $this->input->post('unit'),
	        		'nik' => $this->input->post('nik'),
	        		'jabatan' => $this->input->post('jabatan'),
	        		'golongan' => $this->input->post('golongan'),
	        		'pinjaman' => $this->input->post('pinjaman').',00',
	        		'pinjaman_deskripsi' => $this->input->post('pinjaman_deskripsi'),
	        		'waktu' => $this->input->post('waktu'),
	        		'jenis_pinjaman' => $this->input->post('jenis_pinjaman'),
	        		'keperluan' => $this->input->post('keperluan'),
	        		'email' => $this->input->post('email')
	        	);
	}

	public function loan_confirmation(){
		$this->load->view('layouts/header');
		$this->load->view('pages/loan_confirmation');
		$this->load->view('layouts/footer');
	}

	public function loan_confirmation_action(){
		$recaptcha = new \ReCaptcha\ReCaptcha(SECRET);
		$resp = $recaptcha->verify($this->input->post('g-recaptcha-response'), $this->input->server('REMOTE_ADDR'));
		if ($resp->isSuccess() != TRUE) {
			$this->session->set_userdata('notif', false);
        	redirect(base_url('konfirmasi-permohonan-pinjaman'));
		}
		$this->load->library('form_validation');
		$this->form_validation->set_rules($this->validation_confirmation());
		if ($this->form_validation->run() === FALSE){
        	$this->session->set_userdata('notif', false);
            redirect(base_url('konfirmasi-permohonan-pinjaman'));
        }
        else{
			$data = $this->get_post_confirmation();
			$this->load->library('uploads');
			$path = $this->uploads->upload_file('file', 'pdf|zip');
			if ($path === FALSE) {
				$this->session->set_userdata('notif', false);
            	redirect(base_url('konfirmasi-permohonan-pinjaman'));
			}

			$data['file_path'] = $path;
			$data['action'] = 'loan_confirmation';
			$this->confirmations->create($data);

			$this->load->library('sendmail');
			$this->sendmail->send_to(ADMIN_EMAIL, 'testing', 'testing bosq', $path);
        	$this->session->set_userdata('notif', true);
			redirect(base_url('konfirmasi-permohonan-pinjaman'));
		}		
	}

	public function get_post_confirmation(){
		return $data = array(
			'nik' => $this->input->post('nik'),
			'nama' => $this->input->post('nama'),
			'unit_kerja' => $this->input->post('unit_kerja'),
	        'unit' => $this->input->post('unit')
		);
	}

	public function validation_confirmation(){
		$config = array(
	        array('field' => 'nama', 'label' => 'Nama', 'rules' => 'required'),
	        array('field' => 'unit', 'label' => 'Unit', 'rules' => 'required'),
			array('field' => 'nik','label' => 'NIK', 'rules' => 'required')
		);
		return $config;
	}
}