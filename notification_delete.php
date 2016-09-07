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

$id = optional_param('id', 0, PARAM_INT);

require_login(null, false);

require_capability('block/fn_mentor:createnotificationrule', context_system::instance(), $USER->id);

if ($record = $DB->get_record('block_fn_mentor_notific', array('id' => $id))) {

    $DB->delete_records('block_fn_mentor_notific',  array('id' => $id));

    redirect(new moodle_url('/blocks/fn_mentor/notification_rules.php'), get_string('successful', 'block_fn_mentor'));
} else {
    echo $OUTPUT->header();
    echo 'Not found';
    echo $OUTPUT->footer();
}
