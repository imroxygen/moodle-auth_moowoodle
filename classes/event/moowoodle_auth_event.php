<?php
namespace auth_moowoodle_moodle_connector\event;

defined('MOODLE_INTERNAL') || die();

class moowoodle_auth_event {

	public static function moowoodle_user_sync_observer(\core\event\base $event) {
		$event_data = $event->get_data();
		$user_id = $event_data['relateduserid'];
		$user_data = get_complete_user_data('id', $user_id);
		$user_data_array['email'] = $user_data->email;
		if($user_data->firstname != null)
        $user_data_array['firstname'] = $user_data->firstname;
        if($user_data->lastname != null)
		$user_data_array['lastname'] = $user_data->lastname;
		$user_data_array['username'] = $user_data->username;
		$user_data_array['password'] = $user_data->password;
		$request_url = get_config('auth_moowoodle_moodle_connector', 'wpsiteurl');
		$request_url .= '/wp-json/moowoodle-pro/user_sync/';
		$curl = curl_init($request_url);
		if ($curl === false) {
			die('Failed to initialize cURL');
		}

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 100,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => array('moodle_user_data' => json_encode(array($user_data_array))),
		));

		$response = curl_exec($curl);
		if ($response === false) {
			die('Curl error: ' . curl_error($curl));
		}

		curl_close($curl);

		$decoded_response = json_decode($response, true);
	}

}