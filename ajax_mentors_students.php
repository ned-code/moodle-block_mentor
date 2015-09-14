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
 * Strings for component 'block_fazzi_enrollment', language 'en'
 *
 * @package   block_fazzi_enrollment
 * @copyright Michael Gardener <mgardener@cissq.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once('lib.php');

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

$action   = optional_param('action', false, PARAM_TEXT);
$mentorid = optional_param('mentorid', 0, PARAM_INT);
$filter = optional_param('filter', '', PARAM_RAW);

confirm_sesskey();

require_login();
$records = NULL;
$data = array();
$select_options = array();

$idfield = array(
            'all_mentors'=>'id',
            'mentors_without_mentee'=>'id',
            'all_students'=>'id',
            'students_without_mentor'=>'id',
            'get_mentees'=>'studentid'
            );

//PERMISSION
if ( has_capability('block/fn_mentor:assignmentor', context_system::instance(), $USER->id)) {

    switch ($action) {
        case 'all_mentors':
            $records = get_all_mentors();
            break;

        case 'mentors_without_mentee':
            $records = get_mentors_without_mentee();
            break;

        case 'all_students':
            $records = get_all_students($filter);
            break;

        case 'students_without_mentor':
            $records = get_students_without_mentor($filter);
            break;

        case 'get_mentees':
            $records = get_mentees($mentorid);
            break;
    }

    if($records) {
        $i = 0;
        foreach ($records as $record) {
            $i++;
            $select_options[$i]['id'] = $record->$idfield[$action];
            $select_options[$i]['label'] =  $record->firstname.' '.$record->lastname;
        }
        $data['success'] = true;
        $data['message'] = '';
    } else {
        $data['success'] = true;
        $data['message'] = get_string('not_found', 'block_fn_mentor');
    }

} else {
    $data['success'] = false;
    $data['message'] = get_string('permission_error', 'block_fn_mentor');
}

$data['options'] = $select_options;

echo json_encode($data);