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
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configtext(
            'auth_moowoodle/encryptkey',
            get_string(
                'key', 'auth_moowoodle'
            ),
            get_string(
                'moowoodle_plugin_message',
                'auth_moowoodle'
            ),
            '',
            PARAM_RAW
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'auth_moowoodle/wpsiteurl',
            get_string(
                'wpsiteurl',
                'auth_moowoodle'
            ),
            get_string(
                'wpsiteurl_message',
                'auth_moowoodle'
            ),
            '',
            PARAM_RAW
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'auth_moowoodle/timelimit',
            get_string(
                'timelimit',
                'auth_moowoodle'
            ),
            get_string(
                'timelimit_message',
                'auth_moowoodle'
            ),
            '5',
            PARAM_INT
        )
    );
}
