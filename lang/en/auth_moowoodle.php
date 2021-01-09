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
 * @package moowoodle
 */


$string['auth_moowoodle_secretkey'] = 'Webservice token';
$string['auth_moowoodle_secretkey_desc'] = 'Must match Wordpress plugin setting';

$string['auth_moowoodledescription'] = 'Uses Wordpress user details to create user & log into Moodle and also License setting';
$string['pluginname'] = 'MooWoodle';

$string['auth_moowoodle_timeout'] = 'Link timeout';
$string['auth_moowoodle_timeout_desc'] = 'Minutes before incoming link is considered invalid (allow for reading time on Wordpress page)';

$string['auth_moowoodle_logoffurl'] = 'Logout Url';
$string['auth_moowoodle_logoffurl_desc'] = 'Url to redirect to if the user Logout';

$string['auth_moowoodle_autoopen_desc'] = 'Automatically open the course after successful auth (uses first match in cohort or group)';
$string['auth_moowoodle_autoopen'] = 'Auto open course?';

$string['auth_moowoodle_updateuser'] = 'Update user profile fields using Wordpress values?';
$string['auth_moowoodle_updateuser_desc'] = 'If set, user profile fields such as first and last name will be overwritten each time the SSO occurs. Turn this off if you want to let the user manage their profile fields independantly.';

$string['auth_moowoodle_redirectnoenrol'] = 'Only redirect user to course?';
$string['auth_moowoodle_redirectnoenrol_desc'] = 'If set, the user is being redirected to the course. Otherwise the user is enrolled into the course, if that has not been done already.';

$string['auth_moowoodle_firstname'] = 'Default first name';
$string['auth_moowoodle_firstname_desc'] = 'If no first name is specified by Wordpress, use this value';

$string['auth_moowoodle_lastname'] = 'Default last name';
$string['auth_moowoodle_lastname_desc'] = 'If no last name is specified by Wordpress, use this value';


$string['moowoodle_settings'] = 'Moowoodle Settings';
$string['moowoodle_license'] = 'License Settings';
$string['site_admin'] = 'Site Administrator';
$string['plugins'] = 'Plugins';
$string['authentication'] = 'Authentication';
$string['enter_license_key'] = 'Enter License Key';
$string['enter_product_id'] = 'Enter Product ID';
$string['active_license'] = 'Activate';
$string['not_active'] = 'Not Active';
$string['active'] = 'Active';
$string['deactive_license'] = 'Deactivate';
$string['error'] = 'Error! You do not have permission.';
$string['licensenotactive'] = 'License Not Active!';
