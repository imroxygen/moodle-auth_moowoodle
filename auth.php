<?php
/**
 *
 * @package    auth_moowoodle_moodle_connector
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once $CFG->libdir . '/authlib.php';
class auth_plugin_moowoodle_moodle_connector extends auth_plugin_base {

	public function __construct() {
		$this->authtype = 'moowoodle_moodle_connector';
		$this->config = get_config('auth_moowoodle_moodle_connector');
	}

	public function user_login($username, $password = null) {
		global $CFG, $DB;
		if ($password == null || $password == '') {return false;}
		if ($user = $DB->get_record('user', array('username' => $username, 'mnethostid' => $CFG->mnet_localhost_id))) {
			return true;
		}
		return false;
	}

	public function can_reset_password() {
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