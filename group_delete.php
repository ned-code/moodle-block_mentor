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

$id = optional_param('id', 0, PARAM_INT);
$process = optional_param('process', 0, PARAM_INT);

require_login(null, false);

$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/group_delete.php', array('id' => $id));
$returnpageurl = new moodle_url('/blocks/fn_mentor/group.php');

$PAGE->set_url($thispageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('course');
$title = get_string('delete', 'block_fn_mentor');
$heading = $SITE->fullname;
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('managementorgroups', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/group_members.php'));
$PAGE->navbar->add(get_string('managegroups', 'block_fn_mentor'), $returnpageurl);
$PAGE->navbar->add($title);

$group = $DB->get_record('block_fn_mentor_group', array('id' => $id), '*', MUST_EXIST);

if ($process) {
    require_sesskey();

    // Delete group.
    $DB->delete_records('block_fn_mentor_group', array('id' => $id));

    // Delete group members.
    $DB->delete_records('block_fn_mentor_group_mem', array('groupid' => $id));

    redirect($returnpageurl);
    die;
} else {
    echo $OUTPUT->header();

    $currenttab = 'managementorgroups';
    require('tabs.php');

    echo html_writer::tag('h1', $title, array('class' => 'page-title'));
    echo $OUTPUT->confirm(
        html_writer::div(html_writer::tag('strong', get_string('name', 'block_fn_mentor') . ': ') . $group->name) .
        html_writer::empty_tag('br') .
        html_writer::empty_tag('br') .
        html_writer::div(get_string('deletegroupconfirmmsg', 'block_fn_mentor')) .
        html_writer::empty_tag('br'),
        new moodle_url('/blocks/fn_mentor/group_delete.php',
            array('id' => $id, 'process' => 1)), $returnpageurl);
    echo $OUTPUT->footer();
}