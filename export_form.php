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

require_once($CFG->libdir.'/formslib.php');

class export_form extends moodleform {
    public function definition() {

        $mform = $this->_form;
        $mform->addElement('header', '', get_string('export', 'block_fn_mentor'), '');

        $mform->addElement('advcheckbox', 'mentorsandmanagers', '', get_string('export_mentorsandmanagers', 'block_fn_mentor'), array('disabled' => 'disabled'));
        $mform->setDefault('mentorsandmanagers', 1);
        $mform->addElement('advcheckbox', 'includeenrolledusers', '', get_string('export_includeenrolledusers', 'block_fn_mentor'));
        $mform->addElement('advcheckbox', 'includeallusers', '', get_string('export_includeallusers', 'block_fn_mentor'));



        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);

        $this->add_action_buttons(true, get_string('submit', 'block_fn_mentor'));
    }

    public function validation($data, $files) {
        $errors = array();
        return $errors;
    }
}