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

defined('MOODLE_INTERNAL') || die();

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/blockname',
        get_string('blockname', 'block_ned_mentor'),
        '',
        get_string('blocktitle', 'block_ned_mentor')
    )
);

$themeconfig = theme_config::load($CFG->theme);
$layouts = array();
foreach (array_keys($themeconfig->layouts) as $layout) {
    $layouts[$layout] = $layout;
}

$settings->add(
    new admin_setting_configselect(
        'block_ned_mentor/pagelayout',
        get_string('pagelayout', 'block_ned_mentor'),
        '',
        'login',
        $layouts
    )
);

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/mentor',
        get_string('wordformentor', 'block_ned_mentor'),
        '',
        get_string('mentor', 'block_ned_mentor')
    )
);

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/mentors',
        get_string('wordformentors', 'block_ned_mentor'),
        '',
        get_string('mentors', 'block_ned_mentor')
    )
);

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/mentee',
        get_string('wordformentee', 'block_ned_mentor'),
        '',
        get_string('mentee', 'block_ned_mentor')
    )
);

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/mentees',
        get_string('wordformentees', 'block_ned_mentor'),
        '',
        get_string('mentees', 'block_ned_mentor')
    )
);

$roleoptions = array();
$roles = $DB->get_records('role');
foreach ($roles as $role) {
    $roleoptions[$role->id] = $role->shortname;
}
if ($mentorsystem = $DB->get_record('role', array('shortname' => 'mentor'))) {
    $mentorrolesystemdefault = $mentorsystem->id;
} else {
    $mentorrolesystemdefault = 0;
}

if ($mentoruser = $DB->get_record('role', array('shortname' => 'mentor_user'))) {
    $mentorroleuserdefault = $mentoruser->id;
} else {
    $mentorroleuserdefault = 0;
}

$settings->add(
    new admin_setting_configselect(
        'block_ned_mentor/studentrole',
        get_string('studentrole', 'block_ned_mentor'),
        '',
        5,
        $roleoptions
    )
);

$settings->add(
    new admin_setting_configselect(
        'block_ned_mentor/teacherrole',
        get_string('teacherrole', 'block_ned_mentor'),
        '',
        3,
        $roleoptions
    )
);

$settings->add(
    new admin_setting_configselect(
        'block_ned_mentor/mentor_role_system',
        get_string('mentor_role_system', 'block_ned_mentor'),
        '',
        $mentorrolesystemdefault,
        $roleoptions
    )
);

$settings->add(
    new admin_setting_configselect(
        'block_ned_mentor/mentor_role_user',
        get_string('mentor_role_user', 'block_ned_mentor'),
        '',
        $mentorroleuserdefault,
        $roleoptions
    )
);

$settings->add(
    new admin_setting_configtext(
        'block_ned_mentor/maxnumberofmentees',
        get_string('maxnumberofmentees', 'block_ned_mentor'),
        '',
        '15',
        PARAM_INT
    )
);

$settings->add(
    new admin_setting_configcheckbox(
        'block_ned_mentor/allownotes',
        get_string('allownotes', 'block_ned_mentor'),
        '',
        '0'
    )
);

$coursecaturl = new moodle_url('/blocks/ned_mentor/coursecategories.php');
$settings->add(
    new admin_setting_configempty(
        'block_ned_mentor/coursecategories',
        get_string('coursecategoriesincluded', 'block_ned_mentor'),
        '<a class="btn" href="'.$coursecaturl->out().'">'.get_string('selectcategories', 'block_ned_mentor').'</a>'
    )
);