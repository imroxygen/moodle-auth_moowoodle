<?php
/**
 *
 * @package    auth_moowoodle_moodle_connector
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG, $USER, $SESSION, $DB;

require '../../config.php';
require_once $CFG->libdir . '/moodlelib.php';
require_once $CFG->dirroot . '/cohort/lib.php';
require_once $CFG->dirroot . '/group/lib.php';
require_once $CFG->dirroot . '/course/lib.php';
require_once $CFG->dirroot . "/lib/enrollib.php";

$SESSION->wantsurl = $CFG->wwwroot . '/';
$secret_key = get_config('auth_moowoodle_moodle_connector', 'encryptkey');
$request_url = get_config('auth_moowoodle_moodle_connector', 'wpsiteurl');

$getdata = optional_param('passkey', '', PARAM_RAW);
$timelimit = (integer) get_config('auth_moowoodle_moodle_connector', 'timelimit');
if ($timelimit <= 0) {
	$timelimit = 5;
}
if (!empty($getdata)) {
	$data = json_decode(base64_decode($getdata), true);
	$user_id = $data['user_id'];
	$timestamp = $data['timestamp'];
	$redirect_url = $data['redirect_url'];
	$wp_user_id = $data['wp_user_id'];
	$course_id = $data['course_id'];
	if ($timestamp) {
		$timevalue = new DateTime("@$timestamp");
		$diff = floatval(date_diff(date_create("now"), $timevalue)->format("%i"));
		if ($timestamp > 0 && $diff <= $timelimit) {
			if ($DB->record_exists('user', array('id' => $user_id))) {
				// update manually created user that has the same username but doesn't yet have the right idnumber
		        // ensure we have the latest data
				$user = get_complete_user_data('id', $user_id);
			} else {
				file_put_contents("error.log", date("d/m/Y H:i:s", time()) . ": " . "\n        moowoodle error:No such user id:" . $user_id . "\n", FILE_APPEND);die;
				redirect($redirect_url);
			}
			$request_url .= '/wp-json/moowoodle-pro/sso/';
			$request_data = array(
				'action' => 'login_verify',
				'redirect_to' => $redirect_url,
				'mdl_user_id' => $user->id,
				'mdl_username' => $user->username,
				'mdl_email' => $user->email,
				'timestamp' => $timestamp,
				'course_id' => $course_id,
				'user_id' => $wp_user_id,
				'moowoodle_one_time_code' => $getdata,
			);
			$jeson_request_data = json_encode($request_data);

			$curl = curl_init($request_url);
			if ($curl === false) {
				die('Failed to initialize cURL');
			}
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_TIMEOUT => 100,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => array('moowoodle_token' => base64_encode($jeson_request_data)),
			));
			curl_setopt( $curl, CURLOPT_POST, 1 );
	        curl_setopt( $curl, CURLOPT_POSTFIELDS, $encoded_request_data );
			$response = json_decode(curl_exec($curl), true);
			if ($response != null) {
				$sskey = get_config('auth_moowoodle_moodle_connector', 'encryptkey');
				if ($response['status'] == 'success') {
					if ($response['moowoodle_one_time_code'] == $getdata && $response['sskey'] == md5($sskey)) {
						$authplugin = get_auth_plugin('moowoodle_moodle_connector');
						if ($authplugin->user_login($user->username, $user->password)) {
							$user->loggedin = true;
							$user->site = $CFG->wwwroot;
							complete_user_login($user);
						}
						if ($redirect_url) {
							$SESSION->wantsurl = $redirect_url;
						}
						redirect($redirect_url);
					}
				}
			}
		}
		file_put_contents("error.log", date("d/m/Y H:i:s", time()) . ": " . "\n        moowoodle error:Some one tried to login to (course id) " . $course_id . "> and user id:" . $user_id . "> & (Moodle) & " . $wp_user_id . ">(WordPress) with  time diffarence of:" . $diff . "min or more. on " . json_encode($timevalue) . " and now :" . json_encode(date_create("now")) . "\n", FILE_APPEND);

	}
}

redirect($redirect_url);
