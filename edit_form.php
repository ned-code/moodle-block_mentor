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
defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');


class block_fn_mentor_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        $mform->addElement('static', 'blockinfo', get_string('blockinfo', 'block_fn_mentor'),
            '<a target="_blank" href="http://ned.ca/mentor-manager">http://ned.ca/mentor-manager</a>');

        $yesno = array(0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'config_show_mentor_sort', get_string('show_mentor_sort', 'block_fn_mentor'), $yesno);
        $mform->setDefault('config_show_mentor_sort', 0);

        $mform->addElement('select', 'config_show_mentee_without_course',
            get_string('mentee_without_course', 'block_fn_mentor'), $yesno
        );
        $mform->setDefault('config_show_mentee_without_course', 0);
    }
}