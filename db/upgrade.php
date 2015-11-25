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

function xmldb_block_fn_mentor_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();


    if ($oldversion <= 2015101000) {
        $table = new xmldb_table('block_fn_mentor_notification');

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

    // Create if not exists !
    $table = new xmldb_table('block_fn_mentor_notifica_msg');
    $table->add_field("id", XMLDB_TYPE_INTEGER, '11', null, true, true);
    $table->add_field("notificationid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("type", XMLDB_TYPE_CHAR, '255');
    $table->add_field("receiverid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("userid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("courseid", XMLDB_TYPE_INTEGER, '11');
    $table->add_field("message", XMLDB_TYPE_TEXT);
    $table->add_field("securitykey", XMLDB_TYPE_CHAR, '255');
    $table->add_field("timecreated", XMLDB_TYPE_INTEGER, '11');
    $table->add_field('sent', XMLDB_TYPE_INTEGER, '2',true, null,null,'1');

    $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));
    $table->add_index('notificationid_ix', XMLDB_INDEX_NOTUNIQUE, array('notificationid'));

    if (!$dbman->table_exists($table)) {
        $dbman->create_table($table);
    }

    return true;
}