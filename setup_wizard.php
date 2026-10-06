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
 * Guided setup wizard for connecting Moodle to the MooWoodle WordPress plugin.
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use auth_moowoodle\local\setup_wizard;
use auth_moowoodle\local\settings_handler;
use auth_moowoodle\settings\connection_form;
use auth_moowoodle\settings\general_form;
use auth_moowoodle\settings\synchronization_form;
use auth_moowoodle\settings\webservice_form;
use core\context\system as context_system;

global $CFG, $OUTPUT, $PAGE, $USER;

admin_externalpage_setup('auth_moowoodle_setup_wizard');

$context = context_system::instance();

$step = optional_param('step', '', PARAM_ALPHA);

if (!setup_wizard::is_valid_step($step)) {
    $stored = get_config('auth_moowoodle', 'setup_progress');
    $step = $stored ? setup_wizard::get_next_step($stored) : '';

    if ($step === '') {
        $step = setup_wizard::get_first_step();
    }
}

$pageurl = new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => $step]);
$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('setupwizard', 'auth_moowoodle'));
$PAGE->set_heading(get_string('setupwizard', 'auth_moowoodle'));

// Small "Copy" button behaviour for the read-only site URL / token fields. Safe to
// load on every step: it's a single delegated click listener, and does nothing unless
// a ".auth-moowoodle-copy" button is actually present on the page.
$PAGE->requires->js_call_amd('auth_moowoodle/setup_wizard', 'initCopyButtons');

// Refresh the Web Service step's Token list when the service or user dropdown changes,
// via a small JSON fetch, instead of reloading the page. No page navigation means no
// "leave this page?" prompt from Moodle's unsaved-changes warning, and the "Name for
// the Web Service" field's own show/hide already happens client-side via hideIf().
if ($step === 'webservice') {
    $ajaxurl = (new moodle_url('/auth/moowoodle/wizard_ajax.php'))->out(false);
    $PAGE->requires->js_call_amd('auth_moowoodle/setup_wizard', 'initWebserviceStep', [$ajaxurl]);
}

// Simple GET+sesskey "continue" actions (steps with nothing to submit).
if (data_submitted() && optional_param('continuestep', 0, PARAM_BOOL)) {
    require_sesskey();
    setup_wizard::mark_step_complete($step);
    $next = setup_wizard::get_next_step($step);
    redirect(new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => $next ?: $step]));
}

if (data_submitted() && optional_param('restartwizard', 0, PARAM_BOOL)) {
    require_sesskey();
    unset_config('setup_progress', 'auth_moowoodle');
    redirect(new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => setup_wizard::get_first_step()]));
}

$content = '';

switch ($step) {
    case 'requirements':
        $form = new general_form($pageurl);
        $notification = '';

        if ($data = $form->get_data()) {
            settings_handler::save_general_settings($data);
            $notification = $OUTPUT->notification(get_string('settingssaved', 'auth_moowoodle'), 'success');

            if (!empty($data->saveandcontinue)) {
                setup_wizard::mark_step_complete($step);
                redirect(new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => setup_wizard::get_next_step($step)]));
            }
        } else {
            $protocols = !empty($CFG->webserviceprotocols) ? explode(',', $CFG->webserviceprotocols) : [];

            $form->set_data((object) [
                'enablewebservices' => (bool) $CFG->enablewebservices,
                'restprotocol' => in_array('rest', $protocols, true),
                'passwordpolicy' => (bool) $CFG->passwordpolicy,
                'extendedusernamechars' => (bool) $CFG->extendedusernamechars,
                // Enabled by default until the wizard has been run at least once.
                'enableauth' => get_config('auth_moowoodle', 'setup_progress') ? is_enabled_auth('moowoodle') : true,
            ]);
        }

        $content = $OUTPUT->render_from_template('auth_moowoodle/step', [
            'heading' => get_string('step_requirements', 'auth_moowoodle'),
            'intro' => get_string('requirements_intro', 'auth_moowoodle'),
            'notification' => $notification,
            'formhtml' => $form->render(),
        ]);
        break;

    case 'connection':
        $form = new connection_form($pageurl);
        $notification = '';

        if ($data = $form->get_data()) {
            $wpsiteurl = rtrim(trim($data->wpsiteurl), '/');

            set_config('wpsiteurl', $wpsiteurl, 'auth_moowoodle');
            set_config('encryptkey', trim($data->encryptkey), 'auth_moowoodle');
            set_config('timelimit', (int) $data->timelimit, 'auth_moowoodle');

            if (!empty($data->testconnection)) {
                $result = settings_handler::test_connection($wpsiteurl);
                $notification = $OUTPUT->notification($result['message'], $result['success'] ? 'success' : 'warning');
            } else if (!empty($data->saveandcontinue)) {
                setup_wizard::mark_step_complete($step);
                redirect(new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => setup_wizard::get_next_step($step)]));
            }
        } else {
            // Leave both fields blank when nothing has been configured yet - neither
            // should ever show a converted falsy value or an auto-generated value the
            // admin didn't ask for. An existing saved value is always preserved.
            $form->set_data((object) [
                'wpsiteurl' => get_config('auth_moowoodle', 'wpsiteurl') ?: '',
                'encryptkey' => get_config('auth_moowoodle', 'encryptkey') ?: '',
                'timelimit' => get_config('auth_moowoodle', 'timelimit') ?: 60,
            ]);
        }

        $content = $OUTPUT->render_from_template('auth_moowoodle/step', [
            'heading' => get_string('step_connection', 'auth_moowoodle'),
            'intro' => get_string('connection_intro', 'auth_moowoodle'),
            'notification' => $notification,
            'formhtml' => $form->render(),
        ]);
        break;

    case 'webservice':
        require_capability('moodle/webservice:createtoken', $context);

        $services = settings_handler::get_existing_services();
        $users = settings_handler::get_selectable_users();

        if (empty($users)) {
            $users = [$USER->id => $USER->email];
        }

        // The service currently selected in the dropdown (possibly not yet saved), so the
        // Token list can be refreshed for it when the dropdown change reloads the page.
        $rawserviceid = optional_param('serviceid', '', PARAM_RAW);
        $viewserviceid = $rawserviceid !== '' ? (int) $rawserviceid : (int) get_config('auth_moowoodle', 'webservice_id');
        $tokens = settings_handler::get_tokens_for_service($viewserviceid);

        $form = new webservice_form($pageurl, [
            'services' => $services,
            'users' => $users,
            'tokens' => $tokens,
            'existingservice' => (bool) $viewserviceid,
        ]);

        // Only treat this as a real create/update when the actual submit button was
        // clicked. A plain dropdown-change reload (see the JS above) posts the form
        // without any submit button's name/value, so it never reaches this branch.
        $realsubmit = optional_param('updateservice', '', PARAM_RAW) !== '';
        $justcreated = false;
        $notification = '';

        if ($realsubmit && ($data = $form->get_data())) {
            // Optional functions (beyond the two this plugin always needs) are granted
            // only via the explicit, per-function opt-in on the Synchronization step -
            // never automatically here. A brand new service starts with just the
            // mandatory functions; get_enabled_sync_functions() already returns those
            // (plus whatever the admin has separately opted into) when applied below.
            $result = settings_handler::create_or_update_service(
                (int) $data->serviceid,
                (int) $data->userid,
                $data->newservicename ?? ''
            );

            $notification = $OUTPUT->notification($result['message'], $result['success'] ? 'success' : 'error');

            if ($result['success']) {
                setup_wizard::mark_step_complete($step);

                $justcreated = true;
                $createduserid = (int) $data->userid;

                // Refresh the service/token lists and rebuild the form in place, instead of
                // redirecting, so the newly created service and token show up immediately.
                $viewserviceid = (int) $result['serviceid'];
                $services = settings_handler::get_existing_services();
                $tokens = settings_handler::get_tokens_for_service($viewserviceid);

                // Discard the just-processed submission before rebuilding the form: once a
                // moodleform detects it was submitted, it renders those posted values (e.g.
                // serviceid "0" for "create new") instead of the set_data() defaults below,
                // which would otherwise leave the dropdown stuck on "Create new web service"
                // instead of switching to the service that was just created.
                $_POST = [];

                $form = new webservice_form($pageurl, [
                    'services' => $services,
                    'users' => $users,
                    'tokens' => $tokens,
                    'existingservice' => true,
                ]);
            }
        }

        // Preserve the admin's in-progress "Select user" choice across a reload triggered by
        // changing the service dropdown, instead of resetting it back to the default each time.
        if ($justcreated) {
            $selecteduserid = $createduserid;
        } else {
            $rawuserid = optional_param('userid', 0, PARAM_INT);
            if ($rawuserid && array_key_exists($rawuserid, $users)) {
                $selecteduserid = $rawuserid;
            } else {
                $selecteduserid = array_key_exists((int) $USER->id, $users) ? (int) $USER->id : (int) array_key_first($users);
            }
        }

        $selectedtoken = '';

        if ($viewserviceid) {
            $existingtoken = settings_handler::get_existing_token($viewserviceid, $selecteduserid);

            if ($existingtoken && array_key_exists($existingtoken->token, $tokens)) {
                $selectedtoken = $existingtoken->token;
            }
        }

        $form->set_data((object) [
            'serviceid' => $viewserviceid,
            'newservicename' => optional_param('newservicename', '', PARAM_TEXT),
            'userid' => $selecteduserid,
            'langcode' => $CFG->lang,
            'siteurl' => $CFG->wwwroot,
            'token' => $selectedtoken,
        ]);

        $nexturl = new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => setup_wizard::get_next_step($step)]);

        $content = $OUTPUT->render_from_template('auth_moowoodle/step', [
            'heading' => get_string('step_webservice', 'auth_moowoodle'),
            'intro' => get_string('webservice_intro', 'auth_moowoodle'),
            'notification' => $notification,
            'formhtml' => $form->render(),
            'extra' => $OUTPUT->single_button($nexturl, get_string('next'), 'get'),
        ]);
        break;

    case 'synchronization':
        $readonlyfunctions = settings_handler::READONLY_SYNC_FUNCTIONS;
        $mutatingfunctions = settings_handler::MUTATING_SYNC_FUNCTIONS;

        $form = new synchronization_form($pageurl, [
            'readonly' => $readonlyfunctions,
            'mutating' => $mutatingfunctions,
        ]);

        $notification = '';

        if ($data = $form->get_data()) {
            // Only functions the admin explicitly checked are granted - nothing here
            // is selected by default, and unchecking a box does not by itself revoke
            // access already granted (see synchronization_intro).
            $selected = [];

            foreach (array_merge($readonlyfunctions, $mutatingfunctions) as $functionname) {
                if (!empty($data->$functionname)) {
                    $selected[] = $functionname;
                }
            }

            settings_handler::save_sync_functions($selected);
            $notification = $OUTPUT->notification(get_string('settingssaved', 'auth_moowoodle'), 'success');

            if (!empty($data->saveandcontinue)) {
                setup_wizard::mark_step_complete($step);
                redirect(new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => setup_wizard::get_next_step($step)]));
            }
        } else {
            $enabled = settings_handler::get_enabled_sync_functions();
            $defaults = [];

            foreach (array_merge($readonlyfunctions, $mutatingfunctions) as $functionname) {
                $defaults[$functionname] = in_array($functionname, $enabled, true) ? 1 : 0;
            }

            $form->set_data((object) $defaults);
        }

        // Purely informational - the two functions this plugin always needs are never
        // shown as checkboxes above, so make it clear why they're missing from the list.
        $requirednote = $OUTPUT->notification(
            get_string('synchronization_requirednote_heading', 'auth_moowoodle') . html_writer::alist([
                get_string('synchronization_requiredfunction_get_users', 'auth_moowoodle'),
                get_string('synchronization_requiredfunction_user_sync', 'auth_moowoodle'),
            ]),
            'info'
        );

        $content = $OUTPUT->render_from_template('auth_moowoodle/step', [
            'heading' => get_string('step_synchronization', 'auth_moowoodle'),
            'intro' => get_string('synchronization_intro', 'auth_moowoodle'),
            'notification' => $notification,
            'formhtml' => $form->render(),
            'extra' => $requirednote,
        ]);
        break;

    case 'summary':
        $summary = settings_handler::get_summary();

        $connectiontest = !empty($summary['wordpressurl'])
            ? settings_handler::test_connection($summary['wordpressurl'])
            : ['success' => false, 'message' => get_string('testconnection_invalidurl', 'auth_moowoodle')];

        $statuscell = static function (bool $ok) use ($OUTPUT): string {
            $label = get_string($ok ? 'enabled' : 'disabled', 'auth_moowoodle');

            return $OUTPUT->pix_icon($ok ? 'i/valid' : 'i/invalid', $label) . ' ' . $label;
        };

        $generalrows = [
            ['label' => get_string('req_restprotocol', 'auth_moowoodle'), 'value' => $statuscell($summary['restprotocol'])],
            ['label' => get_string('req_webservices', 'auth_moowoodle'), 'value' => $statuscell($summary['webservices'])],
            ['label' => get_string('req_passwordpolicy', 'auth_moowoodle'), 'value' => $statuscell($summary['passwordpolicy'])],
            [
                'label' => get_string('req_extendedchars', 'auth_moowoodle'),
                'value' => $statuscell($summary['extendedusernamechars']),
            ],
            ['label' => get_string('req_enableauth', 'auth_moowoodle'), 'value' => $statuscell($summary['authenabled'])],
            [
                'label' => get_string('summary_webservicefunctions', 'auth_moowoodle'),
                'value' => $statuscell($summary['webservicefunctions']),
            ],
            ['label' => get_string('summary_capability', 'auth_moowoodle'), 'value' => $statuscell($summary['capability'])],
        ];

        $connectionstepurl = new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => 'connection']);

        $connectionstatus = $connectiontest['success']
            ? $OUTPUT->pix_icon('i/valid', '') . ' ' . get_string('connectionok', 'auth_moowoodle')
            : $OUTPUT->pix_icon('i/invalid', '') . ' ' . s($connectiontest['message']) . ' '
                . html_writer::link($connectionstepurl, get_string('checkmoredetails', 'auth_moowoodle'));

        $notset = get_string('summary_notset', 'auth_moowoodle');
        $displayvalue = static function (string $value) use ($notset): string {
            return $value !== '' ? s($value) : $notset;
        };

        $connectionrows = [
            ['label' => get_string('summary_moodleurl', 'auth_moowoodle'), 'value' => s($summary['moodleurl'])],
            [
                'label' => get_string('summary_webservicename', 'auth_moowoodle'),
                'value' => $displayvalue($summary['webservicename']),
            ],
            ['label' => get_string('webservice_token_label', 'auth_moowoodle'), 'value' => $displayvalue($summary['token'])],
            [
                'label' => get_string('summary_wordpressurl', 'auth_moowoodle'),
                'value' => $displayvalue($summary['wordpressurl']),
            ],
            ['label' => get_string('summary_connectionstatus', 'auth_moowoodle'), 'value' => $connectionstatus],
            ['label' => get_string('summary_langcode', 'auth_moowoodle'), 'value' => s($summary['langcode'])],
        ];

        $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'manageauths']);
        $restarturl = new moodle_url('/auth/moowoodle/setup_wizard.php', ['step' => $step, 'restartwizard' => 1]);

        setup_wizard::mark_step_complete($step);

        $content = $OUTPUT->render_from_template('auth_moowoodle/summary', [
            'heading' => get_string('step_summary', 'auth_moowoodle'),
            'intro' => get_string('summary_intro', 'auth_moowoodle'),
            'generalheading' => get_string('summary_general_heading', 'auth_moowoodle'),
            'generalrows' => $generalrows,
            'connectionheading' => get_string('summary_connection_heading', 'auth_moowoodle'),
            'connectionrows' => $connectionrows,
            'copynote' => get_string('summary_copy_note', 'auth_moowoodle'),
            'gotosettingsbutton' => $OUTPUT->single_button($settingsurl, get_string('gotosettings', 'auth_moowoodle'), 'get'),
            'restartbutton' => $OUTPUT->single_button($restarturl, get_string('redosetup', 'auth_moowoodle'), 'post'),
        ]);
        break;
}

echo $OUTPUT->header();
$tabdata = setup_wizard::get_tabs();
echo $OUTPUT->tabtree($tabdata['tabs'], $step, $tabdata['inactive']);
echo $OUTPUT->box_start('generalbox auth-moowoodle-setup-wizard');
echo $content;
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
