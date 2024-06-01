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
 * Sync wordpress and moodle
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_moowoodle\event;

defined( 'MOODLE_INTERNAL' ) || die();

require_once( __DIR__ . '/../../externallib.php' );
require_once ( $CFG->libdir . '/filelib.php' );

/**
 * Sinc wodrpres with moodle if smothing changed in moodle
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moowoodle_realtime_user_sync {

    /**
     * moodle user sync for event in moodle
     * @param \core\event\base $event
     */
    public static function moowoodle_user_sync_observer( \core\event\base $event ) {
        $userdata = get_complete_user_data( 'id', $event->get_data()[ 'relateduserid' ] );
        
        // file_put_contents('C:\xampp\htdocs\moodle-2\auth\moowoodle\log.txt', var_export( $syncsettings , true ), FILE_APPEND );
        
        $obj = new \auth_moowoodle_external();
        $obj->auth_moowoodle_get_users( 0, 10 );
        
        $userdataarray = [];

        // Get the user data
        $userdataarray[ 'email' ]    = $userdata->email;
        $userdataarray[ 'username' ] = $userdata->username;
        $userdataarray[ 'password' ] = $userdata->password;
        
        // Get the user first name
        if ( $userdata->firstname != null ) {
            $userdataarray[ 'firstname' ] = $userdata->firstname;
        }

        // Get the user last name
        if ( $userdata->lastname != null ) {
            $userdataarray[ 'lastname' ] = $userdata->lastname;
        }

        $requesturl = get_config( 'auth_moowoodle', 'wpsiteurl' ) . '/?rest_route=/moowoodle/v1/user-sync';

        $options = [
            'RETURNTRANSFER' => true,
            'TIMEOUT'        => 100,
        ];

        $curl = new \curl();
        
        if ( $curl === false ) {
            die( 'Failed to initialize cURL' );
        }

        $response = $curl->post( $requesturl, $userdataarray, $options );
    }
}
