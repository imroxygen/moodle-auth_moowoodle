<?php
/**
 *
 * @package    auth_moowoodle_user_sync
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once "$CFG->libdir/externallib.php";
require_once "$CFG->libdir/enrollib.php";
class auth_moowoodle_user_sync_external extends external_api {
	public static function sync_users_parameters() {
		return new external_function_parameters(
			array(
			)
		);
	}

	public static function sync_users() {
		global $DB;

		$sql = "SELECT u.id, u.email, u.username, u.password, u.firstname, u.lastname
                FROM {user} u
                WHERE  u.deleted = 0";
		$users = $DB->get_records_sql($sql);
		$response = array(
			'status' => 'success',
			'data' => json_encode($users),
		);

		return ($response);
	}
	public static function sync_users_returns() {
		return new external_single_structure(
			array(
				'status' => new external_value(PARAM_RAW, 'status: success if success'),
				'data' => new external_value(PARAM_RAW, 'users: all user data'),
			)
		);
	}
}
