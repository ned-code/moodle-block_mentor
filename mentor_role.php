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
require_once($CFG->dirroot.'/blocks/fn_mentor/filters/lib.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/lib.php');

$searchmentor = optional_param('searchmentor', '', PARAM_RAW);

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/mentor_role.php');
$redirecturl = new moodle_url('/blocks/fn_mentor/group.php');

$name = get_string('pluginname', 'block_fn_mentor');
$title = get_string('mentorroles', 'block_fn_mentor');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add($title);

$PAGE->requires->string_for_js('pleaseselectamentor', 'block_fn_mentor');
$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/fn_mentor/js/mentor_role.js?v='.time());
$PAGE->requires->css('/blocks/fn_mentor/css/mentor_role.css?v='.time());

$potentialmentors = block_fn_mentor_get_all_users('', true);
$potentialmentoroptions = array();
if (!empty($potentialmentors)) {
    foreach ($potentialmentors as $user) {
        $potentialmentoroptions[$user->id] = $user->firstname . " " . $user->lastname;
    }
}

$mentors = block_fn_mentor_get_all_mentors('', true, 'selectedmentor');
$selectedmentoroptions = array();
if (!empty($mentors)) {
    foreach ($mentors as $user) {
        $selectedmentoroptions[$user->id] = $user->firstname . " " . $user->lastname;
    }
}

// Assign table.
$table = new html_table();
$table->attributes['class'] = 'assigntable';

// Headers.
$potentialmentorlabel = new html_table_cell();
$potentialmentorlabel->text = html_writer::tag('strong', get_string('potentialusers', 'block_fn_mentor'));

$centerlabel = new html_table_cell();
$centerlabel->text = '';

$mentorlabel = new html_table_cell();
$mentorlabel->text = html_writer::tag('strong', get_string('mentors', 'block_fn_mentor'));

// Header row.
$table->data[] = new html_table_row(
    array(
        $potentialmentorlabel,
        $centerlabel,
        $mentorlabel
    )
);

// Show active filters.
$mentorfiltering = new fn_user_filtering(null, null, null, 'mentor', $thispageurl);
$activementorfilters = $mentorfiltering->display_active_filters();

// Potential mentor select.
$potentialmentorselect = new html_table_cell();
$potentialmentorselect->id = 'potential-mentor-cell';
$potentialmentorselect->text = html_writer::tag('form',
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'searchmentor', 'value' => $searchmentor)),
        array(
            'id' => 'potential-mentor-filter-form',
            'autocomplete' => 'off',
            'method' => 'post'
        )
    ).html_writer::select($potentialmentoroptions, '', '', null,
        array(
            'id' => 'potential-mentor',
            'multiple' => 'multiple',
            'size' => 24
        )
    ).html_writer::empty_tag('input',
        array(
            'type' => 'text',
            'name' => 'potential-mentor-search-text',
            'id' => 'potential-mentor-search-text',
            'size' => 10,
            'value' => s($searchmentor)
        )
    ).html_writer::empty_tag('input',
        array(
            'type' => 'button',
            'id' => 'potential-mentor-clear-btn',
            'name' => 'potential-mentor-clear-btn',
            'value' => get_string('clear'),
            'class' => 'btn btn-secondary clear-group-button'
        )
    ).html_writer::tag('form',
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'filtertype', 'value' => 'mentor')).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $thispageurl->out())).
        html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'submit', 'value' => get_string('filter'), 'class' => 'btn btn-primary')
        ),
        array(
            'id' => 'potential-mentor-filter',
            'autocomplete' => 'off',
            'method' => 'post',
            'action' => $CFG->wwwroot.'/blocks/fn_mentor/user_filter.php'
        )
    ). $activementorfilters;

// Mentor action buttons.
$mentoractionbtns = new html_table_cell();
$mentoractionbtns->id = 'mentor-action-btn-cell';
$mentoractionbtns->text = (
    block_fn_mentor_assign_action_btn(get_string('add_button', 'block_fn_mentor') . ' ' . $OUTPUT->rarrow(), 'add-mentor-btn') .
    block_fn_mentor_assign_action_btn($OUTPUT->larrow() . ' ' . get_string('remove_button', 'block_fn_mentor'), 'remove-mentor-btn')
);

// Show active filters.
$smentorfiltering = new fn_user_filtering(null, null, null, 'selectedmentor', $thispageurl);
$selectedmentorfilters = $smentorfiltering->display_active_filters();

// Selected mentor select.
$selectedmentorselect = new html_table_cell();
$selectedmentorselect->id = 'selected-mentor-cell';
$selectedmentorselect->text = html_writer::select($selectedmentoroptions, '', '', null,
    array(
        'id' => 'selected-mentor',
        'size' => 24
    )
).html_writer::empty_tag('input',
        array(
            'type' => 'text',
            'name' => 'selected-mentor-search-text',
            'id' => 'selected-mentor-search-text',
            'size' => 10,
            'value' => s($searchmentor)
        )
    ).html_writer::empty_tag('input',
        array(
            'type' => 'button',
            'id' => 'selected-mentor-clear-btn',
            'name' => 'selected-mentor-clear-btn',
            'value' => get_string('clear'),
            'class' => 'btn btn-secondary clear-group-button'
        )
    )
    .html_writer::tag('form',
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'filtertype', 'value' => 'selectedmentor')).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $thispageurl->out())).
    html_writer::empty_tag('input',
        array('type' => 'submit', 'name' => 'submit', 'value' => get_string('filter'), 'class' => 'btn btn-primary')
    ),
    array(
        'id' => 'selected-mentor-filter',
        'autocomplete' => 'off',
        'method' => 'post',
        'action' => $CFG->wwwroot.'/blocks/fn_mentor/user_filter.php'
    )). $selectedmentorfilters;

// Multi select row.
$table->data[] = new html_table_row(
    array(
        $potentialmentorselect,
        $mentoractionbtns,
        $selectedmentorselect
    )
);

echo $OUTPUT->header();

$currenttab = 'mentorroles';
require('tabs.php');

echo html_writer::start_tag('div', array('class' => 'no-overflow'));
echo html_writer::table($table);
echo html_writer::end_tag('div');
echo $OUTPUT->footer();