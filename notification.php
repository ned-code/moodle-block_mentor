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
$id       = optional_param('id', 0, PARAM_INT);
$action   = optional_param('action', 'add', PARAM_TEXT);

require_login(null, false);

// PERMISSION.
require_capability('block/fn_mentor:createnotificationrule', context_system::instance(), $USER->id);

if (($action == 'edit') && ($id)) {
    $notificationrule = $DB->get_record('block_fn_mentor_notific', array('id' => $id), '*', MUST_EXIST);
}

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/notification.php');
$PAGE->set_pagelayout('course');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);

$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/fn_mentor/js/selection.js');
$PAGE->requires->js('/blocks/fn_mentor/js/jquery.rsv-2.5.1.js');
$PAGE->requires->js('/blocks/fn_mentor/js/notification_validation.js');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php'));
$PAGE->navbar->add(get_string('notification', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/notification.php'));

$parameters = array();

if ($action == 'edit') {
    $parameters = (array) $notificationrule;
}
$parameters['action'] = $action;

$mform = new notification_form(null, $parameters, 'post', '', array('id' => 'notification_form', 'class' => 'notification_form'));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/blocks/fn_mentor/notification_rules.php'), get_string('successful', 'block_fn_mentor'));
} else if ($fromform = $mform->get_data()) {

    foreach ($_POST as $key => $value) {
        if (strpos($key, "category_") === 0) {
            if (isset($value)) {
                $fromform->category[] = $value;
            }
        } else if (strpos($key, "course_") === 0) {
            if ($value <> '0') {
                $fromform->course[] = $value;
            }
        } else {
            $fromform->$key = $value;
        }
    }

    if (isset($fromform->category)) {
        $fromform->category = implode(',', $fromform->category);
    }

    if (isset($fromform->course)) {
        $fromform->course = implode(',', $fromform->course);
    }

    $rec = new stdClass();
    $rec->timecreated = time();
    $rec->timemodified = time();
    $rec->user = $USER->id;
    $rec->studentappendedmsg = $fromform->studentappendedmsg['text'];
    $rec->mentorappendedmsg = $fromform->mentorappendedmsg['text'];
    $rec->teacherappendedmsg = $fromform->teacherappendedmsg['text'];

    $fields = array('name', 'category', 'course', 'g1', 'g2', 'g3', 'g3_value',
        'g4', 'g4_value', 'g5', 'g5_value', 'g6', 'g6_value', 'consecutive', 'consecutive_value',
        'n1', 'n1_value', 'n2', 'n2_value', 'period', 'mentoremail', 'mentorsms',
        'studentemail', 'studentsms', 'teacheremail', 'teachersms',
        'studentmsgenabled', 'mentormsgenabled','teachermsgenabled',
        'studentgreeting', 'mentorgreeting', 'teachergreeting', 'messagecontent'
    );

    foreach ($fields as $field) {
        $rec->$field = (isset($fromform->$field)) ? $fromform->$field : null;
    }

    if (($action == 'edit') && ($id)) {
        $rec->id = $id;
        $rec->timemodified = time();

        $DB->update_record('block_fn_mentor_notific', $rec);

        redirect(new moodle_url('/blocks/fn_mentor/notification_rules.php'), get_string('successful', 'block_fn_mentor'));

    } else if ($id = $DB->insert_record('block_fn_mentor_notific', $rec)) {
        redirect(new moodle_url('/blocks/fn_mentor/notification_rules.php'), get_string('successful', 'block_fn_mentor'));
    }

} else {

    $toform = new stdClass();
    $toform->action = $action;
    $toform->id = $id;

    if ($action == 'edit') {
        $toform->name = $notificationrule->name;
        $toform->studentappendedmsg['text'] = $notificationrule->studentappendedmsg;
        $toform->mentorappendedmsg['text'] = $notificationrule->mentorappendedmsg;
        $toform->teacherappendedmsg['text'] = $notificationrule->teacherappendedmsg;
    }
}
echo $OUTPUT->header();
$mform->set_data($toform);
$mform->display();
echo $OUTPUT->footer();