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

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot . '/lib/coursecatlib.php');
require_once($CFG->dirroot . '/notes/lib.php');

// Paging options.
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 100, PARAM_INT);
$sort      = optional_param('sort', 'lastname', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
// Filters.
$categoryid       = optional_param('categoryid', 0, PARAM_INT);
$groupid          = optional_param('groupid', 0, PARAM_INT);
$completionstatus = optional_param('completionstatus', 0, PARAM_INT);
$gradepassing     = optional_param('gradepassing', 0, PARAM_INT);
$show             = optional_param('show', 0, PARAM_INT);
$mentor           = optional_param('mentor', 0, PARAM_INT);

require_login(null, false);

// PERMISSION.
$isadmin   = has_capability('block/fn_mentor:manageall', context_system::instance());
$ismentor  = block_fn_mentor_has_system_role($USER->id, get_config('block_fn_mentor', 'mentor_role_system'));
$isteacher = block_fn_mentor_isteacherinanycourse($USER->id);
$isstudent = block_fn_mentor_isstudentinanycourse($USER->id);

$menuurl = new moodle_url('/blocks/fn_mentor/all_students.php',
    array(
        'page' => $page,
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir,
        'categoryid' => $categoryid,
        'groupid' => $groupid,
        'completionstatus' => $completionstatus,
        'gradepassing' => $gradepassing,
        'show' => $show,
        'mentor' => $mentor
    )
);

// Find Mentees.
$mentees = array();
if ($isadmin) {
    $mentees = block_fn_mentor_get_all_mentees();
} else if ($isteacher) {
    if ($menteesbymentor = block_fn_mentor_get_mentees_by_mentor(0, $filter = 'teacher')) {
        foreach ($menteesbymentor as $menteebymentor) {
            if ($menteebymentor['mentee']) {
                foreach ($menteebymentor['mentee'] as $key => $value) {
                    $mentees[$key] = $value;
                }
            }
        }
    }
} else if ($ismentor) {
    $mentees = block_fn_mentor_get_mentees($USER->id);
}

$studentids = implode(',', array_keys($mentees));

// Category filter.
$categorycourses = false;
if ($categoryid) {
    $categorycourses = coursecat::get($categoryid)->get_courses(array('recursive' => true, 'coursecontacts' => true));
}

// Group filter.
$groupmembers = false;
if ($groupid) {
    $selectedgroup = groups_get_group($groupid, 'id, courseid, name, enrolmentkey', MUST_EXIST);
    $groupmembers = groups_get_members($groupid, $fields = 'u.*', 'lastname ASC');
}

if (($isstudent) && (!$isteacher && !$isadmin && !$ismentor)) {
    print_error('invalidpermission', 'block_fn_mentor');
}

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$thispageurl = new moodle_url('/blocks/fn_mentor/all_students.php');

$PAGE->set_url($thispageurl);
if ($pagelayout = get_config('block_fn_mentor', 'pagelayout')) {
    $PAGE->set_pagelayout($pagelayout);
} else {
    $PAGE->set_pagelayout('course');
}
$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_heading($heading);
$PAGE->set_cacheable(true);
$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'),
    new moodle_url('/blocks/fn_mentor/all_students.php')
);

// Settings.
$passinggrade = get_config('block_fn_mentor', 'passinggrade');
$showgradestatus = get_config('block_fn_mentor', 'showgradestatus');
$showsitegroups = get_config('block_fn_mentor', 'showsitegroups');

echo $OUTPUT->header();
$PAGE->requires->js('/blocks/fn_mentor/textrotate.js');
$PAGE->requires->js_function_call('textrotate_init', null, true);

echo html_writer::start_div('', array('id' => 'mentee-course-overview-page'));

// Left.
echo html_writer::start_div('', array('id' => 'mentee-course-overview-left'));
$studentmenu = array();
$studentmenuurl = array();

if ($showallstudents = get_config('block_fn_mentor', 'showallstudents')) {
    $studentmenuurl[0] = $CFG->wwwroot . '/blocks/fn_mentor/all_students.php';
    $studentmenu[$studentmenuurl[0]] = get_string('allstudents', 'block_fn_mentor');
}

$courses = array();

if ($mentees) {
    foreach ($mentees as $mentee) {
        $studentmenuurl[$mentee->studentid] = $CFG->wwwroot.'/blocks/fn_mentor/course_overview.php?menteeid='.$mentee->studentid;
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
        html_writer::span(get_string('select_student', 'block_fn_mentor')).
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
$categoryurl = clone $menuurl;
$categoryurl->param('categoryid', 0);
$categorymenuurl[0] = $categoryurl->out(false);
$categorymenu[$categorymenuurl[0]] = get_string('all', 'block_fn_mentor');

if ($categorycoursesfromsetting = block_fn_mentor_get_setting_courses()) {
    list($sqlf, $params) = $DB->get_in_or_equal($categorycoursesfromsetting);
    $params[] = 1;
    $sql = "SELECT DISTINCT cc.* FROM {course_categories} cc 
            INNER JOIN {course} c ON cc.id = c.category 
            WHERE c.id {$sqlf} AND cc.visible = ?";
} else {
    $sql = "SELECT cc.* FROM {course_categories} cc WHERE cc.visible = ?";
    $params = array(1);
}

if ($categories = $DB->get_records_sql($sql, $params)) {
    foreach ($categories as $category) {
        $categoryurl->param('categoryid', $category->id);
        $categorymenuurl[$category->id] = $categoryurl->out(false);
        $categorymenu[$categorymenuurl[$category->id]] = $category->name;
    }
}
$categorymenuhtml = html_writer::tag('form',
    html_writer::span(get_string('category', 'block_fn_mentor')).
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
    $groupurl = clone $menuurl;
    $groupurl->param('groupid', 0);
    $groupmenuurl[0] = $groupurl->out(false);
    $groupmenu[$groupmenuurl[0]] = get_string('allgroups', 'block_fn_mentor');

    if ($groups = $DB->get_records('groups', array('courseid' => SITEID))) {
        foreach ($groups as $group) {
            $groupurl->param('groupid', $group->id);
            $groupmenuurl[$group->id] = $groupurl->out(false);
            $groupmenu[$groupmenuurl[$group->id]] = $group->name;
        }
    }
    $groupmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('allgroups', 'block_fn_mentor')) .
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

$completionstatuses = array(
    0 => 'All',
    1 => '0%',
    2 => '1-49%',
    3 => '50-75%',
    4 => '75-99%',
    5 => '100%'
);
$completionstatusurl = clone $menuurl;
if ($completionstatuses) {
    foreach ($completionstatuses as $key => $completionstatuslabel) {
        $completionstatusurl->param('completionstatus', $key);
        $completionstatusmenuurl[$key] = $completionstatusurl->out(false);
        $completionstatusmenu[$completionstatusmenuurl[$key]] = $completionstatuslabel;
    }
}
$completionstatusmenuhtml = html_writer::tag('form',
    html_writer::span(get_string('completionstatus', 'block_fn_mentor')) .
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

    $gradepassinoptions = array(
        0 => 'All',
        1 => 'Passing',
        2 => 'Failing'
    );
    $gradepassingurl = clone $menuurl;
    if ($gradepassinoptions) {
        foreach ($gradepassinoptions as $key => $gradepassinglabel) {
            $gradepassingurl->param('gradepassing', $key);
            $gradepassingmenuurl[$key] = $gradepassingurl->out(false);
            $gradepassingmenu[$gradepassingmenuurl[$key]] = $gradepassinglabel;
        }
    }
    $gradepassingmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('grade', 'block_fn_mentor')) .
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

// Perpage.
$perpagemenu = array();
$perpagemenuurl = array();

$perpageoptions = array(
    10 => '10',
    20 => '20',
    50 => '50',
    100 => '100',
    250 => '250',
    500 => '500',
    1000 => '1000',
    5000 => '5000',
);
$perpageurl = clone $menuurl;
if ($perpageoptions) {
    foreach ($perpageoptions as $key => $perpagelabel) {
        $perpageurl->param('perpage', $key);
        $perpagemenuurl[$key] = $perpageurl->out(false);
        $perpagemenu[$perpagemenuurl[$key]] = $perpagelabel;
    }
}
$perpagemenuhtml = html_writer::tag('form',
    html_writer::span(get_string('show', 'block_fn_mentor')) .
    html_writer::select(
        $perpagemenu, 'perpage', $perpagemenuurl[$perpage], null,
        array('onChange' => 'location=document.jumpperpage.perpage'.
            '.options[document.jumpperpage.perpage.selectedIndex].value;'
        )
    ),
    array('id' => 'perpagemenuform', 'name' => 'jumpperpage')
);
$perpagemenuhtml = html_writer::div($perpagemenuhtml, 'mentee-course-overview-block-filter');

// Show menu.
if ($isadmin) {
    $showmenu = array();
    $showmenuurl = array();

    $showoptions = array(
        0 => get_string('allstudents', 'block_fn_mentor'),
        1 => get_string('studentswithmentors', 'block_fn_mentor'),
        2 => get_string('studentswithoutmentors', 'block_fn_mentor')
    );
    $showurl = clone $menuurl;
    if ($showoptions) {
        foreach ($showoptions as $key => $showlabel) {
            $showurl->param('show', $key);
            $showmenuurl[$key] = $showurl->out(false);
            $showmenu[$showmenuurl[$key]] = $showlabel;
        }
    }
    $showmenuhtml = html_writer::tag('form',
        html_writer::span(get_string('show', 'block_fn_mentor')) .
        html_writer::select(
            $showmenu, 'show', $showmenuurl[$show], null,
            array('onChange' => 'location=document.jumpshow.show' .
                '.options[document.jumpshow.show.selectedIndex].value;'
            )
        ),
        array('id' => 'showmenuform', 'name' => 'jumpshow')
    );
    $showmenuhtml = html_writer::div($showmenuhtml, 'mentee-course-overview-block-filter');
} else {
    $showmenuhtml = '';
}

// Mentor menu.
if (($show == 1)  && $isadmin) {
    $mentormenu = array();
    $mentormenuurl = array();

    $mentoroptions = array(
        0 => get_string('allmentors', 'block_fn_mentor'),
    );
    if ($allmentors = block_fn_mentor_get_all_mentors()) {
        foreach ($allmentors as $mntor) {
            $mentoroptions[$mntor->id] = $mntor->firstname . ' ' . $mntor->lastname;
        }
    }
    $mentorurl = clone $menuurl;
    if ($mentoroptions) {
        foreach ($mentoroptions as $key => $mentorlabel) {
            $mentorurl->param('mentor', $key);
            $mentormenuurl[$key] = $mentorurl->out(false);
            $mentormenu[$mentormenuurl[$key]] = $mentorlabel;
        }
    }
    $mentormenuhtml = html_writer::tag('form',
        html_writer::span(get_string('mentor', 'block_fn_mentor')) .
        html_writer::select(
            $mentormenu, 'mentor', $mentormenuurl[$mentor], null,
            array('onChange' => 'location=document.jumpmentor.mentor' .
                '.options[document.jumpmentor.mentor.selectedIndex].value;'
            )
        ),
        array('id' => 'mentormenuform', 'name' => 'jumpmentor')
    );
    $mentormenuhtml = html_writer::div($mentormenuhtml, 'mentee-course-overview-block-filter');
} else {
    $mentormenuhtml = '';
}

// Block.
echo $studentmenuhtml;
echo html_writer::div(
    html_writer::div(
        get_string('filter', 'block_fn_mentor'),
        'mentee-course-overview-block-title'
    ).
    html_writer::div(
        $showmenuhtml.
        $mentormenuhtml.
        $categorymenuhtml.
        $groupmenuhtml.
        $completionstatusmenuhtml.
        $gradepassingmenuhtml.
        $perpagemenuhtml,
        'mentee-course-overview-block-content'
    ),
    'mentee-course-overview-block'
);

$inprogress = get_config('block_fn_mentor', 'inprogress');
$reportdate = get_config('block_fn_mentor', 'reportdate');

$generatebutton = html_writer::div(
    get_string('inprogress', 'block_fn_mentor'),
    'inprogress-msg'
);

if (!$inprogress || ((time() - $reportdate) > 10 * 60)) {
    $generatebutton = $OUTPUT->render(
        new single_button(
            new moodle_url('/blocks/fn_mentor/update_all_students_data.php'),
            get_string('generatenewlist', 'block_fn_mentor')
        )
    );
}

echo html_writer::div(
    html_writer::div(
        get_string('allstudentdataupdateinfo', 'block_fn_mentor', date('m/d/Y H:i', $reportdate)),
        'mentee-update-report-data-text'
    ).
    $generatebutton,
    'mentee-course-overview-block',
    array('id' => 'mentee-update-report-data')
);

echo html_writer::end_div(); // Mentee course overview left.

// Find report courses.
$reportcourses = array();
if ($reportpvt = $DB->get_records('block_fn_mentor_report_pvt', null, '', '*', 0, 1)) {
    $reportpvt = reset($reportpvt);
    foreach ($reportpvt as $index => $item) {
        if (strpos($index, 'completion') === 0) {
            $reportcourseid = (int)str_replace('completion', '', $index);
            if ($categorycourses !== false) {
                if (!isset($categorycourses[$reportcourseid])) {
                    continue;
                }
            }
            $reportcourses[$reportcourseid] = $reportcourseid;
        }
    }
}

// Data column.
$datacolumns = array(
    'id' => 'r.id',
    'name' => 'CONCAT(u.firstname, \' \', u.lastname)',
    'groups' => 'r.groups',
    'courses' => 'r.courses',
    'mentors' => 'r.mentors',
    'lastname' => 'u.lastname'
);

foreach ($reportcourses as $reportcourse) {
    $datacolumns['completion'.$reportcourse] = 'r.completion'.$reportcourse;
    $datacolumns['passing'.$reportcourse] = 'r.passing'.$reportcourse;
}

// Filter.
$where = '';
$datacolumnsfilter = array();
foreach ($reportcourses as $reportcourse) {
    $datacolumnsfilter['completion'.$reportcourse] = 'r.completion'.$reportcourse.' <> -1';
}

if (count($datacolumnsfilter)) {
    $where .= " AND (".implode(' OR ', $datacolumnsfilter).")";
}

if (($show == 1) && $isadmin) {
    $where .= " AND {$datacolumns['mentors']} <> '' ";
}
if (($show == 2) && $isadmin) {
    $where .= " AND {$datacolumns['mentors']} = '' ";
}
if ($mentor  && $isadmin) {
    $where .= " AND  FIND_IN_SET($mentor,{$datacolumns['mentors']})";
}
if ($groupid) {
    $where .= " AND  FIND_IN_SET($groupid,{$datacolumns['groups']})";
}

if ($reportcourses) {
    if ($completionstatus && $gradepassing) {
        $completionstatusfilter = array();
        if ($completionstatus == 1) {
            foreach ($reportcourses as $reportcourse) {
                if ($gradecompletion = $DB->get_record('course_completion_criteria',
                    array('course' => $reportcourse,
                        'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                    )
                )
                ) {
                    $gradecompletionpercentage = $gradecompletion->gradepass;
                } else {
                    $gradecompletionpercentage = $passinggrade;
                }

                if ($gradepassing == 1) {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '=0 AND r.passing' .
                        $reportcourse . '>=' . $gradecompletionpercentage . ')';
                } else {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '=0 AND r.passing' .
                        $reportcourse . '<' . $gradecompletionpercentage . ')';
                }
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 2) {
            foreach ($reportcourses as $reportcourse) {
                if ($gradecompletion = $DB->get_record('course_completion_criteria',
                    array('course' => $reportcourse,
                        'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                    )
                )
                ) {
                    $gradecompletionpercentage = $gradecompletion->gradepass;
                } else {
                    $gradecompletionpercentage = $passinggrade;
                }
                if ($gradepassing == 1) {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>0 AND r.completion' .
                        $reportcourse . '<50 AND r.passing' . $reportcourse . '>=' . $gradecompletionpercentage . ')';
                } else {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>0 AND r.completion' .
                        $reportcourse . '<50 AND r.passing' . $reportcourse . '<' . $gradecompletionpercentage . ')';
                }
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 3) {
            foreach ($reportcourses as $reportcourse) {
                if ($gradecompletion = $DB->get_record('course_completion_criteria',
                    array('course' => $reportcourse,
                        'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                    )
                )
                ) {
                    $gradecompletionpercentage = $gradecompletion->gradepass;
                } else {
                    $gradecompletionpercentage = $passinggrade;
                }

                if ($gradepassing == 1) {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=50 AND r.completion' .
                        $reportcourse . '<75 AND r.passing' . $reportcourse . '>=' . $gradecompletionpercentage . ')';
                } else {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=50 AND r.completion' .
                        $reportcourse . '<75 AND r.passing' . $reportcourse . '<' . $gradecompletionpercentage . ')';
                }
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 4) {
            foreach ($reportcourses as $reportcourse) {
                if ($gradecompletion = $DB->get_record('course_completion_criteria',
                    array('course' => $reportcourse,
                        'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                    )
                )
                ) {
                    $gradecompletionpercentage = $gradecompletion->gradepass;
                } else {
                    $gradecompletionpercentage = $passinggrade;
                }
                if ($gradepassing == 1) {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=75 AND r.completion' .
                        $reportcourse . '<100 AND r.passing' . $reportcourse . '>=' . $gradecompletionpercentage . ')';
                } else {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=75 AND r.completion' .
                        $reportcourse . '<100 AND r.passing' . $reportcourse . '<' . $gradecompletionpercentage . ')';
                }
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 5) {
            foreach ($reportcourses as $reportcourse) {
                if ($gradecompletion = $DB->get_record('course_completion_criteria',
                    array('course' => $reportcourse,
                        'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                    )
                )
                ) {
                    $gradecompletionpercentage = $gradecompletion->gradepass;
                } else {
                    $gradecompletionpercentage = $passinggrade;
                }

                if ($gradepassing == 1) {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '=100 AND r.passing' .
                        $reportcourse . '>=' . $gradecompletionpercentage . ')';
                } else {
                    $completionstatusfilter[] = '(r.completion' . $reportcourse . '=100 AND r.passing' .
                        $reportcourse . '<' . $gradecompletionpercentage . ')';
                }
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        }
    } else if ($completionstatus) {
        $completionstatusfilter = array();
        if ($completionstatus == 1) {
            foreach ($reportcourses as $reportcourse) {
                $completionstatusfilter[] = 'r.completion' . $reportcourse . '=0';
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 2) {
            foreach ($reportcourses as $reportcourse) {
                $completionstatusfilter[] = '(r.completion' . $reportcourse . '>0 AND r.completion' . $reportcourse . '<50)';
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 3) {
            foreach ($reportcourses as $reportcourse) {
                $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=50 AND r.completion' . $reportcourse . '<75)';
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 4) {
            foreach ($reportcourses as $reportcourse) {
                $completionstatusfilter[] = '(r.completion' . $reportcourse . '>=75 AND r.completion' . $reportcourse . '<100)';
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        } else if ($completionstatus == 5) {
            foreach ($reportcourses as $reportcourse) {
                $completionstatusfilter[] = 'r.completion' . $reportcourse . '=100';
            }
            $where .= " AND (" . implode(' OR ', $completionstatusfilter) . ")";
        }
    } else if ($gradepassing) {
        $gradepassingfilter = array();
        foreach ($reportcourses as $reportcourse) {
            if ($gradecompletion = $DB->get_record('course_completion_criteria',
                array('course' => $reportcourse,
                    'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                )
            )
            ) {
                $gradecompletionpercentage = $gradecompletion->gradepass;
            } else {
                $gradecompletionpercentage = $passinggrade;
            }
            if ($gradepassing == 1) {
                $gradepassingfilter[] = 'r.passing' . $reportcourse . '>=' . $gradecompletionpercentage;
            } else {
                $gradepassingfilter[] = 'r.passing' . $reportcourse . '<' . $gradecompletionpercentage;
            }
        }
        $where .= " AND (" . implode(' OR ', $gradepassingfilter) . ")";
    }
} else {
    $where .= " AND 1=0";
}

// Sort.
$order = '';
$sortcourseid = 0;
if (isset($datacolumns[$sort])) {
    if ($sort) {
        $sort = ($dir == 'CLEAR') ? 'name' : $sort;
        $dir = ($dir == 'CLEAR') ? 'ASC' : $dir;
        $order = " ORDER BY $datacolumns[$sort] $dir";
        if (strpos($sort, 'completion') === 0) {
            $sortcourseid = (int)str_replace('completion', '', $sort);
            $where .= " AND  FIND_IN_SET($sortcourseid,{$datacolumns['courses']})";
        }
    }
}

if (has_capability('block/fn_mentor:viewallmentees', context_system::instance())) {
    $menteefilter = "0=0";
} else {
    $menteefilter = " r.userid IN ($studentids) ";
}

// Count records for paging.
$countsql = "SELECT COUNT(1)
               FROM {block_fn_mentor_report_pvt} r
         INNER JOIN {user} u
                 ON r.userid = u.id
              WHERE $menteefilter
                    $where";
$totalcount = $DB->count_records_sql($countsql);

// Table columns.
$columns = array(
    'name'
);
if (isset($selectedgroup)) {
    $columns[] = 'group';
}
foreach ($reportcourses as $reportcourse) {
    $countcolumnsql = "SELECT COUNT(1)
                        FROM {block_fn_mentor_report_pvt} r
                  INNER JOIN {user} u
                         ON r.userid = u.id
                      WHERE $menteefilter
                        AND completion".$reportcourse." > -1
                            $where";
    if ($coluncount = $DB->count_records_sql($countcolumnsql)) {
        $columns[] = 'completion'.$reportcourse;
    }
}

$sql = "SELECT r.*,
               CONCAT(u.firstname, ' ', u.lastname) name,
               u.firstname,
               u.lastname
          FROM {block_fn_mentor_report_pvt} r
    INNER JOIN {user} u
            ON r.userid = u.id
         WHERE $menteefilter
               $where
               $order";

foreach ($columns as $column) {
    $string[$column] = '';
    if (strpos($column, 'completion') === 0) {
        $cid = (int)str_replace('completion', '', $column);
        $columncourse = $DB->get_record('course', array('id' => $cid));
        $string[$column] = html_writer::span($columncourse->shortname, 'completion-activityname');
    } else {
        $string[$column] = get_string($column, 'block_fn_mentor');
    }

    if ($sort != $column) {
        $columnicon = "";
        $columndir = "DESC";
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('sort_inactive', 'block_fn_mentor') . "\" alt=\"\" />";
    } else {
        if ($dir == 'DESC') {
            $columndir = 'ASC';
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('sort_desc', 'block_fn_mentor') . "\" alt=\"\" />";
        } else if ($dir == 'ASC') {
            $columndir = 'CLEAR';
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('sort_asc', 'block_fn_mentor') . "\" alt=\"\" />";
        } else {
            $columndir = 'DESC';
            $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('sort_inactive', 'block_fn_mentor') . "\" alt=\"\" />";
        }
    }

    $sorturl = clone $menuurl;
    $sorturl->param('perpage', $perpage);
    $sorturl->param('sort', $column);
    $sorturl->param('dir', $columndir);

    if (strpos($column, 'completion') === 0) {
        $$column = $string[$column].html_writer::link($sorturl->out(false), $columnicon);
    } else if (($column == 'name') || ($column == 'group')) {
        $$column = '';
    } else {
        $$column = html_writer::link($sorturl->out(false), $string[$column]).$columnicon;
    }
}


$table = new html_table();
$table->attributes = array('class' => 'course-completion');

$table->head = array();
$table->wrap = array();
foreach ($columns as $column) {
    $table->wrap[$column] = '';
    if (strpos($column, 'completion') === 0) {
        $cid = (int)str_replace('completion', '', $column);
        $headcell = new html_table_cell("<a class='sort-course-link' onclick=\"window.open('".
            $CFG->wwwroot."/course/view.php?id=".$cid.
            "', '', 'width=800,height=600,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,".
            "directories=no,scrollbars=yes,resizable=yes'); return false;\" href=\"".
            $CFG->wwwroot."/course/view.php?id=".$cid."\">".
            $$column."</a>"
        );
        if ($sortcourseid == $cid) {
            $headcell->attributes = array('class' => 'header header-yellow');
        } else {
            $headcell->attributes = array('class' => 'header header-grey');
        }

        $table->head[$column] = $headcell;
    } else {
        $table->head[$column] = $$column;
    }
}

$table->wrap['name'] = 'nowrap';

$tablerows = $DB->get_records_sql($sql, null, $page * $perpage, $perpage);

$counter = ($page * $perpage);

foreach ($tablerows as $tablerow) {
    $row = new html_table_row();
    $actionlinks = '';
    foreach ($columns as $column) {
        $varname = 'cell'.$column;

        switch ($column) {
            case 'rowcount':
                $$varname = ++$counter;
                break;
            case 'group':
                if (isset($selectedgroup)) {
                    $$varname = new html_table_cell($selectedgroup->name);
                } else {
                    $$varname = '';
                }
                break;
            case 'name':
                $tablerow->$column = (strlen($tablerow->$column) > 16) ? mb_substr($tablerow->$column, 0, 16) : $tablerow->$column;

                if (block_fn_mentor_get_mentors($tablerow->userid)) {
                    $$varname = new html_table_cell(
                        html_writer::link(
                            new moodle_url('/blocks/fn_mentor/course_overview.php', array('menteeid' => $tablerow->userid)),
                            $tablerow->$column
                        )
                    );
                } else {
                    $$varname = new html_table_cell(
                        $tablerow->$column
                    );
                }
                break;
            case 'timecreated':
            case 'timemodified':
                $$varname = '-';
                if ($tablerow->$column > 0) {
                    $$varname = new html_table_cell(date("m/d/Y g:i A", $tablerow->$column));
                }
                break;
            default:
                if (strpos($column, 'completion') === 0) {
                    $cid = (int)str_replace('completion', '', $column);
                    if ($gradecompletion = $DB->get_record('course_completion_criteria',
                        array('course' => $cid,
                            'criteriatype' => COMPLETION_CRITERIA_TYPE_GRADE
                        )
                    )) {
                        $gradecompletionpercentage = $gradecompletion->gradepass;
                    } else {
                        $gradecompletionpercentage = $passinggrade;
                    }

                    $cell = new html_table_cell('');

                    if ($tablerow->$column == 100) {
                        if (($completionstatus == 5) || ($completionstatus == 0)) {
                            $cell = new html_table_cell('<img src="' .
                                $OUTPUT->pix_url('completed_100', 'block_fn_mentor') . '" />');
                        }
                    } else if ($tablerow->$column >= 75) {
                        if (($completionstatus == 4) || ($completionstatus == 0)) {
                            $cell = new html_table_cell('<img src="' .
                                $OUTPUT->pix_url('completed_75', 'block_fn_mentor') . '" />');
                        }
                    } else if ($tablerow->$column >= 50) {
                        if (($completionstatus == 3) || ($completionstatus == 0)) {
                            $cell = new html_table_cell('<img src="' .
                                $OUTPUT->pix_url('completed_50', 'block_fn_mentor') . '" />');
                        }
                    } else if ($tablerow->$column > 0) {
                        if (($completionstatus == 2) || ($completionstatus == 0)) {
                            $cell = new html_table_cell('<img src="' .
                                $OUTPUT->pix_url('completed_25', 'block_fn_mentor') . '" />');
                        }
                    } else if ($tablerow->$column == 0) {
                        if (($completionstatus == 1) || ($completionstatus == 0)) {
                            $cell = new html_table_cell('<img src="' .
                                $OUTPUT->pix_url('completed_00', 'block_fn_mentor') . '" />');
                        }
                    }

                    $varpassing = 'passing'.$cid;
                    if ($showgradestatus && ($tablerow->$varpassing > -1)) {

                        if ($gradecompletionpercentage <= $tablerow->$varpassing) {
                            if ((($gradepassing == 1) || ($gradepassing == 0)) && ($cell->text <> '')) {
                                $cell->attributes = array('class' => 'passed ' . $gradecompletionpercentage);
                            }
                        } else {
                            if ((($gradepassing == 2) || ($gradepassing == 0)) && ($cell->text <> '')) {
                                $cell->attributes = array('class' => 'failed ' . $gradecompletionpercentage);
                            }
                        }

                        if ($gradecompletionpercentage <= $tablerow->$varpassing) {
                            if ($gradepassing == 2) {
                                $cell->text = '';
                            }
                        } else {
                            if ($gradepassing == 1) {
                                $cell->text = '';
                            }
                        }
                    }
                    $$varname = $cell;
                } else {
                    $$varname = new html_table_cell($tablerow->$column);
                }
        }
    }

    $row->cells = array();
    foreach ($columns as $column) {
        $varname = 'cell' . $column;
        $row->cells[$column] = $$varname;
    }
    $table->data[] = $row;
}

$pagingurl = new moodle_url('/blocks/fn_mentor/all_students.php',
    array(
        'page' => $page,
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir,
        'categoryid' => $categoryid,
        'groupid' => $groupid,
        'completionstatus' => $completionstatus,
        'gradepassing' => $gradepassing
    )
);
$pagingbar = new paging_bar($totalcount, $page, $perpage, $pagingurl, 'page');

echo html_writer::div(
    html_writer::div(
        html_writer::table($table),
        'course-completion-table_wrapper'
    ).
    $OUTPUT->render($pagingbar),
    '',
    array('id' => 'mentee-course-overview-center')
);

echo html_writer::end_div(); // Mentee course overview page.

echo block_fn_mentor_footer();

echo $OUTPUT->footer();