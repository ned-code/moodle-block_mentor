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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->libdir . '/formslib.php');

class block_fn_mentor extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_fn_mentor');
    }

    public function specialization() {
        global $USER;
        if ($title = get_config('block_fn_mentor', 'blockname')) {
            $this->title = $title;
        } else {
            $this->title = get_string('blocktitle', 'block_fn_mentor');
        }
        if ($isstudent = block_fn_mentor_is_mentee($USER->id)) {
            $this->title = get_string('myprogress', 'block_fn_mentor');
        }
    }

    public function get_content() {
        global $CFG, $OUTPUT, $USER, $DB;

        $studentrole = get_config('block_fn_mentor', 'studentrole');

        $sortby = optional_param('sortby', 'mentor', PARAM_TEXT);
        $coursefilter = optional_param('coursefilter', 0, PARAM_INT);
        $groupfilter = optional_param('groupfilter', 0, PARAM_INT);
        $showall = optional_param('showall', 0, PARAM_INT);

        $isadmin   = has_capability('block/fn_mentor:manageall', context_system::instance());
        $ismentor  = block_fn_mentor_has_system_role($USER->id, get_config('block_fn_mentor', 'mentor_role_system'));
        $isteacher = block_fn_mentor_isteacherinanycourse($USER->id);
        $isstudent = block_fn_mentor_isstudentinanycourse($USER->id);

        $strmentor = get_config('block_fn_mentor', 'mentor');
        $strmentors = get_config('block_fn_mentor', 'mentors');
        $strmentee = get_config('block_fn_mentor', 'mentee');
        $strmentees = get_config('block_fn_mentor', 'mentees');
        $maxnumberofmentees = get_config('block_fn_mentor', 'maxnumberofmentees');
        $menteecanview = get_config('block_fn_mentor', 'menteecanview');
        $usementorgroups = get_config('block_fn_mentor', 'usementorgroups');

        if (!isset($this->config->show_mentee_without_course)) {
            $showunenrolledstudents = 0;
        } else {
            $showunenrolledstudents = $this->config->show_mentee_without_course;
        }

        if ( !$maxnumberofmentees) {
            $maxnumberofmentees = 15;
        }

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';
        $this->content->text = '';

        if (!$isadmin && !$ismentor && !$isteacher && !$isstudent) {
            return $this->content;
        }

        if (!has_capability('block/fn_mentor:viewblock', $this->context)) {
            if ($isstudent) {
                if (!$menteecanview) {
                    return $this->content;
                }
            } else {
                return $this->content;
            }
        }

        if ($this->instance->pagetypepattern == 'my-index') {
            $indexphp = "my/index.php";
        } else {
            $indexphp = "index.php";
        }

        // SORT SELECT.
        $sortbyurl = array(
            'mentor' => $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.$coursefilter.'&groupfilter='.$groupfilter.'&sortby=mentor',
            'mentee' => $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.$coursefilter.'&groupfilter='.$groupfilter.'&sortby=mentee'
        );
        $sortmenu = array(
            $sortbyurl['mentor'] => get_config('block_fn_mentor', 'mentor'),
            $sortbyurl['mentee'] => get_config('block_fn_mentor', 'mentee')
        );

        // COURSE SELECT.
        $courseurl = array(
            0 => $CFG->wwwroot.'/'.$indexphp.'?coursefilter=0&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall
        );
        $coursemenu = array($courseurl[0] => get_string('all_courses', 'block_fn_mentor') );

        $filtercourses = array();

        // CATEGORY.
        if ($configcategory = get_config('block_fn_mentor', 'category')) {

            $selectedcategories = explode(',', $configcategory);

            foreach ($selectedcategories as $categoryid) {

                if ($parentcatcourses = $DB->get_records('course', array('category' => $categoryid))) {
                    foreach ($parentcatcourses as $catcourse) {
                        $filtercourses[] = $catcourse->id;
                    }
                }
                if ($categorystructure = block_fn_mentor_get_course_category_tree($categoryid)) {
                    foreach ($categorystructure as $category) {

                        if ($category->courses) {
                            foreach ($category->courses as $subcatcourse) {
                                $filtercourses[] = $subcatcourse->id;
                            }
                        }
                        if ($category->categories) {
                            foreach ($category->categories as $subcategory) {
                                block_fn_mentor_get_selected_courses($subcategory, $filtercourses);
                            }
                        }
                    }
                }
            }
        }

        // COURSE.
        if ($configcourse = get_config('block_fn_mentor', 'course')) {
            $selectedcourses = explode(',', $configcourse);
            $filtercourses = array_merge($filtercourses, $selectedcourses);
        }

        if ($filtercourses) {
            $filter = ' AND c.id IN ('.implode(',' , $filtercourses).')';
        } else {
            $filter = '';
        }

        // Courses - admin.
        if ($isadmin) {
            $sqlcourse = "SELECT c.id,
                                 c.fullname
                            FROM {course} c
                           WHERE c.id > ?
                             AND c.visible = ?
                                 $filter";

            if ($courses = $DB->get_records_sql($sqlcourse, array(1, 1))) {
                foreach ($courses as $course) {
                    $courseurl[$course->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.
                        $course->id.'&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall;
                    $coursemenu[$courseurl[$course->id]] = $course->fullname;
                }
            }
        } else if ($isteacher) { // Course - Teacher.
            if ($courses = block_fn_mentor_get_teacher_courses()) {
                foreach ($courses as $course) {

                    if ($filtercourses) {
                        if (in_array($course->id, $filtercourses)) {
                            $courseurl[$course->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.
                                $course->id.'&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall;
                            $coursemenu[$courseurl[$course->id]] = $course->fullname;
                        }
                    } else {
                        $courseurl[$course->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.
                            $course->id.'&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall;
                        $coursemenu[$courseurl[$course->id]] = $course->fullname;
                    }
                }
            }
        } else if ($ismentor) {
            if ($students = block_fn_mentor_get_mentees_by_mentor(0, $filter, $USER->id)) {
                $students = reset($students);

                list($insql, $params) = $DB->get_in_or_equal(array_keys($students['mentee']));

                $params[] = $studentrole;
                $params[] = 50;

                if ($students['mentee']) {
                    $sql = "SELECT DISTINCT c.id,
                                            c.fullname
                                       FROM {role_assignments} ra
                                 INNER JOIN {context} ctx
                                         ON ra.contextid = ctx.id
                                 INNER JOIN {course} c
                                         ON ctx.instanceid = c.id
                                      WHERE ra.userid $insql
                                        AND ra.roleid = ?
                                        AND ctx.contextlevel = ?";

                    if ($courses = $DB->get_records_sql($sql, $params)) {
                        foreach ($courses as $course) {
                            if ($filtercourses) {
                                if (in_array($course->id, $filtercourses)) {
                                    $courseurl[$course->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.
                                        $course->id.'&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall;
                                    $coursemenu[$courseurl[$course->id]] = $course->fullname;
                                }
                            } else {
                                $courseurl[$course->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.
                                    $course->id.'&groupfilter='.$groupfilter.'&sortby='.$sortby.'&showall='.$showall;
                                $coursemenu[$courseurl[$course->id]] = $course->fullname;
                            }
                        }
                    }
                }
            }
        }

        // Menu.
        if ($isteacher || $isadmin || $ismentor) {
            $this->content->text .= '<div id="mentor-form-container">';
        }
        // Sort.
        if (($isteacher || $isadmin) && (isset($this->config->show_mentor_sort) && $this->config->show_mentor_sort)) {
            $this->content->text .= html_writer::tag('form',
                get_string('sortby', 'block_fn_mentor') . ' ' .
                html_writer::select($sortmenu, 'sortby', $sortbyurl[$sortby], null,
                    array('onChange' => 'location=document.jump1.sortby.options[document.jump1.sortby.selectedIndex].value;')
                ),
                array('id' => 'sortbyForm', 'name' => 'jump1'));
        }
        // Group.
        if (($isadmin || $ismentor)) {

            $groupurl = array(
                0 => $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.$coursefilter.'&groupfilter=0&sortby='.$sortby.'&showall='.$showall
            );
            $groupmenu = array($groupurl[0] => get_string('allmentorgroups', 'block_fn_mentor'));
            if ($isadmin) {
                $groups = $DB->get_records('block_fn_mentor_group', null, 'name ASC');
            } else if ($ismentor) {
                $sql = "SELECT g.id, g.name
                          FROM {block_fn_mentor_group} g
                          JOIN {block_fn_mentor_group_mem} gm
                            ON g.id = gm.groupid
                         WHERE gm.role = ?
                           AND gm.userid = ?
                      ORDER BY g.name ASC";
                $groups = $DB->get_records_sql($sql, array('M', $USER->id));
            }
            if ($groups && $usementorgroups) {
                foreach ($groups as $group) {
                    $groupurl[$group->id] = $CFG->wwwroot.'/'.$indexphp.'?coursefilter='.$coursefilter.'&groupfilter='.$group->id.'&sortby='.$sortby.'&showall='.$showall;
                    $groupmenu[$groupurl[$group->id]] = $group->name;
                }
                $this->content->text .= html_writer::tag('form',
                    html_writer::img($OUTPUT->pix_url('i/group'), get_string('group', 'block_fn_mentor')) . ' ' .
                    html_writer::select($groupmenu, 'groupfilter', $groupurl[$groupfilter], null,
                        array('onChange' => 'location=document.jump3.groupfilter.'.
                            'options[document.jump3.groupfilter.selectedIndex].value;'
                        )
                    ),
                    array('id' => 'groupForm', 'name' => 'jump3')
                );
            }
        }

        // COURSE.
        if (($isteacher || $isadmin || $ismentor) && $courses && ($this->page->course->id == SITEID)) {
            $this->content->text .= html_writer::tag('form',
                html_writer::img($OUTPUT->pix_url('i/course'), get_string('course', 'block_fn_mentor')) . ' ' .
                html_writer::select($coursemenu, 'coursefilter', $courseurl[$coursefilter], null,
                    array('onChange' => 'location=document.jump2.coursefilter.'.
                        'options[document.jump2.coursefilter.selectedIndex].value;'
                    )
                ),
                array('id' => 'courseForm', 'name' => 'jump2')
            );
        }
        if ($isteacher || $isadmin || $ismentor) {
            $this->content->text .= '</div>';
        }

        if (($isstudent) && (!$isteacher && !$isadmin && !$ismentor)) {
            $this->content->text .= block_fn_mentor_render_mentees_by_student($USER->id);
        } else {
            $numberofmentees = 0;

            if ($sortby == 'mentor') {
                if ($ismentor) {
                    $visiblementees = block_fn_mentor_get_mentees_by_mentor($coursefilter, $filter, $USER->id, $groupfilter);
                } else {
                    $visiblementees = block_fn_mentor_get_mentees_by_mentor($coursefilter, $filter, 0, $groupfilter);
                }

                foreach ($visiblementees as $visiblementee) {
                    $numberofmentees += count($visiblementee['mentee']);
                }
                if (($numberofmentees > $maxnumberofmentees) && (!$showall)) {

                    $this->content->text .= '<div class="mentee-footer-menu">';

                    $toomanyusersurl = $CFG->wwwroot.'/'.$indexphp.'?sortby='.$sortby.'&groupfilter='.$groupfilter.'&coursefilter='.$coursefilter.'&showall=1';
                    $this->content->text .= '<div class="too-many-users">'.get_string('toomanyusers', 'block_fn_mentor', $toomanyusersurl).'</div>';

                    $this->content->text .= '<div class="too-many-users last">'.
                        '<a class="btn btn-secondary" href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php">'.
                        '<img src="'.$OUTPUT->pix_url('i/group').'" class="mentee-img"> '.
                        get_string('open_progress_reports', 'block_fn_mentor').'</a></div>';


                    $this->content->text .= '</div>';
                } else {
                    $this->content->text .= block_fn_mentor_render_mentees_by_mentor($visiblementees, $showunenrolledstudents);
                }
            }

            if ($sortby == 'mentee') {
                $visiblementees = block_fn_mentor_get_mentors_by_mentee($coursefilter, $filter);
                $numberofmentees += count($visiblementees);

                if (($numberofmentees > $maxnumberofmentees) && (!$showall)) {
                    $this->content->text .= '<div class="mentee-footer-menu">'.
                        '<div class="mentee-block-menu"><img class="mentee-img" src="'.$OUTPUT->pix_url('i/navigationitem').'">'.
                        '<a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php">'.
                        get_string('open_progress_reports', 'block_fn_mentor').'</a></div>';

                    $this->content->text .= '<div class="mentee-block-menu"><img class="mentee-img" src="'.
                        $OUTPUT->pix_url('i/navigationitem').'">'.
                        '<a href="'.$CFG->wwwroot.'/'.$indexphp.'?sortby='.$sortby.'&coursefilter='.$coursefilter.
                        '&showall=1">'.get_string('show_all', 'block_fn_mentor').'</a></div>';
                    $this->content->text .= '</div>';
                } else {
                    $this->content->text .= block_fn_mentor_render_mentors_by_mentee($visiblementees);
                }
            }
        }
        if (has_capability('block/fn_mentor:assignmentor', context_system::instance())
            || has_capability('block/fn_mentor:createnotificationrule', context_system::instance())) {
            $this->content->text .= '<hr class="footer-separator" />' .
                '<div class="mentee-footer-menu">';
        }
        if (has_capability('block/fn_mentor:assignmentor', context_system::instance())) {
            $this->content->text .= '<div class="mentee-block-menu">'.
                '<img class="mentee-img" src="'.$OUTPUT->pix_url('cog_small', 'block_fn_mentor').'">'.
                '<a href="'.$CFG->wwwroot.'/blocks/fn_mentor/assign.php">'.
                get_string('mentors', 'block_fn_mentor').'</a></div>';
        }
        if (has_capability('block/fn_mentor:createnotificationrule', context_system::instance())) {
            $this->content->text .= '<div class="mentee-block-menu">'.
                '<img class="mentee-img" src="'.$OUTPUT->pix_url('cog_small', 'block_fn_mentor').'">'.
                '<a href="'.$CFG->wwwroot.'/blocks/fn_mentor/notification_rules.php">'.
                get_string('notifications', 'block_fn_mentor').'</a></div>';
        }
        if (has_capability('block/fn_mentor:assignmentor', context_system::instance())
            || has_capability('block/fn_mentor:createnotificationrule', context_system::instance())) {
            $this->content->text .= '</div>';
        }

        return $this->content;
    }

    public function applicable_formats() {
        return array(
            'all' => false,
            'site' => true,
            'course-*' => false,
            'my' => true
        );
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
    }
}
