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

class fn_group_form extends moodleform {
    public function definition() {

        $mform = $this->_form;
        $mform->addElement('header', '', get_string('group', 'block_fn_mentor'), '');

        $mform->addElement('text', 'name', get_string('name', 'block_fn_mentor'), array('size' => '60'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('text', 'idnumber', get_string('idnumber', 'block_fn_mentor'), array('size' => '60'));
        $mform->setType('idnumber', PARAM_TEXT);
        $mform->addRule('idnumber', null, 'required', null, 'client');


        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action');
        $mform->setType('action', PARAM_RAW);

        $this->add_action_buttons(true, get_string('submit', 'block_fn_mentor'));
    }

    function validation($data, $files) {
        global $DB;

        $errors = array();

        $idnumber = trim($data['idnumber']);

        if ($data['id'] and $group = $DB->get_record('block_fn_mentor_group', array('id'=>$data['id']))) {
            if (!empty($idnumber) && $group->idnumber != $idnumber) {
                if (block_fn_mentor_get_group_by_idnumber($idnumber)) {
                    $errors['idnumber'] = get_string('idnumbertaken');
                }
            }
        } else if (!empty($idnumber) && block_fn_mentor_get_group_by_idnumber($idnumber)) {
            $errors['idnumber'] = get_string('idnumbertaken');
        }

        return $errors;
    }
}