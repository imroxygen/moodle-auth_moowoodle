<?php
/**
 *
 * @package    auth_moowoodle_moodle_connector
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
$yesno = array(get_string('yes'), get_string('no'));
// Adding tab setting for the stripepaymentpro.
$settings = new auth_moowoodleconnect_admin_settingspage_tabs(
    'auth_moowoodleconnect',
    get_string('pluginname', 'auth_moowoodleconnect')
);

//General Settings tab
$page = new admin_settingpage('auth_moowoodleconnect_general_settings', new lang_string('moowoodle_settings', 'auth_moowoodleconnect'));

$page->add(new admin_setting_heading('auth_moowoodleconnect/pluginname', '',
							new lang_string('auth_moowoodledescription', 'auth_moowoodleconnect')));
$page->add(new admin_setting_configtext('auth_moowoodleconnect/encryptkey', get_string('key', 'auth_moowoodleconnect'),
						get_string('message','auth_moowoodleconnect','auth'),'', PARAM_RAW));
$page->add(new admin_setting_configtext('auth_moowoodleconnect/wpsiteurl', get_string('wpsiteurl', 'auth_moowoodleconnect'),
						get_string('message2','auth_moowoodleconnect','auth'),'', PARAM_RAW));
$page->add(new admin_setting_configtext('auth_moowoodleconnect/timelimit', get_string('timelimit', 'auth_moowoodleconnect'),
						get_string('message3','auth_moowoodleconnect','auth'),'5', PARAM_INT));




$settings->add($page);


?>
