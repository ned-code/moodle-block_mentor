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
 * @package    block_fn_mentor
 * @copyright  Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('lib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

$action   = optional_param('action', false, PARAM_TEXT);
$mentorid = optional_param('mentorid', 0, PARAM_INT);
$studentids = optional_param('studentids', null, PARAM_RAW);

confirm_sesskey();

require_login(null, false);

$data = array();
$data['success'] = true;
$data['message'] = '';

// PERMISSION.
if ( has_capability('block/fn_mentor:assignmentor', context_system::instance(), $USER->id)) {

    $studentlist = explode(',', $studentids);

    foreach ($studentlist as $key => $value) {
        $studentlist[$key] = (int) $value;
        if ( $studentlist[$key] != $value ) {
            unset($studentlist[$key]);
        }
    }

    list($insql, $params) = $DB->get_in_or_equal($studentlist);

    switch ($action) {
        case 'add':
            $mentorrole = get_config('block_fn_mentor', 'mentor_role_user');

            $sqlusers = "SELECT u.id FROM {user} u WHERE id $insql";
            $users = $DB->get_records_sql($sqlusers, $params);

            if ($mentorrole && $mentorid) {
                foreach ($users as $user) {
                    $usercontext = context_user::instance($user->id);
                    role_assign($mentorrole, $mentorid, $usercontext->id);
                }
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;

        case 'remove':
            $mentorrole = get_config('block_fn_mentor', 'mentor_role_user');

            $sqlusers = "SELECT u.id FROM {user} u WHERE id $insql";
            $users = $DB->get_records_sql($sqlusers, $params);

            if ($mentorrole && $mentorid) {
                foreach ($users as $user) {
                    $usercontext = context_user::instance($user->id);
                    role_unassign($mentorrole, $mentorid, $usercontext->id);
                }
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
    }

} else {
    $data['success'] = false;
    $data['message'] = get_string('permission_error', 'block_fn_mentor');
}
echo json_encode($data);