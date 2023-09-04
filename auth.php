<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/authlib.php');
class auth_plugin_moowoodleconnect extends auth_plugin_base {

	public function __construct() {
			$this->authtype = 'moowoodleconnect';
			$this->config = get_config('auth_moowoodleconnect');
	}

	public function user_login ($username, $password = null) {
		global $CFG, $DB;
		if ($password == null || $password == '') { return false; }	
		if ($user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
						return true;
		}
		return false;
	}

 	public function can_reset_password()
  {
      return false;
  }

	public function can_change_password() {
		return false;
  }

  public function change_password_url() {
   	return;
  }

  public function is_internal() {
		return false;
  }

  public function prevent_local_passwords() {
    return false;
  }

}
?>