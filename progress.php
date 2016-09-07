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
 * Performs checkout of the strings into the translation table
 *
 * @package    tool
 * @subpackage customlang
 * @copyright  2010 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true); // progress bar is used here

require(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');

require_login(null, false);
$contextsystem = context_system::instance();

$thispageurl = new moodle_url('/blocks/fn_mentor/progress.php');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->set_cacheable(false);    // progress bar is used here

$name = get_string('companies', 'block_seat');
$title = get_string('companies', 'block_seat');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_seat'));
$PAGE->navbar->add($name);

$PAGE->set_title($title);
$PAGE->set_heading($heading);
echo $OUTPUT->header();

$progressbar = new progress_bar();
$progressbar->create();         // prints the HTML code of the progress bar

// we may need a bit of extra execution time and memory here
core_php_time_limit::raise(HOURSECS);
raise_memory_limit(MEMORY_EXTRA);
block_fn_mentor_generate_report($students, $progressbar);

echo $OUTPUT->continue_button(new moodle_url('/admin/tool/customlang/edit.php', array('lng' => $lng)), 'get');
echo $OUTPUT->footer();