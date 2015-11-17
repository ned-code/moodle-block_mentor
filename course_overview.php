
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
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot . '/notes/lib.php');

// Parameters.
$menteeid      = optional_param('menteeid', 0, PARAM_INT);
$courseid      = optional_param('courseid', 0, PARAM_INT);

require_login();

// PERMISSION.
$isadmin   = is_siteadmin($USER->id);
$ismentor  = block_fn_mentor_has_system_role($USER->id, get_config('block_fn_mentor', 'mentor_role_system'));
$isteacher = block_fn_mentor_isteacherinanycourse($USER->id);
$isstudent = block_fn_mentor_isstudentinanycourse($USER->id);

$allownotes = get_config('block_fn_mentor', 'allownotes');

if ($allownotes && $ismentor) {
    $allownotes = true;
} elseif ($isadmin || $isteacher ) {
    $allownotes = true;
} else {
    $allownotes = false;
}


//Find Mentess
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

//Pick a mentee if not selected
if (!$menteeid && $mentees) {
    $var = reset($mentees);
    $menteeid = $var->studentid;
}

if (($isstudent) && ($USER->id <> $menteeid)  && (!$isteacher && !$isadmin && !$ismentor)) {
    print_error('invalidpermission', 'block_fn_mentor');
}

$mentee = $DB->get_record('user', array('id'=>$menteeid), '*', MUST_EXIST);

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/fn_mentor/course_overview.php');

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


if ($mentees){
    foreach ($mentees as $_mentee) {
        $student_menu_url[$_mentee->studentid] = $CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$_mentee->studentid;
        $student_menu[$student_menu_url[$_mentee->studentid]] = $_mentee->firstname .' '.$_mentee->lastname;
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

//COURSES
if (!$enrolled_courses = enrol_get_all_users_courses($menteeid, 'id,fullname,shortname', NULL, 'fullname ASC')) {
    $enrolled_courses = array();
}

$courseids = implode(",", array_keys($enrolled_courses));

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

//NOTES
if ($view = has_capability('block/fn_mentor:viewcoursenotes', context_system::instance()) && $allownotes) {
    echo '<div class="mentee-course-overview-block">
              <div class="mentee-course-overview-block-title">
                  '.get_string('notes', 'block_fn_mentor').'
              </div>
              <div class="fz_popup_wrapper">
                  <a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'"
                                        onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.$mentee->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                        class="" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/popup_icon.gif"></a>
              </div>
              <div class="mentee-course-overview-block-content">';



            //COURSE NOTES
            if ($courseids && $view) {
                $sql_notes = "SELECT p.*, c.fullname
                                FROM {post} p
                          INNER JOIN {course} c
                                  ON p.courseid = c.id
                               WHERE p.module = 'notes'
                                 AND p.userid = ?
                                 AND p.courseid IN ($courseids)
                                 AND p.publishstate IN ('site', 'public')
                            ORDER BY p.lastmodified DESC";

                if ($notes = $DB->get_records_sql($sql_notes, array($mentee->id),0, 3)) {
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


    echo     '</div>
          </div>';
}
echo '</div>';

//CENTER
echo '<div id="mentee-course-overview-center">';

if ($enrolled_courses) {

    foreach ($enrolled_courses as $enrolled_course) {

        if ($courseid && ($courseid <> $enrolled_course->id)) {
            continue;
        }

        $course = $DB->get_record('course', array('id' => $enrolled_course->id), '*', MUST_EXIST);

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
                /*
                COMPLETION_INCOMPLETE 0
                COMPLETION_COMPLETE 1
                COMPLETION_COMPLETE_PASS 2
                COMPLETION_COMPLETE_FAIL 3
                */
                $completionstate = $data->completionstate;
                $assignment_status = block_fn_mentor_assignment_status($activity, $menteeid);

                //echo "$activity->name | $completionstate | $assignment_status <br>\n";
                //COMPLETION_INCOMPLETE
                if ($completionstate == 0) {
                    //Show activity as complete when conditions are met
                    if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignment_status
                    ) {

                        if (isset($assignment_status)) {
                            if ($assignment_status == 'saved') {
                                $savedactivities++;
                            } else if ($assignment_status == 'submitted') {
                                $notattemptedactivities++;
                            } else if ($assignment_status == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
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
                        && $assignment_status
                    ) {

                        if (isset($assignment_status)) {
                            if ($assignment_status == 'saved') {
                                $savedactivities++;
                            } else if ($assignment_status == 'submitted') {
                                $completedactivities++;
                            } else if ($assignment_status == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
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
                        && $assignment_status
                    ) {

                        if (isset($assignment_status)) {
                            if ($assignment_status == 'saved') {
                                $savedactivities++;
                            } else if ($assignment_status == 'submitted') {
                                $incompletedactivities++;
                            } else if ($assignment_status == 'waitinggrade') {
                                $waitingforgradeactivities++;
                            }
                        } else {
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
        onclick="window.open(\'' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=completed' . '&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $completedactivities . ' ' . $completed . '</a>';

            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/completed.gif"
                                            class="icon" alt="">';

            //Incomplete
            if ($num_grade_to_pass && $incompletedactivities > 0) {
                $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=incompleted' . '&navlevel=top"
            onclick="window.open(\'' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=incompleted' . '&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
            class="">' . $incompletedactivities . ' ' . $incompleted . '</a>';

                $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/incomplete.gif"
                                                class="icon" alt="">';
            }

            //Draft
            if ($savedactivities > 0) {
                $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=draft' . '&navlevel=top"
            onclick="window.open(\'' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=draft' . '&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
            class="">' . $savedactivities . ' ' . $draft . '</a>';

                $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/saved.gif"
                                                class="icon" alt="">';
            }

            //Not Attempted
            $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=notattempted' . '&navlevel=top"
        onclick="window.open(\'' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=notattempted' . '&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $notattemptedactivities . ' ' . $notattempted . '</a>';

            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/notattempted.gif"
                                            class="icon" alt="">';

            //Waiting for grade
            $progress_data->content->items[] = '<a  href="' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=waitingforgrade' . '&navlevel=top"
        onclick="window.open(\'' . $CFG->wwwroot . '/blocks/fn_mentor/listactivities.php?id=' . $course->id . '&menteeid=' . $menteeid . '&show=waitingforgrade' . '&navlevel=top\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
        class="">' . $waitingforgradeactivities . ' ' . $waitingforgrade . '</a>';
            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/unmarked.gif" class="icon" alt="">';

        } else {
            $progress_data->content->items[] = "<p>Completion tracking is not enabled in this course.</p>";
            $progress_data->content->icons[] = '<img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/warning.gif" class="icon" alt="">';
        }

        $progress_html = '';

        foreach ($progress_data->content->items as $key => $value) {
            $progress_html .= '<div class="overview-progress-list">' . $progress_data->content->icons[$key] . $progress_data->content->items[$key] . '</div>';
        }


        ##################################


        echo '<table class="mentee-course-overview-center_table block">';
        echo '<tr>';
        //1
        echo '<td valign="top" class="mentee-grey-border">';
        echo '<div class="overview-course coursetitle"><a  href="' . $CFG->wwwroot . '/course/view.php?id=' . $enrolled_course->id . '"
    onclick="window.open(\'' . $CFG->wwwroot . '/course/view.php?id=' . $enrolled_course->id . '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
    class="" >' . $enrolled_course->fullname . '</a></div>';
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
            echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>' . get_string('teacher', 'block_fn_mentor') . ': </span></td><td valign="top">';
            foreach ($teachers as $teacher) {
                //echo '<a class="mentor-profile" target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$teacher->id.'">' . $teacher->firstname.' '.$teacher->lastname.'</a> <a href="'.$CFG->wwwroot.'/message/index.php?id='.$teacher->id.'" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />';
                $lastaccess = get_string('lastaccess') . get_string('labelsep', 'langconfig') . block_fn_mentor_format_time(time() - $teacher->lastaccess);
                echo '<div><a onclick="window.open(\'' . $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id . '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id . '">' . $teacher->firstname . ' ' . $teacher->lastname . '</a>
                  <a onclick="window.open(\'' . $CFG->wwwroot . '/message/index.php?id=' . $teacher->id . '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $teacher->id . '"><img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/email.png"></a><br />' .
                    '<span class="mentee-lastaccess">' . $lastaccess . '</span></div>';
            }

        }
        echo '</table>';
        echo '</div>';

        echo '<div class="overview-mentor">';
        echo '<table class="mentee-teacher-table">';
        if ($mentors = block_fn_mentor_get_mentors($mentee->id)) {
            echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>';
            echo (get_config('mentor', 'blockname')) ? get_config('mentor', 'blockname') : get_string('mentor', 'block_fn_mentor') . ': ';
            echo '</span></td><td valign="top">';
            foreach ($mentors as $mentor) {
                $lastaccess = get_string('lastaccess') . get_string('labelsep', 'langconfig') . block_fn_mentor_format_time(time() - $mentor->lastaccess);
                //echo '<a class="mentor-profile" target="_blank" href="'.$CFG->wwwroot.'/user/profile.php?id='.$mentor->mentorid.'">' . $mentor->firstname.' '.$mentor->lastname.'</a> <a href="'.$CFG->wwwroot.'/message/index.php?id='.$mentor->mentorid.'" ><img src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/email.png"></a><br />';
                echo '<div><a onclick="window.open(\'' . $CFG->wwwroot . '/user/profile.php?id=' . $mentor->mentorid . '\', \'\', \'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $mentor->mentorid . '">' . $mentor->firstname . ' ' . $mentor->lastname . '</a>
                  <a onclick="window.open(\'' . $CFG->wwwroot . '/message/index.php?id=' . $mentor->mentorid . '\', \'\', \'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $mentor->mentorid . '"><img src="' . $CFG->wwwroot . '/blocks/fn_mentor/pix/email.png"></a><br />' .
                    '<span class="mentee-lastaccess">' . $lastaccess . '</span></div>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '</td>';
        //2
        echo '<td valign="top" class="mentee-blue-border">';
        echo '<div class="overview-progress blue">Progress</div>';
        echo '<div class="vertical-textd">' . $progress_html . '</div>';
        echo '</td>';
        //3
        echo '<td valign="top" class="mentee-blue-border">';
        echo '<div class="overview-progress blue">Grade</div>';
        echo block_fn_mentor_print_grade_summary($course->id, $mentee->id);
        echo '</td>';

        echo '</tr>';
        echo '</table>';

        echo '</div>'; //mentee-course-overview-center_course
    }

} else {
    echo get_string('notenrolledanycourse', 'block_fn_mentor');
}

echo '</div>'; // mentee-course-overview-center.

echo '</div>'; // mentee-course-overview-page.

echo $OUTPUT->footer();