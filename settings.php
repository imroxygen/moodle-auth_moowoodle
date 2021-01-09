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
 * @author DualCube
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth_moowoodle
 */

defined('MOODLE_INTERNAL') || die();

$configkey = new lang_string('auth_moowoodle_secretkey', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_secretkey_desc', 'auth_moowoodle');
$configdefault = 'Webservice Token';
$settings->add(new admin_setting_configtext('auth_moowoodle/sharedsecret', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('auth_moowoodle_timeout', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_timeout_desc', 'auth_moowoodle');
$configdefault = 5;
$settings->add(new admin_setting_configtext('auth_moowoodle/timeout', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('auth_moowoodle_logoffurl', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_logoffurl_desc', 'auth_moowoodle');
$configdefault = 'your-wordpress-site/wp-login.php?action=logout';
$settings->add(new admin_setting_configtext('auth_moowoodle/logoffurl', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('auth_moowoodle_autoopen', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_autoopen_desc', 'auth_moowoodle');
$configdefault = 'yes';
$settings->add(new admin_setting_configselect('auth_moowoodle/autoopen', $configkey, $configdesc, $configdefault, array(
        'yes' => 'Yes',
        'no' => 'No',
    )));

$configkey = new lang_string('auth_moowoodle_updateuser', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_updateuser_desc', 'auth_moowoodle');
$configdefault = 'yes';
$settings->add(new admin_setting_configselect('auth_moowoodle/updateuser', $configkey, $configdesc, $configdefault, array(
        'yes' => 'Yes',
        'no' => 'No',
    )));

$configkey = new lang_string('auth_moowoodle_redirectnoenrol', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_redirectnoenrol_desc', 'auth_moowoodle');
$configdefault = 'no';
$settings->add(new admin_setting_configselect('auth_moowoodle/redirectnoenrol', $configkey, $configdesc, $configdefault, array(
        'no' => 'No',
        'yes' => 'Yes',
    )));

$configkey = new lang_string('auth_moowoodle_firstname', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_firstname_desc', 'auth_moowoodle');
$configdefault = 'default-first-name';
$settings->add(new admin_setting_configtext('auth_moowoodle/firstname', $configkey, $configdesc, $configdefault, PARAM_TEXT));

$configkey = new lang_string('auth_moowoodle_lastname', 'auth_moowoodle');
$configdesc = new lang_string('auth_moowoodle_lastname_desc', 'auth_moowoodle');
$configdefault = 'default-last-name';
$settings->add(new admin_setting_configtext('auth_moowoodle/lastname', $configkey, $configdesc, $configdefault, PARAM_TEXT));
