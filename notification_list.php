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

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/notificaton_form.php');

// Parameters.
$id       = required_param('id', PARAM_INT);

require_login(null, false);

// PERMISSION.
require_capability('block/fn_mentor:createnotificationrule', context_system::instance());

$notificationrule = $DB->get_record('block_fn_mentor_notific', array('id' => $id), '*', MUST_EXIST);

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/notification.php');
$PAGE->set_pagelayout('course');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);

$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php'));
$PAGE->navbar->add(get_string('notification_rules', 'block_fn_mentor'),
    new moodle_url('/blocks/fn_mentor/notification_rules.php')
);
$PAGE->navbar->add(get_string('listrecipients', 'block_fn_mentor'));

echo $OUTPUT->header();

echo '<div id="notification-wrapper">';
echo '<h1>' . get_string('notification_rules', 'block_fn_mentor') . '</h1>';
echo block_fn_mentor_render_notification_rule_table($notificationrule, 1);
echo '</div>';

echo html_writer::div(get_string('followingstudentsmatch', 'block_fn_mentor'), 'recipient-list-desc');

$sql = "SELECT u.* 
          FROM {user} u 
         WHERE u.id IN (SELECT DISTINCT nl.userid 
                          FROM {block_fn_mentor_notific_list} nl 
                         WHERE nl.notificationid = ?) 
      ORDER BY u.lastname ASC";

if ($students = $DB->get_records_sql($sql, array($id))) {
    foreach ($students as $student) {
        echo html_writer::div(fullname($student), 'recipient-list-student');
    }
}

echo html_writer::div(get_string('followinguserssend', 'block_fn_mentor'), 'recipient-list-desc');

$sql = "SELECT u.* 
          FROM {user} u 
         WHERE u.id IN (SELECT DISTINCT nl.receiverid 
                          FROM {block_fn_mentor_notific_list} nl 
                         WHERE nl.type = ? 
                           AND nl.notificationid = ?) 
      ORDER BY u.lastname ASC";
if ($users = $DB->get_records_sql($sql, array('student', $id))) {
    foreach ($users as $user) {
        echo html_writer::div(fullname($user), 'recipient-list-student');
    }
}
if ($users = $DB->get_records_sql($sql, array('mentor', $id))) {
    foreach ($users as $user) {
        echo html_writer::div(fullname($user), 'recipient-list-mentor');
    }
}
if ($users = $DB->get_records_sql($sql, array('teacher', $id))) {
    foreach ($users as $user) {
        echo html_writer::div(fullname($user), 'recipient-list-teacher');
    }
}

echo block_fn_mentor_footer();

echo $OUTPUT->footer();