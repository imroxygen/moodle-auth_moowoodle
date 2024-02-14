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
namespace auth_moowoodle\external;
require_once("{$CFG->libdir}/externallib.php");// support for previous version of moodle 4.2
use external_api;// use core_external\external_api;
use external_function_parameters;// use core_external\external_function_parameters;
use external_single_structure;// use core_external\external_single_structure;
use external_value;// use core_external\external_value;

class user_sync extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'end_id' => new external_value(PARAM_RAW, 'The Last id to send next batch of user data'),
                'limit' => new external_value(PARAM_RAW, 'The limit to sent batch of user data'),
            ]
        );
    }

    public static function execute($endid, $limit) {
        global $DB, $CFG;
        if (is_numeric($limit) && is_numeric($endid)) {
            $limit = (int) $limit + 1;
            $sql = "SELECT u.id, u.email, u.username, u.password, u.firstname, u.lastname
                      FROM {user} u
                     WHERE u.id > :endid AND u.deleted = 0
                  ORDER BY u.id ASC
                     LIMIT :limit";
            $param = [
                'endid' => (int) $endid,
                'limit' => $limit,
            ];
            $users = $DB->get_records_sql($sql, $param);
            $response = [
                'status' => 'success',
                'data' => json_encode($users),
            ];
            return ($response);
        } else if (is_array(json_decode($limit, true)) && is_array(json_decode($endid, true))) {
            require_once($CFG->dirroot . '/user/lib.php');
            $wpuserdata = json_decode($limit, true);
            $syncsettings = json_decode($endid, true);
            $moodleuserdata = $DB->get_record('user', ['email' => $wpuserdata['email']]);
            $moodleuserid['created'] = false;
            if ($moodleuserdata) {
                $userid = $moodleuserdata->id;
                $moodleuserdata->email = $wpuserdata['email'];
                if (isset($syncsettings['sync_username']) && $syncsettings['sync_username'] == "Enable") {
                    $moodleuserdata->username = $wpuserdata['username'];
                }
                if (($wpuserdata['password'] != null && isset($syncsettings['sync_password'])
                        && $syncsettings['sync_password'] == "Enable")) {
                    if (strpos($wpuserdata['password'], "$2y$") === 0) {
                        $moodleuserdata->password = $wpuserdata['password'];
                    } else {
                        $moodleuserid['created'] = true;
                    }
                }
                if (isset($syncsettings['sync_user_first_name']) && $syncsettings['sync_user_first_name'] == "Enable"
                        && $wpuserdata['firstname'] != null) {
                    $moodleuserdata->firstname = $wpuserdata['firstname'];
                }
                if (isset($syncsettings['sync_user_last_name']) && $syncsettings['sync_user_last_name'] == "Enable"
                        && $wpuserdata['lastname'] != null) {
                    $moodleuserdata->lastname = $wpuserdata['lastname'];
                }
                user_update_user($moodleuserdata, true, false);
            } else {
                $moodleuserdata = new stdClass();
                $moodleuserdata->email = $wpuserdata['email'];
                $moodleuserdata->username = $wpuserdata['username'];
                $moodleuserdata->password = $wpuserdata['password'];
                if (strpos($wpuserdata['password'], "$2y$") !== 0) {
                    $moodleuserid['created'] = true;
                }

                $moodleuserdata->firstname = $wpuserdata['firstname'];
                $moodleuserdata->lastname = $wpuserdata['lastname'];
                $moodleuserdata->auth = 'manual';
                $moodleuserdata->lang = $wpuserdata['lang'];
                $userid = user_create_user($moodleuserdata, true, false);
            }
            $moodleuserid['id'] = $userid;
            $response = [
                'status' => 'success',
                'data' => json_encode($moodleuserid),
            ];
            return ($response);
        } else {
            $response = [
                'status' => 'failed',
                'data' => json_encode('Bad Request'),
            ];
            return ($response);
        }
    }
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_RAW, 'status: success if success'),
                'data' => new external_value(PARAM_RAW, 'users: all user data'),
            ]
        );
    }
}
