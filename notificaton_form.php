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

require_once($CFG->libdir . '/formslib.php');
$PAGE->requires->js('/blocks/fn_mentor/validation.js');

class notification_form extends moodleform {

    public function definition() {

        $mform =& $this->_form;

        $g2 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g2'])) {
            if ($this->_customdata['g2']) {
                $g2 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g4 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g4'])) {
            if ($this->_customdata['g4']) {
                $g4 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g4value = '';
        if (isset($this->_customdata['g4_value'])) {
            if ($this->_customdata['g4_value'] >= 0) {
                $g4value = $this->_customdata['g4_value'];
            }
        }

        $g6 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g6'])) {
            if ($this->_customdata['g6']) {
                $g6 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g6value = '';
        if (isset($this->_customdata['g6_value'])) {
            if ($this->_customdata['g6_value'] >= 0) {
                $g6value = $this->_customdata['g6_value'];
            }
        }

        $n1 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['n1'])) {
            if ($this->_customdata['n1']) {
                $n1 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $n1value = '';
        if (isset($this->_customdata['n1_value'])) {
            if ($this->_customdata['n1_value'] >= 0) {
                $n1value = $this->_customdata['n1_value'];
            }
        }

        $n2 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['n2'])) {
            if ($this->_customdata['n2']) {
                $n2 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $n2value = '';
        if (isset($this->_customdata['n2_value'])) {
            if ($this->_customdata['n2_value'] >= 0) {
                $n2value = $this->_customdata['n2_value'];
            }
        }


        $consecutive = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['consecutive'])) {
            if ($this->_customdata['consecutive']) {
                $consecutive = 'block_fn_mentor_checkbox_checked';
            }
        }

        $consecutivevalue = '';
        if (isset($this->_customdata['consecutive_value'])) {
            if ($this->_customdata['consecutive_value'] >= 0) {
                $consecutivevalue = $this->_customdata['consecutive_value'];
            }
        }

        $teacheremail = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['teacheremail'])) {
            if ($this->_customdata['teacheremail']) {
                $teacheremail = 'block_fn_mentor_checkbox_checked';
            }
        }
        $teachersms = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['teachersms'])) {
            if ($this->_customdata['teachersms']) {
                $teachersms = 'block_fn_mentor_checkbox_checked';
            }
        }

        $studentemail = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['studentemail'])) {
            if ($this->_customdata['studentemail']) {
                $studentemail = 'block_fn_mentor_checkbox_checked';
            }
        }
        $studentsms = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['studentsms'])) {
            if ($this->_customdata['studentsms']) {
                $studentsms = 'block_fn_mentor_checkbox_checked';
            }
        }

        $mentoremail = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['mentoremail'])) {
            if ($this->_customdata['mentoremail']) {
                $mentoremail = 'block_fn_mentor_checkbox_checked';
            }
        }
        $mentorsms = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['mentorsms'])) {
            if ($this->_customdata['mentorsms']) {
                $mentorsms = 'block_fn_mentor_checkbox_checked';
            }
        }

        $whattosendall = 'block_fn_mentor_radio';
        if (isset($this->_customdata['messagecontent'])) {
            if ($this->_customdata['messagecontent'] == BLOCK_FN_MENTOR_MESSAGE_SEND_ALL) {
                $whattosendall = 'block_fn_mentor_radio_checked';
            }
        }

        $whattosendappended = 'block_fn_mentor_radio';
        if (isset($this->_customdata['messagecontent'])) {
            if ($this->_customdata['messagecontent'] == BLOCK_FN_MENTOR_MESSAGE_SEND_APPENDED) {
                $whattosendappended = 'block_fn_mentor_radio_checked';
            }
        }

        $period = '';
        if (isset($this->_customdata['period'])) {
            if ($this->_customdata['period'] >= 0) {
                $period = $this->_customdata['period'];
            }
        }

        $studentmsgenabled = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['studentmsgenabled'])) {
            if ($this->_customdata['studentmsgenabled']) {
                $studentmsgenabled = 'block_fn_mentor_checkbox_checked';
            }
        }

        $mentormsgenabled = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['mentormsgenabled'])) {
            if ($this->_customdata['mentormsgenabled']) {
                $mentormsgenabled = 'block_fn_mentor_checkbox_checked';
            }
        }

        $teachermsgenabled = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['teachermsgenabled'])) {
            if ($this->_customdata['teachermsgenabled']) {
                $teachermsgenabled = 'block_fn_mentor_checkbox_checked';
            }
        }


        $studentgreeting = '';
        if (isset($this->_customdata['studentgreeting'])) {
            $studentgreeting = $this->_customdata['studentgreeting'];
        }

        $mentorgreeting = '';
        if (isset($this->_customdata['mentorgreeting'])) {
            $mentorgreeting = $this->_customdata['mentorgreeting'];
        }

        $teachergreeting = '';
        if (isset($this->_customdata['teachergreeting'])) {
            $teachergreeting = $this->_customdata['teachergreeting'];
        }

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('text', 'name', get_string('rule_name', 'block_fn_mentor'));
        $mform->setType('name', PARAM_NOTAGS);

        $mform->addElement('html', '<table class="notification">');
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">'.get_string('whentosend', 'block_fn_mentor').'</th>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">');
        $mform->addElement('html',  html_writer::tag('p', $g2('g2', 'g2', '_checkbox', 1) . ' '.
                get_string('anycoursegrade', 'block_fn_mentor')) .
            html_writer::tag('p', $g4('g4', 'g4', '_checkbox', 1) . ' Course Grade below ' .
                block_fn_mentor_textinput('g4_value', 'g4_value', '_textinput', $g4value) . ' %'
            ) .
            html_writer::tag('p', $g6('g6', 'g6', '_checkbox', 1) . ' Course Grade above ' .
                block_fn_mentor_textinput('g6_value', 'g6_value', '_textinput', $g6value) . ' %'
            ) .
            html_writer::tag('p', $n1('n1', 'n1', '_checkbox', 1) . ' No login for ' .
                block_fn_mentor_textinput('n1_value', 'n1_value', '_textinput', $n1value) . ' days <N1>'
            ) .
            html_writer::tag('p', $n2('n2', 'n2', '_checkbox', 1) . ' No activity for ' .
                block_fn_mentor_textinput('n2_value', 'n2_value', '_textinput', $n2value) . ' days <N2>'
            ) .
            html_writer::tag('p', $consecutive('consecutive', 'consecutive2', '_checkbox', 1) . ' Logged in for ' .
                block_fn_mentor_textinput('consecutive_value', 'consecutive_value', '_textinput', $consecutivevalue) . ' consecutive days <N2>'
            )
        );
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">');
        $mform->addElement('html', get_string('whattosend', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">'.
            $whattosendall('messagecontent', '', '_radio', BLOCK_FN_MENTOR_MESSAGE_SEND_ALL) . ' '.
            get_string('sendall', 'block_fn_mentor').'<br>'.
            $whattosendappended('messagecontent', '', '_radio', BLOCK_FN_MENTOR_MESSAGE_SEND_APPENDED) . ' '.
            get_string('sendappended', 'block_fn_mentor'));
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th>');
        $mform->addElement('html', get_string('whotosend', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '<th>');
        $mform->addElement('html', get_string('howoften', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td>');
        $mform->addElement('html', html_writer::tag('table',
            html_writer::tag('tr',
                html_writer::tag('td', 'Teacher').
                html_writer::tag('td', $teacheremail('teacheremail', 'teacheremail', '_checkbox', 1) . ' Email').
                html_writer::tag('td', $teachersms('teachersms', 'teachersms', '_checkbox', 1) . ' SMS')
            ).
            html_writer::tag('tr',
                html_writer::tag('td', 'Mentor').
                html_writer::tag('td', $mentoremail('mentoremail', 'mentoremail', '_checkbox', 1) . ' Email').
                html_writer::tag('td', $mentorsms('mentorsms', 'mentorsms', '_checkbox', 1) . ' SMS')
            ).
            html_writer::tag('tr',
                html_writer::tag('td', 'Student').
                html_writer::tag('td', $studentemail('studentemail', 'studentemail', '_checkbox', 1) . ' Email').
                html_writer::tag('td', $studentsms('studentsms', 'studentsms', '_checkbox', 1) . ' SMS')
            ),
            array('class' => 'block_fn_mentor_whotosend')
        ));
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '<td>');
        $mform->addElement('html', html_writer::tag(
            'p',
            'Every ' . block_fn_mentor_textinput('period', 'period', '_textinput', $period) . ' days')
        );
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');

        $greetingoptions = array(
            'firstname' => get_string('firstname', 'block_fn_mentor'),
            'rolename' => get_string('rolename', 'block_fn_mentor'),
            'sirmadam' => get_string('sirmadam', 'block_fn_mentor'),
        );
        
        
        // Student.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">');
        $mform->addElement('html', $studentmsgenabled('studentmsgenabled', 'studentmsgenabled', '_checkbox', 1).' '.
            get_string('studentappendedmsg', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">');
        $mform->addElement('html', '<p style="text-align:left;">'.
            get_string('dear', 'block_fn_mentor'). ' '.
            html_writer::select(
                $greetingoptions, 'studentgreeting', $studentgreeting, ''
            ).'</p>');
        $mform->addElement('editor', 'studentappendedmsg', '');
        $mform->setType('studentappendedmsg', PARAM_RAW);
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');


        // Mentor.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">');
        $mform->addElement('html', $mentormsgenabled('mentormsgenabled', 'mentormsgenabled', '_checkbox', 1).' '.
            get_string('mentorappendedmsg', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">');
        $mform->addElement('html', '<p style="text-align:left;">'.
            get_string('dear', 'block_fn_mentor'). ' '.
            html_writer::select(
                $greetingoptions, 'mentorgreeting', $mentorgreeting, ''
            ).'</p>');
        $mform->addElement('editor', 'mentorappendedmsg', '');
        $mform->setType('mentorappendedmsg', PARAM_RAW);
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');


        // Teacher.
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">');
        $mform->addElement('html', $teachermsgenabled('teachermsgenabled', 'teachermsgenabled', '_checkbox', 1).' '.
            get_string('teacherappendedmsg', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">');
        $mform->addElement('html', '<p style="text-align:left;">'.
            get_string('dear', 'block_fn_mentor'). ' '.
            html_writer::select(
                $greetingoptions, 'teachergreeting', $teachergreeting, ''
            ).'</p>');
        $mform->addElement('editor', 'teacherappendedmsg', '');
        $mform->setType('teacherappendedmsg', PARAM_RAW);
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');








        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<th colspan="2">');
        $mform->addElement('html', get_string('applyto', 'block_fn_mentor'));
        $mform->addElement('html', '</th>');
        $mform->addElement('html', '</tr>');

        $categories = block_fn_mentor_get_course_category_tree();
        $mform->addElement('html', '<tr>');
        $mform->addElement('html', '<td colspan="2">');
        $mform->addElement('html', block_fn_mentor_category_tree_form($categories,
            (isset($this->_customdata['category'])) ? $this->_customdata['category'] : '',
            (isset($this->_customdata['course'])) ? $this->_customdata['course'] : ''));
        $mform->addElement('html', '</td>');
        $mform->addElement('html', '</tr>');

        $mform->addElement('html', '</table>');


        $this->add_action_buttons();

    }
}
