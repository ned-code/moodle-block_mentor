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
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers.
 */
class block_fn_mentor_observer {
    public static function user_updated(\core\event\user_updated $event) {
        if (!$assignmentorinprofile = get_config('block_fn_mentor', 'assignmentorinprofile')) {
            return;
        }
        // Check user field. Assign mentor if necessary.
        $data = $event->get_data();
        $profile = profile_user_record($data['objectid'], false);
        if (!empty($profile->mentoremail)) {
            block_fn_mentor_assign_mentor_from_profile($data['objectid'], $profile->mentoremail);
            self::update_user_profile($data['objectid']);
        }

    }

    public static function role_assigned(\core\event\role_assigned $event) {
        if (!$assignmentorinprofile = get_config('block_fn_mentor', 'assignmentorinprofile')) {
            return;
        }
        if (!$mentorroleuser = get_config('block_fn_mentor', 'mentor_role_user')) {
            return;
        }

        $data = $event->get_data();
        if ($data['contextlevel'] == CONTEXT_USER && $data['objectid'] == $mentorroleuser) {
            self::set_user_profile($data['contextinstanceid'], $data['relateduserid'],
                BLOCK_FN_MENTOR_PROFILE_FIELD_EMAIL, 'add');
            self::update_user_profile($data['contextinstanceid']);
        }
    }

    public static function role_unassigned(\core\event\role_unassigned $event) {
        if (!$assignmentorinprofile = get_config('block_fn_mentor', 'assignmentorinprofile')) {
            return;
        }
        if (!$mentorroleuser = get_config('block_fn_mentor', 'mentor_role_user')) {
            return;
        }

        $data = $event->get_data();
        if ($data['contextlevel'] == CONTEXT_USER && $data['objectid'] == $mentorroleuser) {
            self::set_user_profile($data['contextinstanceid'], $data['relateduserid'],
                BLOCK_FN_MENTOR_PROFILE_FIELD_EMAIL, 'remove');
            self::update_user_profile($data['contextinstanceid']);
        }
    }

    public static function user_profile_viewed(\core\event\user_profile_viewed $event) {
        if (!$assignmentorinprofile = get_config('block_fn_mentor', 'assignmentorinprofile')) {
            return;
        }
        if (!$mentorroleuser = get_config('block_fn_mentor', 'mentor_role_user')) {
            return;
        }

        $data = $event->get_data();
        self::update_user_profile($data['objectid'], $data['contextid'], $mentorroleuser);
    }

    public static function set_user_profile($menteeid, $mentorid, $fieldname, $action) {
        global $DB;

        if (!$datafield = $DB->get_record('user_info_field', array('shortname' => $fieldname))) {
            return;
        }
        if (!$mentor = $DB->get_record('user', array('id' => $mentorid, 'deleted' => 0))) {
            return;
        }
        if (!$mentee = $DB->get_record('user', array('id' => $menteeid, 'deleted' => 0))) {
            return;
        }

        // Mentee profile fields.
        $profile = profile_user_record($menteeid, false);
        $updaterequired = false;

        if (empty($profile->$fieldname)) {
            if ($action == 'add') {
                $profile->$fieldname = $mentor->email;
                $updaterequired = true;
            }
        } else {
            $profile->$fieldname = str_replace(' ', '', $profile->$fieldname);
            $savedemails = explode(',', $profile->$fieldname);

            if ($action == 'add') {
                if (!in_array($mentor->email, $savedemails)) {
                    $savedemails[] = $mentor->email;
                    $profile->$fieldname = implode(', ', $savedemails);
                    $updaterequired = true;
                }
            } else if ($action == 'remove') {
                if (($key = array_search($mentor->email, $savedemails)) !== false) {
                    unset($savedemails[$key]);
                    $profile->$fieldname = implode(', ', $savedemails);
                    $updaterequired = true;
                }
            }
        }
        if ($updaterequired) {
            $data = new stdClass();
            $data->userid = $mentee->id;
            $data->fieldid = $datafield->id;
            $data->data = $profile->$fieldname;

            if ($dataid = $DB->get_field('user_info_data', 'id', array('userid' => $mentee->id, 'fieldid' => $datafield->id))) {
                $data->id = $dataid;
                $DB->update_record('user_info_data', $data);
            } else {
                $DB->insert_record('user_info_data', $data);
            }
        }
    }

    public static function update_user_profile($menteeid, $contextid=null, $mentorroleuser=null) {
        global $DB;
        if (!$mentorroleuser) {
            if (!$mentorroleuser = get_config('block_fn_mentor', 'mentor_role_user')) {
                return;
            }
        }
        if (!$contextid) {
            $usercontext = context_user::instance($menteeid);
            $contextid = $usercontext->id;
        }
        $sql = "SELECT ra.id,
                       ra.userid
                  FROM {role_assignments} ra
                  JOIN {user} u
                    ON ra.userid = u.id
                 WHERE ra.contextid = ?
                   AND ra.roleid = ?
                   AND u.deleted = 0";
        if ($mentors = $DB->get_records_sql($sql, array($contextid, $mentorroleuser))) {
            foreach ($mentors as $mentor) {
                self::set_user_profile($menteeid, $mentor->userid,
                    BLOCK_FN_MENTOR_PROFILE_FIELD_EMAIL, 'add');
            }
        }
    }
}
