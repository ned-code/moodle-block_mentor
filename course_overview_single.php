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

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot.'/notes/lib.php');
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/user/lib.php';

//Parameters
$menteeid      = optional_param('menteeid', 0, PARAM_INT);
$courseid      = optional_param('courseid', 0, PARAM_INT);
$navpage       = optional_param('page', 'overview', PARAM_TEXT);

$allownotes = get_config('block_fn_mentor', 'allownotes');


require_login();

$mentee = $DB->get_record('user', array('id'=>$menteeid), '*', MUST_EXIST);

//COURSES
if (!$enrolled_courses = enrol_get_all_users_courses($menteeid, 'id,fullname,shortname', NULL, 'fullname ASC')) {
    print_error('error_enrolled_course', 'block_fn_mentor');
}
//Select enrolled course
if (! isset($enrolled_courses[$courseid])) {
    $courseid = key($enrolled_courses);
}

if ($courseid) {
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
}

//PERMISSION
//require_capability('local/fn_mentor:view', context_system::instance(), $USER->id);
$isadmin   = is_siteadmin($USER->id);
$ismentor  = block_fn_mentor_has_system_role($USER->id, get_config('block_fn_mentor', 'mentor_role_system'));
$isteacher = block_fn_mentor_isteacherinanycourse($USER->id);
$isstudent = block_fn_mentor_isstudentinanycourse($USER->id);

if ($allownotes && $ismentor) {
    $allownotes = true;
} elseif ($isadmin || $isteacher ) {
    $allownotes = true;
} else {
    $allownotes = false;
}

if (($isstudent) && ($USER->id <> $menteeid)  && (!$isteacher && !$isadmin && !$ismentor)) {
    print_error('invalidpermission', 'block_fn_mentor');
}

$messages = array();

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/course_overview_single.php');

if ($pagelayout = get_config('block_fn_mentor', 'pagelayout')) {
    $PAGE->set_pagelayout($pagelayout);
}else{
    $PAGE->set_pagelayout('course');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);
$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php', array('menteeid'=>$menteeid)));

echo $OUTPUT->header();

echo '<div id="mentee-course-overview-page">';
//LEFT
echo '<div id="mentee-course-overview-left">';

$lastaccess = '';
if ($mentee->lastaccess) {
    $lastaccess .= get_string('lastaccess').get_string('labelsep', 'langconfig'). block_fn_mentor_format_time(time() - $mentee->lastaccess);
} else {
    $lastaccess .= get_string('lastaccess').get_string('labelsep', 'langconfig').get_string('never');
}

$student_menu = array();
$student_menu_url = array();
$mentees = array();

if ($isadmin) {
    $mentees = block_fn_mentor_get_all_mentees();
} elseif ($isteacher) {
    if ($mentees_by_mentor = block_fn_mentor_get_mentees_by_mentor(0, $filter='teacher')) {
        foreach ($mentees_by_mentor as $mentee_by_mentor) {
            if ($mentee_by_mentor['mentee']) {
                foreach ($mentee_by_mentor['mentee'] as $key => $value) {
                    $mentees[$key] = $value;
                }
            }
        }
    }
} elseif ($ismentor) {
    $mentees = block_fn_mentor_get_mentees($USER->id);
}

if ($mentees){
    foreach ($mentees as $_mentee) {
        $student_menu_url[$_mentee->studentid] = $CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?menteeid='.$_mentee->studentid;
        $student_menu[$student_menu_url[$_mentee->studentid]] = $_mentee->firstname.' '.$_mentee->lastname;
    }
}

$studentmenu = '';

if ((!$isstudent) || ($isadmin || $ismentor  || $isteacher)) {
    $studentmenu = html_writer::tag('form', '<span>'.get_string('select_student', 'block_fn_mentor').'</span>'.
        html_writer::select($student_menu, 'sortby', $student_menu_url[$menteeid], null, array('onChange' => 'location=document.jump1.sortby.options[document.jump1.sortby.selectedIndex].value;')),
        array('id'=>'studentFilterForm', 'name'=>'jump1'));

    $studentmenu = '<div class="mentee-course-overview-block-filter"> '.$studentmenu.' </div>';
}

//BLOCK-1
echo $studentmenu.'
      <div class="mentee-course-overview-block">
          <div class="mentee-course-overview-block-title">
              '.get_string('student', 'block_fn_mentor').'
          </div>
          <div class="mentee-course-overview-block-content">'.
    $OUTPUT->container($OUTPUT->user_picture($mentee, array('courseid'=>$COURSE->id)), "userimage").
    $OUTPUT->container('<a  href="'.$CFG->wwwroot.'/user/view.php?id='.$mentee->id.'&course=1"
                                onclick="window.open(\''.$CFG->wwwroot.'/user/view.php?id='.$mentee->id.'&course=1\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                class="" >'.fullname($mentee, true).'</a>
                    &nbsp;&nbsp;<a href="'.$CFG->wwwroot.'/message/index.php?id='.$mentee->id.'" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a>', "userfullname").
    '<span class="mentee-lastaccess">'.$lastaccess.'</span>' .
    '
          </div>
      </div>';



$courselist = "";

if ($courseid == 0) {
    $courselist .= '<div class="allcourses active">
         <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$menteeid.'&courseid=0">'.get_string('allcourses', 'block_fn_mentor').'</a></div>';
} else {
    $courselist .= '<div class="allcourses">
         <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$menteeid.'&courseid=0">'.get_string('allcourses', 'block_fn_mentor').'</a></div>';
}
foreach ($enrolled_courses as $enrolled_course) {
    if ($courseid == $enrolled_course->id) {
        $courselist .= '<div class="courselist active">
            <img class="mentees-course-bullet" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/b.gif"><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?menteeid='.$menteeid.'&courseid='.$enrolled_course->id.'">'.$enrolled_course->fullname.'</a></div>';
    } else {
        $courselist .= '<div class="courselist">
            <img class="mentees-course-bullet" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/b.gif"><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?menteeid='.$menteeid.'&courseid='.$enrolled_course->id.'">'.$enrolled_course->fullname.'</a></div>';
    }

}

echo '<div class="mentee-course-overview-block">
          <div class="mentee-course-overview-block-title">
              '.get_string('courses', 'block_fn_mentor').'
          </div>
          <div class="mentee-course-overview-block-content">
              '.$courselist.'
          </div>
      </div>';


echo '</div>';

$class_overview = '';
$class_grade = '';
$class_activity = '';
$class_note = '';
$class_notification = '';

if ($navpage == 'overview') {
    $class_overview = ' class="active"';
}elseif ($navpage == 'grade') {
    $class_grade = ' class="active"';
}elseif ($navpage == 'outline') {
    $class_activity = ' class="active"';
}elseif ($navpage == 'note') {
    $class_note = ' class="active"';
}elseif ($navpage == 'notification') {
    $class_notification = ' class="active"';
}


//CENTER
echo '<div id="mentee-course-overview-center-single" class="block">'.
    '<div id="mentee-course-overview-center-menu-container">';
echo '<div class="mentee-course-overview-center-course-title"><a  href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"
    onclick="window.open(\''.$CFG->wwwroot.'/course/view.php?id='.$course->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
    class="" >'.$course->fullname.'</a></div>';

//if (!$isstudent) {
echo '<div class="mentee-course-overview-center-course-menu">
          <table class="mentee-menu">
            <tr>
                <td'.$class_overview.'><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?menteeid='.$menteeid.'&courseid='.$courseid.'">Overview</a></td>
                <td'.$class_grade.'><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?page=grade&menteeid='.$menteeid.'&courseid='.$courseid.'">Grades</a></td>
                <td'.$class_activity.'><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?page=outline&menteeid='.$menteeid.'&courseid='.$courseid.'">Activity</a></td>
                <!--<td'.$class_note.'><a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview_single.php?page=notes&menteeid='.$menteeid.'&courseid='.$courseid.'">Notes</a></td>-->';
echo '</tr>
          </table>
          </div>';
//}
echo '</div>';

if ($navpage == 'overview'){
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

    echo '<div class="mentee-course-overview-center_course">';
    ##################################

    $context = context_course::instance($course->id);

    //Count grade to pass activities
    $sql_grade_to_pass = "SELECT Count(gi.id)
                            FROM {grade_items} gi
                           WHERE gi.courseid = ?
                             AND gi.gradepass > ?";

    $num_grade_to_pass = $DB->count_records_sql($sql_grade_to_pass, array($course->id, 0));

    if (isset($SESSION->completioncache)) {
        unset($SESSION->completioncache);
    }
    $progress_data = new stdClass();
    $progress_data->content = new stdClass;
    $progress_data->content->items = array();
    $progress_data->content->icons = array();
    $progress_data->content->footer = '';
    $completedactivities = 0;
    $incompletedactivities = 0;
    $savedactivities = 0;
    $notattemptedactivities = 0;
    $waitingforgradeactivities = 0;

    $completion = new completion_info($course);
    $activities = $completion->get_activities();

    if ($completion->is_enabled() && !empty($completion)) {

        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }

            $data = $completion->get_data($activity, true, $menteeid, null);

            $completionstate = $data->completionstate;
            $assignment_status = block_fn_mentor_assignment_status($activity, $menteeid);

            // COMPLETION_INCOMPLETE.
            if ($completionstate == 0) {
                //Show activity as complete when conditions are met
                if (($activity->module == 1)
                    && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                    && ($activity->completion == 2)
                    && $assignment_status) {

                    if (isset($assignment_status)) {
                        if ($assignment_status == 'saved') {
                            $savedactivities++;
                        } else if ($assignment_status == 'submitted') {
                            $notattemptedactivities++;
                        } else if ($assignment_status == 'waitinggrade') {
                            $waitingforgradeactivities++;
                        }
                    }else{
                        $notattemptedactivities++;
                    }
                } else {
                    $notattemptedactivities++;
                }
                //COMPLETION_COMPLETE - COMPLETION_COMPLETE_PASS
            } elseif ($completionstate == 1 || $completionstate == 2) {
                if (($activity->module == 1)
                    && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                    && ($activity->completion == 2)
                    && $assignment_status) {

                    if (isset($assignment_status)) {
                        if ($assignment_status == 'saved') {
                            $savedactivities++;
                        } else if ($assignment_status == 'submitted') {
                            $completedactivities++;
                        } else if ($assignment_status == 'waitinggrade') {
                            $waitingforgradeactivities++;
                        }
                    }else{
                        $completedactivities++;
                    }
                } else {
                    $completedactivities++;
                }

                //COMPLETION_COMPLETE_FAIL
            } elseif ($completionstate == 3) {
                //Show activity as complete when conditions are met
                if (($activity->module == 1)
                    && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                    && ($activity->completion == 2)
                    && $assignment_status) {

                    if (isset($assignment_status)) {
                        if ($assignment_status == 'saved') {
                            $savedactivities++;
                        } else if ($assignment_status == 'submitted') {
                            $incompletedactivities++;
                        } else if ($assignment_status == 'waitinggrade') {
                            $waitingforgradeactivities++;
                        }
                    }else{
                        $incompletedactivities++;
                    }
                } else {
                    $incompletedactivities++;
                }
            } else {
                // do nothing
            }
        }

        if ($incompletedactivities == 0) {
            $completed = get_string('completed', 'block_fn_mentor');
            $incompleted = get_string('incompleted', 'block_fn_mentor');
        } else {
            $completed = get_string('completed2', 'block_fn_mentor');
            $incompleted = get_string('incompleted2', 'block_fn_mentor');
        }
        $draft = get_string('draft', 'block_fn_mentor');
        $notattempted = get_string('notattempted', 'block_fn_mentor');
        $waitingforgrade = get_string('waitingforgrade', 'block_fn_mentor');

        //Completed
        $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=completed' . '&navlevel=top"
        onclick="window.open(\''.$CFG->wwwroot.'/blocks/fn_mentor/listactivities.php?id='.$course->id.'&menteeid='.$menteeid.'&show=completed'.'&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $completedactivities . ' '.$completed.'</a>';

        $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/completed.gif"
                                            class="icon" alt="">';

        //Incomplete
        if ($num_grade_to_pass && $incompletedactivities > 0) {
            $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=incompleted' . '&navlevel=top"
            onclick="window.open(\''.$CFG->wwwroot.'/blocks/fn_mentor/listactivities.php?id='.$course->id.'&menteeid='.$menteeid.'&show=incompleted'.'&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
            class="">' . $incompletedactivities . ' '.$incompleted.'</a>';

            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/incomplete.gif"
                                                class="icon" alt="">';
        }

        //Draft
        if ($savedactivities > 0) {
            $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=draft' . '&navlevel=top"
            onclick="window.open(\''.$CFG->wwwroot.'/blocks/fn_mentor/listactivities.php?id='.$course->id.'&menteeid='.$menteeid.'&show=draft'.'&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
            class="">' . $savedactivities . ' '.$draft.'</a>';

            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/saved.gif"
                                                class="icon" alt="">';
        }

        //Not Attempted
        $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=notattempted' . '&navlevel=top"
        onclick="window.open(\''.$CFG->wwwroot.'/blocks/fn_mentor/listactivities.php?id='.$course->id.'&menteeid='.$menteeid.'&show=notattempted'.'&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $notattemptedactivities . ' '.$notattempted.'</a>';

        $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/notattempted.gif"
                                            class="icon" alt="">';

        //Waiting for grade
        $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=waitingforgrade' . '&navlevel=top"
        onclick="window.open(\''.$CFG->wwwroot.'/blocks/fn_mentor/listactivities.php?id='.$course->id.'&menteeid='.$menteeid.'&show=waitingforgrade'.'&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $waitingforgradeactivities . ' '.$waitingforgrade.'</a>';
        $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/unmarked.gif" class="icon" alt="">';

    } else {
        $progress_data->content->items[] = "<p>Completion tracking is not enabled in this course.</p>";
        $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/warning.gif"
                                                    class="icon" alt="">';
    }

    $progress = '';

    foreach ($progress_data->content->items as $key => $value) {
        $progress .= '<div class="overview-progress-list">'.$progress_data->content->icons[$key] . $progress_data->content->items[$key] . '</div>';
    }


    ##################################


    echo '<table class="mentee-course-overview-center_table">';
    echo '<tr>';
    //1
    echo '<td valign="top" class="mentee-grey-border">';
    echo '<div class="overview-teacher">';
    echo '<table class="mentee-teacher-table">';
    //Course teachers
    $sql_techer = "SELECT u.id,
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
                      AND ctx.instanceid = ?";

    if ($teachers = $DB->get_records_sql($sql_techer, array(50, 3, $course->id))) {
        echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>'.get_string('teacher', 'block_fn_mentor').': </span></td><td valign="top">';
        foreach ($teachers as $teacher) {
            //echo '<a class="mentor-profile" target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$teacher->id.'">' . $teacher->firstname.' '.$teacher->lastname.'</a> <a href="'.$CFG->wwwroot.'/message/index.php?id='.$teacher->id.'" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />';
            $lastaccess = get_string('lastaccess').get_string('labelsep', 'langconfig'). block_fn_mentor_format_time(time() - $teacher->lastaccess);
            echo '<div><a onclick="window.open(\''.$CFG->wwwroot.'/user/profile.php?id='.$teacher->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="'.$CFG->wwwroot.'/user/profile.php?id='.$teacher->id.'">' . $teacher->firstname.' '.$teacher->lastname.'</a>
                  <a onclick="window.open(\''.$CFG->wwwroot.'/message/index.php?id='.$teacher->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="'.$CFG->wwwroot.'/user/profile.php?id='.$teacher->id.'"><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />'.
                '<span class="mentee-lastaccess">'.$lastaccess.'</span></div>';
        }

    }
    echo '</table>';
    echo '</div>';

    echo '<div class="overview-mentor">';
    echo '<table class="mentee-teacher-table">';
    if ($mentors = block_fn_mentor_get_mentors($mentee->id)) {
        echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>';
        echo (get_config('mentor', 'blockname')) ? get_config('mentor', 'blockname') : get_string('mentor', 'block_fn_mentor').': ';
        echo '</span></td><td valign="top">';
        foreach ($mentors as $mentor) {
            $lastaccess = get_string('lastaccess').get_string('labelsep', 'langconfig'). block_fn_mentor_format_time(time() - $mentor->lastaccess);
            //echo '<a class="mentor-profile" target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'">' . $mentor->firstname.' '.$mentor->lastname.'</a> <a href="'.$CFG->wwwroot.'/message/index.php?id='.$mentor->mentorid.'" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />';
            echo '<div><a onclick="window.open(\''.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'\', \'\', \'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'">' . $mentor->firstname.' '.$mentor->lastname.'</a>
                  <a onclick="window.open(\''.$CFG->wwwroot.'/message/index.php?id='.$mentor->mentorid.'\', \'\', \'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'"><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />'.
                '<span class="mentee-lastaccess">'.$lastaccess.'</span></div>';
        }
        echo '</td></tr>';
    }
    echo '</table>';
    echo '</div>';
    echo '</td>';
    //2
    echo '<td valign="top" class="mentee-blue-border">';
    echo '<div class="overview-progress blue">Progress</div>';
    echo '<div class="vertical-textd">'.$progress.'</div>';
    echo '</td>';
    //3
    echo '<td valign="top" class="mentee-blue-border">';
    echo '<div class="overview-progress blue">Grade</div>';
    echo block_fn_mentor_print_grade_summary ($course->id , $mentee->id);
    echo '</td>';

    echo '</tr>';
    echo '</table>';

    echo '</div>'; //mentee-course-overview-center_course

    //SIMPLE GRADE BOOK
    echo '<table class="simple-gradebook">';
    echo '<tr>';
    echo '<td class="blue">'.get_string('submitted_activities', 'block_fn_mentor');
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo '<td class="white mentee-blue-border" align="middle">';

    ##########################################
    $unsubmitted = 0;//Sgow only submitted
    /// Array of functions to call for grading purposes for modules.
    $mod_grades_array = array(
        'assign' => 'assign.submissions.fn.php',
        'quiz' => 'quiz.submissions.fn.php',
        'assignment' => 'assignment.submissions.fn.php',
        'forum' => 'forum.submissions.fn.php',
    );

    $cobject = new stdClass();
    $cobject->course = $course;

    // if comes from course page
    //$currentgroup = get_current_group($course->id);

    $simplegradebook = array();
    $weekactivitycount = array();
    $simplegradebook[$mentee->id]['name'] = $mentee->firstname.' '.substr($mentee->lastname,0,1).'.';

    /// Collect modules data
    //get_all_mods($course->id, $mods, $modnames, $modnamesplural, $modnamesused);
    $modnames = get_module_types_names();
    $modnamesplural = get_module_types_names(true);
    $modinfo = get_fast_modinfo($course->id);
    $mods = $modinfo->get_cms();
    $modnamesused = $modinfo->get_used_module_names();

    $mod_array = array($mods, $modnames, $modnamesplural, $modnamesused);

    $cobject->mods = &$mods;
    $cobject->modnames = &$modnames;
    $cobject->modnamesplural = &$modnamesplural;
    $cobject->modnamesused = &$modnamesused;
    $cobject->sections = &$sections;

    //FIND CURRENT WEEK
    $courseformatoptions = course_get_format($course)->get_format_options();
    $course_numsections = $courseformatoptions['numsections'];
    $courseformat = course_get_format($course)->get_format();

    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems

    $weekofseconds = 604800;
    $course_enddate = $course->startdate + ($weekofseconds * $course_numsections);

    //  Calculate the current week based on today's date and the starting date of the course.
    $currentweek = ($timenow > $course->startdate) ? (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;
    $currentweek = min($currentweek, $course_numsections);


    /// Search through all the modules, pulling out grade data
    //$sections = get_all_sections($course->id); // Sort everything the same as the course
    $sections = get_fast_modinfo($course->id)->get_section_info_all();

    //if ($view == "less"){
    //    $upto = min($currentweek+1, sizeof($sections));
    //}else{
    $upto = sizeof($sections);
    //}



    for ($i = 0; $i < $upto; $i++) {
        $numberofitem = 0;
        if (isset($sections[$i])) {   // should always be true
            $section = $sections[$i];
            if ($section->sequence) {
                $sectionmods = explode(",", $section->sequence);
                foreach ($sectionmods as $sectionmod) { //print_r($mods[$sectionmod]);die;
                    if (empty($mods[$sectionmod])) {
                        continue;
                    }

                    $mod = $mods[$sectionmod];
                    if(! isset($mod_grades_array[$mod->modname])){
                        continue;
                    }
                    /// Don't count it if you can't see it.
                    $mcontext = context_module::instance($mod->id);
                    if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $mcontext)) {
                        continue;
                    }

                    $instance = $DB->get_record($mod->modname, array("id" => $mod->instance));
                    $item = $DB->get_record('grade_items', array("itemtype" => 'mod', "itemmodule" => $mod->modname, "iteminstance" => $mod->instance));

                    $libfile = $CFG->dirroot . '/mod/' . $mod->modname . '/lib.php';
                    if (file_exists($libfile)) {
                        require_once($libfile);
                        $gradefunction = $mod->modname . "_get_user_grades";

                        if ((($mod->modname != 'forum') || (($instance->assessed > 0) && has_capability('mod/forum:rate', $mcontext))) && // Only include forums that are assessed only by teachers.
                            isset($mod_grades_array[$mod->modname])) {

                            if (function_exists($gradefunction)) {
                                ++$numberofitem;

                                $image = "<A target='_blank' HREF=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\"   TITLE=\"$instance->name\">
                                    <IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$mod->modname/pix/icon.png\" HEIGHT=16 WIDTH=16 ALT=\"$mod->modfullname\"></A>";


                                $weekactivitycount[$i]['mod'][] = $image;
                                foreach ($simplegradebook as $key => $value) {

                                    if (($mod->modname == 'quiz')||($mod->modname == 'forum')){

                                        if($grade = $gradefunction($instance, $key)){
                                            if ($item->gradepass > 0){
                                                if ($grade[$key]->rawgrade >=$item->gradepass){
                                                    $simplegradebook[$key]['grade'][$i][$mod->id] = 'marked.gif';//passed
                                                    $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                                }else{
                                                    $simplegradebook[$key]['grade'][$i][$mod->id] = 'incomplete.gif';//fail
                                                    $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                                }
                                            }else{
                                                $simplegradebook[$key]['grade'][$i][$mod->id] = 'graded_.gif';//Graded (grade-to-pass is not set)
                                                $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                            }
                                        }else{
                                            $simplegradebook[$key]['grade'][$i][$mod->id] = 'ungraded.gif';
                                            if($unsubmitted){
                                                $simplegradebook[$key]['avg'][]=array('grade'=>0, 'grademax'=>$item->grademax);
                                            }
                                        }
                                    } else if ($modstatus = block_fn_mentor_assignment_status($mod, $key, true)){

                                        switch ($modstatus) {
                                            case 'submitted':
                                                if($grade = $gradefunction($instance, $key)){
                                                    if ($item->gradepass > 0){
                                                        if ($grade[$key]->rawgrade >=$item->gradepass){
                                                            $simplegradebook[$key]['grade'][$i][$mod->id] = 'marked.gif';//passed
                                                            $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                                        }else{
                                                            $simplegradebook[$key]['grade'][$i][$mod->id] = 'incomplete.gif';//fail
                                                            $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                                        }
                                                    }else{
                                                        $simplegradebook[$key]['grade'][$i][$mod->id] = 'graded_.gif';//Graded (grade-to-pass is not set)
                                                        $simplegradebook[$key]['avg'][]=array('grade'=>$grade[$key]->rawgrade, 'grademax'=>$item->grademax);
                                                    }
                                                }else{

                                                }
                                                break;

                                            case 'saved':
                                                $simplegradebook[$key]['grade'][$i][$mod->id] = 'saved.gif';
                                                break;

                                            case 'waitinggrade':
                                                $simplegradebook[$key]['grade'][$i][$mod->id] = 'unmarked.gif';
                                                break;
                                        }
                                    } else {
                                        $simplegradebook[$key]['grade'][$i][$mod->id] = 'ungraded.gif';
                                        if($unsubmitted){
                                            $simplegradebook[$key]['avg'][]=array('grade'=>0, 'grademax'=>$item->grademax);
                                        }
                                    }

                                }

                            }

                        }
                    }
                }
            }
        }
        $weekactivitycount[$i]['numofweek'] = $numberofitem;
    } // a new Moodle nesting record? ;-)


    echo '<div class="tablecontainer">';
    $gradebook = reset($simplegradebook);

    if (isset($gradebook['grade'])) {
        //TABLE
        echo "<table class='simplegradebook'>";

        echo "<tr>";
        //echo "<th>Name</th>";
        //echo "<th>%</th>";
        foreach ($weekactivitycount as $weeknum => $weekactivity) {
            if ($weekactivity['numofweek']){
                if ($courseformat == 'topics') {
                    echo '<th colspan="'.$weekactivity['numofweek'].'">Topic-'.$weeknum.'</th>';
                } elseif ($courseformat == 'weeks') {
                    echo '<th colspan="'.$weekactivity['numofweek'].'">Week-'.$weeknum.'</th>';
                } else {
                    echo '<th colspan="'.$weekactivity['numofweek'].'">Section-'.$weeknum.'</th>';
                }

            }
        }
        echo "</tr>";

        echo "<tr>";
        //echo "<td class='mod-icon'></td>";
        //echo "<td class='mod-icon'></td>";
        foreach ($weekactivitycount as $key => $value) {
            if ($value['numofweek']){
                foreach ($value['mod'] as $imagelink) {
                    echo '<td class="mod-icon">'.$imagelink.'</td>';
                }
            }
        }
        echo "</tr>";
        $counter = 0;
        foreach ($simplegradebook as $studentid => $studentreport) {
            $counter++;
            if ($counter % 2 == 0){
                $studentClass = "even";
            } else {
                $studentClass =  "odd";
            }
            echo '<tr>';
            //echo '<td nowrap="nowrap" class="'.$studentClass.' name"><a target="_blank" href='.$CFG->wwwroot.'/grade/report/user/index.php?userid='.$studentid.'&id='.$course->id.'">'.$studentreport['name'].'</a></td>';
            //echo $studentreport['courseavg'];
            $gradetot = 0;
            $grademaxtot = 0;
            $avg = 0;

            if (isset($studentreport['avg'])){

            }else{
                echo '<td class="red"> - </td>';
            }

            foreach ($studentreport['grade'] as  $sgrades) {
                foreach ($sgrades as $sgrade) {
                    //echo '<td>'.$sgrade.'</td>';
                    echo '<td class="'.$studentClass.' icon">'.'<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/'.$sgrade.'" height="16" width="16" alt="">'.'</td>';
                }
            }
            echo '</tr>';
        }

        echo "</table>";
    } else {
        echo '<div class="mentees-error">'.get_string('no_activities', 'block_fn_mentor').'</div>';
    }



    echo "</div>";
    ##########################################
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    //NOTES
    if ($view = has_capability('block/fn_mentor:viewcoursenotes', context_system::instance()) && $allownotes) {
        echo '<table class="simple-gradebook">';
        echo '<tr>';
        echo '<td class="blue">'.get_string('notes', 'block_fn_mentor');
        echo '<div class="fz_popup_wrapper_single">
                  <a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'"
                                        onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                        class="" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/popup_icon.gif"></a>
              </div>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td class="white mentee-blue-border">';

        if ($courseid && $view) {
            $sql_notes = "SELECT p.*, c.fullname
                            FROM {post} p
                      INNER JOIN {course} c
                              ON p.courseid = c.id
                           WHERE p.module = 'notes'
                             AND p.userid = ?
                             AND p.courseid = ?
                             AND p.publishstate IN ('site', 'public')
                           UNION
                          SELECT p.*, c.fullname
                            FROM {post} p
                      INNER JOIN {course} c
                              ON p.courseid = c.id
                           WHERE p.module = 'notes'
                             AND p.userid = ?
                             AND p.courseid = ?
                             AND p.usermodified =?
                             AND p.publishstate IN ('draft')
                        ORDER BY lastmodified DESC";

            if ($notes = $DB->get_records_sql($sql_notes, array($mentee->id, $courseid, $mentee->id, $courseid, $USER->id),0, 5)) {
                foreach ($notes as $note) {
                    $ccontext = context_course::instance($note->courseid);
                    $cfullname = format_string($note->fullname, true, array('context' => $ccontext));
                    $header = '<h3 class="notestitle"><a href="' . $CFG->wwwroot . '/course/view.php?id=' . $note->courseid . '">' . $cfullname . '</a></h3>';
                    echo $header;
                    block_fn_mentor_note_print($note, NOTES_SHOW_FULL);
                }
                //Show all notes  http://localhost/moodle25/notes/index.php?user=127
                echo '<a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'"
                                    onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                    class="" >'.get_string('show_all_notes', 'block_fn_mentor').'</a>';
            } else {
                //Add a note  add_a_note
                echo '<a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'"
                                    onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                    class="" >'.get_string('add_a_note', 'block_fn_mentor').'</a>';
            }
        }


        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

}

elseif ($navpage == 'grade') {
    echo '<div class="mentee-grade_report">';
    /// basic access checks
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('nocourseid');
    }

    grade_report_user_profilereport($course, $mentee, true);

    echo '</div>';

}

elseif ($navpage == 'outline') {

    require_once($CFG->dirroot.'/report/outline/locallib.php');

    $userid   = required_param('menteeid', PARAM_INT);
    $courseid = required_param('courseid', PARAM_INT);
    $mode     = optional_param('mode', 'outline', PARAM_ALPHA);

    if ($mode !== 'complete' and $mode !== 'outline') {
        $mode = 'outline';
    }

    $user = $DB->get_record('user', array('id'=>$userid, 'deleted'=>0), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

    $coursecontext   = context_course::instance($course->id);
    $personalcontext = context_user::instance($user->id);

    $manager = get_log_manager();
    if (method_exists($manager, 'legacy_add_to_log')) {
        $manager->legacy_add_to_log($course->id, 'course', 'report outline', "report/outline/user.php?id=$user->id&course=$course->id&mode=$mode", $course->id);
    }

    $stractivityreport = get_string('activityreport');
    /*
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/report/outline/user.php', array('id'=>$user->id, 'course'=>$course->id, 'mode'=>$mode));
    $PAGE->navigation->extend_for_user($user);
    $PAGE->navigation->set_userid_for_parent_checks($user->id); // see MDL-25805 for reasons and for full commit reference for reversal when fixed.
    $PAGE->set_title("$course->shortname: $stractivityreport");
    $PAGE->set_heading($course->fullname);
    echo $OUTPUT->header();
     */

    $modinfo = get_fast_modinfo($course);
    $sections = $modinfo->get_section_info_all();
    $itemsprinted = false;

    foreach ($sections as $i => $section) {

        if ($section->uservisible) { // prevent hidden sections in user activity. Thanks to Geoff Wilbert!
            // Check the section has modules/resources, if not there is nothing to display.
            if (!empty($modinfo->sections[$i])) {
                $itemsprinted = true;
                echo '<div class="section">';
                echo '<h2>';
                echo get_section_name($course, $section);
                echo "</h2>";

                echo '<div class="content">';

                if ($mode == "outline") {
                    echo "<table cellpadding=\"4\" cellspacing=\"0\">";
                }

                foreach ($modinfo->sections[$i] as $cmid) {
                    $mod = $modinfo->cms[$cmid];

                    if (empty($mod->uservisible)) {
                        //continue;
                    }

                    $instance = $DB->get_record("$mod->modname", array("id"=>$mod->instance));
                    $libfile = "$CFG->dirroot/mod/$mod->modname/lib.php";

                    if (file_exists($libfile)) {
                        require_once($libfile);

                        switch ($mode) {
                            case "outline":
                                $user_outline = $mod->modname."_user_outline";
                                if (function_exists($user_outline)) {
                                    $output = $user_outline($course, $user, $mod, $instance);
                                    block_fn_mentor_report_outline_print_row($mod, $instance, $output);
                                }
                                break;
                            case "complete":
                                $user_complete = $mod->modname."_user_complete";
                                if (function_exists($user_complete)) {
                                    $image = $OUTPUT->pix_icon('icon', $mod->modfullname, 'mod_'.$mod->modname, array('class'=>'icon'));
                                    echo "<h4>$image $mod->modfullname: ".
                                        "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\">".
                                        format_string($instance->name,true)."</a></h4>";

                                    ob_start();

                                    echo "<ul>";
                                    $user_complete($course, $user, $mod, $instance);
                                    echo "</ul>";

                                    $output = ob_get_contents();
                                    ob_end_clean();

                                    if (str_replace(' ', '', $output) != '<ul></ul>') {
                                        echo $output;
                                    }
                                }
                                break;
                        }
                    }
                }

                if ($mode == "outline") {
                    echo "</table>";
                }
                echo '</div>';  // content
                echo '</div>';  // section
            }
        }
    }
    /*
   if (!$itemsprinted) {
       echo $OUTPUT->notification(get_string('nothingtodisplay'));
   }

   echo $OUTPUT->footer();
   */
}

elseif ($navpage == 'notes') {

    require_once($CFG->dirroot.'/notes/lib.php');

    /// retrieve parameters
    $courseid     = optional_param('courseid', SITEID, PARAM_INT);
    $userid       = optional_param('menteeid', 0, PARAM_INT);
    $filtertype   = optional_param('filtertype', '', PARAM_ALPHA);
    $filterselect = optional_param('filterselect', 0, PARAM_INT);

    if (empty($CFG->enablenotes)) {
        print_error('notesdisabled', 'notes');
    }

    $url = new moodle_url('/notes/index.php');
    if ($courseid != SITEID) {
        $url->param('course', $courseid);
    }
    if ($userid !== 0) {
        $url->param('user', $userid);
    }
    $PAGE->set_url($url);

    /// tabs compatibility
    switch($filtertype) {
        case 'course':
            $courseid = $filterselect;
            break;
        case 'site':
            $courseid = SITEID;
            break;
    }

    if (empty($courseid)) {
        $courseid = SITEID;
    }

    /// locate course information
    $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);

    /// locate user information
    if ($userid) {
        $user = $DB->get_record('user', array('id'=>$userid), '*', MUST_EXIST);
        $filtertype = 'user';
        $filterselect = $user->id;

        if ($user->deleted) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('userdeleted'));
            echo $OUTPUT->footer();
            die;
        }

    } else {
        $filtertype = 'course';
        $filterselect = $course->id;
    }

    /// require login to access notes
    //require_login($course);

    /// output HTML
    if ($course->id == SITEID) {
        $coursecontext = context_system::instance();   // SYSTEM context
    } else {
        $coursecontext = context_course::instance($course->id);   // Course context
    }
    //require_capability('moodle/notes:view', $coursecontext);
    $systemcontext = context_system::instance();   // SYSTEM context

    add_to_log($courseid, 'notes', 'view', 'index.php?course='.$courseid.'&amp;user='.$userid, 'view notes');

    $strnotes = get_string('notes', 'notes');
    /*
    if ($userid) {
        $PAGE->set_context(context_user::instance($user->id));
        $PAGE->navigation->extend_for_user($user);
    } else {
        $link = null;
        if (has_capability('moodle/course:viewparticipants', $coursecontext) || has_capability('moodle/site:viewparticipants', $systemcontext)) {
            $link = new moodle_url('/user/index.php',array('id'=>$course->id));
        }
    }

    $PAGE->set_pagelayout('course');
    $PAGE->set_title($course->shortname . ': ' . $strnotes);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
    */
    if ($userid) {
        echo $OUTPUT->heading(fullname($user).': '.$strnotes);
    } else {
        echo $OUTPUT->heading(format_string($course->shortname, true, array('context' => $coursecontext)).': '.$strnotes);
    }

    $strsitenotes = get_string('sitenotes', 'notes');
    $strcoursenotes = get_string('coursenotes', 'notes');
    $strpersonalnotes = get_string('personalnotes', 'notes');
    $straddnewnote = get_string('addnewnote', 'notes');

    echo $OUTPUT->box_start();

    if ($courseid != SITEID) {
        //echo '<a href="#sitenotes">' . $strsitenotes . '</a> | <a href="#coursenotes">' . $strcoursenotes . '</a> | <a href="#personalnotes">' . $strpersonalnotes . '</a>';
        $context = context_user::instance($userid);
        $addid = has_capability('moodle/notes:manage', $context) ? $courseid : 0;
        $view = has_capability('moodle/notes:view', $context);
        $fullname = format_string($course->fullname, true, array('context' => $context));
        note_print_notes('<a name="sitenotes"></a>' . $strsitenotes, $addid, $view, 0, $userid, NOTES_STATE_SITE, 0);
        note_print_notes('<a name="coursenotes"></a>' . $strcoursenotes. ' ('.$fullname.')', $addid, $view, $courseid, $userid, NOTES_STATE_PUBLIC, 0);
        note_print_notes('<a name="personalnotes"></a>' . $strpersonalnotes, $addid, $view, $courseid, $userid, NOTES_STATE_DRAFT, $USER->id);

    } else {  // Normal course
        //echo '<a href="#sitenotes">' . $strsitenotes . '</a> | <a href="#coursenotes">' . $strcoursenotes . '</a>';
        $view = has_capability('moodle/notes:view', context_system::instance());
        note_print_notes('<a name="sitenotes"></a>' . $strsitenotes, 0, $view, 0, $userid, NOTES_STATE_SITE, 0);
        echo '<a name="coursenotes"></a>';

        if (!empty($userid)) {
            $courses = enrol_get_users_courses($userid);
            foreach($courses as $c) {
                $ccontext = context_course::instance($c->id);
                $cfullname = format_string($c->fullname, true, array('context' => $ccontext));
                $header = '<a href="' . $CFG->wwwroot . '/course/view.php?id=' . $c->id . '">' . $cfullname . '</a>';
                if (has_capability('moodle/notes:manage', context_course::instance($c->id))) {
                    $addid = $c->id;
                } else {
                    $addid = 0;
                }
                note_print_notes($header, $addid, $view, $c->id, $userid, NOTES_STATE_PUBLIC, 0);
            }
        }
    }

    echo $OUTPUT->box_end();

}

echo '</div>'; //mentee-course-overview-center-single
echo '</div>'; //mentee-course-overview-page
echo $OUTPUT->footer();