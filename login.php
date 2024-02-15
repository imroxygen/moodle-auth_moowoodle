<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
$SESSION->wantsurl = $CFG->wwwroot . '/';
$requesturl = get_config('auth_moowoodle', 'wpsiteurl');

$getdata = optional_param('passkey', '', PARAM_RAW);
$timelimit = (integer) get_config('auth_moowoodle', 'timelimit');
if ($timelimit <= 0) {
    $timelimit = 5;
}
$data = !empty($getdata) ? json_decode(base64_decode($getdata), true) : false;
if ($data && $data['timestamp'] && $data['timestamp'] > 0
    && floatval(date_diff(date_create("now"), new DateTime("@{$data['timestamp']}"))->format("%i")) <= (integer) get_config('auth_moowoodle', 'timelimit')
    && $DB->record_exists('user', ['id' => $data['user_id']])) {
    $user = get_complete_user_data('id', $data['user_id']);
    $requesturl .= $data['verify_url'];

    $curl = curl_init($requesturl);
    if ($curl === false) {
        die('Failed to initialize cURL');
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 100,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'moowoodle_token' => convert_uuencode(
                json_encode([
                    'action' => 'login_verify',
                    'redirect_to' => $data['redirect_url'],
                    'mdl_user_id' => $user->id,
                    'mdl_username' => $user->username,
                    'mdl_email' => $user->email,
                    'timestamp' => $data['timestamp'],
                    'course_id' => $data['course_id'],
                    'user_id' => $data['wp_user_id'],
                    'moowoodle_one_time_code' => $getdata,
                ])
            )
        ],
    ]);
    $response = json_decode(curl_exec($curl), true);
    if ($response != null && $response['status'] == 'success' && $response['moowoodle_one_time_code'] == $getdata
        && $response['sskey'] == md5(get_config('auth_moowoodle', 'encryptkey'))) {
        if (get_auth_plugin('moowoodle')->user_login($user->username, null)) {
            $user->loggedin = true;
            $user->site = $CFG->wwwroot;
            complete_user_login($user);
        }
        if ($data['redirect_url']) {
            $SESSION->wantsurl = $data['redirect_url'];
        }
    }
}

redirect($SESSION->wantsurl);
