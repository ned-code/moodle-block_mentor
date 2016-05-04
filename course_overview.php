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
 * @package    block_ned_mentor
 * @copyright  Michael Gardener <mgardener@cissq.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/ned_mentor/lib.php');
require_once($CFG->dirroot . '/notes/lib.php');

// Parameters.
$menteeid      = optional_param('menteeid', 0, PARAM_INT);
$courseid      = optional_param('courseid', 0, PARAM_INT);

require_login(null, false);

// PERMISSION.
$isadmin   = has_capability('block/ned_mentor:manageall', context_system::instance());
$ismentor  = block_ned_mentor_has_system_role($USER->id, get_config('block_ned_mentor', 'mentor_role_system'));
$isteacher = block_ned_mentor_isteacherinanycourse($USER->id);
$isstudent = block_ned_mentor_isstudentinanycourse($USER->id);

$allownotes = get_config('block_ned_mentor', 'allownotes');

if ($allownotes && $ismentor) {
    $allownotes = true;
} else if ($isadmin || $isteacher ) {
    $allownotes = true;
} else {
    $allownotes = false;
}


// Find Mentees.
$mentees = array();
if ($isadmin) {
    $mentees = block_ned_mentor_get_all_mentees();
} else if ($isteacher) {
    if ($menteesbymentor = block_ned_mentor_get_mentees_by_mentor(0, $filter = 'teacher')) {
        foreach ($menteesbymentor as $menteebymentor) {
            if ($menteebymentor['mentee']) {
                foreach ($menteebymentor['mentee'] as $key => $value) {
                    $mentees[$key] = $value;
                }
            }
        }
    }
} else if ($ismentor) {
    $mentees = block_ned_mentor_get_mentees($USER->id);
}

// Pick a mentee if not selected.
if (!$menteeid && $mentees) {
    $var = reset($mentees);
    $menteeid = $var->studentid;
}

if (($isstudent) && ($USER->id <> $menteeid)  && (!$isteacher && !$isadmin && !$ismentor)) {
    print_error('invalidpermission', 'block_ned_mentor');
}

$menteeuser = $DB->get_record('user', array('id' => $menteeid), '*', MUST_EXIST);

$title = get_string('page_title_assign_mentor', 'block_ned_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/ned_mentor/course_overview.php');

if ($pagelayout = get_config('block_ned_mentor', 'pagelayout')) {
    $PAGE->set_pagelayout($pagelayout);
} else {
    $PAGE->set_pagelayout('course');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);
$PAGE->requires->css('/blocks/ned_mentor/css/styles.css');

$PAGE->navbar->add(get_string('pluginname', 'block_ned_mentor'),
    new moodle_url('/blocks/ned_mentor/course_overview.php', array('menteeid' => $menteeid))
);

echo $OUTPUT->header();

echo '<div id="mentee-course-overview-page">';
// LEFT.
echo '<div id="mentee-course-overview-left">';

$lastaccess = '';
if ($menteeuser->lastaccess) {
    $lastaccess .= get_string('lastaccess').get_string('labelsep', 'langconfig').
        block_ned_mentor_format_time(time() - $menteeuser->lastaccess);
} else {
    $lastaccess .= get_string('lastaccess').get_string('labelsep', 'langconfig').get_string('never');
}

$studentmenu = array();
$studentmenuurl = array();


if($showallstudents = get_config('block_ned_mentor', 'showallstudents')) {
    $studentmenuurl[0] = $CFG->wwwroot . '/blocks/ned_mentor/all_students.php';
    $studentmenu[$studentmenuurl[0]] = get_string('allstudents', 'block_ned_mentor');
}

if ($mentees) {
    foreach ($mentees as $mentee) {
        $studentmenuurl[$mentee->studentid] = $CFG->wwwroot.'/blocks/ned_mentor/course_overview.php?menteeid='.$mentee->studentid;
        $studentmenu[$studentmenuurl[$mentee->studentid]] = $mentee->firstname .' '.$mentee->lastname;
    }
}

$studentmenuhtml = '';

if ((!$isstudent) || ($isadmin || $ismentor  || $isteacher)) {
    $studentmenuhtml = html_writer::tag('form', '<span>'.get_string('select_student', 'block_ned_mentor').'</span>'.
        html_writer::select(
            $studentmenu, 'sortby', $studentmenuurl[$menteeid], null,
            array('onChange' => 'location=document.jump1.sortby.options[document.jump1.sortby.selectedIndex].value;')
        ),
        array('id' => 'studentFilterForm', 'name' => 'jump1')
    );

    $studentmenuhtml = '<div class="mentee-course-overview-block-filter"> '.$studentmenuhtml.' </div>';
}

// BLOCK-1.
echo $studentmenuhtml.'
      <div class="mentee-course-overview-block">
          <div class="mentee-course-overview-block-title">
              '.get_string('student', 'block_ned_mentor').'
          </div>
          <div class="mentee-course-overview-block-content">'.
    $OUTPUT->container($OUTPUT->user_picture($menteeuser, array('courseid' => $COURSE->id)), "userimage").
    $OUTPUT->container('<a  href="'.$CFG->wwwroot.'/user/view.php?id='.$menteeuser->id.'&course=1" '.
        'onclick="window.open(\''.$CFG->wwwroot.'/user/view.php?id='.$menteeuser->id.
        '&course=1\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,'.
        'directories=no,scrollbars=yes,resizable=yes\'); return false;" class="" >'.fullname($menteeuser, true).'</a>'.
        '&nbsp;&nbsp;<a href="'.$CFG->wwwroot.'/message/index.php?id='.$menteeuser->id.'" ><img src="'.$CFG->wwwroot.
        '/blocks/ned_mentor/pix/email.png"></a>', "userfullname"
    ).
    '<span class="mentee-lastaccess">'.$lastaccess.'</span>' .
    '</div></div>';

// COURSES.
if (!$enrolledcourses = enrol_get_all_users_courses($menteeid, false, 'id,fullname,shortname', 'fullname ASC')) {
    $enrolledcourses = array();
}

$filtercourses = array();

if ($configcategory = get_config('block_ned_mentor', 'category')) {

    $selectedcategories = explode(',', $configcategory);

    foreach ($selectedcategories as $categoryid) {

        if ($parentcatcourses = $DB->get_records('course', array('category' => $categoryid))) {
            foreach ($parentcatcourses as $catcourse) {
                $filtercourses[] = $catcourse->id;
            }
        }
        if ($categorystructure = block_ned_mentor_get_course_category_tree($categoryid)) {
            foreach ($categorystructure as $category) {

                if ($category->courses) {
                    foreach ($category->courses as $subcatcourse) {
                        $filtercourses[] = $subcatcourse->id;
                    }
                }
                if ($category->categories) {
                    foreach ($category->categories as $subcategory) {
                        block_ned_mentor_get_selected_courses($subcategory, $filtercourses);
                    }
                }
            }
        }
    }
}

if ($configcourse = get_config('block_ned_mentor', 'course')) {
    $selectedcourses = explode(',', $configcourse);
    $filtercourses = array_merge($filtercourses, $selectedcourses);
}

if ($enrolledcourses && $filtercourses) {
    foreach ($enrolledcourses as $key => $enrolledcourse) {
        if (!in_array($enrolledcourse->id, $filtercourses)) {
            unset($enrolledcourses[$key]);
        }
    }
}

$courseids = implode(",", array_keys($enrolledcourses));

$courselist = "";

if ($courseid == 0) {
    $courselist .= '<div class="allcourses active">'.
        '<a href="'.$CFG->wwwroot.'/blocks/ned_mentor/course_overview.php?menteeid='.$menteeid.'&courseid=0">'.
        get_string('allcourses', 'block_ned_mentor').'</a></div>';
} else {
    $courselist .= '<div class="allcourses">'.
        '<a href="'.$CFG->wwwroot.'/blocks/ned_mentor/course_overview.php?menteeid='.$menteeid.'&courseid=0">'.
        get_string('allcourses', 'block_ned_mentor').'</a></div>';
}
foreach ($enrolledcourses as $enrolledcourse) {
    if ($courseid == $enrolledcourse->id) {
        $courselist .= '<div class="courselist active">
            <img class="mentees-course-bullet" src="'.$CFG->wwwroot.'/blocks/ned_mentor/pix/b.gif">'.
            '<a href="'.$CFG->wwwroot.'/blocks/ned_mentor/course_overview_single.php?menteeid='.
            $menteeid.'&courseid='.$enrolledcourse->id.'">'.$enrolledcourse->fullname.'</a></div>';
    } else {
        $courselist .= '<div class="courselist">
            <img class="mentees-course-bullet" src="'.$CFG->wwwroot.'/blocks/ned_mentor/pix/b.gif">'.
            '<a href="'.$CFG->wwwroot.'/blocks/ned_mentor/course_overview_single.php?menteeid='.
            $menteeid.'&courseid='.$enrolledcourse->id.'">'.$enrolledcourse->fullname.'</a></div>';
    }
}

echo '<div class="mentee-course-overview-block">
          <div class="mentee-course-overview-block-title">
              '.get_string('courses', 'block_ned_mentor').'
          </div>
          <div class="mentee-course-overview-block-content">

              '.$courselist.'
          </div>
      </div>';

// NOTES.
if ($view = has_capability('block/ned_mentor:viewcoursenotes', context_system::instance()) && $allownotes) {
    echo '<div class="mentee-course-overview-block">
              <div class="mentee-course-overview-block-title">
                  '.get_string('notes', 'block_ned_mentor').'
              </div>
              <div class="fz_popup_wrapper">
                  <a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$menteeuser->id.'"
                  onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.
                  $menteeuser->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,'.
                  'status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;"
                                        class="" ><img src="'.$CFG->wwwroot.'/blocks/ned_mentor/pix/popup_icon.gif"></a>
              </div>
              <div class="mentee-course-overview-block-content">';



    // COURSE NOTES.
    if ($courseids && $view) {
        $sqlnotes = "SELECT p.*, c.fullname
                                FROM {post} p
                          INNER JOIN {course} c
                                  ON p.courseid = c.id
                               WHERE p.module = 'notes'
                                 AND p.userid = ?
                                 AND p.courseid IN ($courseids)
                                 AND p.publishstate IN ('site', 'public')
                            ORDER BY p.lastmodified DESC";

        if ($notes = $DB->get_records_sql($sqlnotes, array($menteeuser->id), 0, 3)) {
            foreach ($notes as $note) {
                $ccontext = context_course::instance($note->courseid);
                $cfullname = format_string($note->fullname, true, array('context' => $ccontext));
                $header = '<h3 class="notestitle"><a href="' . $CFG->wwwroot .
                    '/course/view.php?id=' . $note->courseid . '">' . $cfullname . '</a></h3>';
                echo $header;
                block_ned_mentor_note_print($note, NOTES_SHOW_FULL);
            }
            // Show all notes.
            echo '<a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$menteeuser->id.
                '"onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.
                $menteeuser->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,'.
                'directories=no,scrollbars=yes,resizable=yes\'); return false;" class="" >'.
                get_string('show_all_notes', 'block_ned_mentor').'</a>';
        } else {
            // Add a note.
            echo '<a  href="'.$CFG->wwwroot.'/notes/index.php?user='.$menteeuser->id.
                '"onclick="window.open(\''.$CFG->wwwroot.'/notes/index.php?user='.
                $menteeuser->id.'\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,'.
                'directories=no,scrollbars=yes,resizable=yes\'); return false;" class="" >'.
                get_string('add_a_note', 'block_ned_mentor').'</a>';
        }
    }


    echo     '</div>
          </div>';
}
echo '</div>';

// CENTER.
echo '<div id="mentee-course-overview-center">';

if ($enrolledcourses) {

    foreach ($enrolledcourses as $enrolledcourse) {

        if ($courseid && ($courseid <> $enrolledcourse->id)) {
            continue;
        }

        $course = $DB->get_record('course', array('id' => $enrolledcourse->id), '*', MUST_EXIST);

        echo '<div class="mentee-course-overview-center_course">';

        $context = context_course::instance($course->id);

        $progressdata = block_ned_mentor_activity_progress($course, $menteeid);

        $progresshtml = '';

        foreach ($progressdata->content->items as $key => $value) {
            $progresshtml .= '<div class="overview-progress-list">' . $progressdata->content->icons[$key] .
                $progressdata->content->items[$key] . '</div>';
        }

        echo '<table class="mentee-course-overview-center_table block">';
        echo '<tr>';

        echo '<td valign="top" class="mentee-grey-border">';
        echo '<div class="overview-course coursetitle"><a  href="' . $CFG->wwwroot . '/course/view.php?id=' .
            $enrolledcourse->id . '" onclick="window.open(\'' . $CFG->wwwroot . '/course/view.php?id=' .
            $enrolledcourse->id . '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,'.
            'copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" class="" >' .
            $enrolledcourse->fullname . '</a></div>';

        echo '<div class="overview-teacher">';
        echo '<table class="mentee-teacher-table">';
        // Course teachers.
        $sqltecher = "SELECT u.id,
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

        if ($teachers = $DB->get_records_sql($sqltecher, array(50, 3, $course->id))) {
            echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>' .
                get_string('teacher', 'block_ned_mentor') . ': </span></td><td valign="top">';

            foreach ($teachers as $teacher) {
                $lastaccess = get_string('lastaccess') . get_string('labelsep', 'langconfig') .
                    block_ned_mentor_format_time(time() - $teacher->lastaccess);

                echo '<div><a onclick="window.open(\'' . $CFG->wwwroot . '/user/profile.php?id=' .
                    $teacher->id . '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,'.
                    'status=no,directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot .
                    '/user/profile.php?id=' . $teacher->id . '">' . $teacher->firstname . ' ' . $teacher->lastname .
                    '</a><a onclick="window.open(\'' . $CFG->wwwroot . '/message/index.php?id=' . $teacher->id .
                    '\', \'\', \'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,'.
                    'directories=no,scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot .
                    '/user/profile.php?id=' . $teacher->id . '"><img src="' . $CFG->wwwroot .
                    '/blocks/ned_mentor/pix/email.png"></a><br />' .
                    '<span class="mentee-lastaccess">' . $lastaccess . '</span></div>';
            }

        }
        echo '</table>';
        echo '</div>';

        echo '<div class="overview-mentor">';
        echo '<table class="mentee-teacher-table">';
        if ($mentors = block_ned_mentor_get_mentors($menteeuser->id)) {
            echo '<tr><td class="mentee-teacher-table-label" valign="top"><span>';
            echo (get_config('mentor', 'blockname')) ? get_config('mentor', 'blockname') : get_string('mentor',
                    'block_ned_mentor') . ': ';
            echo '</span></td><td valign="top">';
            foreach ($mentors as $mentor) {
                $lastaccess = get_string('lastaccess') . get_string('labelsep', 'langconfig') .
                    block_ned_mentor_format_time(time() - $mentor->lastaccess);

                echo '<div><a onclick="window.open(\'' . $CFG->wwwroot . '/user/profile.php?id=' . $mentor->mentorid .
                    '\', \'\', \'width=620,height=450,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,',
                    'scrollbars=yes,resizable=yes\'); return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' .
                    $mentor->mentorid . '">' . $mentor->firstname . ' ' . $mentor->lastname . '</a><a onclick="window.open(\'' .
                    $CFG->wwwroot . '/message/index.php?id=' . $mentor->mentorid . '\', \'\', \'width=620,height=450,toolbar=no,',
                    'location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes\'); ',
                    'return false;" href="' . $CFG->wwwroot . '/user/profile.php?id=' . $mentor->mentorid .
                    '"><img src="' . $CFG->wwwroot . '/blocks/ned_mentor/pix/email.png"></a><br />' .
                    '<span class="mentee-lastaccess">' . $lastaccess . '</span></div>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
        echo '</div>';
        echo '</td>';
        // Progress.
        echo '<td valign="top" class="mentee-blue-border">';
        echo '<div class="overview-progress blue">'.get_string('progress', 'block_ned_mentor').'</div>';
        echo '<div class="vertical-textd">' . $progresshtml . '</div>';
        echo '</td>';
        // Grade.
        echo '<td valign="top" class="mentee-blue-border">';
        echo '<div class="overview-progress blue">'.get_string('grade', 'block_ned_mentor').'</div>';
        echo block_ned_mentor_print_grade_summary($course->id, $menteeuser->id);
        echo '</td>';

        echo '</tr>';
        echo '</table>';

        echo '</div>'; // Mentee course overview center course.
    }

} else {
    echo get_string('notenrolledanycourse', 'block_ned_mentor');
}

echo '</div>'; // Mentee course overview center.

echo '</div>'; // Mentee course overview page.

echo $OUTPUT->footer();