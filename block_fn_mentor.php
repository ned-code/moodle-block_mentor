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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->libdir . '/formslib.php');

class block_fn_mentor extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_fn_mentor');
    }

    public function specialization() {
        if ($title = get_config('block_fn_mentor', 'blockname')) {
            $this->title = $title;
        } else {
            $this->title = get_string('pluginname', 'block_fn_mentor');
        }
    }

    function get_content() {
        global $CFG, $OUTPUT, $USER, $DB, $COURSE;

        $sortby = optional_param('sortby', 'mentor', PARAM_TEXT);
        $coursefilter = optional_param('coursefilter', 0, PARAM_INT);
        $showall = optional_param('showall', 0, PARAM_INT);

        $isadmin   = is_siteadmin($USER->id);
        $ismentor  = has_system_role($USER->id, get_config('block_fn_mentor', 'mentor_role_system'));
        $isteacher = _isteacherinanycourse($USER->id);
        $isstudent = _isstudentinanycourse($USER->id);


        $str_mentor = get_config('block_fn_mentor', 'mentor');
        $str_mentors = get_config('block_fn_mentor', 'mentors');
        $str_mentee = get_config('block_fn_mentor', 'mentee');
        $str_mentees = get_config('block_fn_mentor', 'mentees');
        $maxnumberofmentees = get_config('block_fn_mentor', 'maxnumberofmentees');

        if (! is_integer($maxnumberofmentees)) {
            $maxnumberofmentees = 15;
        }



        //Additional user filter according to user type
        $filter = '';
        if ($isadmin) {
            $filter = 'admin';
            $this->title = $str_mentors . ' - ' . $str_mentees;
        } elseif ($isteacher) {
            $filter = 'teacher';
            $this->title = $str_mentors . ' - ' . $str_mentees;
        } elseif ($ismentor) {
            $filter = 'mentor';
            $this->title = 'My ' . $str_mentees;
        } elseif ($isstudent) {
            $filter = 'student';
            $this->title = 'My ' . $str_mentors;
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


        //SORT SELECT
        $sortbyURL = array(
                        'mentor' => $CFG->wwwroot.'/index.php?coursefilter='.$coursefilter.'&sortby=mentor',
                        'mentee' => $CFG->wwwroot.'/index.php?coursefilter='.$coursefilter.'&sortby=mentee'
                        );
        $sortmenu= array(
                        $sortbyURL['mentor'] => get_config('block_fn_mentor', 'mentor'),
                        $sortbyURL['mentee'] => get_config('block_fn_mentor', 'mentee')
                        );

        //COURSE SELECT
        $courseURL = array(
                        //$CFG->wwwroot.'/index.php?course=0&sortby='.$sortby => get_string('all_courses', 'block_fn_mentor')
                        0 => $CFG->wwwroot.'/index.php?coursefilter=0&sortby='.$sortby.'&showall='.$showall
                        );
        $coursemenu = array($courseURL[0] => get_string('all_courses', 'block_fn_mentor') );

        // COURSES - ADMIN
        if ($isadmin) {
            $sqlCourse = "SELECT c.id,
                                 c.fullname
                            FROM {course} c
                           WHERE c.id > ?
                             AND c.visible = ?";

            if ($courses = $DB->get_records_sql($sqlCourse, array(1, 1))) {
                foreach ($courses as $course) {
                    $courseURL[$course->id] = $CFG->wwwroot.'/index.php?coursefilter='.$course->id.'&sortby='.$sortby.'&showall='.$showall;
                    $coursemenu[$courseURL[$course->id]] = $course->fullname;
                }
            }
        // COURSES - TEACHER
        } elseif ($isteacher) {
            if ($courses = get_teacher_courses()) {
                foreach ($courses as $course) {
                    $courseURL[$course->id] = $CFG->wwwroot.'/index.php?coursefilter='.$course->id.'&sortby='.$sortby.'&showall='.$showall;
                    $coursemenu[$courseURL[$course->id]] = $course->fullname;
                }
            }
        // COURSES - TEACHER
        } elseif ($ismentor) {
            if ($students = get_mentees_by_mentor(0, $filter)) {
                $students = reset($students);
                $student_ids =  implode(",", array_keys($students['mentee']));

                if ($student_ids) {
                    $sql = "SELECT DISTINCT c.id,
                                            c.fullname
                                       FROM mdl_role_assignments AS ra
                                 INNER JOIN {context} ctx
                                         ON ra.contextid = ctx.id
                                 INNER JOIN {course} c
                                         ON ctx.instanceid = c.id
                                      WHERE ra.userid IN ($student_ids)
                                        AND ra.roleid = 5
                                        AND ctx.contextlevel = 50";

                    if ($courses = $DB->get_records_sql($sql)) {
                        foreach ($courses as $course) {
                            $courseURL[$course->id] = $CFG->wwwroot.'/index.php?coursefilter='.$course->id.'&sortby='.$sortby.'&showall='.$showall;
                            $coursemenu[$courseURL[$course->id]] = $course->fullname;
                        }
                    }
                }
            }
        }



        //MENU
        if ($isteacher || $isadmin || $ismentor) {
            $this->content->text .= '<div id="mentor-form-container">';
        }
        //SORT
        if ($isteacher || $isadmin && (isset($this->config->show_mentor_sort) && $this->config->show_mentor_sort)) {
            $this->content->text .= html_writer::tag('form',
                                        get_string('sortby', 'block_fn_mentor') . ' ' .
                                        html_writer::select($sortmenu, 'sortby', $sortbyURL[$sortby], null, array('onChange' => 'location=document.jump1.sortby.options[document.jump1.sortby.selectedIndex].value;')),
                                    array('id'=>'sortbyForm', 'name'=>'jump1'));
        }
        //COURSE
        if (($isteacher || $isadmin || $ismentor) && $courses) {
            $this->content->text .= html_writer::tag('form',
                                        get_string('course', 'block_fn_mentor') . ' ' .
                                        html_writer::select($coursemenu, 'coursefilter', $courseURL[$coursefilter], null, array('onChange' => 'location=document.jump2.coursefilter.options[document.jump2.coursefilter.selectedIndex].value;')),
                                    array('id'=>'courseForm', 'name'=>'jump2'));
            $this->content->text .= '</div>';
        }

        if (($isstudent) && (!$isteacher && !$isadmin && !$ismentor)) {
            $this->content->text .= render_mentees_by_student($USER->id);
        } else {

            $number_of_mentees = 0;

            if ($sortby == 'mentor') {
                $visible_mentees =  get_mentees_by_mentor($coursefilter, $filter);
                foreach ($visible_mentees as $visible_mentee) {
                    $number_of_mentees += sizeof($visible_mentee['mentee']);
                }
                if (($number_of_mentees > $maxnumberofmentees) && (!$showall)) {

                    $this->content->text .= '<div class="mentee-block-menu"><img class="mentee-img" style="width: 12px;" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
                                             <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php">'.get_string('open_progress_reports', 'block_fn_mentor').'</a></div>';

                    $this->content->text .= '<div class="mentee-block-menu"><img class="mentee-img" style="width: 12px;" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
                                             <a href="'.$CFG->wwwroot.'/index.php?sortby='.$sortby.'&coursefilter='.$coursefilter.'&showall=1">'.get_string('show_all', 'block_fn_mentor').'</a></div>';

                } else {
                    $this->content->text .= render_mentees_by_mentor($visible_mentees);
                }

            }


            if ($sortby == 'mentee') {
                $visible_mentees =  get_mentors_by_mentee($coursefilter, $filter);
                $number_of_mentees += sizeof($visible_mentees);

                if (($number_of_mentees > $maxnumberofmentees) && (!$showall)) {

                    $this->content->text .= '<div class="mentee-block-menu"><img class="mentee-img" style="width: 12px;" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
                                             <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/course_overview.php">'.get_string('open_progress_reports', 'block_fn_mentor').'</a></div>';

                    $this->content->text .= '<div class="mentee-block-menu"><img class="mentee-img" style="width: 12px;" src="'.$CFG->wwwroot.'/blocks/fn_mentor/pix/mentor_bullet.png">
                                             <a href="'.$CFG->wwwroot.'/index.php?sortby='.$sortby.'&coursefilter='.$coursefilter.'&showall=1">'.get_string('show_all', 'block_fn_mentor').'</a></div>';

                } else {
                    $this->content->text .= render_mentors_by_mentee($visible_mentees);
                }
            }

        }

        $this->content->text .= '<hr style="margin-top:12px;height:1px;border:none;color:#ddd;background-color:#ddd;" /><div class="mentee-footer-menu">';

        if ($isadmin) {
            $this->content->text .= '<div class="mentee-block-menu">
                                     <img class="mentee-img" src="'.$OUTPUT->pix_url('i/navigationitem').'">
                                     <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/assign_mentor.php">'.get_string('assign_mentor', 'block_fn_mentor').'</a></div>';
        }
        if ($isadmin && has_capability('block/fn_mentor:createnotificationrule', context_system::instance())) {
            $this->content->text .= '<div class="mentee-block-menu">
                                     <img class="mentee-img" src="'.$OUTPUT->pix_url('i/navigationitem').'">
                                     <a href="'.$CFG->wwwroot.'/blocks/fn_mentor/notification_rules.php">'.get_string('manage_notification', 'block_fn_mentor').'</a></div>';
        }
        $this->content->text .= '</div>';
        return $this->content;
    }

    public function applicable_formats() {
        return array('all' => true);
    }

    public function instance_allow_multiple() {
          return false;
    }

    function has_config() {
        return true;
    }

    public function cron() {
        fn_send_notifications();
    }

}
