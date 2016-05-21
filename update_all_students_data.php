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
 * @package    block_ned_mentor
 * @copyright  Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true); // progress bar is used here

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/ned_mentor/lib.php');

$process = optional_param('process', 0, PARAM_INT);

require_login(null, false);

// Permission.
$isadmin   = has_capability('block/ned_mentor:manageall', context_system::instance());
$ismentor  = block_ned_mentor_has_system_role($USER->id, get_config('block_ned_mentor', 'mentor_role_system'));
$isteacher = block_ned_mentor_isteacherinanycourse($USER->id);
$isstudent = block_ned_mentor_isstudentinanycourse($USER->id);

if (!$isteacher && !$isadmin && !$ismentor) {
    print_error('invalidpermission', 'block_ned_mentor');
}

$PAGE->set_url('/blocks/ned_mentor/update_all_students_data.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$PAGE->set_cacheable(false);    // progress bar is used here
$PAGE->requires->css('/blocks/ned_mentor/css/styles.css');

$title = get_string('updatedata', 'block_ned_mentor');
$heading = $SITE->fullname;

$PAGE->set_title($heading);
$PAGE->set_heading($heading);


$PAGE->navbar->add(get_string('pluginname', 'block_ned_mentor'));
$PAGE->navbar->add(get_string('allstudents', 'block_ned_mentor'),
    new moodle_url('/blocks/ned_mentor/all_students.php')
);
$PAGE->navbar->add($title);

if ($process) {
    echo $OUTPUT->header();
    require_sesskey();
    $progressbar = new progress_bar();
    $progressbar->create();
    core_php_time_limit::raise(HOURSECS);
    raise_memory_limit(MEMORY_EXTRA);
    block_ned_mentor_generate_report($progressbar);
    echo $OUTPUT->continue_button(new moodle_url('/blocks/ned_mentor/all_students.php'), 'get');
    echo $OUTPUT->footer();
    die;
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('h1', $title, array('class' => 'page-title'));
    echo $OUTPUT->confirm(
        html_writer::div(get_string('allstudentdataupdate', 'block_ned_mentor'), 'alert alert-block alert-danger'),
        new moodle_url('/blocks/ned_mentor/update_all_students_data.php', array('process' => 1)),
        new moodle_url('/blocks/ned_mentor/all_students.php')
    );
    echo $OUTPUT->footer();
}