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

$id = required_param('id', PARAM_INT);
$securitykey = required_param('key', PARAM_TEXT);

$msg = $DB->get_record('block_fn_mentor_notific_msg', array('id' => $id, 'securitykey' => $securitykey), '*', MUST_EXIST);

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/notification.php');
$PAGE->set_pagelayout('popup');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);

$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php'));
$PAGE->navbar->add(get_string('notification', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/notification.php'));

echo $OUTPUT->header();
echo $msg->message;
echo $OUTPUT->footer();