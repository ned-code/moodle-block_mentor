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

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/importexport.php');
$redirecturl = new moodle_url('/blocks/fn_mentor/group.php');

$name = get_string('pluginname', 'block_fn_mentor');
$title = get_string('importexport', 'block_fn_mentor');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('importexport', 'block_fn_mentor'));
$PAGE->requires->css('/blocks/fn_mentor/css/assign.css?v='.time());

echo $OUTPUT->header();

$currenttab = 'importexport';
require('tabs.php');

$import = html_writer::tag('form',
    html_writer::empty_tag('input', array(
        'type' => 'submit',
        'value' => get_string('importusers', 'block_fn_mentor'),
        'class' => 'btn btn-secondary import-submit-btn',
    )),
    array(
        'class' => 'import-export-user-form',
        'action' => $CFG->wwwroot . '/blocks/fn_mentor/import.php',
        'method' => 'post',
        'autocomplete' => 'off'
    )
);
$export = html_writer::tag('form',
    html_writer::empty_tag('input', array(
        'type' => 'submit',
        'value' => get_string('exportusers', 'block_fn_mentor'),
        'class' => 'btn btn-secondary ecport-submit-btn',
    )),
    array(
        'class' => 'import-export-user-form',
        'action' => $CFG->wwwroot . '/blocks/fn_mentor/export.php',
        'method' => 'post',
        'autocomplete' => 'off'
    )
);


echo html_writer::start_tag('div', array('class' => 'no-overflow'));
$warningicon = html_writer::img($OUTPUT->pix_url('i/warning'), '');
echo html_writer::div(get_string('importexportpagedesc', 'block_fn_mentor', $warningicon), 'import-export-page-desc');
echo html_writer::end_tag('div');
echo html_writer::div($import .' '. $export, 'import-export-user-wrapper');
echo $OUTPUT->footer();