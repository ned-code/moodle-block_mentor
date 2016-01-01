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

require_once ($CFG->dirroot.'/mod/assignment/lib.php');

function block_fn_mentor_get_all_students($filter = ''){
    global $DB, $CFG;

    $studentrole = get_config('block_fn_mentor', 'studentrole');

    $params = array(0, 0, $studentrole);
    $wherecondions = '';

    if ($filter) {
        $wherecondions .= " AND (u.firstname LIKE '%".$filter."%'
                            OR u.lastname LIKE '%".$filter."%'
                            OR u.email LIKE '%".$filter."%')";
        $params[] = "%".$filter."%";
        $params[] = "%".$filter."%";
        $params[] = "%".$filter."%";
    }

    $sql = "SELECT DISTINCT u.id,
                            u.firstname,
                            u.lastname
                       FROM {role_assignments} ra
                 INNER JOIN {user} u
                         ON ra.userid = u.id
                      WHERE u.deleted = ?
                        AND u.suspended = ?
                        AND ra.roleid = ?
                        $wherecondions
                   ORDER BY u.lastname ASC";

    $everyone = $DB->get_records_sql($sql, $params);

    return $everyone;
}

function block_fn_mentor_get_students_without_mentor($filter = ''){
    global $DB, $CFG;

    if (! $mentor_roleid = get_config('block_fn_mentor', 'mentor_role_user')){
        return false;
    }

    $studentrole = get_config('block_fn_mentor', 'studentrole');
    $params = array(0, 0, $studentrole);

    $wherecondions = '';

    if ($filter) {
        $wherecondions .= " AND (u.firstname LIKE '%".$filter."%'
                            OR u.lastname LIKE '%".$filter."%'
                            OR u.email LIKE '%".$filter."%')";
        $params[] = "%".$filter."%";
        $params[] = "%".$filter."%";
        $params[] = "%".$filter."%";
    }

    $sql = "SELECT DISTINCT u.id,
                            u.firstname,
                            u.lastname
                       FROM {role_assignments} ra
                 INNER JOIN {user} u
                         ON ra.userid = u.id
                      WHERE u.deleted = ?
                        AND u.suspended = ?
                        AND ra.roleid = ?
                        $wherecondions
                   ORDER BY u.lastname ASC";

    $everyone = $DB->get_records_sql($sql, $params);

    $sql_mentor = "SELECT ra.id,
                          ra.userid AS mentorid,
                          ctx.instanceid AS studentid
                     FROM {context} ctx
               INNER JOIN {role_assignments} ra
                       ON ctx.id = ra.contextid
                    WHERE ctx.contextlevel = ?
                      AND ra.roleid = ?";

    $stu_with_mentor = array();

    if ($students_with_mentor = $DB->get_records_sql($sql_mentor, array(CONTEXT_USER, $mentor_roleid))) {
        foreach ($students_with_mentor as $key => $value) {
            $stu_with_mentor[$value->studentid] = $value->studentid;
        }
    }

    $students_without_mentor = array_diff_key($everyone, $stu_with_mentor);

    return $students_without_mentor;
}

function block_fn_mentor_get_mentors_without_mentee(){
    global $DB, $CFG;

    if (! $mentor_roleid = get_config('block_fn_mentor', 'mentor_role_system')){
        return false;
    }
    $sql = "SELECT DISTINCT u.id,
                            u.firstname,
                            u.lastname
                       FROM {role_assignments} ra
                 INNER JOIN {user} u
                         ON ra.userid = u.id
                      WHERE u.deleted = ?
                        AND u.suspended = ?
                        AND ra.roleid = ?
                   ORDER BY u.lastname ASC";

    $everyone = $DB->get_records_sql($sql, array(0, 0, $mentor_roleid));

     if (! $mentor_roleid_user = get_config('block_fn_mentor', 'mentor_role_user')){
        return false;
    }

    $sql_mentor = "SELECT ra.id,
                          ra.userid AS mentorid,
                          ctx.instanceid AS studentid
                     FROM {context} ctx
               INNER JOIN {role_assignments} ra
                       ON ctx.id = ra.contextid
                    WHERE ctx.contextlevel = ?
                      AND ra.roleid = ?";

    $men_with_mentee = array();

    if ($mentors_with_mentee = $DB->get_records_sql($sql_mentor, array(CONTEXT_USER, $mentor_roleid_user))) {
        foreach ($mentors_with_mentee as $key => $value) {
            $men_with_mentee[$value->mentorid] = $value->mentorid;
        }
    }

    $mentors_without_mentee = array_diff_key($everyone, $men_with_mentee);

    return $mentors_without_mentee;
}

function block_fn_mentor_get_all_mentees($studentids=''){
    global $DB, $CFG;

    if (! $mentor_roleid = get_config('block_fn_mentor', 'mentor_role_user')){
        return false;
    }

    $sql_mentor = "SELECT ra.id,
                          ra.userid AS mentorid,
                          ctx.instanceid AS studentid,
                          u.firstname,
                          u.lastname
                     FROM {context} ctx
               INNER JOIN {role_assignments} ra
                       ON ctx.id = ra.contextid
               INNER JOIN {user} u
                       ON ctx.instanceid = u.id
                    WHERE ctx.contextlevel = ?
                      AND ra.roleid = ?
                 ORDER BY u.lastname ASC";

    $stu_with_mentor = array();

    if ($students_with_mentor = $DB->get_records_sql($sql_mentor, array(CONTEXT_USER, $mentor_roleid))) {
        foreach ($students_with_mentor as $key => $value) {
            $stu_with_mentor[$value->studentid] = $value;
        }
    }

    if ($studentids) {
        $stu_with_mentor = array_intersect_key($stu_with_mentor, $studentids);
    }
    return $stu_with_mentor;
}

function block_fn_mentor_get_all_mentors(){
    global $DB, $CFG;

    if (! $mentor_roleid = get_config('block_fn_mentor', 'mentor_role_system')){
        return false;
    }
    $sql = "SELECT DISTINCT u.id,
                            u.firstname,
                            u.lastname
                       FROM {role_assignments} ra
                 INNER JOIN {user} u
                         ON ra.userid = u.id
                      WHERE u.deleted = ?
                        AND u.suspended = ?
                        AND ra.roleid = ?
                   ORDER BY u.lastname ASC";

    $everyone = $DB->get_records_sql($sql, array(0, 0, $mentor_roleid));

    return $everyone;
}

function block_fn_mentor_get_mentees($mentorid, $courseid=0, $studentids = ''){
    global $DB, $CFG;

    if (! $mentor_roleid = get_config('block_fn_mentor', 'mentor_role_user')){
        return false;
    }
    $studentrole = get_config('block_fn_mentor', 'studentrole');

    $course_students = array();

    if ($courseid) {
        $sqlCourseStudents = "SELECT ra.userid AS studentid,
                                     u.firstname,
                                     u.lastname
                                FROM {context} ctx
                          INNER JOIN {role_assignments} ra
                                  ON ctx.id = ra.contextid
                          INNER JOIN {user} u
                                  ON ra.userid = u.id
                               WHERE ctx.contextlevel = ?
                                 AND ra.roleid = ?
                                 AND ctx.instanceid = ?";
        $course_students = $DB->get_records_sql($sqlCourseStudents, array(50, $studentrole, $courseid));
    }

    $sql = "SELECT ctx.instanceid AS studentid,
                   u.firstname,
                   u.lastname
              FROM {role_assignments} ra
        INNER JOIN {context} ctx
                ON ra.contextid = ctx.id
        INNER JOIN {user} u
                ON ctx.instanceid = u.id
             WHERE ra.roleid = ?
               AND ra.userid = ?
               AND ctx.contextlevel = ?
          ORDER BY u.lastname ASC";

    $mentees =  $DB->get_records_sql($sql, array($mentor_roleid, $mentorid, CONTEXT_USER));

    if ($course_students) {
        $mentees = array_intersect_key($mentees, $course_students);
    }

    if ($studentids) {
        $mentees = array_intersect_key($mentees, $studentids);
    }

    return $mentees;

}

function block_fn_mentor_get_mentors($menteeid){
    global $DB, $CFG;

    if (! $mentorroleid = get_config('block_fn_mentor', 'mentor_role_user')){
        return false;
    }

    if (! $mentorsysroleid = get_config('block_fn_mentor', 'mentor_role_system')){
        return false;
    }

    $sql = "SELECT ra.id,
                   ra.userid AS mentorid,
                   u.firstname,
                   u.lastname,
                   u.lastaccess
              FROM {context} ctx
        INNER JOIN {role_assignments} ra
                ON ctx.id = ra.contextid
        INNER JOIN {user} u
                ON ra.userid = u.id
             WHERE ctx.contextlevel = ?
               AND ra.roleid = ?
               AND ctx.instanceid = ?
               AND ra.userid IN (SELECT ra2.userid
                                   FROM {role_assignments} ra2
                             INNER JOIN {context} ctx2 ON ra2.contextid = ctx2.id
                                  WHERE ra2.roleid = ?
                                    AND ctx2.contextlevel = ?)
          ORDER BY u.lastname ASC";

    return $DB->get_records_sql($sql, array(CONTEXT_USER, $mentorroleid, $menteeid, $mentorsysroleid,  CONTEXT_SYSTEM));

}

function block_fn_mentor_isteacherinanycourse($userid=NULL) {
    global $DB, $CFG, $USER;

    if (! $userid) {
        $userid = $USER->id;
    }
    /// If this user is assigned as an editing teacher anywhere then return true
    if ($roles = get_roles_with_capability('moodle/course:update', CAP_ALLOW)) {
        foreach ($roles as $role) {
            if ($DB->record_exists('role_assignments', array('roleid' =>$role->id, 'userid' => $userid))) {
                return true;
            }
        }
    }
    return false;
}

function block_fn_mentor_isstudentinanycourse($userid=NULL) {
    global $DB, $CFG, $USER;

    if (! $userid) {
        $userid = $USER->id;
    }
    $studentrole = get_config('block_fn_mentor', 'studentrole');
    if ($DB->record_exists_sql("SELECT 1
                                  FROM {context} ctx
                            INNER JOIN {role_assignments} ra
                                    ON ctx.id = ra.contextid
                                 WHERE ctx.contextlevel = ?
                                   AND ra.roleid = ?
                                   AND ra.userid = ?", array(50, $studentrole, $userid))) {
        return true;
    }
    return false;
}

function block_fn_mentor_has_system_role($userid, $roleid) {
    global $DB;

    $sql = "SELECT 1
              FROM {role_assignments} ra
        INNER JOIN {context} ctx ON ra.contextid = ctx.id
             WHERE ra.roleid = :rolename
               AND ctx.contextlevel = :contextlevel
               AND ra.userid = :userid";

    return $DB->record_exists_sql($sql, array('rolename'=>$roleid, 'contextlevel'=>CONTEXT_SYSTEM, 'userid'=>$userid));

}

//Mentees by Mentor
function block_fn_mentor_get_mentees_by_mentor($courseid=0, $filter='') {
    global $DB, $CFG, $USER;

    $data = array();
    $all_course_students = array();

    if ($filter == 'teacher') {
        if ($courses = block_fn_mentor_get_teacher_courses()) {
            $courseids = implode(",", array_keys($courses));
            $all_course_students = block_fn_mentor_get_enrolled_course_users ($courseids);
        }
    }

    if ($filter == 'mentor') {
        if ($mentees = block_fn_mentor_get_mentees($USER->id, $courseid, $all_course_students)){
            $data[$USER->id]['mentor'] = $USER;
            $data[$USER->id]['mentee'] = $mentees;
        }
        return $data;
    }

    if ($mentors = get_role_users(get_config('block_fn_mentor', 'mentor_role_system'),context_system::instance(), false, 'u.id, u.firstname, u.lastname', 'u.lastname')) {
        foreach ($mentors as $mentor) {
            if ($mentees = block_fn_mentor_get_mentees($mentor->id, $courseid, $all_course_students)){
                $data[$mentor->id]['mentor'] = $mentor;
                $data[$mentor->id]['mentee'] = $mentees;
            }
        }
    }

    if ($filter == 'teacher') {
        if ($mentees = block_fn_mentor_get_mentees($USER->id, $courseid, array())){
            $data[$USER->id]['mentor'] = $USER;
            $data[$USER->id]['mentee'] = $mentees;
        }
    }

    return $data;
}

function block_fn_mentor_render_mentees_by_mentor($data) {
    global $DB, $CFG;

    $coursefilter = optional_param('coursefilter', 0, PARAM_INT);

    $html = '';
    foreach ($data as $mentor) {
        $html .= '<div class="mentor"><strong><img class="mentor-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png"> <a class="mentor-profile" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor['mentor']->id.'"
        onclick="window.open(\''.$CFG->wwwroot.'/user/profile.php?id='.$mentor['mentor']->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;">'.$mentor['mentor']->firstname . ' ' . $mentor['mentor']->lastname.'</a>
        </strong></div>';
        foreach ($mentor['mentee'] as $mentee) {
            $grade_summary = block_fn_mentor_grade_summary($mentee->studentid, $coursefilter);
            if (($grade_summary->attempted >= 50) && ($grade_summary->all >= 50)) {
                $mentee_icon = 'mentee_green.png';
            } elseif (($grade_summary->attempted >= 50) && ($grade_summary->all < 50)) {
                $mentee_icon = 'mentee_red_green.png';
            } elseif (($grade_summary->attempted < 50) && ($grade_summary->all >= 50)) {
                $mentee_icon = 'mentee_red_green.png';
            } elseif (($grade_summary->attempted < 50) && ($grade_summary->all < 50)) {
                $mentee_icon = 'mentee_red.png';
            }
            $html .= '<div class="mentee"><img class="mentee-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/'.$mentee_icon.'"><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$mentee->studentid.'" >' .$mentee->firstname . ' ' . $mentee->lastname . '</a></div>';
        }
    }
    return $html;
}

//Mentors by Mentee
function block_fn_mentor_get_mentors_by_mentee($courseid=0, $filter='') {
    global $DB, $CFG;

    $data = array();
    $all_course_students = array();

    if ($filter == 'teacher') {
        if ($courses = block_fn_mentor_get_teacher_courses()) {
            $courseids = implode(",", array_keys($courses));
            $all_course_students = block_fn_mentor_get_enrolled_course_users ($courseids);
        }
    }

    if ($mentees = block_fn_mentor_get_all_mentees($all_course_students)){
        foreach ($mentees as $mentee) {
            if ($mentor = block_fn_mentor_get_mentors($mentee->studentid)){
                $data[$mentee->studentid]['mentee'] = $mentee;
                $data[$mentee->studentid]['mentor'] = $mentor;
            }
        }
    }

    return $data;
}

function block_fn_mentor_render_mentors_by_mentee($data) {
    global $DB, $CFG;

    $coursefilter = optional_param('coursefilter', 0, PARAM_INT);

    $html = '';
    foreach ($data as $mentee) {

        $grade_summary = block_fn_mentor_grade_summary($mentee['mentee']->studentid, $coursefilter);
        //print_r($grade_summary);die;
        if (($grade_summary->attempted >= 50) && ($grade_summary->all >= 50)) {
            $mentee_icon = 'mentee_green.png';
        } elseif (($grade_summary->attempted >= 50) && ($grade_summary->all < 50)) {
            $mentee_icon = 'mentee_red_green.png';
        } elseif (($grade_summary->attempted < 50) && ($grade_summary->all >= 50)) {
            $mentee_icon = 'mentee_red_green.png';
        } elseif (($grade_summary->attempted < 50) && ($grade_summary->all < 50)) {
            $mentee_icon = 'mentee_red.png';
        }

        $html .= '<div class="mentee"><strong>
                  <img class="mentor-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/'.$mentee_icon.'">
                  <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$mentee['mentee']->studentid.'" >' .$mentee['mentee']->firstname . ' ' . $mentee['mentee']->lastname . '</strong></a></div>';
        foreach ($mentee['mentor'] as $mentor) {
            $html .= '<div class="mentor"><img class="mentee-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
            <a  href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'"
            onclick="window.open(\''.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
            class="mentor-profile" >'.$mentor->firstname . ' ' . $mentor->lastname.'</a></div>';
        }
    }
    return $html;
}

function block_fn_mentor_render_mentees_by_student($menteeid) {
    global $DB, $CFG;

    $html = '';

    $mentee = $DB->get_record('user', array('id'=>$menteeid));

    if ($mentors = block_fn_mentor_get_mentors($menteeid)) {
        $html .= '<div class="mentee">
                  <img class="mentor-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentee_red.png">
                  <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$mentee->id.'" >' .$mentee->firstname . ' ' . $mentee->lastname . '</a></div>';
        foreach ($mentors as $mentor) {
            $html .= '<div class="mentor">
                      <img class="mentee-img" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
                      <a class="mentor-profile" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'">' .$mentor->firstname . ' ' . $mentor->lastname . '</a></div>';
        }

    }
    return $html;
}

function block_fn_mentor_assignment_status($mod, $userid) {
    global $CFG, $DB, $USER, $SESSION;

    if(isset($SESSION->completioncache)){
        unset($SESSION->completioncache);
    }

    if ($mod->modname == 'assignment') {
        if  (!($assignment = $DB->get_record('assignment', array('id' => $mod->instance)))) {

            return false;   // Doesn't exist... wtf?
        }
        require_once ($CFG->dirroot.'/mod/assignment/type/'.$assignment->assignmenttype.'/assignment.class.php');
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($mod->id, $assignment, $mod);

        if (!($submission = $assignmentinstance->get_submission($userid)) || empty($submission->timemodified)) {
            return false;
        }

        switch ($assignment->assignmenttype) {
            case "upload":
                if($assignment->var4){ //if var4 enable then assignment can be saved
                    if(!empty($submission->timemodified)
                            && (empty($submission->data2))
                            && (empty($submission->timemarked))){
                        return 'saved';

                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && empty($submission->timemarked)){
                        return 'submitted';
                    }
                    else if(!empty($submission->timemodified)
                            && ($submission->data2='submitted')
                            && ($submission->grade==-1)){
                        return 'submitted';

                    }
                }
                else if(empty($submission->timemarked)){
                    return 'submitted';
                }
                break;
            case "uploadsingle":
                if(empty($submission->timemarked)){
                     return 'submitted';
                }
                break;
            case "online":
                if(empty($submission->timemarked)){
                     return 'submitted';
                }
                break;
            case "offline":
                if(empty($submission->timemarked)){
                     return 'submitted';
                }
                break;
        }
    } else if ($mod->modname == 'assign') {
        if  (!($assignment = $DB->get_record('assign', array('id' => $mod->instance)))) {
            return false; // Doesn't exist
        }

        if (!$submission = $DB->get_records('assign_submission', array('assignment'=>$assignment->id, 'userid'=>$userid), 'attemptnumber DESC', '*', 0, 1)) {
            return false;
        }else{
            $submission = reset($submission);
        }

        $attemptnumber = $submission->attemptnumber;

        if (($submission->status == 'reopened') && ($submission->attemptnumber > 0)){
            $attemptnumber = $submission->attemptnumber - 1;
        }

        if ($submissionisgraded = $DB->get_records('assign_grades', array('assignment'=>$assignment->id, 'userid'=>$userid, 'attemptnumber' => $attemptnumber), 'attemptnumber DESC', '*', 0, 1)) {
            $submissionisgraded = reset($submissionisgraded);
            if ($submissionisgraded->grade > -1){
              if ($submission->timemodified > $submissionisgraded->timemodified) {
                    $graded = false;
                }else{
                    $graded = true;
                }
            }else{
                $graded = false;
            }
        }else {
            $graded = false;
        }


        if ($submission->status == 'draft') {
            if($graded){
                return 'submitted';
            }else{
                return 'saved';
            }
        }
        if ($submission->status == 'reopened') {
            if($graded){
                return 'submitted';
            }else{
                return 'waitinggrade';
            }
        }
        if ($submission->status == 'submitted') {
            if($graded){
                return 'submitted';
            }else{
                return 'waitinggrade';
            }
        }
    } else {
        return false;
    }
}

function block_fn_mentor_grade_summary($studentid, $courseid=0) {

    global $DB;

    $data = new stdClass();
    $courses = array();
    $coursegrades = array();

    $grade_total = array('attempted_grade'=>0,
                         'attempted_max'=>0,
                         'all_max'=>0);

    if ($courseid) {
        $courses[$courseid] = $courseid;
    } else {
        $courses = block_fn_mentor_get_student_courses($studentid);
    }

    if ($courses) {
        foreach ($courses as $id => $value) {

            $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

            /// Available modules for grading.
            $mod_available = array(
                'assign' => '1',
                'quiz' => '1',
                'assignment' => '1',
                'forum' => '1',
            );

            $context = context_course::instance($course->id);

            /// Collect modules data
            $mods = get_course_mods($course->id);

            //Skip some mods
            foreach ($mods as $mod) {
                if (!isset($mod_available[$mod->modname])) {
                    continue;
                }

                if ($mod->groupingid) {
                    $sql_grouiping = "SELECT 1
                                    FROM {groupings_groups} gg
                              INNER JOIN {groups_members} gm
                                      ON gg.groupid = gm.groupid
                                   WHERE gg.groupingid = ?
                                     AND gm.userid = ?";
                    if (!$DB->record_exists_sql($sql_grouiping, array($mod->groupingid, $studentid))) {
                        continue;
                    }
                }


                if (!$grade_item = $DB->get_record('grade_items', array('itemtype' => 'mod', 'itemmodule' => $mod->modname, 'iteminstance' => $mod->instance))) {
                    continue;
                }

                $grade_total['all_max'] += $grade_item->grademax;

                if ($grade_grade = $DB->get_record('grade_grades', array('itemid' => $grade_item->id, 'userid' => $studentid))) {

                    if ($mod->modname == 'assign') {
                        if ($assign_grades = $DB->get_records('assign_grades', array('assignment' => $mod->instance, 'userid' => $studentid), 'attemptnumber DESC')) {
                            $assign_grade = reset($assign_grades);
                            if ($assign_grade->grade >= 0) {
                                //Graded
                                $grade_total['attempted_grade'] += $grade_grade->finalgrade;
                                $grade_total['attempted_max'] += $grade_item->grademax;
                            }
                        }
                    } else {
                        //Graded
                        $grade_total['attempted_grade'] += $grade_grade->finalgrade;
                        $grade_total['attempted_max'] += $grade_item->grademax;
                    }

                } else {
                    //Ungraded
                }
            }

        }
    }
    if ($grade_total['attempted_max']) {
        $attempted = round(($grade_total['attempted_grade'] / $grade_total['attempted_max']) * 100);
    } else {
        $attempted = 0;
    }
    if ($grade_total['all_max']) {
        $all = round(($grade_total['attempted_grade'] / $grade_total['all_max']) * 100);
    } else {
        $all = 0;
    }

    $data->attempted = $attempted;
    $data->all = $all;

    if ($courses) {
        foreach ($courses as $id => $value) {
            $sqlcourseaverage = "SELECT gg.id,
                                gg.rawgrademax,
                                gg.finalgrade
                           FROM {grade_items} gi
                           JOIN {grade_grades} gg
                             ON gi.id = gg.itemid
                          WHERE gi.itemtype = ?
                            AND gi.courseid = ?
                            AND gg.userid = ?";
            if ($courseaverage = $DB->get_record_sql($sqlcourseaverage, array('course', $id, $studentid))) {
                $coursegrades[$id] = ($courseaverage->finalgrade / $courseaverage->rawgrademax) * 100;
            }
        }
    }
    if (count($coursegrades)) {
        $data->allcourseaverge = round(array_sum($coursegrades) / count($coursegrades));
    } else {
        $data->allcourseaverge = 0;
    }


    if ($courseid) {

        if (isset($coursegrades[$courseid])) {
            $data->courseaverage = round($coursegrades[$courseid]);
        } else {
            $data->courseaverage = 0;
        }

        $sqlactivity = "SELECT gi.id,
                           gg.finalgrade
                      FROM {grade_items} gi
           LEFT OUTER JOIN {grade_grades} gg
                        ON gi.id = gg.itemid
                     WHERE gi.courseid = ?
                       AND gi.itemtype = ?
                       AND gg.userid = ?";
        if ($gradedavtivities = $DB->get_records_sql($sqlactivity, array($courseid, 'mod', $studentid))) {
            $numofactivities = 0;
            $numofgraded = 0;
            foreach ($gradedavtivities as $gradedavtivity) {
                $numofactivities++;
                if (is_numeric($gradedavtivity->finalgrade)) {
                    $numofgraded++;
                }
            }
            $data->numofcompleted = "$numofgraded/$numofactivities";
            $data->percentageofcompleted = round(($numofgraded / $numofactivities) * 100);
        } else {
            $data->numofcompleted = "N/A";
            $data->percentageofcompleted = 0;
        }
    }

    return $data;
}

function block_fn_mentor_print_grade_summary ($courseid , $studentid) {

    global $CFG, $DB;

    $html = '';

    $grade_summary = block_fn_mentor_grade_summary($studentid, $courseid);

    $html .= '<table class="mentee-course-overview-grade_table">';
    $html .= '<tr>';
    $html .= '<td class="overview-grade-left" valign="middle">'.get_string('numofcomplete', 'block_fn_mentor').':</td>';
    $html .= '<td class="overview-grade-right grey" valign="middle">'.$grade_summary->numofcompleted.'</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="overview-grade-left" valign="middle">'.get_string('currentgrade', 'block_fn_mentor').':</td>';
    $class = ($grade_summary->courseaverage >= 50) ? 'green' : 'red';
    $html .= '<td class="overview-grade-right '.$class.'" valign="middle">'.$grade_summary->courseaverage.'%</td>';
    $html .= '</tr>';
    $html .= '</table>';
    return $html;
}

function block_fn_mentor_get_teacher_courses ($teacherid=0) {
    global $CFG, $DB, $USER;

    if (! $teacherid)
        $teacherid = $USER->id;

    $sql = "SELECT c.id,
                   c.fullname
              FROM {context} ctx
        INNER JOIN {role_assignments} ra
                ON ctx.id = ra.contextid
        INNER JOIN {course} c
                ON ctx.instanceid = c.id
             WHERE ctx.contextlevel = ?
               AND ra.roleid = ?
               AND ra.userid = ?";

    if ($courses = $DB->get_records_sql($sql, array(50, 3, $teacherid))) {
        return $courses;
    }
    return false;
}

function block_fn_mentor_get_student_courses ($studentid=0) {
    global $CFG, $DB, $USER;

    if (! $studentid)
        $studentid = $USER->id;

    $studentrole = get_config('block_fn_mentor', 'studentrole');

    $sql = "SELECT c.id,
                   c.fullname
              FROM {context} ctx
        INNER JOIN {role_assignments} ra
                ON ctx.id = ra.contextid
        INNER JOIN {course} c
                ON ctx.instanceid = c.id
             WHERE ctx.contextlevel = ?
               AND ra.roleid = ?
               AND ra.userid = ?";

    if ($courses = $DB->get_records_sql($sql, array(50, $studentrole, $studentid))) {
        return $courses;
    }
    return false;
}

function block_fn_mentor_get_enrolled_course_users ($courseids) {
    global $CFG, $DB, $USER;

    $sql = "SELECT ue.userid
              FROM {course} course
              JOIN {enrol} en
                ON en.courseid = course.id
              JOIN {user_enrolments} ue
                ON ue.enrolid = en.id
             WHERE en.courseid IN (?)";

    if ($enrolled_users = $DB->get_records_sql($sql, array($courseids))) {
        return $enrolled_users;
    }
    return false;
}

function block_fn_mentor_single_button($url, $buttonname, $class='singlebutton', $id='singlebutton') {

    return '<div class="'.$class.'">
            <button class="'.$class.'" id="'.$id.'" url="'.$url.'">'.$buttonname.'</button>
            </div>';
}

function block_fn_mentor_get_course_category_tree($id = 0, $depth = 0) {
    global $DB, $CFG;
    $viewhiddencats = has_capability('moodle/category:viewhiddencategories', context_system::instance());
    $categories = block_fn_mentor_get_child_categories($id);
    $categoryids = array();
    foreach ($categories as $key => &$category) {
        if (!$category->visible && !$viewhiddencats) {
            unset($categories[$key]);
            continue;
        }
        $categoryids[$category->id] = $category;
        if (empty($CFG->maxcategorydepth) || $depth <= $CFG->maxcategorydepth) {
            list($category->categories, $subcategories) = get_course_category_tree($category->id, $depth+1);
            foreach ($subcategories as $subid=>$subcat) {
                $categoryids[$subid] = $subcat;
            }
            $category->courses = array();
        }
    }

    if ($depth > 0) {
        // This is a recursive call so return the required array
        return array($categories, $categoryids);
    }

    if (empty($categoryids)) {
        // No categories available (probably all hidden).
        return array();
    }

    // The depth is 0 this function has just been called so we can finish it off
    $ccselect = ", " . context_helper::get_preload_record_columns_sql('ctx');
    $ccjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ".CONTEXT_COURSE.")";


    list($catsql, $catparams) = $DB->get_in_or_equal(array_keys($categoryids));
    $sql = "SELECT
            c.id,c.sortorder,c.visible,c.fullname,c.shortname,c.summary,c.category
            $ccselect
            FROM {course} c
            $ccjoin
            WHERE c.category $catsql ORDER BY c.sortorder ASC";
    if ($courses = $DB->get_records_sql($sql, $catparams)) {
        // loop throught them
        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            //context_instance_preload($course);
            context_helper::preload_from_record($course);
            if (!empty($course->visible) || has_capability('moodle/course:viewhiddencourses', context_course::instance($course->id))) {
                $categoryids[$course->category]->courses[$course->id] = $course;
            }
        }
    }
    return $categories;
}

function block_fn_mentor_get_child_categories($parentid) {
    global $DB;

    $rv = array();
    $sql = context_helper::get_preload_record_columns_sql('ctx');
    $records = $DB->get_records_sql("SELECT c.*, $sql FROM {course_categories} c ".
            "JOIN {context} ctx on ctx.instanceid = c.id AND ctx.contextlevel = ? WHERE c.parent = ? ORDER BY c.sortorder",
            array(CONTEXT_COURSECAT, $parentid));
    foreach ($records as $category) {
        context_helper::preload_from_record($category);
        if (!$category->visible && !has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
            continue;
        }
        $rv[] = $category;
    }
    return $rv;
}

function block_fn_mentor_category_tree_form($structures, $categoryids='', $courseids='') {
    $categoryids = explode(',', $categoryids);
    $courseids = explode(',', $courseids);

    $content = '<ul class="fz_tree" id="fz_tree">';
    foreach ($structures as $structure) {
        $content .= '<li>';
        if (in_array($structure->id, $categoryids)) {
            $content .= block_fn_mentor_checkbox_checked('category_'.$structure->id, 'category_'.$structure->id, '_checkbox', $structure->id) . ' <span class="fz_form_course_category">'. $structure->name . '</span>';
        } else {
            $content .= block_fn_mentor_checkbox('category_'.$structure->id, 'category_'.$structure->id, '_checkbox', $structure->id) . ' <span class="fz_form_course_category">'. $structure->name . '</span>';
        }

        if ($structure->courses) {
            $content .= '<ul>';
            foreach ($structure->courses as $course) {
                if (in_array($course->id, $courseids)) {
                    $content .= html_writer::tag('li', block_fn_mentor_checkbox_checked('course_'.$course->id, 'course_'.$course->id, '_checkbox', $course->id) . ' <span class="fz_form_course">'. $course->fullname.'</span>');
                } else {
                    $content .= html_writer::tag('li', block_fn_mentor_checkbox('course_'.$course->id, 'course_'.$course->id, '_checkbox', $course->id) . ' <span class="fz_form_course">'. $course->fullname.'</span>');
                }
            }
            $content .= '</ul>';
        }
        $content .= block_fn_mentor_sub_category_tree_form($structure, $categoryids, $courseids);
        $content .= '</li>';
    }
    $content .= '</ul>';
    return $content;
}

function block_fn_mentor_sub_category_tree_form($structure, $categoryids=NULL, $courseids=NULL) {
    $content = "<ul>";
    if ($structure->categories) {
        foreach ($structure->categories as $category) {
            $content .= '<li>';
            if (in_array($category->id, $categoryids)) {
                $content .= block_fn_mentor_checkbox_checked('category_'.$category->id, 'category_'.$category->id, '_checkbox', $category->id) . ' <span class="fz_form_course_category">'. $category->name.'</span>';
            } else {
                $content .= block_fn_mentor_checkbox('category_'.$category->id, 'category_'.$category->id, '_checkbox', $category->id) . ' <span class="fz_form_course_category">'. $category->name.'</span>';
            }
            if ($category->courses) {
                $content .= '<ul>';
                foreach ($category->courses as $course) {
                    if (in_array($course->id, $courseids)) {
                        $content .= html_writer::tag('li', block_fn_mentor_checkbox_checked('course_'.$course->id, 'course_'.$course->id, '_checkbox', $course->id) . ' <span class="fz_form_course">'. $course->fullname.'</span>');
                    } else {
                        $content .= html_writer::tag('li', block_fn_mentor_checkbox('course_'.$course->id, 'course_'.$course->id, '_checkbox', $course->id) . ' <span class="fz_form_course">'. $course->fullname.'</span>');
                    }
                }
                $content .= '</ul>';
            }
            $content .= block_fn_mentor_sub_category_tree_form($category, $categoryids, $courseids);
            $content .= '</li>';
        }
    }
    $content .= "</ul>";
    return $content;
}

function block_fn_mentor_button($text, $id) {
    return html_writer::tag('p',
        html_writer::empty_tag('input', array(
            'value' => $text, 'type' => 'button', 'id' => $id
        ))
    );
};

function block_fn_mentor_checkbox($name, $id , $class, $value) {
    return html_writer::empty_tag('input', array(
            'value' => $value, 'type' => 'checkbox', 'id' => $id, 'name' => $name, 'class' => $class
        )
    );
}

function block_fn_mentor_checkbox_checked($name, $id , $class, $value) {
    return html_writer::empty_tag('input', array(
            'value' => $value, 'type' => 'checkbox', 'id' => $id, 'name' => $name, 'class' => $class, 'checked' => 'checked'
        )
    );
}

function block_fn_mentor_textinput($name, $id, $class , $value = '') {
    return html_writer::empty_tag('input', array(
            'value' => $value, 'type' => 'text', 'id' => $id, 'name' => $name, 'class' => $class
        )
    );
}

function block_fn_mentor_single_button_form ($class, $url, $hiddens, $buttontext, $onclick='') {

    $hiddeninputs = '';

    if ($hiddens) {
        foreach ($hiddens as $key => $value) {
            $hiddeninputs .= '<input type="hidden" value="'.$value.'" name="'.$key.'"/>';
        }
    }

    $form = '<div class="'.$class.'">
              <form action="'.$url.'" method="post">
                <div>
                  <input type="hidden" value="'.sesskey().'" name="sesskey"/>
                  '.$hiddeninputs.'
                  <input class="singlebutton" onclick="'.$onclick.'" type="submit" value="'.$buttontext.'"/>
                </div>
              </form>
            </div>';

    return $form;
}

function block_fn_mentor_render_notification_rule_table($notification, $number) {
    global $CFG, $DB;

    $menteeid      = optional_param('menteeid', 0, PARAM_INT);
    $courseid      = optional_param('courseid', 0, PARAM_INT);

    $html = '';
    $html .= '<table class="notification_rule" cellspacing="0">
                 <tr>
                    <td colspan="3" class="notification_rule_ruleno"><strong>'.get_string('rule', 'block_fn_mentor').' '.$number.':</strong> '.$notification->name.'</td>
                    <td colspan="2" class="notification_rule_button">';

    $html .= block_fn_mentor_single_button_form ('create_new_rule', new moodle_url('/blocks/fn_mentor/notification_send.php', array('id'=>$notification->id, 'action'=>'send', 'sesskey'=>sesskey())), NULL, get_string('run_now', 'block_fn_mentor'));
    $html .= block_fn_mentor_single_button_form ('create_new_rule', new moodle_url('/blocks/fn_mentor/notification.php', array('id'=>$notification->id, 'action'=>'edit')), NULL, get_string('open', 'block_fn_mentor'));
    $html .= block_fn_mentor_single_button_form ('create_new_rule', new moodle_url('/blocks/fn_mentor/notification_delete.php', array('id'=>$notification->id, 'action'=>'edit')), NULL, get_string('delete', 'block_fn_mentor'), 'return confirm(\'Do you want to delete record?\')');

    $html .='</td>
                  </tr>
                  <tr>
                    <th class="notification_c1" nowrap="nowrap">'.get_string('apply_to', 'block_fn_mentor').'</th>
                    <th class="notification_c2" nowrap="nowrap">'.get_string('when_to_send', 'block_fn_mentor').'</th>
                    <th class="notification_c3" nowrap="nowrap">'.get_string('who_to_send', 'block_fn_mentor').'</th>
                    <th class="notification_c4" nowrap="nowrap">'.get_string('how_often', 'block_fn_mentor').'</th>
                    <th class="notification_c5" nowrap="nowrap">'.get_string('appended_message', 'block_fn_mentor').'</th>
                  </tr>
                  <tr>
                    <td class="notification_rule_body notification_c1">';

    if ($notification->category){
        if ($categories = $DB->get_records_select('course_categories', 'id IN ('.$notification->category.')')) {
            $html .= '<ul class="fn-course-category">';
            foreach ($categories as $category) {
                $html .= '<li>'.$category->name.'</li>';
            }
            $html .= '</ul>';
        }
    }

    if ($notification->course){
        if ($courses = $DB->get_records_select('course', 'id IN ('.$notification->course.')')) {
            $html .= '<ul>';
            foreach ($courses as $course) {
                $html .= '<li>'.$course->fullname.'</li>';
            }
            $html .= '</ul>';
        }
    }
    $html .= '</td><td class="notification_rule_body notification_c2">';

    if ($notification->g2 || $notification->g4 || $notification->g6 || $notification->n1 || $notification->n2){

        $html .= '<ul>';
        if ($notification->g2) {
            $html .= '<li>'.get_string('g2', 'block_fn_mentor').'</li>';
        }
        if ($notification->g4) {
            $html .= '<li>'.get_string('g4', 'block_fn_mentor', $notification->g4_value).'</li>';
        }
        if ($notification->g6) {
            $html .= '<li>'.get_string('g6', 'block_fn_mentor', $notification->g6_value).'</li>';
        }
        if ($notification->n1) {
            $html .= '<li>'.get_string('n1', 'block_fn_mentor', $notification->n1_value).'</li>';
        }
        if ($notification->n2) {
            $html .= '<li>'.get_string('n2', 'block_fn_mentor', $notification->n2_value).'</li>';
        }
        $html .= '</ul>';
    }

    $html .= '</td><td class="notification_rule_body notification_c3" nowrap="nowrap">';

    $mentornotificationtype = array();
    $teachernotificationtype = array();
    $studentnotificationtype = array();

    if ($notification->mentoremail || $notification->mentorsms || $notification->studentemail || $notification->studentsms || $notification->teacheremail || $notification->teachersms){
        $html .= '<ul>';
        if ($notification->mentoremail) {
            $mentornotificationtype[] = get_string('email', 'block_fn_mentor');
        }
        if ($notification->mentorsms) {
            $mentornotificationtype[] = get_string('sms', 'block_fn_mentor');
        }
        if ($mentornotificationtype) {
            $html .= '<li>'.get_string('mentornotificationtype', 'block_fn_mentor', implode(', ', $mentornotificationtype)).'</li>';
        }

        if ($notification->studentemail) {
            $studentnotificationtype[] = get_string('email', 'block_fn_mentor');
        }
        if ($notification->studentsms) {
            $studentnotificationtype[] = get_string('sms', 'block_fn_mentor');
        }
        if ($studentnotificationtype) {
            $html .= '<li>'.get_string('studentnotificationtype', 'block_fn_mentor', implode(', ', $studentnotificationtype)).'</li>';
        }

        if ($notification->teacheremail) {
            $teachernotificationtype[] = get_string('email', 'block_fn_mentor');
        }
        if ($notification->teachersms) {
            $teachernotificationtype[] = get_string('sms', 'block_fn_mentor');
        }
        if ($teachernotificationtype) {
            $html .= '<li>'.get_string('teachernotificationtype', 'block_fn_mentor', implode(', ', $teachernotificationtype)).'</li>';
        }

        $html .= '</ul>';
    }
    $html .= '</td>
                    <td class="notification_rule_body notification_c4">'.get_string('period', 'block_fn_mentor', $notification->period).'</td>
                    <td class="notification_rule_body notification_c5">'.$notification->appended_message.'</td>
                  </tr>
                </table>';
    return $html;
}

function block_fn_mentor_last_activity ($studentid) {
    global $CFG, $DB;

    $last_submission = NULL;
    $last_attempt = NULL;
    $last_post = NULL;

    //assign
    $sql_assign = "SELECT s.id,
                          s.timemodified
                     FROM {assign_submission} s
                    WHERE s.userid = ?
                      AND s.status = 'submitted'
                 ORDER BY s.timemodified DESC";

    if ($submissions = $DB->get_records_sql($sql_assign, array($studentid))) {
        $submission = reset($submissions);
        $last_submission = round(((time() - $submission->timemodified) / (24*60*60)), 0);
    }

    //quiz
    $sql_quiz = "SELECT qa.id,
                        qa.timefinish
                   FROM {quiz_attempts} qa
                  WHERE qa.state = 'finished'
                    AND qa.userid = ?
               ORDER BY qa.timefinish DESC";

    if ($attempts = $DB->get_records_sql($sql_quiz, array($studentid))) {
        $attempt = reset($attempts);
        $last_attempt = round(((time() - $attempt->timefinish) / (24*60*60)), 0);
    }

    //forum
    $sql_forum = "SELECT f.id,
                         f.modified
                    FROM {forum_posts} f
                   WHERE f.userid = ?
                ORDER BY f.modified DESC";

    if ($posts = $DB->get_records_sql($sql_forum, array($studentid))) {
        $post = reset($posts);
        $last_post = round(((time() - $post->modified) / (24*60*60)), 0);
    }

    return min($last_submission, $last_attempt, $last_post);
}

function block_fn_mentor_report_outline_print_row($mod, $instance, $result) {
    global $OUTPUT, $CFG;

    $image = "<img src=\"" . $OUTPUT->pix_url('icon', $mod->modname) . "\" class=\"icon\" alt=\"$mod->modfullname\" />";

    echo "<tr>";
    echo "<td valign=\"top\">$image</td>";
    echo "<td valign=\"top\" style=\"width:300\">";

    //echo "   <a target=\"_blank\" title=\"$mod->modfullname\"";
    //echo "   href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">".format_string($instance->name,true)."</a></td>";

    echo "<a title=\"$mod->modfullname\"  href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\"
          onclick=\"window.open('$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id', '', 'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes'); return false;\"
          class=\"\" >".format_string($instance->name,true)."</a></td>";


    echo "<td>&nbsp;&nbsp;&nbsp;</td>";
    echo "<td valign=\"top\">";
    if (isset($result->info)) {
        echo "$result->info";
    } else {
        echo "<p style=\"text-align:center\">-</p>";
    }
    echo "</td>";
    echo "<td>&nbsp;&nbsp;&nbsp;</td>";
    if (!empty($result->time)) {
        $timeago = format_time(time() - $result->time);
        echo "<td valign=\"top\" style=\"white-space: nowrap\">".userdate($result->time)." ($timeago)</td>";
    }
    echo "</tr>";
}

function block_fn_mentor_format_time($totalsecs, $str=NULL) {

    $totalsecs = abs($totalsecs);

    if (!$str) {  // Create the str structure the slow way
        $str = new stdClass();
        $str->day   = get_string('day');
        $str->days  = get_string('days');
        $str->hour  = get_string('hour');
        $str->hours = get_string('hours');
        $str->min   = get_string('min');
        $str->mins  = get_string('mins');
        $str->sec   = get_string('sec');
        $str->secs  = get_string('secs');
        $str->year  = get_string('year');
        $str->years = get_string('years');
    }


    $years     = floor($totalsecs/YEARSECS);
    $remainder = $totalsecs - ($years*YEARSECS);
    $days      = floor($remainder/DAYSECS);
    $remainder = $totalsecs - ($days*DAYSECS);
    $hours     = floor($remainder/HOURSECS);
    $remainder = $remainder - ($hours*HOURSECS);
    $mins      = floor($remainder/MINSECS);
    $secs      = $remainder - ($mins*MINSECS);

    $ss = ($secs == 1)  ? $str->sec  : $str->secs;
    $sm = ($mins == 1)  ? $str->min  : $str->mins;
    $sh = ($hours == 1) ? $str->hour : $str->hours;
    $sd = ($days == 1)  ? $str->day  : $str->days;
    $sy = ($years == 1)  ? $str->year  : $str->years;

    $oyears = '';
    $odays = '';
    $ohours = '';
    $omins = '';
    $osecs = '';

    if ($years)  $oyears  = $years .' '. $sy;
    if ($days)  $odays  = $days .' '. $sd;
    if ($hours) $ohours = $hours .' '. $sh;
    if ($mins)  $omins  = $mins .' '. $sm;
    if ($secs)  $osecs  = $secs .' '. $ss;

    if ($years) return trim($oyears);
    if ($days)  return trim($odays);
    if ($hours) return trim($ohours);
    if ($mins)  return trim($omins);
    if ($secs)  return $osecs;
    return get_string('now');
}

function block_fn_mentor_note_print($note, $detail = NOTES_SHOW_FULL) {
    global $CFG, $USER, $DB, $OUTPUT;

    if (!$user = $DB->get_record('user', array('id'=>$note->userid))) {
        debugging("User $note->userid not found");
        return;
    }
    if (!$author = $DB->get_record('user', array('id'=>$note->usermodified))) {
        debugging("User $note->usermodified not found");
        return;
    }

    $context = context_course::instance($note->courseid);
    $systemcontext = context_system::instance();

    $authoring = new stdClass();
    $authoring->name = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$author->id.'&amp;course='.$note->courseid.'">'.fullname($author).'</a>';
    $authoring->date = userdate($note->lastmodified);

    echo '<div class="notepost '. $note->publishstate . 'notepost' .
        ($note->usermodified == $USER->id ? ' ownnotepost' : '')  .
        '" id="note-'. $note->id .'">';

    // print note head (e.g. author, user refering to, etc)
    if ($detail & NOTES_SHOW_HEAD) {
        echo '<div class="header">';
        echo '<div class="user">';
        echo $OUTPUT->user_picture($user, array('courseid'=>$note->courseid));
        echo fullname($user) . '</div>';
        echo '<div class="info">' .
            get_string('bynameondate', 'notes', $authoring) . '</div>';
        echo '</div>';
    }

    // print note content
    if ($detail & NOTES_SHOW_BODY) {
        echo '<div class="content">';
        echo format_text($note->content, $note->format, array('overflowdiv'=>true));
        echo '</div>';
    }
    echo '</div>';
}

function block_fn_mentor_send_notifications($notificationid=null, $output=false) {
    global $DB;

    $notificationreport = '';
    $time = time();
    $studentroleid = get_config('block_fn_mentor', 'studentrole');
    $teacherroleid = get_config('block_fn_mentor', 'teacherrole');

    if ($notification_rules = $DB->get_records('block_fn_mentor_notification')) {

        foreach ($notification_rules as $notification_rule) {

            if ($notificationid && $notificationid <> $notification_rule->id) {
                continue;
            }

            if (!$notification_rule->crontime) {
                $notification_rule->crontime = '2000-01-01';
            }

            $date1 = new DateTime($notification_rule->crontime);
            $now = new DateTime(date("Y-m-d"));

            $diff = $now->diff($date1)->format("%a");

            //Check period
            if (($notification_rule->period > $diff) && !$notificationid) {
                continue;
            }

            if (!($notification_rule->g2)
                && !($notification_rule->g4 && $notification_rule->g4_value)
                && !($notification_rule->g6 && $notification_rule->g6_value)
                && !($notification_rule->n1 && $notification_rule->n1_value)
                && !($notification_rule->n2 && $notification_rule->n2_value) ) {
                continue;
            }

            $courses = array();
            $notification_message = array();

            $get_courses = function($category, &$courses){
                if ($category->courses) {
                    foreach ($category->courses as $course) {
                        $courses[] =  $course->id;
                    }
                }
                if ($category->categories) {
                    foreach ($category->categories as $subcat) {
                        $get_courses($subcat, $course);
                    }
                }
            };

            // CATEGORY.
            if ($notification_rule->category) {

                $notification_categories = explode(',', $notification_rule->category);

                foreach ($notification_categories as $categoryid) {

                    if ($parentcat_courses = $DB->get_records('course', array('category'=>$categoryid))) {
                        foreach ($parentcat_courses as $cat_course) {
                            $courses[] =  $cat_course->id;
                        }
                    }
                    if ($category_structure = block_fn_mentor_get_course_category_tree($categoryid)) {
                        foreach ($category_structure as $category) {

                            if ($category->courses) {
                                foreach ($category->courses as $subcat_course) {
                                    $courses[] =  $subcat_course->id;
                                }
                            }
                            if ($category->categories) {
                                foreach ($category->categories as $subcategory) {
                                    $get_courses($subcategory, $courses);
                                }
                            }
                        }
                    }
                }
            }

            // COURSE.
            if ($notification_rule->course) {
                $notification = explode(',', $notification_rule->course);
                $courses = array_merge($courses, $notification);
            }

            // PREPARE NOTIFICATION FOR EACH COURSES.
            foreach ($courses as $courseid) {
                if ($course = $DB->get_record('course', array('id'=>$courseid))) {

                    $context = context_course::instance($course->id);

                    if ($students = get_enrolled_users($context, 'mod/assign:submit', 0,'u.*', null, 0, 0, true)) {
                        foreach ($students as $student) {
                            if ($student->suspended) {
                                continue;
                            }
                            $message = "";
                            $grade_summary = block_fn_mentor_grade_summary($student->id, $course->id);
                            $lastaccess = 0;

                            $notification_message[$student->id][$course->id]['studentname'] = $student->firstname . ' ' . $student->lastname;


                            if ($notification_rule->g2) {
                                $message .= '<li>'.get_string('g2_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g2'=>$grade_summary->courseaverage)).'</li>';
                                $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                $notification_message[$student->id][$course->id]['message'] = $message;
                            }
                            if ($notification_rule->g4 && $notification_rule->g4_value) {
                                if ($grade_summary->courseaverage < $notification_rule->g4_value){
                                    $message .= '<li>'.get_string('g4_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g4'=>$grade_summary->courseaverage, 'g4_value'=>$notification_rule->g3_value)).'</li>';
                                    $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                    $notification_message[$student->id][$course->id]['message'] = $message;
                                }
                            }
                            if ($notification_rule->g6 && $notification_rule->g6_value) {
                                if ($grade_summary->courseaverage > $notification_rule->g6_value){
                                    $message .= '<li>'.get_string('g6_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g6'=>$grade_summary->courseaverage, 'g6_value'=>$notification_rule->g6_value)).'</li>';
                                    $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                    $notification_message[$student->id][$course->id]['message'] = $message;
                                }
                            }

                            if ($notification_rule->n1 && $notification_rule->n1_value) {
                                if ($student->lastaccess > 0) {
                                    $lastaccess = round(((time() - $student->lastaccess) /(24*60*60)), 0);
                                }
                                if ($lastaccess >= $notification_rule->n1_value){
                                    $message .= '<li>'.get_string('n1_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'n1'=>$lastaccess)).'</li>';
                                    $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                    $notification_message[$student->id][$course->id]['message'] = $message;
                                }
                            }

                            if ($notification_rule->n2 && $notification_rule->n2_value) {
                                $last_activity = block_fn_mentor_last_activity($student->id);
                                if (is_numeric($last_activity)) {
                                    if ($last_activity >= $notification_rule->n2_value){
                                        $message .= '<li>'.get_string('n2_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'n2'=>$last_activity)).'</li>';
                                        $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                        $notification_message[$student->id][$course->id]['message'] = $message;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            ///print_object($notification_message);die;
            // SEND EMAILS FOR EACH RULE.
            foreach ($notification_message as $student_id => $course_messages) {

                // STUDENT.
                if (!$student = $DB->get_record('user', array('id'=>$student_id))) {
                    continue;
                }

                foreach ($course_messages as $course_id => $course_message) {
                    if (!isset($course_message['message'])) {
                        continue;
                    }
                    // TEACHER.
                    if ($notification_rule->teacheremail || $notification_rule->teachersms) {
                        // Course teachers.
                        $sqlteacher = "SELECT u.id,
                                              u.firstname,
                                              u.lastname
                                         FROM {context} ctx
                                   INNER JOIN {role_assignments} ra
                                           ON ctx.id = ra.contextid
                                   INNER JOIN {user} u
                                           ON ra.userid = u.id
                                        WHERE ctx.contextlevel = ?
                                          AND ra.roleid = ?
                                          AND ctx.instanceid = ?";

                        if ($teachers = $DB->get_records_sql($sqlteacher, array(50, $teacherroleid, $course_id))) {
                            foreach ($teachers as $teacher) {
                                if (!$to = $DB->get_record('user', array('id' => $teacher->id))) {
                                    continue;
                                }
                                $rec = new stdClass();
                                $rec->notificationid = $notification_rule->id;
                                $rec->type = 'teacher';
                                $rec->receiverid = $teacher->id;
                                $rec->userid = $student_id;
                                $rec->courseid = $course_id;
                                $rec->message = $course_message['message'];
                                $rec->timecreated = $time;
                                $rec->securitykey = md5(uniqid(rand(), true));
                                $rec->sent = 0;
                                $DB->insert_record('block_fn_mentor_notifica_msg', $rec);
                            }
                        }
                    }

                    //STUDENT
                    if ($notification_rule->studentemail || $notification_rule->studentsms) {
                        $rec = new stdClass();
                        $rec->notificationid = $notification_rule->id;
                        $rec->type = 'student';
                        $rec->receiverid = $student_id;
                        $rec->userid = $student_id;
                        $rec->courseid = $course_id;
                        $rec->message = $course_message['message'];
                        $rec->timecreated = $time;
                        $rec->securitykey = md5(uniqid(rand(), true));
                        $rec->sent = 0;
                        $DB->insert_record('block_fn_mentor_notifica_msg', $rec);
                    }

                    //MENTOR
                    if ($notification_rule->mentoremail || $notification_rule->mentorsms) {
                        $mentors = block_fn_mentor_get_mentors($student_id);
                        foreach ($mentors as $mentor) {
                            if (!$to = $DB->get_record('user', array('id' => $mentor->mentorid))) {
                                continue;
                            }
                            $rec = new stdClass();
                            $rec->notificationid = $notification_rule->id;
                            $rec->type = 'mentor';
                            $rec->receiverid = $mentor->mentorid;
                            $rec->userid = $student_id;
                            $rec->courseid = $course_id;
                            $rec->message = $course_message['message'];
                            $rec->timecreated = $time;
                            $rec->securitykey = md5(uniqid(rand(), true));
                            $rec->sent = 0;
                            $DB->insert_record('block_fn_mentor_notifica_msg', $rec);
                        }
                    }
                }
            }
            $update_sql = "UPDATE {block_fn_mentor_notification} SET crontime=? WHERE id=?";
            $DB->execute($update_sql, array(date("Y-m-d"), $notification_rule->id));
        } // END OF EACH NOTIFICATION.

        $notificationreport .= block_fn_mentor_group_messages();
    }

    if ($output) {
        return $notificationreport;
    }
}

function block_fn_mentor_group_messages () {
    global $DB;

    $site = get_site();
    $supportuser = core_user::get_support_user();
    $subject = get_string('progressreportfrom', 'block_fn_mentor', format_string($site->fullname));
    $notificationreport = '';

    $sqlgroup = "SELECT n.id,
                        n.notificationid,
                        n.type,
                        n.receiverid,
                        n.userid,
                        n.courseid,
                        n.message,
                        n.securitykey,
                        n.timecreated,
                        n.sent
                   FROM {block_fn_mentor_notifica_msg} n
                  WHERE n.sent = 0
                    AND n.type IN ('mentor', 'student', 'teacher')
               GROUP BY n.receiverid,
                        n.type,
                        n.notificationid,
                        n.timecreated";

    if ($groups = $DB->get_records_sql($sqlgroup)) {
        foreach ($groups as $group) {
            $emailbody = '';
            $notification = $DB->get_record('block_fn_mentor_notification', array('id' => $group->notificationid));

            if ($messages = $DB->get_records('block_fn_mentor_notifica_msg',
                array('receiverid' => $group->receiverid, 'type' => $group->type, 'timecreated' => $group->timecreated, 'notificationid' => $group->notificationid, 'sent' => '0'), 'userid ASC')) {

                foreach ($messages as $message) {
                    $student = $DB->get_record('user', array('id' => $message->userid));
                    $course = $DB->get_record('course', array('id' => $message->courseid));

                    $emailbody .= get_string('progressreportfrom', 'block_fn_mentor', format_string($site->fullname)).' <br />';
                    $emailbody .= get_string('student', 'block_fn_mentor').':  <strong>' . $student->firstname . ' ' . $student->lastname . '</strong> <br /><hr />';

                    $emailbody .= get_string('course', 'block_fn_mentor').': ' . $course->fullname . ' <br />';
                    $emailbody .= '<ul>' . $message->message . '</ul>';

                    $menteeurl = new moodle_url('/blocks/fn_mentor/course_overview.php', array('menteeid' => $student->id));
                    $emailbody .= '<p>' . get_string('linktomentorpage', 'block_fn_mentor', $menteeurl->out()) . '</p><hr />';

                    $message->sent = 1;
                    $DB->update_record('block_fn_mentor_notifica_msg', $message);
                }

                $appended_message = '';
                if ($notification->appended_message) {
                    $appended_message = '<p>' . $notification->appended_message . '</p>';
                }
                $emailbody .= $appended_message . '<hr />';
                $emailbody .= get_string('automatedmessage', 'block_fn_mentor', format_string($site->fullname));



                $rec = new stdClass();
                $rec->notificationid = $group->notificationid;
                $rec->type = 'email';
                $rec->receiverid = $group->receiverid;
                $rec->message = $emailbody;
                $rec->timecreated = time();
                $rec->securitykey = md5(uniqid(rand(), true));
                $rec->sent = 0;
                $nid = $DB->insert_record('block_fn_mentor_notifica_msg', $rec);

                $messageurl = new moodle_url('/blocks/fn_mentor/notification_message.php', array('id'=>$nid,'key'=>$rec->securitykey));

                //test it out!
                $new_url = get_tiny_url($messageurl->out(false));

                $smsbody = get_string('progressreportfrom', 'block_fn_mentor', format_string($site->fullname))."\n".
                           get_string('clickhere', 'block_fn_mentor', $new_url);

                $sent = 0;
                if ($to = $DB->get_record('user', array('id' => $group->receiverid))) {
                    $emailsent = $group->type . 'email';
                    $smssent = $group->type . 'sms';
                    if ($notification->$emailsent) {
                        if (email_to_user($to, $supportuser, $subject, '', $emailbody)) {
                            $sent = 1;
                            $notificationreport .= $to->firstname . ' ' . $to->lastname . get_string('emailsent', 'block_fn_mentor') . '<br>';
                        } else {
                            $notificationreport .= '<span class="fn_mentor_error">'.$to->firstname . ' ' . $to->lastname . get_string('emailerror', 'block_fn_mentor') . '</span><br>';
                        }
                    }

                    if ($notification->$smssent) {
                        if (block_fn_mentor_sms_to_user($to, $supportuser, $subject, '', $smsbody)) {
                            $sent = 1;
                            $notificationreport .= $to->firstname . ' ' . $to->lastname . get_string('smssent', 'block_fn_mentor') . '<br>';
                        } else {
                            $notificationreport .= '<span class="fn_mentor_error">'.$to->firstname . ' ' . $to->lastname . get_string('smserror', 'block_fn_mentor') . '</span><br>';
                        }
                    }
                    $rec->id = $nid;
                    $rec->sent = $sent;
                    $DB->update_record('block_fn_mentor_notifica_msg', $rec);
                }
            }
        }
    }
    return $notificationreport;
}

function block_fn_mentor_sms_to_user ($user, $from, $subject, $messagetext, $messagehtml = '') {
    global $DB;

    $sqlphonenumber = "SELECT t1.shortname, t2.data
						 FROM {user_info_field} AS t1 , {user_info_data} AS t2
					    WHERE t1.id = t2.fieldid
						  AND t1.shortname = 'mobilephone'
						  AND t2.userid = ?";

    if ($phonenumber = $DB->get_record_sql($sqlphonenumber, array($user->id))) {
        $smsnumber = $phonenumber->data;

        $sqlprovider = "SELECT t1.shortname, t2.data
     					  FROM {user_info_field} AS t1 , {user_info_data} AS t2
					 	 WHERE t1.id = t2.fieldid
						   AND t1.shortname = 'mobileprovider'
						   AND t2.userid = ?";

        if ($phoneprovider = $DB->get_record_sql($sqlprovider, array($user->id))) {
            $sms_provider_full = $phoneprovider->data;
            $sms_provider_array = explode('~', $sms_provider_full);
            $sms_provider = $sms_provider_array[1];
            $user->email = $smsnumber . $sms_provider;

            return email_to_user($user, $from, get_string('notification', 'block_fn_mentor'), strip_tags($messagehtml), '');
        }

    }
    return false;
}
function get_tiny_url($url)  {
    $ch = curl_init();
    $timeout = 5;
    curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}