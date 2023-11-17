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
$settings = new auth_moowoodle_moodle_connector_admin_settingspage_tabs(
	'auth_moowoodle_moodle_connector',
	get_string('pluginname', 'auth_moowoodle_moodle_connector')
);

//General Settings tab
$page = new admin_settingpage('auth_moowoodle_moodle_connector_general_settings', new lang_string('moowoodle_settings', 'auth_moowoodle_moodle_connector'));

$page->add(new admin_setting_heading('auth_moowoodle_moodle_connector/pluginname', '',
	new lang_string('auth_moowoodledescription', 'auth_moowoodle_moodle_connector')));
$page->add(new admin_setting_configtext('auth_moowoodle_moodle_connector/encryptkey', get_string('key', 'auth_moowoodle_moodle_connector'),
	get_string('message', 'auth_moowoodle_moodle_connector', 'auth'), '', PARAM_RAW));
$page->add(new admin_setting_configtext('auth_moowoodle_moodle_connector/wpsiteurl', get_string('wpsiteurl', 'auth_moowoodle_moodle_connector'),
	get_string('message2', 'auth_moowoodle_moodle_connector', 'auth'), '', PARAM_RAW));
$page->add(new admin_setting_configtext('auth_moowoodle_moodle_connector/timelimit', get_string('timelimit', 'auth_moowoodle_moodle_connector'),
	get_string('message3', 'auth_moowoodle_moodle_connector', 'auth'), '5', PARAM_INT));

$settings->add($page);

?>
