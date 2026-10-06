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
 * External function: auth_moowoodle_get_users.
 *
 * @package    auth_moowoodle
 * @author     DualCube <admin@dualcube.com>
 * @copyright  2026 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_moowoodle\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External function: auth_moowoodle_get_users.
 */
class get_users extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'endid' => new external_value(PARAM_RAW, 'The last id to send the next batch of user data'),
            'limit' => new external_value(PARAM_RAW, 'The limit for the batch of user data'),
            'roles' => new external_value(PARAM_RAW, 'The role ids, a comma separated string of role ids'),
        ]);
    }

    /**
     * Get all users, batched by id, restricted to the given roles.
     *
     * Only the profile fields the WordPress integration actually needs are
     * selected and returned. Password hashes are never exported.
     *
     * @param int $endid
     * @param int $limit
     * @param string $roles Comma separated role ids.
     * @return array
     */
    public static function execute($endid, $limit, $roles) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'endid' => $endid,
            'limit' => $limit,
            'roles' => $roles,
        ]);

        $context = \core\context\system::instance();
        self::validate_context($context);
        require_capability('auth/moowoodle:exportusers', $context);

        $endid = $params['endid'];
        $limit = $params['limit'];
        $roles = $params['roles'];

        if (!is_numeric($limit) || !is_numeric($endid)) {
            return [
                'status' => 'failed',
                'data' => json_encode('Bad Request'),
            ];
        }

        $limit = (int) $limit + 1;

        // Sanitize role ids and prepare SQL placeholders.
        $roleids = explode(',', $roles);
        $roleids = array_map('intval', $roleids);

        [$rolesql, $roleparams] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'roleid');

        $sql = "SELECT u.id, u.email, u.username, u.firstname, u.lastname
                  FROM {user} u
                  JOIN {role_assignments} ra ON u.id = ra.userid
                 WHERE u.id > :endid
                   AND u.deleted = 0
                   AND ra.roleid $rolesql
              ORDER BY u.id ASC";

        $sqlparams = array_merge(['endid' => (int) $endid], $roleparams);

        $records = $limit <= 0
            ? $DB->get_records_sql($sql, $sqlparams)
            : $DB->get_records_sql($sql, $sqlparams, 0, $limit);

        // Explicitly allow-list the exported fields, rather than passing the
        // DB row straight through, so nothing beyond these fields can ever
        // leak through this endpoint. Password hashes are never exported.
        $users = [];
        foreach ($records as $record) {
            $user = [
                'id' => (int) $record->id,
                'email' => $record->email,
                'username' => $record->username,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
            ];

            $users[] = $user;
        }

        return [
            'status' => 'success',
            'data' => json_encode($users),
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_RAW, 'status: success if success'),
            'data' => new external_value(PARAM_RAW, 'users: all user data'),
        ]);
    }
}
