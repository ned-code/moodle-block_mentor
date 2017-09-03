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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/export_form.php');

$id     = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'edit', PARAM_RAW);

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

if (!is_siteadmin()) {
    print_error('permissionerror', 'block_fn_mentor');
    die;
}

$PAGE->https_required();

$thispageurl = new moodle_url('/blocks/fn_mentor/export.php');
$returnpageurl = new moodle_url('/blocks/fn_mentor/importexport.php');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->verify_https_required();

$name = get_string('export', 'block_fn_mentor');
$title = get_string('export', 'block_fn_mentor');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('importexport', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/importexport.php'));
$PAGE->navbar->add($name);
$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/fn_mentor/js/export.js?v='.time());


$PAGE->set_title($title);
$PAGE->set_heading($heading);

$mform = new export_form(null, array());

if ($mform->is_cancelled()) {
    redirect($returnpageurl);
} else if ($fromform = $mform->get_data()) {

    $includeextranedcolumns = get_config('block_fn_mentor', 'includeextranedcolumns');

    $mentors = block_fn_mentor_get_all_mentors();
    $mentees = block_fn_mentor_get_all_mentees();
    $userids = array_merge(array_keys($mentors), array_keys($mentees));

    if ($fromform->includeenrolledusers) {
        $enrolledusers = block_fn_mentor_users_has_active_enrollment();
        $userids = array_merge($userids, array_keys($enrolledusers));
    } else if ($fromform->includeallusers) {
        $userids = array_keys($users = $DB->get_records('user', array('deleted' => 0)));
    }

    $sql = "SELECT MAX(g.numofgroups) maxnumberofgroups
              FROM (SELECT COUNT(gm.groupid) numofgroups
                      FROM {block_fn_mentor_group_mem} gm
                  GROUP BY gm.userid) g";
    if ($m = $DB->get_record_sql($sql)) {
        $maxgroup = $m->maxnumberofgroups;
    } else {
        $maxgroup = 0;
    }

    $maxmentor = block_fn_mentor_max_number_of_mentor($userids);

    // Export data.
    set_time_limit(300);
    raise_memory_limit(MEMORY_EXTRA);
    $table = new stdClass();

    $headers = array('firstname', 'lastname', 'email', 'mentor_role');

    for ($i = 1; $i <= $maxgroup; $i++) {
        $headers[] = 'mentor_group'.$i;
    }
    for ($i = 1; $i <= $maxmentor; $i++) {
        $headers[] = 'mentor'.$i;
    }
    if ($includeextranedcolumns) {
        $headers[] = 'site_group';
        $headers[] = 'cohort';
        $headers[] = 'themefront';
        $headers[] = 'themecourse';
        $headers[] = 'subnumber';

        $themes = array_keys(get_list_of_themes());
    }

    // Default row.
    $defaultrow = array();
    foreach ($headers as $header) {
        $defaultrow[$header] = '';
    }

    // Output headers so that the file is downloaded rather than displayed.
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=export.csv');

    // Create a file pointer connected to the output stream.
    $outputcsv = fopen('php://output', 'w');

    // Output the column headings.
    fputcsv($outputcsv, $headers);

    list($insql, $params) = $DB->get_in_or_equal($userids);

    $params[] = 0;

    $sql = "SELECT *
              FROM {user} u
             WHERE u.id {$insql}
               AND u.deleted = ?
          ORDER BY u.lastname ASC";

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $user) {
        $row = $defaultrow;
        $row['firstname'] = $user->firstname;
        $row['lastname'] = $user->lastname;
        $row['email'] = $user->email;

        if (block_fn_mentor_is_mentor($user->id)) {
            $row['mentor_role'] = 'mentor';

            // Mentor group.
            $sql = "SELECT gm.id, g.name
                      FROM {block_fn_mentor_group_mem} gm
                      JOIN {block_fn_mentor_group} g
                        ON gm.groupid = g.id
                     WHERE gm.role = ?
                       AND gm.userid = ?";
            if ($groups = $DB->get_records_sql($sql, array('M', $user->id))) {
                $i = 0;
                foreach ($groups as $group) {
                    ++$i;
                    $row['mentor_group'.$i] = $group->name;
                }
            }
        } else if (block_fn_mentor_is_mentee($user->id)) {
            $row['mentor_role'] = 'mentee';
            // Mentors.
            if ($mentors = block_fn_mentor_get_assigned_mentors($user->id)) {
                $mentors = array_values($mentors);
                for ($i = 0; $i < $maxmentor; $i++) {
                    $j = $i + 1;
                    if (isset($mentors[$i])) {
                        $row['mentor'.$j] = $mentors[$i]->email;
                    }
                }
            }
        }

        if ($includeextranedcolumns) {
            // Site group.
            $sql = "SELECT g.id, g.name 
                  FROM {groups} g 
                  JOIN {groups_members} gm 
                    ON g.id = gm.groupid 
                 WHERE g.courseid = ? 
                   AND gm.userid = ?";
            if ($sitegroups = $DB->get_records_sql($sql, array(SITEID, $user->id))) {
                $sitegroups = reset($sitegroups);
                $row['site_group'] = $sitegroups->name;
            }

            // Site cohort.
            $sql = "SELECT c.id, c.name, c.idnumber 
                 FROM {cohort} c 
                 JOIN {cohort_members} cm 
                   ON c.id = cm.cohortid 
                WHERE c.contextid = ? 
                  AND cm.userid = ?";
            if ($cohorts = $DB->get_records_sql($sql, array($contextsystem->id, $user->id))) {
                $cohorts = reset($cohorts);
                $row['cohort'] = $cohorts->idnumber;
            }

            // Site and course theme.
            $sql = "SELECT d.id, d.data 
                      FROM {user_info_data} d 
                      JOIN {user_info_field} f 
                        ON d.fieldid = f.id 
                     WHERE d.userid = ? 
                       AND f.shortname = ?";
            $themefront = $DB->get_record_sql($sql, array($user->id, 'themefront'));
            $themecourse = $DB->get_record_sql($sql, array($user->id, 'themecourse'));
            $subnumber = $DB->get_record_sql($sql, array($user->id, 'subnumber'));
            if (!empty($themefront->data)) {
                if (in_array($themefront->data, $themes)) {
                    $row['themefront'] = $themefront->data;
                }
            }
            if (!empty($themecourse->data)) {
                if (in_array($themecourse->data, $themes)) {
                    $row['themefront'] = $themecourse->data;
                }
            }
            if (!empty($subnumber->data)) {
                if (in_array($subnumber->data, $themes)) {
                    $row['subnumber'] = $subnumber->data;
                }
            }
        }

        fputcsv($outputcsv, $row);
    }
    $rs->close();
    exit;
}

echo $OUTPUT->header();

$currenttab = 'importexport';
require('tabs.php');

$mform->display();
echo $OUTPUT->footer();