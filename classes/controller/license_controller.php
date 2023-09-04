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
 * Stripe enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol_stripepaymentpro
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2023 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_stripepaymentpro\controller;

if (!class_exists('license_controller')) {
    class license_controller{
        /**
         *
         * @var string api key
         */
        private $license_key = '';
        /**
         *
         * @var string subscriptionid
         */
        private $subscriptionid = '';
        /**
         *
         * @var string plugin_short_name
         */
        private $plugin_short_name = '';
        /**
         *
         * @var string license avtivation url redirect
         */
        private $license_avtivation_url = '';
        /**
         *
         * @var string productid
         */
        private $productid = '';
        /**
         *
         * @var string Slug to be used in url and functions name
         */
        private $plugin_slug = '';
        /**
         *
         * @var string stores the current plugin version
         */
        private $plugin_version = '';
        /**
         *
         * @var string  Stores the URL of store. Retrieves updates from
         *              this store
         */
        private $store_url = '';
        /**
         *
         * @var string  Name of the Author
         */
        private $author_name = '';
        /**
         * Developer Note: This variable is used everywhere to check license information and verify the data.
         * Change the Name of this variable in this file wherever it appears and also remove this comment
         * After you are done with adding Licensing
         * And String to add- 'noactivationremain' , 'noapi' ,
         */
        public $license_auth_data = array(
            'plugin_type' => 'enrol', //Plugin type with full directory path eg.'gread/report' use to log 
            'plugin_slug' => 'enrol_stripepaymentpro', //this slug is used to store the data in db. 
            'plugin_short_name' => 'stripepaymentpro', //this short name is the plugin directory name
            'plugin_version' => '1.0.0', //Current Version of the plugin. This should be similar to Version tag mentioned in Plugin headers
            'store_url' => 'https://dualstg.wpengine.com/', //Url where program pings to check if update is available and license validity
            'author_name' => 'DualCube', //Author Name
            'license_avtivation_url' => '/admin/settings.php?section=enrol_stripepaymentpro#enrol_stripepaymentpro_general_settings', //redirect url to activate license
        );
        public function __construct() {
            $this->plugin_type       = $this->license_auth_data['plugin_type'];
            $this->plugin_slug       = $this->license_auth_data['plugin_slug'];
            $this->author_name       = $this->license_auth_data['author_name'];
            $this->plugin_short_name = $this->license_auth_data['plugin_short_name'];
            $this->plugin_version    = $this->license_auth_data['plugin_version'];
            $this->store_url         = $this->license_auth_data['store_url'];
            $this->license_avtivation_url       = $this->license_auth_data['license_avtivation_url'];
            $this->plugin = enrol_get_plugin($this->plugin_short_name);
            $this->license_key = $this->plugin->get_config('apikey');
            $this->subscriptionid = $this->plugin->get_config('subscriptionid');
            $this->productid = $this->plugin->get_config('productid');
        }
        public function check_response_status($license_data, $current_response_code, $valid_response_code) {
            global $DB;
            if ($license_data->success || !in_array($current_response_code, $valid_response_code)) {
                return false;
            }
            return true;
        }
        public function activate_license() {
            global $CFG;
            // Get cURL resource
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $this->store_url,
                CURLOPT_POST => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POSTFIELDS => array(
                    'wc-api' => 'wc-am-api',
                    'wc_am_action' => 'activate',
                    'instance' => $this->subscriptionid,
                    'product_id' => $this->productid,
                    'api_key' => $this->license_key,
                    'version' => $this->plugin_version,
                    'object' => urlencode($CFG->wwwroot),
                )
            ));
            // Send the request & save response to $resp
            $resp = curl_exec($curl);
            $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            // Close request to clear up some resources
            curl_close($curl);
            $license_data = $resp;
            $valid_response_code = array('200', '301');
            $is_data_available = $this->check_response_status($license_data, $current_response_code, $valid_response_code);
            if ($is_data_available == false) {
                return $license_data->data->error;
            } 
            return $license_data;
        }
        public function deactivate_license() {
            global $DB, $CFG;
            if (!empty($this->license_key)) {
                // Get cURL resource
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $this->store_url,
                    CURLOPT_POST => 1,
                    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => array(
                        'wc-api' => 'wc-am-api',
                        'wc_am_action' => 'deactivate',
                        'instance' => $this->subscriptionid,
                        'product_id' => $this->productid,
                        'api_key' => $this->license_key,
                        'version' => $this->plugin_version,
                        'object' => urlencode($CFG->wwwroot),
                    )
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                // Close request to clear up some resources
                curl_close($curl);
                $license_data = $resp;
                $valid_response_code = array('200', '301');
                $is_data_available = $this->check_response_status($license_data, $current_response_code, $valid_response_code);
                if ($is_data_available == false) {
                    return $license_data->data->error;
                }
                return $license_data;
            }
        }
        public function add_data() {
            if (is_siteadmin()) {
                global $CFG;
                $licensestatus = self::get_status_from_api();
                if ($licensestatus->success) {
                    if (isset($_POST[$this->plugin_short_name . '_license_activate'])) {
                        if ($licensestatus->status_check == 'inactive' && $licensestatus->data->activations_remaining > 0) {
                            $log = $this->activate_license();
                            $log  = date("Fj,Y,g:ia") . ' :: ' . $log  . PHP_EOL;
                            file_put_contents($CFG->dirroot . '/' . $this->plugin_type . '/' . $this->plugin_short_name . '/log', $log, FILE_APPEND);
                            redirect($CFG->wwwroot . $this->license_avtivation_url);
                        } elseif ($licensestatus->data->activations_remaining == 0) {
                            $log  = date("Fj,Y,g:ia") . ' :: ' . get_string('noactivationremain', $this->plugin_slug ) . PHP_EOL;
                            file_put_contents($CFG->dirroot . '/' . $this->plugin_type . '/' . $this->plugin_short_name . '/log', $log, FILE_APPEND);
                            redirect($CFG->wwwroot . $this->license_avtivation_url);
                        }
                    } elseif (isset($_POST[$this->plugin_short_name . '_license_deactivate'])) {
                        if ($licensestatus->status_check == 'active') {
                            $log = $this->deactivate_license();
                            $log  = date("Fj,Y,g:ia") . ' :: ' . $log  . PHP_EOL;
                            file_put_contents($CFG->dirroot . '/' . $this->plugin_type . '/' . $this->plugin_short_name . '/log', $log, FILE_APPEND);
                            redirect($CFG->wwwroot . $this->license_avtivation_url);
                        }
                    }
                } else {
                    if ($licensestatus->status_check == 'active') {
                        $log = $this->deactivate_license();
                        $log  = date("Fj,Y,g:ia") . ' :: ' . $log  . PHP_EOL;
                        file_put_contents($CFG->dirroot . '/' . $this->plugin_type . '/' . $this->plugin_short_name . '/log', $log, FILE_APPEND);
                        redirect($CFG->wwwroot . $this->license_avtivation_url);
                    }
                }
                return $licensestatus;
            }
        }
        public function get_status_from_api() {
            global $CFG;
            if ($this->subscriptionid == null) {
                $this->subscriptionid = '0000';
            }
            if ($this->license_key) {
                // Get cURL resource
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => $this->store_url,
                    CURLOPT_POST => 1,
                    CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'] . ' - ' . $CFG->wwwroot,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_POSTFIELDS => array(
                        'wc-api' => 'wc-am-api',
                        'wc_am_action' => 'status',
                        'instance' => $this->subscriptionid,
                        'product_id' => $this->productid,
                        'api_key' => $this->license_key,
                        'version' => $this->plugin_version,
                    )
                ));
                // Send the request & save response to $resp
                $resp = curl_exec($curl);
                $current_response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                // Close request to clear up some resources
                curl_close($curl);
                $license_data = json_decode($resp);
                $expirey = $license_data->data->api_key_expirations->wc_subs_resources[0]->friendly_api_key_expiration_date;
                if ($this->subscriptionid != $license_data->data->api_key_expirations->wc_subs_resources[0]->sub_id) {
                    $this->plugin->set_config('subscriptionid', $license_data->data->api_key_expirations->wc_subs_resources[0]->sub_id);
                }
                if ($current_response_code == 200) {
                    if ($license_data->success) {
                        return $license_data;
                    } else {
                        return ($license_data->data->error);
                    }
                } else {
                    echo ($license_data->data->error_code);
                }
            } else {
                return get_string('noapi', 'enrol_stripepaymentpro');
            }
        }
    }
}
