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

/**
 * External library
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_moowoodle_external extends core_external\external_api {
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_moowoodle_get_users_parameters(): core_external\external_function_parameters {
        return new core_external\external_function_parameters(
            [
                'endid'  => new core_external\external_value( PARAM_RAW, 'The Last id to send next batch of user data' ),
                'limit'  => new core_external\external_value( PARAM_RAW, 'The limit to sent batch of user data' ),
            ]
        );
    }

    /**
     * get all users
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
                  ORDER BY u.id ASC";
            $param = [
                'endid' => (int) $endid,
            ];

            $response = [
                'status' => 'success',
                'data' => json_encode($DB->get_records_sql($sql, $param, 0, $limit)),
            ];
        } else {
            $response = [
                'status' => 'failed',
                'data' => json_encode('Bad Request'),
            ];
        }
        return ($response);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_moowoodle_get_users_returns(): core_external\external_single_structure {
        return new core_external\external_single_structure(
            [
                'status' => new core_external\external_value(PARAM_RAW, 'status: success if success'),
                'data'   => new core_external\external_value(PARAM_RAW, 'users: all user data'),
            ]
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function auth_moowoodle_user_sync_parameters(): core_external\external_function_parameters {
        return new core_external\external_function_parameters(
            [
                'userdata'  => new core_external\external_value( PARAM_RAW, 'wordpress user data ' ),
                'setting'   => new core_external\external_value( PARAM_RAW, 'setting information from wordpress' ),
            ]
        );
    }

    /**
     * update user in moodle if something changed in wordpress
     * @param object $setting (json object)
     * @param object $userdata (json object)
     * @return  array
     */
    public static function auth_moowoodle_user_sync( $userdata, $setting ) {
        global $DB, $CFG;
        
        $wpuserdata   = json_decode( $userdata, true );
        $syncsettings = json_decode( $setting, true );
        
        if ( is_array( $wpuserdata ) && is_array( $syncsettings ) ) {
            
            require_once($CFG->dirroot . '/user/lib.php');
            
            $moodleuserdata = $DB->get_record( 'user', [ 'email' => $wpuserdata[ 'email' ] ] );
            
            $response[ 'created' ] = false;

            if ( ! $moodleuserdata->id ) {
                $moodleuserdata = new stdClass();
            }

            $moodleuserdata->email = $wpuserdata[ 'email' ];
            
            if ( isset( $syncsettings[ 'username' ] ) || ! $moodleuserdata->id ) {
                $moodleuserdata->username = $wpuserdata[ 'username' ];
            }

            $updatepassword = false;
            if ( isset( $syncsettings[ 'sync_password' ] ) && $wpuserdata[ 'password' ] != null || ! $moodleuserdata->id ) {
                if ( strpos( $wpuserdata[ 'password' ], "$2y$" ) === 0 ) {
                    $moodleuserdata->password = $wpuserdata[ 'password' ];
                    $updatepassword = true;
                }
            }
            
            if ( isset( $syncsettings[ 'firstname' ] ) && $wpuserdata[ 'firstname' ] != null || ! $moodleuserdata->id ) {
                $moodleuserdata->firstname = $wpuserdata[ 'firstname' ];
            }
            
            if ( isset( $syncsettings[ 'lastname' ] ) && $wpuserdata[ 'lastname' ] != null || ! $moodleuserdata->id ) {
                $moodleuserdata->lastname = $wpuserdata[ 'lastname' ];
            }
            
            if ( $moodleuserdata->id ) {
                user_update_user( $moodleuserdata, $updatepassword, false );
                $userid = $moodleuserdata->id;
            } else {
                $moodleuserdata->auth = 'manual';
                $moodleuserdata->lang = $wpuserdata[ 'lang' ];
                $userid = user_create_user( $moodleuserdata, $updatepassword, false );
                $response[ 'created' ] = true;
            }
            
            $response[ 'id' ] = $userid;

            $response = [
                'status' => 'success',
                'data'   => json_encode( $response ),
            ];
        } else {
            $response = [
                'status' => 'failed',
                'data'   => json_encode( 'Bad Request' ),
            ];
        }
        
        return ( $response );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function auth_moowoodle_user_sync_returns(): core_external\external_single_structure {
        return new core_external\external_single_structure(
            [
                'status' => new core_external\external_value( PARAM_RAW, 'status: success if success' ),
                'data'   => new core_external\external_value( PARAM_RAW, 'moode user id' ),
            ]
        );
    }
}