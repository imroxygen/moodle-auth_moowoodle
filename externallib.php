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
				'end_id' => new external_value(PARAM_RAW, 'The Last id to send next batch of user data'),
				'limit' => new external_value(PARAM_RAW, 'The limit to sent batch of user data'),
			)
		);
	}

	public static function sync_users($end_id, $limit) {
		global $DB;
		$limit = $limit+1;
		$sql = "SELECT u.id, u.email, u.username, u.password, u.firstname, u.lastname FROM {user} u WHERE u.id > ".(int)$end_id." u.deleted = 0 AND ORDER BY u.id ASC LIMIT ".$limit;
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
