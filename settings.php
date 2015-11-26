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
 * Strings for component 'block_fn_mentor', language 'en'
 *
 * @package   block_fn_mentor
 * @copyright Michael Gardener <mgardener@cissq.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext('block_fn_mentor/blockname', get_string('blockname', 'block_fn_mentor'), '', get_string('pluginname', 'block_fn_mentor')));

$themeconfig = theme_config::load($CFG->theme);
$layouts = array();
foreach (array_keys($themeconfig->layouts) as $layout) {
    $layouts[$layout] = $layout;
}

$settings->add(new admin_setting_configselect('block_fn_mentor/pagelayout', get_string('pagelayout', 'block_fn_mentor'), '','course', $layouts));

$settings->add(new admin_setting_configtext('block_fn_mentor/mentor', get_string('wordformentor', 'block_fn_mentor'), '', get_string('mentor', 'block_fn_mentor')));
$settings->add(new admin_setting_configtext('block_fn_mentor/mentors', get_string('wordformentors', 'block_fn_mentor'), '', get_string('mentors', 'block_fn_mentor')));
$settings->add(new admin_setting_configtext('block_fn_mentor/mentee', get_string('wordformentee', 'block_fn_mentor'), '', get_string('mentee', 'block_fn_mentor')));
$settings->add(new admin_setting_configtext('block_fn_mentor/mentees', get_string('wordformentees', 'block_fn_mentor'), '', get_string('mentees', 'block_fn_mentor')));

$role_options = array();

$roles = $DB->get_records('role');

foreach ($roles as $role) {
    $role_options[$role->id] = $role->shortname;
}


if ($mentor_system = $DB->get_record('role', array('shortname'=>'mentor'))) {
    $mentor_role_system_default = $mentor_system->id;
} else {
    $mentor_role_system_default = 0;
}

if ($mentor_user = $DB->get_record('role', array('shortname'=>'mentor_user'))) {
    $mentor_role_user_default = $mentor_user->id;
} else {
    $mentor_role_user_default = 0;
}

$settings->add(new admin_setting_configselect('block_fn_mentor/studentrole', get_string('studentrole', 'block_fn_mentor'),'', 5, $role_options));
$settings->add(new admin_setting_configselect('block_fn_mentor/teacherrole', get_string('teacherrole', 'block_fn_mentor'),'', 3, $role_options));
$settings->add(new admin_setting_configselect('block_fn_mentor/mentor_role_system', get_string('mentor_role_system', 'block_fn_mentor'),'', $mentor_role_system_default, $role_options));
$settings->add(new admin_setting_configselect('block_fn_mentor/mentor_role_user', get_string('mentor_role_user', 'block_fn_mentor'),'', $mentor_role_user_default, $role_options));
$settings->add(new admin_setting_configtext('block_fn_mentor/maxnumberofmentees', get_string('maxnumberofmentees', 'block_fn_mentor'), '', '15', PARAM_INT));
$settings->add(new admin_setting_configcheckbox('block_fn_mentor/allownotes', get_string('allownotes', 'block_fn_mentor'), '', '0'));