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
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/filters/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$filtertype = optional_param('filtertype', '', PARAM_TEXT);
$returnurl = optional_param('returnurl', '', PARAM_TEXT);

if (($filtertype != 'mentor') && ($filtertype != 'selectedmentor') && ($filtertype != 'mentee')) {
    print_error('filtertype');
}

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/user_filter.php', array('filtertype' => $filtertype));

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);

$name = get_string('filter', 'block_fn_mentor');
$title = get_string('filter', 'block_fn_mentor');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string($filtertype, 'block_fn_mentor'), $returnurl);
$PAGE->navbar->add($name);

$PAGE->set_title($title);
$PAGE->set_heading($heading);

$ufiltering = new fn_user_filtering(null, null, null, $filtertype, $returnurl);
echo $OUTPUT->header();
list($extrasql, $params) = $ufiltering->get_sql_filter();
$ufiltering->display_add();
$ufiltering->display_active();
$ufiltering->display_close();

$xxx =  html_writer::tag('form',
    html_writer::empty_tag('input',
        array(
            'type' => 'submit',
            'name' => 'submit',
            'value' => get_string('close', 'block_fn_mentor'),
            'class' => 'btn btn-primary'
        )
    ),
    array(
        'id' => 'potential-mentor-filter',
        'autocomplete' => 'off',
        'method' => 'post',
        'action' => $returnurl
    )
);

echo $OUTPUT->footer();
