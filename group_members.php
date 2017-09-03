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

$groupid = optional_param('groupid', 0, PARAM_INT);
$potentialmentorfilter = optional_param('pmentor', 'all_mentors', PARAM_RAW);
$potentialmenteefilter = optional_param('pmentee', 'all_mentees', PARAM_RAW);
$searchmentor = optional_param('searchmentor', '', PARAM_RAW);
$searchmentee = optional_param('searchmentee', '', PARAM_RAW);

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/group_members.php', array('groupid' => $groupid));
$redirecturl = new moodle_url('/blocks/fn_mentor/group.php', array('groupid' => $groupid));

$name = get_string('pluginname', 'block_fn_mentor');
$title = get_string('managementorgroups', 'block_fn_mentor');

if ($groupid && !$group = $DB->get_record('block_fn_mentor_group', array('id' => $groupid))) {
    print_error('invalidgroupid', 'block_fn_mentor');
}
// Get plugin setting.
if (!$usementorgroups = get_config('block_fn_mentor', 'usementorgroups')) {
    print_error('mentorgroupsnotavilable', 'block_fn_mentor');
}

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
$PAGE->requires->js('/blocks/fn_mentor/js/group_members.js?v='.time());
$PAGE->requires->css('/blocks/fn_mentor/css/group_members.css?v='.time());


switch ($potentialmentorfilter) {
    case 'all_mentors':
        $mentors = block_fn_mentor_get_all_mentors('', true);
        break;
    case 'mentors_without_mentee':
        $mentors = block_fn_mentor_get_mentors_without_mentee('', true);
        break;
    case 'mentors_without_groups':
        $mentors = block_fn_mentor_get_mentors_without_groups('', true);
        break;
}
$potentialmentoroptions = array();
if (!empty($mentors)) {
    foreach ($mentors as $user) {
        $potentialmentoroptions[$user->id] = $user->firstname . " " . $user->lastname;
    }
}
$potentialmenteeoptions = array();

if ($groupid) {
    $selectedmentoroptions = block_fn_mentor_get_group_members($groupid, 'M');
    $selectedmenteeoptions = array();
} else {
    $selectedmentoroptions = array();
    $selectedmenteeoptions = array();
}

// Remove selected mentors from potential list.
if ($selectedmentoroptions) {
    foreach ($selectedmentoroptions as $index => $selectedmentoroption) {
        if (isset($potentialmentoroptions[$index])) {
            unset($potentialmentoroptions[$index]);
        }
    }
}
// Remove selected mentees from potential list.
if ($selectedmenteeoptions) {
    foreach ($selectedmenteeoptions as $index => $selectedmenteeoption) {
        if (isset($potentialmenteeoptions[$index])) {
            unset($potentialmenteeoptions[$index]);
        }
    }
}

// Filters.
$potentialmentorfilteroptions = array(
    'all_mentors' => get_string('all_mentors', 'block_fn_mentor'),
    'mentors_without_groups' => get_string('mentors_without_groups', 'block_fn_mentor'),
    'mentors_without_mentee' => get_string('mentors_without_mentee', 'block_fn_mentor')
);

$potentialmenteefilteroptions = array(
    'all_mentees' => get_string('all_mentees', 'block_fn_mentor'),
    'mentees_without_mentor' => get_string('mentees_without_mentor', 'block_fn_mentor')
);

// Assign table.
$table = new html_table();
$table->attributes['class'] = 'assigntable';

// Headers.
$potentialmentorlabel = new html_table_cell();
$potentialmentorlabel->text = html_writer::tag('strong', get_string('availablementors', 'block_fn_mentor'));

$centerlabel = new html_table_cell();
$centerlabel->text = '';

$selectedmentorlabel = new html_table_cell();
$selectedmentorlabel->text = html_writer::tag('strong', get_string('groupmentors', 'block_fn_mentor'));

$selectedmenteelabel = new html_table_cell();
$selectedmenteelabel->text = html_writer::tag('strong', get_string('groupmentees', 'block_fn_mentor'), array('id' => 'selected-mentee-label'));

$potentialmenteelabel = new html_table_cell();
$potentialmenteelabel->text = html_writer::tag('strong', get_string('availablementees', 'block_fn_mentor'), array('id' => 'potential-mentee-label'));

// Header row.
$table->data[] = new html_table_row(
    array(
        $potentialmentorlabel,
        $centerlabel,
        $selectedmentorlabel,
        $selectedmenteelabel,
        $centerlabel,
        $potentialmenteelabel
    )
);

// Show active filters.
$mentorfiltering = new fn_user_filtering(null, null, null, 'mentor', $thispageurl);
$activementorfilters = $mentorfiltering->display_active_filters();

// Potential mentor select.
$potentialmentorselect = new html_table_cell();
$potentialmentorselect->id = 'potential-mentor-cell';
$potentialmentorselect->text = html_writer::tag('form',
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'groupid', 'value' => $groupid)).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pmentee', 'value' => $potentialmenteefilter)).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'searchmentor', 'value' => $searchmentor)).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'searchmentee', 'value' => $searchmentee)).
        html_writer::select(
            $potentialmentorfilteroptions, 'pmentor', $potentialmentorfilter,
            null,
            array(
                'id' => 'potential-mentor-filter',
                'onChange' => 'this.form.submit();'
            )
        ),
        array(
            'id' => 'potential-mentor-filter-form',
            'autocomplete' => 'off',
            'method' => 'post'
        )
    ).html_writer::select($potentialmentoroptions, '', '', null,
        array(
            'id' => 'potential-mentor',
            'multiple' => 'multiple',
            'size' => 20,
            'groupid' => $groupid
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
            'class' => 'btn btn-secondary clear-group-button',
            'groupid' => $groupid
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

// Selected mentor select.
$selectedmentorselect = new html_table_cell();
$selectedmentorselect->id = 'selected-mentor-cell';
$selectedmentorselect->text = html_writer::select($selectedmentoroptions, '', '', null,
    array(
        'id' => 'selected-mentor',
        'size' => 24,
        'groupid' => $groupid
    )
). html_writer::empty_tag(
    'input',
    array(
        'value' => get_string('setgroupleader', 'block_fn_mentor'),
        'type' => 'button',
        'id' => 'btn-group-leader-toggle',
        'class' => 'btn btn-secondary',
        //'data-toggle' => 'button'
    )
);

// Selected mentee select.
$selectedmenteeselect = new html_table_cell();
$selectedmenteeselect->id = 'selected-mentee-cell';
$selectedmenteeselect->text = html_writer::select($selectedmenteeoptions, '', '', null,
    array(
        'id' => 'selected-mentee',
        'multiple' => 'multiple',
        'size' => 24,
        'groupid' => $groupid
    )
);

// Mentee action buttons.
$menteeactionbtns = new html_table_cell();
$menteeactionbtns->id = 'mentee-action-btn-cell';
$menteeactionbtns->text = (
    block_fn_mentor_assign_action_btn($OUTPUT->larrow() . ' ' . get_string('add_button', 'block_fn_mentor'), 'add-mentee-btn') .
    block_fn_mentor_assign_action_btn(get_string('remove_button', 'block_fn_mentor') . ' ' . $OUTPUT->rarrow(), 'remove-mentee-btn')
);

// Show active filters.
$menteefiltering = new fn_user_filtering(null, null, null, 'mentee', $thispageurl);
$activementeefilters = $menteefiltering->display_active_filters();

// Potential mentee select.
$potentialmenteeselect = new html_table_cell();
$potentialmenteeselect->id = 'potential-mentee-cell';
$potentialmenteeselect->text = html_writer::tag('form',
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'groupid', 'value' => $groupid)).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pmentor', 'value' => $potentialmentorfilter)).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'searchmentor', 'value' => $searchmentor)).
    html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'searchmentee', 'value' => $searchmentee)),
    array(
        'id' => 'potential-mentee-filter-form',
        'autocomplete' => 'off',
        'method' => 'post'
    )
).html_writer::select($potentialmenteeoptions, '', '', null,
    array(
        'id' => 'potential-mentee',
        'multiple' => 'multiple',
        'size' => 24,
        'groupid' => $groupid
    )
). html_writer::empty_tag('input',
    array(
        'type' => 'text',
        'name' => 'potential-mentee-search-text',
        'id' => 'potential-mentee-search-text',
        'size' => 10,
        'value' => s($searchmentee)
    )
). html_writer::empty_tag('input',
    array(
        'type' => 'button',
        'id' => 'potential-mentee-clear-btn',
        'name' => 'potential-mentee-clear-btn',
        'value' => get_string('clear'),
        'class' => 'btn btn-secondary clear-group-button',
        'groupid' => $groupid
    )
).html_writer::tag('form',
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'filtertype', 'value' => 'mentee')).
        html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $thispageurl->out())).
        html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'submit', 'value' => get_string('filter'), 'class' => 'btn btn-primary')
        ),
        array(
            'id' => 'potential-mentee-filter',
            'autocomplete' => 'off',
            'method' => 'post',
            'action' => $CFG->wwwroot.'/blocks/fn_mentor/user_filter.php'
        )
    ).$activementeefilters;

// Multi select row.
$table->data[] = new html_table_row(
    array(
        $potentialmentorselect,
        $mentoractionbtns,
        $selectedmentorselect,
        $selectedmenteeselect,
        $menteeactionbtns,
        $potentialmenteeselect
    )
);

echo $OUTPUT->header();

$currenttab = 'managementorgroups';
require('tabs.php');

$groupoptions = array();
$groupurl = array();
$menuurl = new moodle_url('/blocks/fn_mentor/group_members.php', array('groupid' => 0));

// Not in group.
$menuurl->param('groupid', 0);
$groupurl[0] = $menuurl->out(false);
$groupoptions[$menuurl->out(false)] = get_string('pleaseselectagroup', 'block_fn_mentor');

if ($groups = $DB->get_records('block_fn_mentor_group', null, 'name asc')) {
    foreach ($groups as $group) {
        $menuurl->param('groupid', $group->id);
        $groupurl[$group->id] = $menuurl->out(false);
        $groupoptions[$menuurl->out(false)] = $group->name;
    }
}
$groupmangelink = html_writer::link(
    new moodle_url('/blocks/fn_mentor/group.php'),
    get_string('managegroups', 'block_fn_mentor'),
    array('class' => 'manage-group-link')
);

$groupselectorform = html_writer::tag('form',
    html_writer::tag('strong', get_string('group', 'block_fn_mentor') . ': ') .
    html_writer::select(
        $groupoptions, 'group',
        $groupurl[$groupid], null,
        array('onChange' => 'location=document.jump1.group.options[document.jump1.group.selectedIndex].value;')
    ) .
    $groupmangelink,
    array('id' => 'group-selection-form', 'name' => 'jump1')
);

echo $groupselectorform;
echo html_writer::start_tag('div', array('class' => 'no-overflow'));
if ($groupid > 0) {
    echo html_writer::table($table);
}
echo html_writer::end_tag('div');
echo $OUTPUT->footer();