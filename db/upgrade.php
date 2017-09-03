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

function xmldb_block_fn_mentor_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Create if not exists !
    $table = new xmldb_table('block_fn_mentor_notific_msg');
    $table->add_field("id", XMLDB_TYPE_INTEGER, '11', null, true, true);
    $table->add_field("notificationid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("type", XMLDB_TYPE_CHAR, '255');
    $table->add_field("receiverid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("userid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("courseid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("message", XMLDB_TYPE_TEXT);
    $table->add_field("securitykey", XMLDB_TYPE_CHAR, '255');
    $table->add_field("timecreated", XMLDB_TYPE_INTEGER, '11');
    $table->add_field('sent', XMLDB_TYPE_INTEGER, '2', true, null, null, '1');

    $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_index('notificationid_ix', XMLDB_INDEX_NOTUNIQUE, array('notificationid'));

    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    if ($oldversion <= 2015101000) {
        $table = new xmldb_table('block_fn_mentor_notific');

        $field = new xmldb_field('mentoremail');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('mentorsms');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teacheremail');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teachersms');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('studentemail');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('studentsms');
        if (!$dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_INTEGER, '2', null, null, null, 0, 'appended_message');
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion <= 2016050800) {
        $table = new xmldb_table('block_fn_mentor_report_data');
        $table->add_field("id", XMLDB_TYPE_INTEGER, '11', null, true, true);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, '11');
        $table->add_field("courseid", XMLDB_TYPE_INTEGER, '11');
        $table->add_field("groups", XMLDB_TYPE_TEXT);
        $table->add_field("mentors", XMLDB_TYPE_TEXT);
        $table->add_field("completionrate", XMLDB_TYPE_NUMBER, '10,2');
        $table->add_field("passinggrade", XMLDB_TYPE_NUMBER, '10,2');
        $table->add_field("timemodified", XMLDB_TYPE_INTEGER, '11', null, null, null, '0');
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '2', true, null, null, '0');

        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('cour_ix', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('block_fn_mentor_report_pvt');
        $table->add_field("id", XMLDB_TYPE_INTEGER, '11', null, true, true);
        $table->add_field("userid", XMLDB_TYPE_INTEGER, '11');
        $table->add_field("groups", XMLDB_TYPE_TEXT);
        $table->add_field("mentors", XMLDB_TYPE_TEXT);

        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_index('use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion <= 2016051700) {
        // Define table block_fn_mentor_notific to be created.
        $table = new xmldb_table('block_fn_mentor_notific');

        // Adding fields to table block_fn_mentor_notific.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('category', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('course', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('g1', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g2', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g3', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g3_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g4', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g4_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g5', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g5_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g6', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g6_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('n1', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('n1_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('n2', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('n2_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('period', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('mentoremail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('mentorsms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('studentemail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('studentsms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('teacheremail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('teachersms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('appended_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('crontime', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('studentmsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('mentormsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('teachermsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('studentappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('mentorappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('teacherappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('studentgreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('mentorgreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('teachergreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        // Adding keys to table block_fn_mentor_notific.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_fn_mentor_notific.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('block_fn_mentor_report_pvt');
        $field = new xmldb_field('courses', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion <= 2016051701) {
        $table = new xmldb_table('block_fn_mentor_notific');
        $field = new xmldb_field('studentmsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('mentormsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teachermsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('studentappendedmsg', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('mentorappendedmsg', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teacherappendedmsg', XMLDB_TYPE_TEXT);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('studentgreeting', XMLDB_TYPE_CHAR, '20', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('mentorgreeting', XMLDB_TYPE_CHAR, '20', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teachergreeting', XMLDB_TYPE_CHAR, '20', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2017040500) {

        // Define table block_fn_mentor_notific_list to be created.
        $table = new xmldb_table('block_fn_mentor_notific_list');

        // Adding fields to table block_fn_mentor_notific_list.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notificationid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('receiverid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('securitykey', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('sent', XMLDB_TYPE_INTEGER, '2', null, null, null, '1');

        // Adding keys to table block_fn_mentor_notific_list.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_fn_mentor_notific_list.
        $table->add_index('mdl_blocfnmentnotimsg_not_ix', XMLDB_INDEX_NOTUNIQUE, array('notificationid'));

        // Conditionally launch create table for block_fn_mentor_notific_list.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Fn_mentor savepoint reached.
        upgrade_block_savepoint(true, 2017040500, 'fn_mentor');
    }

    if ($oldversion < 2017060600) {

        $table = new xmldb_table('block_fn_mentor_notific');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('category', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('course', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('user', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('g1', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g2', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g3', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g3_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g4', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g4_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g5', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g5_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('g6', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('g6_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('n1', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('n1_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('n2', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('n2_value', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('period', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('mentoremail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('mentorsms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('studentemail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('studentsms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('teacheremail', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('teachersms', XMLDB_TYPE_INTEGER, '2', null, null, null, null);
        $table->add_field('appended_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, null, null, null);
        $table->add_field('crontime', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('studentmsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('mentormsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('teachermsgenabled', XMLDB_TYPE_INTEGER, '4', null, null, null, '0');
        $table->add_field('studentappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('mentorappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('teacherappendedmsg', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('studentgreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('mentorgreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('teachergreeting', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        $table = new xmldb_table('block_fn_mentor_group_mem');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('role', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('mdl_cohomemb_cohuse_uix', XMLDB_KEY_UNIQUE, array('groupid', 'userid'));
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('mdl_cohomemb_coh_ix', XMLDB_INDEX_NOTUNIQUE, array('groupid'));
        $table->add_index('mdl_cohomemb_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        upgrade_block_savepoint(true, 2017060600, 'fn_mentor');
    }

    if ($oldversion < 2017061301) {

        // Define table block_fn_mentor_group to be created.
        $table = new xmldb_table('block_fn_mentor_group');

        // Adding fields to table block_fn_mentor_group.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '254', null, null, null, '');
        $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, null, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, null, null, '0');

        // Adding keys to table block_fn_mentor_group.
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for block_fn_mentor_group.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }


        // Define table block_fn_mentor_group_mem to be created.
        $table = new xmldb_table('block_fn_mentor_group_mem');

        // Adding fields to table block_fn_mentor_group_mem.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('role', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('timeadded', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table block_fn_mentor_group_mem.
        $table->add_key('mdl_cohomemb_cohuse_uix', XMLDB_KEY_UNIQUE, array('groupid', 'userid'));
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table block_fn_mentor_group_mem.
        $table->add_index('mdl_cohomemb_coh_ix', XMLDB_INDEX_NOTUNIQUE, array('groupid'));
        $table->add_index('mdl_cohomemb_use_ix', XMLDB_INDEX_NOTUNIQUE, array('userid'));

        // Conditionally launch create table for block_fn_mentor_group_mem.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Fn_mentor savepoint reached.
        upgrade_block_savepoint(true, 2017061301, 'fn_mentor');
    }

    if ($oldversion < 2017071100) {

        $table = new xmldb_table('block_fn_mentor_group_mem');
        $field = new xmldb_field('teamleader', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'role');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2017071100, 'fn_mentor');
    }

    if ($oldversion < 2017072400) {
        $table = new xmldb_table('block_fn_mentor_notific');
        $field = new xmldb_field('messagecontent', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'teachergreeting');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2017072400, 'fn_mentor');
    }

    if ($oldversion < 2017072502) {

        $table = new xmldb_table('block_fn_mentor_notific');
        $field = new xmldb_field('consecutive', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'n2_value');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('consecutive_value', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'consecutive');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2017072502, 'fn_mentor');
    }

    return true;
}