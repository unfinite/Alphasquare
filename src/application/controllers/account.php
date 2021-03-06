<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Account Controller
 *
 * This controller is to do with user accounts.
 * - Login/Logout
 * - Register
 * - Forgot password
 * - etc.
 *
 * @package Controllers
*/

class Account extends CI_Controller {

  public function __construct() {
    parent::__construct();
    $this->load->model('account_model');
  }

  /**
   * Login page
   * URL: /login
   */
  public function login() {

    // If user is already logged in, redirect to dashboard
    if($this->php_session->get('loggedin')) {
      redirect('dashboard');
    }

    // If user submitted login form
    if($this->input->post('submit')) {
      $username = trim($this->input->post('username'));
      $password = $this->input->post('password');
      $next = $this->input->post('next');

      $return = 'login?username='.$username;
      if($next) {
        $return .= '&next='.$next;
      }

      // Check if length of username or password is < 1
      if(strlen($username) < 1 || strlen($password) < 1) {
        msg('Please enter your username and password.');
        redirect($return);
      }

      // Call the login method of the account model, which returns true or false
      $correct = $this->account_model->authenticate($username, $password);

      // If the auth method returned true, the username was correct
      // The user is now logged in
      if($correct) {
        // Redirect to return url
        redirect($next);
      }
      else {
        msg('Incorrect username or password.');
        redirect($return);
      }

    }
    else {
      // Load the login page view
      $data['title'] = 'Sign in';
      $data['fixed_container'] = true;
      $data['stylesheets'] = array('assets/css/bootstrap-social.css');
      $this->template->load('account/login', $data);
    }

  }

  /**
   * Logout
   * URL: /logout
   */
  public function logout() {
    // If user is already logged out, redirect to login page
    if(!$this->php_session->get('loggedin')) {
      redirect('login');
    }
    // Log the event
    $this->events->log('account', 'logout');
    // Destroy session
    $this->php_session->destroy();
    // Show message
    // doesn't work because of the session being destroyed
    //msg('You have been signed out. Have a great day!', 'info');
    // Redirect to login page
    redirect('login');
  }

  /**
   * Registration page
   * URL: /register
   */
  public function register() {

    // If user is already logged in, redirect to dashboard
    if($this->php_session->get('loggedin')) {
      redirect('dashboard');
    }

    // Load form validation library
    $this->load->library('form_validation');
    // load recaptcha
    $this->load->library('recaptcha');

    // Set all the rules
    $this->form_validation->set_rules('username', 'Username', 'required|trim|xss_clean|alpha_dash|valid_username|is_unique[users.username]');
    $this->form_validation->set_rules('email', 'Email', 'required|trim|xss_clean|valid_email|is_unique[users.email]');
    $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
    $this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');

    // Set message for is_unique rule
    $this->form_validation->set_message('is_unique', 'The %s you entered is already taken.');


    // If the form was submitted and validated
    if($this->form_validation->run()) {
      // Create the account...
    	$this->recaptcha->recaptcha_check_answer();
    	if ($this->recaptcha->getIsValid()) {
    	      $username = $this->input->post('username');
    	      $email = $this->input->post('email');
    	      $password = $this->input->post('password');
    	      $created = $this->account_model->create($username, $email, $password);
    	
    	      if($created) {
    	        // If they were registered, log them in
    	        $this->account_model->authenticate($username, $password);
    	        // Show an alert box
    	        msg("<strong>Welcome to Alphasquare!</strong> Get started by posting a debate. Go ahead; don't be shy!", 'info', 'text-align:center;font-size:15px;');
    	        // Go to dashboard
              $this->php_session->set('tour', true);
    	        redirect(REGISTER_REDIRECT);
    	      }
    	      else {
    	        msg('Sorry, an error has occurred. Please try again.');
    	      }
    	} else {

	      $data['recaptchaerror'] = true;
	      $data['recaptcha_html'] = $this->recaptcha->recaptcha_get_html();
	      $data['title'] = 'Register';
	      $data['fixed_container'] = true;
	      $data['errors'] = validation_errors();
	      $data['stylesheets'] = array('assets/css/bootstrap-social.css');
	      $this->template->load('account/register', $data);

    	}
    }
    else {

      // Either the form did not validate, or there was no form submitted
      // So load the register view
    	$data['recaptchaerror'] = false;
      $data['recaptcha_html'] = $this->recaptcha->recaptcha_get_html();
      $data['title'] = 'Register';
      $data['fixed_container'] = true;
      $data['errors'] = validation_errors();
      $data['stylesheets'] = array('assets/css/bootstrap-social.css');
      $this->template->load('account/register', $data);
    }
  }

  /**
   * Forgot password page
   * URL: /account/forgot_password
   * @param string $token Reset password token. If a token is present (and valid), it will show the reset password form.
   */
  public function forgot_password($token = null) {

    $data['fixed_container'] = true;

    // If token is provided...
    if($token) {
      // Load the reset pass page view
      $data['title'] = 'Reset Password';
      $data['token'] = $token;
      $this->template->load('account/reset_password', $data);
    }
    else {
      // Load the forgot pass page view
      $data['title'] = 'Forgot Password';
      $this->template->load('account/forgot_password', $data);
    }
  }

  /**
   * Resets forgot password form.
   * URL: /account/reset_password
   */
  public function reset_password($token) {

      if (is_numeric($token) & isset($token)) {

        $tkdata = $this->account_model->retrieve_info_token($token);

        if ($tkdata == false) {

          die();

        } else {
          $pw = $this->input->post('newpw');
          if ($pw !== "" and $pw !== null) {

            $uid = $tkdata['userid'];
            $newpw = $this->input->post('newpw');
            $tkdata = $this->account_model->change_password_uid($uid, $newpw);
            $this->account_model->delete_password_token($tkdata['token']);
            redirect("login");

          } else {

          $uid = $tkdata['userid'];
          $data['title'] = 'Change Password';
          $this->template->load('account/reset_password', $data);

        }
        
      }

    } else {

      die();

    }

  }

  
 /**
   * Processes tours
   * URL: /account/tour
   */
  public function tour($boolean) {
    switch ($boolean) {
      case "true":
        $this->php_session->set('tour', true);
        echo "ok";
        break;
      
      case "false":
        echo "no";
        $this->php_session->set('tour', false);
        break;
    }
  }
    

  /**
   * Processes the forgot password form.
   * URL: /account/forgot_password_submit
   */
  public function forgot_password_submit() {
    $email = $this->input->post('email');

    // Get the user's info by their email
    $this->load->model('people_model');
    $info = $this->people_model->get_info($email, 'email', 'id, username, email');

    // If user doesn't exist
    if(!$info) {
      msg('Sorry, that email address is not associated with any account on Alphasquare.');
      redirect('account/forgot_password');
    }

    $id = $info['id'];

    // Generate a forgot pass token
    $token = $this->account_model->create_password_token($id);

    // If token was created, send the email
    if($token) {
      $this->load->library('custom_email');
      $email_data = array(
        'subject' => 'Reset your password on Alphasquare',
        'type' => 'reset_password',
        'to' => $info['email'],
        'token' => $token
      );
      $this->custom_email->set_data($email_data);
      if($this->custom_email->send()) {
        msg('We have sent an email to <b>'.$info['email'].'</b> with further instructions. If you didn\'t get the email, please check your spam folder.', 'info');
        redirect('login');
      }

    }
    else {
      msg('An error occurred. Please try again.');
      redirect('account/forgot_password');
    }
  }

}

/* End of file account.php */
/* Location: ./application/controllers/account.php */