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

$action   = optional_param('action', false, PARAM_TEXT);
$userid = optional_param('userid', 0, PARAM_INT);
$targetid = optional_param('targetid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);

confirm_sesskey();

require_login(null, false);

$data = array();
$data['success'] = false;
$data['message'] = '';
$data['params'] = array(
    'action' => $action,
    'userid' => $userid,
    'targetid' => $targetid,
    'groupid' => $groupid
);

// Permission.
if (has_capability('block/fn_mentor:assignmentor', context_system::instance())) {

    if (!$userid) {
        $action = 'skip';
    }

    $mentorrolesystem = get_config('block_fn_mentor', 'mentor_role_system');
    $mentorroleuser = get_config('block_fn_mentor', 'mentor_role_user');

    // Actions.
    switch ($action) {
        case 'add-mentor':
            // Add into group.
            if ($groupid) {
                block_fn_mentor_add_group_member($userid, $groupid, 'M');
            }
            break;
        case 'remove-mentor':
            // Remove from group.
            if ($groupid) {
                block_fn_mentor_remove_group_member($userid, $groupid, 'M');
            }
            break;
        case 'add-mentee':
            $usercontext = context_user::instance($userid);

            $mentor = $DB->get_record('user', array('id' => $targetid, 'deleted' => 0));

            if ($mentorroleuser && $mentor) {
                role_assign($mentorroleuser, $mentor->id, $usercontext->id);
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'add-mentee-group':
            $usercontext = context_user::instance($userid);

            $mentor = $DB->get_record('user', array('id' => $targetid, 'deleted' => 0));

            if ($groupid && $mentor) {
                block_fn_mentor_add_group_member($userid, $groupid, 'U');
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'remove-mentee':
            $usercontext = context_user::instance($userid);

            $mentor = $DB->get_record('user', array('id' => $targetid, 'deleted' => 0));

            if ($mentorroleuser && $mentor) {
                role_unassign($mentorroleuser, $mentor->id, $usercontext->id);
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'remove-mentee-group':
            $usercontext = context_user::instance($userid);

            $mentor = $DB->get_record('user', array('id' => $targetid, 'deleted' => 0));

            if ($groupid && $mentor) {
                block_fn_mentor_remove_group_member($userid, $groupid, 'U');
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'assign-mentor':
            $mentor = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));

            $systemcontext = context_system::instance();
            if ($mentorrolesystem && $mentor) {
                role_assign($mentorrolesystem, $mentor->id, $systemcontext->id);
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'unassign-mentor':
            $mentor = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));

            $systemcontext = context_system::instance();
            if ($mentorrolesystem && $mentor) {
                role_unassign($mentorrolesystem, $mentor->id, $systemcontext->id);
            } else {
                $data['success'] = false;
                $data['message'] = '';
            }
            break;
        case 'toggle-group-leader':
            $mentor = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));

            if ($DB->record_exists('block_fn_mentor_group', array('id' => $groupid))) {
                if ($gm = $DB->get_record('block_fn_mentor_group_mem', array('groupid' => $groupid, 'userid' => $userid, 'role' => 'M'))) {
                    $member = new stdClass();
                    $member->id = $gm->id;
                    $member->teamleader = ($gm->teamleader) ? 0 : 1;
                    $data['success'] = $DB->update_record('block_fn_mentor_group_mem', $member);
                }
            }
            break;
    }

} else {
    $data['success'] = false;
    $data['message'] = get_string('permission_error', 'block_fn_mentor');
}
echo json_encode($data);