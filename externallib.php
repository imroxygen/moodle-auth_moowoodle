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
 * External library
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/externallib.php");

/**
 * External library
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_moowoodle_external extends \external_api {

    /**
     *
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_moowoodle_get_users_parameters(): \external_function_parameters {
        return new \external_function_parameters(
            [
                'endid' => new \external_value( PARAM_RAW, 'The Last id to send next batch of user data' ),
                'limit'  => new \external_value( PARAM_RAW, 'The limit to sent batch of user data' ),
            ]
        );
    }

    /**
     * get all users
     *
     * @param int $endid
     * @param int $limit
     */
    public static function auth_moowoodle_get_users($endid, $limit) {
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
            $response = [
                'status' => 'success',
                'data' => json_encode($DB->get_records_sql($sql, $param)),
            ];
        } else {
            $response = [
                'status' => 'failed',
                'data' => json_encode('Bad Request'),
            ];
        }
        file_put_contents( 'C:\xampp\htdocs\moodle4.4\auth\moowoodle' . "/error.log", date("d/m/Y H:i:s", time()) . ":orders: : " . var_export( "hiiiiiiiiiiiiiiiiiiiii", true) . "\n", FILE_APPEND);
        return ($response);
    }
    
    /**
     *
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_moowoodle_get_users_returns(): \external_single_structure {
        return new \external_single_structure(
            [
                'status' => new \external_value(PARAM_RAW, 'status: success if success'),
                'data'   => new \external_value(PARAM_RAW, 'users: all user data'),
            ]
        );
    }

    /**
     *
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_moowoodle_user_sync_parameters(): \external_function_parameters {
        return new \external_function_parameters(
            [
                'endid' => new \external_value( PARAM_RAW, 'setting information from wordpress' ),
                'limit'  => new \external_value( PARAM_RAW, 'wordpress user data ' ),
            ]
        );
    }

    /**
     * update user in moodle if something changed in wordpress
     *
     * @param object $endid (json object)
     * @param object $limit (json object)
     * @return  array
     */
    public static function auth_moowoodle_user_sync($endid, $limit) {
        global $DB, $CFG;
        if (is_array(json_decode($limit, true)) && is_array(json_decode($endid, true))) {
            require_once($CFG->dirroot . '/user/lib.php');
            $wpuserdata = json_decode($limit, true);
            $syncsettings = json_decode($endid, true);
            $moodleuserdata = $DB->get_record('user', ['email' => $wpuserdata['email']]);
            $moodleuserid['created'] = false;
            if(!$moodleuserdata->id) {
                $moodleuserdata = new \stdClass();
            }
            $moodleuserdata->email = $wpuserdata['email'];
            if ((isset($syncsettings['sync_username']) && $syncsettings['sync_username'] == "Enable") || !$moodleuserdata->id) {
                $moodleuserdata->username = $wpuserdata['username'];
            }
            if (($wpuserdata['password'] != null && isset($syncsettings['sync_password'])
                    && $syncsettings['sync_password'] == "Enable")|| !$moodleuserdata->id) {
                if (strpos($wpuserdata['password'], "$2y$") === 0) {
                    $moodleuserdata->password = $wpuserdata['password'];
                }
            }
            if ((isset($syncsettings['sync_user_first_name']) && $syncsettings['sync_user_first_name'] == "Enable"
                    && $wpuserdata['firstname'] != null) || !$moodleuserdata->id) {
                $moodleuserdata->firstname = $wpuserdata['firstname'];
            }
            if ((isset($syncsettings['sync_user_last_name']) && $syncsettings['sync_user_last_name'] == "Enable"
                    && $wpuserdata['lastname'] != null) || !$moodleuserdata->id) {
                $moodleuserdata->lastname = $wpuserdata['lastname'];
            }
            if ($moodleuserdata) {
                user_update_user($moodleuserdata, true, false);
            } else {
                $moodleuserdata->auth = 'manual';
                $moodleuserdata->lang = $wpuserdata['lang'];
                $userid = user_create_user($moodleuserdata, true, false);
                $moodleuserid['created'] = true;
            }
            $moodleuserid['id'] = $userid;
            $response = [
                'status' => 'success',
                'data' => json_encode($moodleuserid),
            ];
        } else {
            $response = [
                'status' => 'failed',
                'data' => json_encode('Bad Request'),
            ];
        }
       file_put_contents( 'C:\xampp\htdocs\moodle4.4\auth\moowoodle' . "/error.log", date("d/m/Y H:i:s", time()) . ":orders: : " . var_export($response. "hiiiiiiiiiiiiiiiiiiiiiii2333333333", true) . "\n", FILE_APPEND);
        return ($response);
    }

    /**
     * 
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_moowoodle_user_sync_returns(): \external_single_structure {
        return new \external_single_structure(
            [
                'status' => new \external_value(PARAM_RAW, 'status: success if success'),
                'data'   => new \external_value(PARAM_RAW, 'moode user id'),
            ]
        );
    }
}
