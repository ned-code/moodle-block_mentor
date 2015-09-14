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
 * Strings for component 'block_fn_mentor', language 'en'
 *
 * @package   block_fn_mentor
 * @copyright Michael Gardener <mgardener@cissq.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('assign_mentor_form.php');
require_once('lib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $SITE;

//PARAMETERS
$mentor_menu = optional_param('mentor_menu', 'all_mentors', PARAM_TEXT);
$student_menu = optional_param('student_menu', 'all_students', PARAM_TEXT);

require_login();

//PERMISSION
require_capability('block/fn_mentor:assignmentor', context_system::instance(), $USER->id);

switch ($mentor_menu) {
    case 'all_mentors':
        $mentors = get_all_mentors();
        break;

    case 'mentors_without_mentee':
        $mentors = get_mentors_without_mentee();
        break;
}

switch ($student_menu) {
    case 'all_students':
        $students = get_all_students();
        break;

    case 'students_without_mentor':
        $students = get_students_without_mentor();
        break;
}

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/assign_mentor.php');
$PAGE->set_pagelayout('course');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);

//$PAGE->requires->js('/blocks/fn_mentor/js/jquery.js');
$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/fn_mentor/js/selection.js');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php'));
$PAGE->navbar->add(get_string('manage_mentors', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/assign_mentor.php'));

echo $OUTPUT->header();

echo '<div id="LoadingImage" style="display: none"><img src="'.$CFG->wwwroot.'/pix/i/loading.gif" /></div>';


$form = new assign_mentor_form(null, array(
    'mentors' => $mentors,
    'students' => $students
),'post', '', array('id'=>'assignmentorform'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php?id=' . $courseid));
    // DWE we should check if we have selected users or emails around here.
} else if ($data = $form->get_data()) {
    //
}

$toform = new object();
$toform->mentor_menu = $mentor_menu;
$toform->student_menu = $student_menu;

$form->set_data($toform);

$form->display();

echo $OUTPUT->footer();