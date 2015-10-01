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

require_once($CFG->libdir . '/formslib.php');
$PAGE->requires->js('/blocks/fn_mentor/validation.js');

class notification_form extends moodleform {

    public function definition() {
        global $CFG, $DB, $USER, $COURSE, $OUTPUT;

        $mform =& $this->_form;

        //$mform->setDefault('email',$this->_customdata['email']);

        $g1 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g1'])) {
            if ($this->_customdata['g1']) {
                $g1 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g2 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g2'])) {
            if ($this->_customdata['g2']) {
                $g2 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g3 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g3'])) {
            if ($this->_customdata['g3']) {
                $g3 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g3_value = '';
        if (isset($this->_customdata['g3_value'])) {
            if ($this->_customdata['g3_value'] >= 0) {
                $g3_value = $this->_customdata['g3_value'];
            }
        }

        $g4 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g4'])) {
            if ($this->_customdata['g4']) {
                $g4 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g4_value = '';
        if (isset($this->_customdata['g4_value'])) {
            if ($this->_customdata['g4_value'] >= 0) {
                $g4_value = $this->_customdata['g4_value'];
            }
        }

        $g5 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g5'])) {
            if ($this->_customdata['g5']) {
                $g5 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g5_value = '';
        if (isset($this->_customdata['g5_value'])) {
            if ($this->_customdata['g5_value']  >= 0) {
                $g5_value = $this->_customdata['g5_value'];
            }
        }

        $g6 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['g6'])) {
            if ($this->_customdata['g6']) {
                $g6 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $g6_value = '';
        if (isset($this->_customdata['g6_value'])) {
            if ($this->_customdata['g6_value'] >= 0) {
                $g6_value = $this->_customdata['g6_value'];
            }
        }

        $n1 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['n1'])) {
            if ($this->_customdata['n1']) {
                $n1 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $n1_value = '';
        if (isset($this->_customdata['n1_value'])) {
            if ($this->_customdata['n1_value'] >= 0) {
                $n1_value = $this->_customdata['n1_value'];
            }
        }

        $n2 = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['n2'])) {
            if ($this->_customdata['n2']) {
                $n2 = 'block_fn_mentor_checkbox_checked';
            }
        }

        $n2_value = '';
        if (isset($this->_customdata['n2_value'])) {
            if ($this->_customdata['n2_value'] >= 0) {
                $n2_value = $this->_customdata['n2_value'];
            }
        }

        $teacher = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['teacher'])) {
            if ($this->_customdata['teacher']) {
                $teacher = 'block_fn_mentor_checkbox_checked';
            }
        }

        $student = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['student'])) {
            if ($this->_customdata['student']) {
                $student = 'block_fn_mentor_checkbox_checked';
            }
        }

        $mentor = 'block_fn_mentor_checkbox';
        if (isset($this->_customdata['mentor'])) {
            if ($this->_customdata['mentor']) {
                $mentor = 'block_fn_mentor_checkbox_checked';
            }
        }

        $period = '';
        if (isset($this->_customdata['period'])) {
            if ($this->_customdata['period'] >= 0) {
                $period = $this->_customdata['period'];
            }
        }

        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('text', 'name', get_string('rule_name', 'block_fn_mentor')); // Add elements to your form
        $mform->setType('name', PARAM_NOTAGS);


        $table = new html_table();
        $table->attributes['class'] = 'notification';
        $table->head = array('When and what to send', 'Who to send it to', 'How often to send');


        $c1 = new html_table_cell();
        $c2 = new html_table_cell();
        $c3 = new html_table_cell();

        $c1->text = (
            html_writer::tag('p', $g1('g1', 'g1', '_checkbox', 1) . ' Grade for all completed activities <G1>') .
            html_writer::tag('p', $g2('g2', 'g2', '_checkbox', 1) . ' Grade for all available activities <G2>') .
            html_writer::tag('p', $g3('g3', 'g3', '_checkbox', 1) . ' Grade below ' . block_fn_mentor_textinput('g3_value', 'g3_value', '_textinput', $g3_value) . ' % for all completed activities <G3>') .
            html_writer::tag('p', $g4('g4', 'g4', '_checkbox', 1) . ' Grade below ' . block_fn_mentor_textinput('g4_value', 'g4_value', '_textinput', $g4_value) . ' % for all available activities <G4>') .
            html_writer::tag('p', $g5('g5', 'g5', '_checkbox', 1) . ' Grade above ' . block_fn_mentor_textinput('g5_value', 'g5_value', '_textinput', $g5_value) . ' % for all completed activities <G5>') .
            html_writer::tag('p', $g6('g6', 'g6', '_checkbox', 1) . ' Grade above ' . block_fn_mentor_textinput('g6_value', 'g6_value', '_textinput', $g6_value) . ' % for all available activities <G6>') .
            html_writer::tag('p', $n1('n1', 'n1', '_checkbox', 1) . ' No login for ' . block_fn_mentor_textinput('n1_value', 'n1_value', '_textinput', $n1_value) . ' days <N1>') .
            html_writer::tag('p', $n2('n2', 'n2', '_checkbox', 1) . ' No activity for ' . block_fn_mentor_textinput('n2_value', 'n2_value', '_textinput', $n2_value) . ' days <N2>')
        );

        $c2->text = (
            html_writer::tag('p', $teacher('teacher', 'teacher', '_checkbox', 1) . ' Teacher') .
            html_writer::tag('p', $mentor('mentor', 'mentor', '_checkbox', 1) . ' Mentor') .
            html_writer::tag('p', $student('student', 'student', '_checkbox', 1) . ' Student')
        );

        $c3->text = (
            html_writer::tag('p', 'Every ' . block_fn_mentor_textinput('period', 'period', '_textinput', $period) . ' days')
        );

        $table->data[] = new html_table_row(array( $c1, $c2, $c3));

        //Apply To Header
        $c1 = new html_table_cell();
        $c1->colspan = 3;
        $c1->header = true;
        $c1->text = "Appended Message (optional)";
        $table->data[] = new html_table_row(array( $c1));

        //Apply To Header
        $c1 = new html_table_cell();
        $c1->colspan = 3;
        $c1->style = 'text-align: center;';

        $appended_message = '';
        if (isset($this->_customdata['appended_message'])) {
            $appended_message = $this->_customdata['appended_message'];
        }

        $c1->text = '<textarea name="appended_message" rows="6" cols="80">' .
                        $appended_message .
                    '</textarea>';
        $table->data[] = new html_table_row(array( $c1));


        //Apply To Header
        $c1 = new html_table_cell();
        $c1->colspan = 3;
        $c1->header = true;
        $c1->text = "Apply to";
        $table->data[] = new html_table_row(array( $c1));

        $c1 = new html_table_cell();
        $c1->colspan = 3;

        $categories = block_fn_mentor_get_course_category_tree();



        $c1->text = block_fn_mentor_category_tree_form($categories,
                                       (isset($this->_customdata['category']))?$this->_customdata['category']:'',
                                       (isset($this->_customdata['course']))?$this->_customdata['course']:'');
        $table->data[] = new html_table_row(array( $c1));

        $mform->addElement('static', 'selectors', '', html_writer::table($table));

        $this->add_action_buttons();

    }
}
