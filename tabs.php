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

if (!defined('MOODLE_INTERNAL')) {
    die();
}

$id = optional_param('id', 0, PARAM_INT);

if (!isset($currenttab)) {
    $currenttab = 'managementorgroups';
}

$context = context_system::instance();

$usementorgroups = get_config('block_fn_mentor', 'usementorgroups');

$toprow = array();

$toprow[] = new tabobject(
    'managementormentee',
    new moodle_url('/blocks/fn_mentor/assign.php'),
    get_string('managementormenteerelations', 'block_fn_mentor')
);

if ($usementorgroups) {
    $toprow[] = new tabobject(
        'managementorgroups',
        new moodle_url('/blocks/fn_mentor/group_members.php', array('id' => $id)),
        get_string('managementorgroups', 'block_fn_mentor')
    );
}

$toprow[] = new tabobject(
    'mentorroles',
    new moodle_url('/blocks/fn_mentor/mentor_role.php'),
    get_string('mentorroles', 'block_fn_mentor')
);

$toprow[] = new tabobject(
    'activecategories',
    new moodle_url('/blocks/fn_mentor/coursecategories.php'),
    get_string('activecategories', 'block_fn_mentor')
);

$import = '';
if (is_siteadmin()) {
    $import = html_writer::img($OUTPUT->pix_url('i/import'), '', array('class' => 'fn_mentor-tab-icons')) . ' ' .
        html_writer::link(
            new moodle_url('/blocks/fn_mentor/importexport.php'),
            get_string('importexport', 'block_fn_mentor')
        );
}
$config = '';
if (has_capability('moodle/site:config', context_system::instance())) {
    $config = html_writer::img($OUTPUT->pix_url('i/settings'), '', array('class' => 'fn_mentor-tab-icons')) . ' ' .
        html_writer::link(
            new moodle_url('/admin/settings.php', array('section' => 'blocksettingfn_mentor')),
            get_string('config', 'block_fn_mentor'), array('target' => '_blank')
        );
}

$info = html_writer::img($OUTPUT->pix_url('i/info'), '', array('class' => 'fn_mentor-tab-icons')).' '.
    html_writer::link(
        'http://ned.ca/mentor-manager',
        get_string('help', 'block_fn_mentor'), array('target' => '_blank')
    );

echo html_writer::div(
    $OUTPUT->tabtree($toprow, $currenttab).
    html_writer::div($import.' '.$config.' '.$info, 'block_fn_mentor_tabmeenuicons'),
    'block_fn_mentor_tabmenu'
);
