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
namespace auth_moowoodle\event;


class moowoodle_realtime_user_sync {

    public static function moowoodle_user_sync_observer(\core\event\base $event) {
        $userdata = get_complete_user_data('id', $event->get_data()['relateduserid']);
        $userdataarray['email'] = $userdata->email;
        if ($userdata->firstname != null) {
            $userdataarray['firstname'] = $userdata->firstname;
        }

        if ($userdata->lastname != null) {
            $userdataarray['lastname'] = $userdata->lastname;
        }

        $userdataarray['username'] = $userdata->username;
        $userdataarray['password'] = $userdata->password;
        $requesturl = get_config('auth_moowoodle', 'wpsiteurl') . '?rest_route=/moowoodle-pro';
        $curl = curl_init($requesturl);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $requesturl  = json_decode(curl_exec($curl),true)['routes']['/moowoodle-pro/user_sync']['_links']['self'][0]['href'];
        curl_close($curl);
        $curl = curl_init($requesturl);
        if ($curl === false) {
            die('Failed to initialize cURL');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 100,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['moodle_user_data' => json_encode([$userdataarray])],
        ]);

        $response = curl_exec($curl);
        if ($response === false) {
            die('Curl error: ' . curl_error($curl));
        }

        curl_close($curl);
    }

}
