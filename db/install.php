<?php
/*
 * This file is part of Spark LMS
 *
 * Copyright (C) 2010 onwards Spark Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mustafa Bahcaci <mbahcaci@charterresources.us>
 * @package spark
 * @subpackage spark_core
 */

function xmldb_block_fn_mentor_install() {
    global $CFG, $DB, $SITE;

    $systemcontext = context_system::instance();

    // Create new roles.
    if ($ms = $DB->get_record('role', array('shortname'=>'mentor'))) {
        $mentor_system = $ms->id;
    } else {
        $mentor_system = create_role('Mentor', 'mentor', '', '');
        set_role_contextlevels($mentor_system, array(CONTEXT_SYSTEM));
    }
      if ($us = $DB->get_record('role', array('shortname'=>'mentor_user'))) {
          $mentor_user = $us->id;
    } else {
          $mentor_user = create_role('Mentor user', 'mentor_user', '', '');
          set_role_contextlevels($mentor_user, array(CONTEXT_USER));
    }


    // Extra capability tweaks for new roles without archetypes
    if ($user_system = $DB->get_record('role', array('shortname'=>'user'))) {
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $user_system->id, $systemcontext->id, true);
    }

    assign_capability('moodle/notes:view', CAP_ALLOW, $mentor_system, $systemcontext->id, true);
    assign_capability('block/fn_mentor:viewcoursenotes', CAP_ALLOW, $mentor_system, $systemcontext->id, true);

    assign_capability('mod/assign:grade', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('mod/assign:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('mod/assignment:grade', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('mod/assignment:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/user:readuserblogs', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/user:readuserposts', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/user:viewuseractivitiesreport', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/user:viewdetails', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('gradereport/user:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/grade:viewall', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('gradereport/grader:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/user:viewalldetails', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/grade:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('report/outline:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('report/progress:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/notes:view', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('moodle/notes:manage', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    assign_capability('block/fn_mentor:viewcoursenotese', CAP_ALLOW, $mentor_user, $systemcontext->id, true);
    $systemcontext->mark_dirty();

    return true;
}