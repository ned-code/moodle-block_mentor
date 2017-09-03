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
require_once($CFG->dirroot.'/blocks/fn_mentor/group_form.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/lib.php');

$id     = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'edit', PARAM_RAW);

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$PAGE->https_required();

$thispageurl = new moodle_url('/blocks/fn_mentor/group_edit.php', array('id' => $id, 'action' => $action));
$returnpageurl = new moodle_url('/blocks/fn_mentor/group.php');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->verify_https_required();

$name = get_string('addedit', 'block_fn_mentor');
$title = get_string('addedit', 'block_fn_mentor');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('managementorgroups', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/group_members.php'));
$PAGE->navbar->add(get_string('managegroups', 'block_fn_mentor'), $returnpageurl);
$PAGE->navbar->add($name);

$PAGE->set_title($title);
$PAGE->set_heading($heading);

$mform = new fn_group_form(null, array());

if ($action == 'edit') {
    $toform = $DB->get_record('block_fn_mentor_group', array('id' => $id), '*', MUST_EXIST);
}

if ($mform->is_cancelled()) {
    redirect($returnpageurl);
} else if ($fromform = $mform->get_data()) {
    $rec = new stdClass();
    $rec->name = $fromform->name;
    $rec->idnumber = $fromform->idnumber;

    if ($action == 'add') {
        $rec->timecreated = time();
        $rec->id = $DB->insert_record('block_fn_mentor_group', $rec);
        redirect($returnpageurl);
    } else {
        $rec->id = $id;
        $rec->timemodified = time();
        $DB->update_record('block_fn_mentor_group', $rec);
        redirect($returnpageurl);
    }
    exit;
}

echo $OUTPUT->header();

$currenttab = 'managementorgroups';
require('tabs.php');

if (($action == 'edit') && ($id)) {
    $toform->action = $action;
    $mform->set_data($toform);
} else {
    $toform = new stdClass();
    $toform->action = $action;
    $mform->set_data($toform);
}

$mform->display();

echo $OUTPUT->footer();