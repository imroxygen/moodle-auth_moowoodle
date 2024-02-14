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
$secretkey = get_config('auth_moowoodle', 'encryptkey');
$requesturl = get_config('auth_moowoodle', 'wpsiteurl');

$getdata = optional_param('passkey', '', PARAM_RAW);
$timelimit = (integer) get_config('auth_moowoodle', 'timelimit');
if ($timelimit <= 0) {
    $timelimit = 5;
}
if (!empty($getdata)) {
    $data = json_decode(base64_decode($getdata), true);
    $userid = $data['user_id'];
    $timestamp = $data['timestamp'];
    $redirecturl = $data['redirect_url'];
    $wpuserid = $data['wp_user_id'];
    $courseid = $data['course_id'];
    $resturl = $data['verify_url'];
    if ($timestamp) {
        $timevalue = new DateTime("@$timestamp");
        $diff = floatval(date_diff(date_create("now"), $timevalue)->format("%i"));
        if ($timestamp > 0 && $diff <= $timelimit) {
            if ($DB->record_exists('user', ['id' => $userid])) {
                $user = get_complete_user_data('id', $userid);
            } else {
                redirect($redirecturl);
            }
            $requesturl .= $resturl;
            $requestdata = [
                'action' => 'login_verify',
                'redirect_to' => $redirecturl,
                'mdl_user_id' => $user->id,
                'mdl_username' => $user->username,
                'mdl_email' => $user->email,
                'timestamp' => $timestamp,
                'course_id' => $courseid,
                'user_id' => $wpuserid,
                'moowoodle_one_time_code' => $getdata,
            ];
            $jesonrequestdata = json_encode($requestdata);

            $curl = curl_init($requesturl);
            if ($curl === false) {
                die('Failed to initialize cURL');
            }
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT => 100,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['moowoodle_token' => convert_uuencode($jesonrequestdata)],
            ]);
            $response = json_decode(curl_exec($curl), true);
            if ($response != null) {
                $sskey = get_config('auth_moowoodle', 'encryptkey');
                if ($response['status'] == 'success') {
                    if ($response['moowoodle_one_time_code'] == $getdata && $response['sskey'] == md5($sskey)) {
                        $authplugin = get_auth_plugin('moowoodle');
                        if ($authplugin->user_login($user->username, $user->password)) {
                            $user->loggedin = true;
                            $user->site = $CFG->wwwroot;
                            complete_user_login($user);
                        }
                        if ($redirecturl) {
                            $SESSION->wantsurl = $redirecturl;
                        }
                        redirect($redirecturl);
                    }
                }
            }
        }
        $filecontent = date("d/m/Y H:i:s", time()) . ": " . "\n moowoodle error: Someone tried to login to (course id) "
        . $courseid . "> and user id:" . $userid . "> & (Moodle) & " . $wpuserid . ">(WordPress) with time difference of:" . $diff .
        "min or more. on " . json_encode($timevalue) . " and now :" . json_encode(date_create("now")) . "\n";
        file_put_contents("error.log", $filecontent, FILE_APPEND);
    }
}

redirect($redirecturl);
