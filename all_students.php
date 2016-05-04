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
require_once($CFG->dirroot . '/lib/coursecatlib.php');
require_once($CFG->dirroot . '/notes/lib.php');

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$completionstatus = optional_param('completionstatus', 0, PARAM_INT);
$gradepassing = optional_param('gradepassing', 0, PARAM_INT);

require_login(null, false);

// PERMISSION.
$isadmin   = has_capability('block/ned_mentor:manageall', context_system::instance());
$ismentor  = block_ned_mentor_has_system_role($USER->id, get_config('block_ned_mentor', 'mentor_role_system'));
$isteacher = block_ned_mentor_isteacherinanycourse($USER->id);
$isstudent = block_ned_mentor_isstudentinanycourse($USER->id);

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

// Category filter.
$categorycourses = false;
if ($categoryid) {
    $categorycourses = coursecat::get($categoryid)->get_courses(array('recursive' => true, 'coursecontacts' => true));
}

// Group filter.
$groupmembers = false;
if ($groupid) {
    $selectedgroup = groups_get_group($groupid, 'id, courseid, name, enrolmentkey', MUST_EXIST);
    $groupmembers = groups_get_members($groupid, $fields = 'u.*', $sort = 'lastname ASC');
}
if ($mentees) {
    foreach ($mentees as $key => $mentee) {
        if ($groupmembers !== false) {
            if (!isset($groupmembers[$mentee->studentid])) {
                unset($mentees[$key]);
            }
        }
    }
}

if (($isstudent) && ($USER->id <> $menteeid)  && (!$isteacher && !$isadmin && !$ismentor)) {
    print_error('invalidpermission', 'block_ned_mentor');
}

$title = get_string('page_title_assign_mentor', 'block_ned_mentor');
$heading = $SITE->fullname;

$PAGE->set_url('/blocks/ned_mentor/all_students.php');
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
    new moodle_url('/blocks/ned_mentor/all_students.php')
);

// Settings.
$passinggrade = get_config('block_ned_mentor', 'passinggrade');
$showgradestatus = get_config('block_ned_mentor', 'showgradestatus');
$showsitegroups = get_config('block_ned_mentor', 'showsitegroups');

echo $OUTPUT->header();
$PAGE->requires->js('/blocks/ned_mentor/textrotate.js');
$PAGE->requires->js_function_call('textrotate_init', null, true);

echo html_writer::start_div('', array('id' => 'mentee-course-overview-page'));

// Left.
echo html_writer::start_div('', array('id' => 'mentee-course-overview-left'));
$studentmenu = array();
$studentmenuurl = array();

if ($showallstudents = get_config('block_ned_mentor', 'showallstudents')) {
    $studentmenuurl[0] = $CFG->wwwroot . '/blocks/ned_mentor/all_students.php';
    $studentmenu[$studentmenuurl[0]] = get_string('allstudents', 'block_ned_mentor');
}

$courses = array();

if ($mentees) {
    foreach ($mentees as $mentee) {
        $studentmenuurl[$mentee->studentid] = $CFG->wwwroot.'/blocks/ned_mentor/course_overview.php?menteeid='.$mentee->studentid;
        $studentmenu[$studentmenuurl[$mentee->studentid]] = $mentee->firstname .' '.$mentee->lastname;

        if ($enrolledcourses = enrol_get_all_users_courses($mentee->studentid, false , 'id,fullname,shortname', 'fullname ASC')) {
            foreach ($enrolledcourses as $key => $enrolledcourse) {
                $courses[$key] = $DB->get_record('course', array('id' => $enrolledcourse->id));
            }
        }
    }
}

$studentmenuhtml = '';
if ((!$isstudent) || ($isadmin || $ismentor  || $isteacher)) {
    $studentmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('select_student', 'block_ned_mentor')).
        html_writer::select(
            $studentmenu, 'sortby', $studentmenuurl[0], null,
            array('onChange' => 'location=document.jump1.sortby.options[document.jump1.sortby.selectedIndex].value;')
        ),
        array('id' => 'studentFilterForm', 'name' => 'jump1')
    );
    $studentmenuhtml = html_writer::div($studentmenuhtml, 'mentee-course-overview-block-filter');
}

// Course category.
$categorymenu = array();
$categorymenuurl = array();

$categoryurl = new moodle_url('/blocks/ned_mentor/all_students.php',
    array(
        'categoryid' => 0,
        'groupid' => $groupid,
        'completionstatus' => $completionstatus,
        'gradepassing' => $gradepassing
    )
);
$categorymenuurl[0] = $categoryurl->out(false);
$categorymenu[$categorymenuurl[0]] = get_string('all', 'block_ned_mentor');

if ($categories = $DB->get_records('course_categories', array('visible' => 1))) {
    foreach ($categories as $category) {
        $categoryurl->param('categoryid', $category->id);
        $categorymenuurl[$category->id] = $categoryurl->out(false);
        $categorymenu[$categorymenuurl[$category->id]] = $category->name;
    }
}
$categorymenuhtml = html_writer::tag('form',
    html_writer::span(get_string('category', 'block_ned_mentor')).
    html_writer::select(
        $categorymenu, 'category', $categorymenuurl[$categoryid], null,
        array('onChange' => 'location=document.jumpcategory.category.options[document.jumpcategory.category.selectedIndex].value;')
    ),
    array('id' => 'categorymenuform', 'name' => 'jumpcategory')
);
$categorymenuhtml = html_writer::div($categorymenuhtml, 'mentee-course-overview-block-filter');


// Group menu.
$groupmenuhtml = '';
if ($showsitegroups) {
    $groupmenu = array();
    $groupmenuurl = array();

    $groupurl = new moodle_url('/blocks/ned_mentor/all_students.php',
        array(
            'categoryid' => $categoryid,
            'groupid' => 0,
            'completionstatus' => $completionstatus,
            'gradepassing' => $gradepassing
        )
    );
    $groupmenuurl[0] = $groupurl->out(false);
    $groupmenu[$groupmenuurl[0]] = get_string('allgroups', 'block_ned_mentor');

    if ($groups = $DB->get_records('groups', array('courseid' => SITEID))) {
        foreach ($groups as $group) {
            $groupurl->param('groupid', $group->id);
            $groupmenuurl[$group->id] = $groupurl->out(false);
            $groupmenu[$groupmenuurl[$group->id]] = $group->name;
        }
    }
    $groupmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('allgroups', 'block_ned_mentor')) .
        html_writer::select(
            $groupmenu, 'group', $groupmenuurl[$groupid], null,
            array('onChange' => 'location=document.jumpgroup.group.options[document.jumpgroup.group.selectedIndex].value;')
        ),
        array('id' => 'groupmenuform', 'name' => 'jumpgroup')
    );
    $groupmenuhtml = html_writer::div($groupmenuhtml, 'mentee-course-overview-block-filter');
}

// Completion statuses.
$completionstatusmenu = array();
$completionstatusmenuurl = array();

$completionstatusurl = new moodle_url('/blocks/ned_mentor/all_students.php',
    array(
        'categoryid' => $categoryid,
        'groupid' => $groupid,
        'completionstatus' => 0,
        'gradepassing' => $gradepassing
    )
);

$completionstatuses = array(
    0 => 'All',
    1 => '0%',
    2 => '1-49%',
    3 => '50-74%',
    4 => '74-99%',
    5 => '100%'
);
if ($completionstatuses) {
    foreach ($completionstatuses as $key => $completionstatuslabel) {
        $completionstatusurl->param('completionstatus', $key);
        $completionstatusmenuurl[$key] = $completionstatusurl->out(false);
        $completionstatusmenu[$completionstatusmenuurl[$key]] = $completionstatuslabel;
    }
}
$completionstatusmenuhtml = html_writer::tag('form',
    html_writer::span(get_string('completionstatus', 'block_ned_mentor')) .
    html_writer::select(
        $completionstatusmenu, 'completionstatus', $completionstatusmenuurl[$completionstatus], null,
        array('onChange' => 'location=document.jumpcompletionstatus.completionstatus'.
            '.options[document.jumpcompletionstatus.completionstatus.selectedIndex].value;'
        )
    ),
    array('id' => 'completionstatusmenuform', 'name' => 'jumpcompletionstatus')
);
$completionstatusmenuhtml = html_writer::div($completionstatusmenuhtml, 'mentee-course-overview-block-filter');


// Grade passing.
$gradepassingmenuhtml = '';
if ($showgradestatus) {
    $gradepassingmenu = array();
    $gradepassingmenuurl = array();

    $gradepassingurl = new moodle_url('/blocks/ned_mentor/all_students.php',
        array(
            'categoryid' => $categoryid,
            'groupid' => $groupid,
            'completionstatus' => $completionstatus,
            'gradepassing' => 0
        )
    );

    $gradepassinoptions = array(
        0 => 'All',
        1 => 'Passing',
        2 => 'Failing'
    );
    if ($gradepassinoptions) {
        foreach ($gradepassinoptions as $key => $gradepassinglabel) {
            $gradepassingurl->param('gradepassing', $key);
            $gradepassingmenuurl[$key] = $gradepassingurl->out(false);
            $gradepassingmenu[$gradepassingmenuurl[$key]] = $gradepassinglabel;
        }
    }
    $gradepassingmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('grade', 'block_ned_mentor')) .
        html_writer::select(
            $gradepassingmenu, 'gradepassing', $gradepassingmenuurl[$gradepassing], null,
            array('onChange' => 'location=document.jumpgradepassing.gradepassing'.
                '.options[document.jumpgradepassing.gradepassing.selectedIndex].value;'
            )
        ),
        array('id' => 'gradepassingmenuform', 'name' => 'jumpgradepassing')
    );
    $gradepassingmenuhtml = html_writer::div($gradepassingmenuhtml, 'mentee-course-overview-block-filter');
}

// Block.
echo $studentmenuhtml;
echo html_writer::div(
    html_writer::div(
        get_string('filter', 'block_ned_mentor'),
        'mentee-course-overview-block-title'
    ).
    html_writer::div(
        $categorymenuhtml.
        $groupmenuhtml.
        $completionstatusmenuhtml.
        $gradepassingmenuhtml,
        'mentee-course-overview-block-content'
    ),
    'mentee-course-overview-block'
);
echo html_writer::end_div(); // Mentee course overview left.



$table = new html_table();
$table->attributes = array('class' => 'course-completion');
$table->head = array('');

if ($groupid) {
    $table->head[] = '';
}

foreach ($courses as $course) {
    if ($categorycourses !== false) {
        if (!isset($categorycourses[$course->id])) {
            continue;
        }
    }
    $headcell = new html_table_cell("<a onclick=\"window.open('".
        $CFG->wwwroot."/course/view.php?id=".$course->id.
        "', '', 'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,".
        "directories=no,scrollbars=yes,resizable=yes'); return false;\" href=\"".
        $CFG->wwwroot."/course/view.php?id=".$course->id."\">".
        html_writer::span($course->shortname, 'completion-activityname')."</a>"
    );
    $headcell->attributes = array('class' => 'header header-grey');
    $table->head[] = $headcell;
}
foreach ($mentees as $mentee) {
    $row = new html_table_row();
    $row->cells[] = new html_table_cell(
        html_writer::link(
            new moodle_url('/blocks/ned_mentor/course_overview.php', array('menteeid' => $mentee->studentid)),
            $mentee->firstname.' '.$mentee->lastname
        )
    );

    if ($groupid) {
        $row->cells[] = new html_table_cell($selectedgroup->name);
    }
    $completionstatusecounter = array(0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);
    $gradepassingcounter = array(0 => 0, 1 => 0, 2 => 0);
    foreach ($courses as $course) {
        if ($categorycourses !== false) {
            if (!isset($categorycourses[$course->id])) {
                continue;
            }
        }
        $cell = new html_table_cell();
        $cell->attributes = array('class' => 'passed');
        $progress = '';
        $progressdata = block_ned_mentor_activity_progress($course, $mentee->studentid);
        $progressdata->completed;
        $progressdata->total;
        $percentageofcompletion = 0;

        if ($progressdata->total) {
            $percentageofcompletion = ($progressdata->completed / $progressdata->total) * 100;
        }

        if ($gradecompletion = $DB->get_record('course_completion_criteria',
            array('course' => $course->id,
                'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
            )
        )) {
            $gradecompletionpercentage = $gradecompletion->gradepass;
        } else {
            $gradecompletionpercentage = $passinggrade;
        }

        if (is_enrolled(context_course::instance($course->id), $mentee->studentid)) {
            if ($percentageofcompletion == 100) {
                ++$completionstatusecounter[5];
                if (($completionstatus == 5) || ($completionstatus == 0)) {
                    $cell = new html_table_cell('<img src="' . $OUTPUT->pix_url('completed_100', 'block_ned_mentor') . '" />');
                }
            } else if ($percentageofcompletion >= 75) {
                ++$completionstatusecounter[4];
                if (($completionstatus == 4) || ($completionstatus == 0)) {
                    $cell = new html_table_cell('<img src="' . $OUTPUT->pix_url('completed_75', 'block_ned_mentor') . '" />');
                }
            } else if ($percentageofcompletion >= 50) {
                ++$completionstatusecounter[3];
                if (($completionstatus == 3) || ($completionstatus == 0)) {
                    $cell = new html_table_cell('<img src="' . $OUTPUT->pix_url('completed_50', 'block_ned_mentor') . '" />');
                }
            } else if ($percentageofcompletion > 0) {
                ++$completionstatusecounter[2];
                if (($completionstatus == 2) || ($completionstatus == 0)) {
                    $cell = new html_table_cell('<img src="' . $OUTPUT->pix_url('completed_25', 'block_ned_mentor') . '" />');
                }
            } else if ($percentageofcompletion == 0) {
                ++$completionstatusecounter[1];
                if (($completionstatus == 1) || ($completionstatus == 0)) {
                    $cell = new html_table_cell('<img src="' . $OUTPUT->pix_url('completed_00', 'block_ned_mentor') . '" />');
                }
            } else {
                ++$completionstatusecounter[0];
                $cell = new html_table_cell('');
            }
            if ($showgradestatus) {
                if ($gradecompletionpercentage <= $progressdata->percentage) {
                    ++$gradepassingcounter[1];
                    if ($gradepassing == 2) {
                        $cell = new html_table_cell('');
                    } else {
                        $cell->attributes = array('class' => 'passed ' . $gradecompletionpercentage);
                    }
                } else {
                    ++$gradepassingcounter[2];
                    if ($gradepassing == 1) {
                        $cell = new html_table_cell('');
                    } else {
                        $cell->attributes = array('class' => 'failed ' . $gradecompletionpercentage);
                    }
                }
            }
            if ($percentageofcompletion == 0) {
                $cell->attributes = array('class' => '');
            }
        } else {
            $cell = new html_table_cell('');
        }
        $row->cells[] = $cell;
    }

    if ($completionstatus) {
        if ($completionstatusecounter[$completionstatus] > 0) {
            if ($gradepassing) {
                if ($gradepassingcounter[$gradepassing] > 0) {
                    $table->data[] = $row;
                }
            } else {
                $table->data[] = $row;
            }
        }
    } else {
        if ($gradepassing) {
            if ($gradepassingcounter[$gradepassing] > 0) {
                $table->data[] = $row;
            }
        } else {
            $table->data[] = $row;
        }
    }



}

// Content.
echo html_writer::div(html_writer::table($table), '', array('id' => 'mentee-course-overview-center'));
echo html_writer::end_div(); // Mentee course overview page.
echo $OUTPUT->footer();