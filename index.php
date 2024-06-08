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
 * user login in moodle
 * 
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ( '../../config.php' );
require_once ( $CFG->libdir . '/filelib.php' );
$SESSION->wantsurl = $CFG->wwwroot . '/';

$passkey = optional_param( 'passkey', '', PARAM_RAW );

if ( $passkey ) {
    $requestdata = base64_decode( $passkey );
    $requestdata = json_decode( $requestdata, true );

    // Get timestamp.
    $timestamp = $requestdata[ 'timestamp' ];

    // Calculate time difference.
    $timedif = time() - $timestamp;

    $userexist = $DB->record_exists( 'user', [ 'id' => $requestdata[ 'user_id' ] ] );
    
    if ( $timedif < get_config( 'auth_moowoodle', 'timelimit' ) * 60 && $userexist ) {

        // Get the user data
        $user = get_complete_user_data( 'id', $requestdata[ 'user_id' ] );
        
        // Get wordpress requset url
        $requesturl = get_config( 'auth_moowoodle', 'wpsiteurl' ) . '/?rest_route=/moowoodle/v1/sso';

        $curl = new curl();

        // Prepare request data
        $reqdata = [
            'action'        => 'login_verify',
            'redirect_to'   => $requestdata['redirect_url'],
            'mdl_user_id'   => $user->id,
            'mdl_username'  => $user->username,
            'mdl_email'     => $user->email,
            'timestamp'     => $requestdata['timestamp'],
            'course_id'     => $requestdata['course_id'],
            'user_id'       => $requestdata['wp_user_id'],
            'one_time_code' => $passkey,
        ];

        // Send request to wordpress server.
        $response = $curl->post(
            $requesturl,
            $reqdata,
            [
                'RETURNTRANSFER' => 1,
                'TIMEOUT'        => 100,
            ]
        );
        
        $response = json_decode( $response, true );

        if ( ! $response ) {
            throw new moodle_exception( $curl->error );
        }

        if ( $response[ 'status' ] == 'unauthorized' ) {
            throw new moodle_exception( 'Unauthorized access, contact with site admin.' );
        }

        if ( $response[ 'moowoodle_one_time_code' ] != $passkey ) {
            throw new moodle_exception( 'Unauthorized access, one-time-code mismatch.' );
        }

        if ( $response[ 'sskey' ] != md5( get_config( 'auth_moowoodle', 'encryptkey') ) ) {
            throw new moodle_exception( 'Unauthorized access, sskey mismatch.' );
        }
        
        if ( $response[ 'status' ] == 'success' ) {
            $user->loggedin = true;
            $user->site = $CFG->wwwroot;
            unset_user_preference( 'auth_forcepasswordchange', $user );
            complete_user_login($user);
        }

        if ( $requestdata[ 'redirect_url' ] ) {
            $SESSION->wantsurl = $requestdata[ 'redirect_url' ];
        }
    }
}

redirect( $SESSION->wantsurl );
