<?php 
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Employee extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
    $this->load->model('staff_model');
    login_required();
    $employee = $this->staff_model->allowAccess();
    if ($employee == false) {
      redirect('dashboard');
    }
  }

  public function index() {
    $data['title'] = 'Panel';
    $this->template->load('admin/welcome', $data);
  }

  public function users() {
    $data['title'] = 'Panel';
    $this->template->load('admin/userlist', $data);
  }

  public function ban() {
    $data['title'] = 'Panel';
    $this->template->load('admin/ban', $data);
  }

}
