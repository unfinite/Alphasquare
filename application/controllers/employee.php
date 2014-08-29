<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Employee extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('staff_model');
    login_required();
    $employee = $this->staff_model->allowAccess();
    if ($employee = false) {
      redirect('dashboard');
    }
  }

  public function index() {
    $this->template->load('admin/welcome');
  }

}
