<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	public function index(){
		$this->load->view('layouts/header');
		$this->load->view('pages/index');
		$this->load->view('layouts/footer');
	}
}