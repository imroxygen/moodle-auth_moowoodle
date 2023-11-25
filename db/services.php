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
 * @package     auth_moowoodle_user_sync
 * @author      DualCube <admin@dualcube.com>
 * @copyright   Dualcube (https://dualcube.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
	'auth_moowoodle_user_sync_get_all_users_data' => array(
		'classname' => 'auth_moowoodle_user_sync_external',
		'methodname' => 'sync_users',
		'classpath' => 'auth/moowoodle_moodle_connector/externallib.php',
		'description' => 'Sync user data with WordPress or external source',
		'type' => 'write',
		'capabilities' => 'moodle/user:create',
	),
);
