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

class assign_mentor_form extends moodleform {

    public function definition() {
        global $CFG, $USER, $COURSE, $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('hidden', 'mentor_menu', '');
        $mform->setType('mentor_menu', PARAM_TEXT);

        $mform->addElement('hidden', 'student_menu', '');
        $mform->setType('student_menu', PARAM_TEXT);

        $table = new html_table();
        $table->attributes['class'] = 'assignmentor';

        $mentormenu = array(
                        'all_mentors' => get_string('all_mentors', 'block_fn_mentor'),
                        'mentors_without_mentee' => get_string('mentors_without_mentee', 'block_fn_mentor')
                        );
        $mentorroleid = get_config('block_fn_mentor', 'mentor_role_system');
        $assignroleurl = new moodle_url($CFG->wwwroot.'/admin/roles/assign.php', array('contextid' => 1, 'roleid' => $mentorroleid));
        $assignrolebutton =  block_fn_mentor_single_button($assignroleurl->out(), get_string('manage_mentor_role', 'block_fn_mentor'), 'single_button', 'assign_role');
        $infobutton =  block_fn_mentor_single_button($assignroleurl->out(), get_string('info_about_selected_people', 'block_fn_mentor'), 'single_button', 'assign_role');

        // MENTOR SELECT.
        $selectmentor = new html_table_cell();

        $mentoroptions = array();
        foreach ($this->_customdata['mentors'] as $user) {
            $mentoroptions[$user->id] = $user->firstname . " " . $user->lastname;
        }

        $selectmentor->text =  html_writer::tag('div', get_config('block_fn_mentor','mentors'), array('class' => 'object_labels')) .
                                html_writer::tag('div',
                                    html_writer::select($mentormenu, '', '', null, array('id' => 'mentor_menu', 'class'=>'ignoredirty')).
                                    html_writer::select($mentoroptions, '', '', null, array('id' => 'selectmentor', 'class'=>'ignoredirty', 'size' => 20))) .
                                    $assignrolebutton;

        $selectmentee = new html_table_cell();

        $selectmentee->text =  html_writer::tag('div', get_config('block_fn_mentor','mentees'), array('class' => 'object_labels') ) .
                                html_writer::tag('div', html_writer::select(array(), '', '', null, array('id' => 'selectmentee', 'class'=>'ignoredirty', 'multiple' => 'multiple', 'size' => 21)));


        $embed = function ($text, $id) {
            return html_writer::tag('p',
                html_writer::empty_tag('input', array(
                    'value' => $text, 'type' => 'button', 'id' => $id
                ))
            );
        };


        $centerbuttons = new html_table_cell();
        $centerbuttons->text = (
            $embed($OUTPUT->larrow() . ' ' . get_string('add_button', 'block_fn_mentor'), 'add_button') .
            $embed(get_string('remove_button', 'block_fn_mentor') . ' ' . $OUTPUT->rarrow(), 'remove_button') .
            $embed(get_string('add_all', 'block_fn_mentor'), 'add_all') .
            $embed(get_string('remove_all', 'block_fn_mentor'), 'remove_all')
        );

        $studentmenu = array(
                        'all_students' => get_string('all_students', 'block_fn_mentor'),
                        'students_without_mentor' => get_string('students_without_mentor', 'block_fn_mentor')
                        );

        // STUDENT SELECT.
        $studentoptions = array();

        foreach ($this->_customdata['students'] as $user) {
            $studentoptions[$user->id] = $user->firstname . " " . $user->lastname;
        }

        $selectstudent = new html_table_cell();
        $selectstudent->text =  html_writer::tag('div', get_string('students', 'block_fn_mentor'), array('class' => 'object_labels') ) .
                          html_writer::tag('div',
                            html_writer::select($studentmenu, '', '', null, array('id' => 'student_menu', 'class'=>'ignoredirty')).
                            html_writer::select($studentoptions, '', '', null, array('id' => 'selectstudent', 'class'=>'ignoredirty', 'multiple' => 'multiple', 'size' => 20))).
                          html_writer::tag('div',
                                       get_string('search', 'block_fn_mentor') .
                                       ' <input type="text" id="student_search" name="student_search">', array('id' => 'student_search_container'));

        $selectmentor->style = 'vertical-align: top;';
        $selectmentee->style = 'vertical-align: top;';
        $centerbuttons->style = 'vertical-align: middle;';
        $selectstudent->style = 'vertical-align: top;';

        $table->data[] = new html_table_row(array($selectmentor, $selectmentee, $centerbuttons, $selectstudent));

        $mform->addElement('static', 'selectors', '', html_writer::table($table));
    }
}
