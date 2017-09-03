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

class coursecategory_form extends moodleform {

    public function definition() {

        $mform = $this->_form;
        //$mform->addElement('static', 'description', '', get_string('markinmanagerscoursecatsdesc', 'block_fn_mentor'));

        $table = new html_table();
        $table->attributes['class'] = 'notification';

        $c1 = new html_table_cell();

        $categories = block_fn_mentor_get_course_category_tree();

        $c1->text = block_fn_mentor_category_tree_form($categories,
                                       (isset($this->_customdata['category'])) ? $this->_customdata['category'] : '',
                                       (isset($this->_customdata['course'])) ? $this->_customdata['course'] : '');
        $table->data[] = new html_table_row(array( $c1));

        $mform->addElement('static', 'selectors', '', html_writer::table($table));

        $this->add_action_buttons();

    }
}
