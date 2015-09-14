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
        global $CFG, $DB;

        mtrace( "BLOCK Mentors Mentees" );
        $today = time();
        $site = get_site();
        $supportuser = core_user::get_support_user();
        $subject = 'Progress Report from '.format_string($site->fullname);

        if ($notification_rules = $DB->get_records('mentors_mentees_notification')) {

            foreach ($notification_rules as $notification_rule) {

                if (!$notification_rule->crontime) {
                    $notification_rule->crontime = '2000-01-01';
                }

                $date1 = new DateTime($notification_rule->crontime);
                $now = new DateTime(date("Y-m-d"));

                $diff = $now->diff($date1)->format("%a");

                //Check period
                if ($notification_rule->period > $diff) {
                    continue;
                }

                if (!($notification_rule->g1)
                    && !($notification_rule->g2)
                    && !($notification_rule->g3 && $notification_rule->g3_value)
                    && !($notification_rule->g4 && $notification_rule->g4_value)
                    && !($notification_rule->g5 && $notification_rule->g5_value)
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

                //CATEGORY
                if ($notification_rule->category) {

                    $notification_categories = explode(',', $notification_rule->category);

                    foreach ($notification_categories as $categoryid) {

                        if ($parentcat_courses = $DB->get_records('course', array('category'=>$categoryid))) {
                            foreach ($parentcat_courses as $cat_course) {
                                $courses[] =  $cat_course->id;
                            }
                        }
                        if ($category_structure = _get_course_category_tree($categoryid)) {
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

                //COURSE
                if ($notification_rule->course) {
                    $notification = explode(',', $notification_rule->course);
                    $courses = array_merge($courses, $notification);
                }

                //PREPARE NOTIFICATION FOR EACH COURSES
                foreach ($courses as $courseid) {
                    if ($course = $DB->get_record('course', array('id'=>$courseid))) {

                        $context = context_course::instance($course->id);

                        //if ($students = get_enrolled_users($context, 'moodle/grade:view')) {   print_r($students);die;
                        if ($students = get_role_users(5, $context)) {
                            foreach ($students as $student) {

                                $message = "";
                                $grade_summary = grade_summary($student->id, $course->id);
                                $lastaccess = 0;

                                $notification_message[$student->id][$course->id]['studentname'] = $student->firstname . ' ' . $student->lastname;

                                if ($notification_rule->g1) {
                                    $message .= '<li>'.get_string('g1_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g1'=>$grade_summary->attempted)).'</li>';
                                    $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                    $notification_message[$student->id][$course->id]['message'] = $message;
                                }

                                if ($notification_rule->g2) {
                                    $message .= '<li>'.get_string('g2_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g2'=>$grade_summary->all)).'</li>';
                                    $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                    $notification_message[$student->id][$course->id]['message'] = $message;
                                }

                                if ($notification_rule->g3 && $notification_rule->g3_value) {
                                    if ($grade_summary->attempted < $notification_rule->g3_value){
                                        $message .= '<li>'.get_string('g3_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g3'=>$grade_summary->attempted, 'g3_value'=>$notification_rule->g3_value)).'</li>';
                                        $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                        $notification_message[$student->id][$course->id]['message'] = $message;
                                    }
                                }

                                if ($notification_rule->g4 && $notification_rule->g4_value) {
                                    if ($grade_summary->attempted < $notification_rule->g4_value){
                                        $message .= '<li>'.get_string('g4_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g4'=>$grade_summary->all, 'g4_value'=>$notification_rule->g3_value)).'</li>';
                                        $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                        $notification_message[$student->id][$course->id]['message'] = $message;
                                    }
                                }

                                if ($notification_rule->g5 && $notification_rule->g5_value) {
                                    if ($grade_summary->attempted > $notification_rule->g5_value){
                                        $message .= '<li>'.get_string('g5_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g5'=>$grade_summary->attempted, 'g5_value'=>$notification_rule->g5_value)).'</li>';
                                        $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                        $notification_message[$student->id][$course->id]['message'] = $message;
                                    }
                                }

                                if ($notification_rule->g6 && $notification_rule->g6_value) {
                                    if ($grade_summary->attempted > $notification_rule->g6_value){
                                        $message .= '<li>'.get_string('g6_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'g6'=>$grade_summary->all, 'g6_value'=>$notification_rule->g6_value)).'</li>';
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
                                    $last_activity = last_activity($student->id);
                                    if (is_numeric($last_activity)) {
                                        if ($last_activity >= $notification_rule->n2_value){
                                            $message .= '<li>'.get_string('n2_message', 'block_fn_mentor', array('firstname'=>$student->firstname, 'n2'=>$last_activity)).'</li>';
                                            $notification_message[$student->id][$course->id]['coursename'] = $course->fullname;
                                            $notification_message[$student->id][$course->id]['message'] = $message;
                                        }
                                    }

                                    //echo last_activity (464, $notification_rule->n2_value);
                                }


                            }
                        }
                    }
                }

                //SEND EMAILS FOR EACH RULE
                foreach ($notification_message as $student_id => $course_messages) {

                    $course_array = array();
                    $messageHTML = '';

                    //STUDENT
                    if (!$student = $DB->get_record('user', array('id'=>$student_id))) {
                        continue;
                    }

                    $header = new stdClass();
                    $header->sitename = format_string($site->fullname);
                    $header->studentname = fullname($student);

                    $mentee_url = new moodle_url('/blocks/fn_mentor/course_overview.php?menteeid='.$student_id);
                    $appended_message = '';
                    if ($notification_rule->appended_message) {
                        $appended_message = '<p>' . $notification_rule->appended_message . '</p>';
                    }
                    $message_footer = $appended_message . '<hr />';
                    $message_footer .= '<p>'.get_string('linktomentorpage', 'block_fn_mentor', $mentee_url->out()).'</p>';
                    $message_footer .= 'This is an authomated message from '.format_string($site->fullname).'. Please do not reply to this message';

                    $send_message = false;

                    foreach ($course_messages as $course_id => $course_message) {
                        if (!isset($course_message['message'])) {
                            $send_message = false;
                            continue;
                        } else {
                            $send_message = true;
                        }
                        $messageHTML .= 'Course: '.$course_message['coursename'].' <br />';
                        $messageHTML .= '<ul>'.$course_message['message'].'</ul>';

                        $course_array[] = $course_message['coursename'];

                        //TEACHER
                        if ($notification_rule->teacher) {
                            //Course teachers
                            $sql_techer = "SELECT u.id,
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

                            if ($teachers = $DB->get_records_sql($sql_techer, array(50, 3, $course_id))) {
                                foreach ($teachers as $teacher) {
                                    if (!$to = $DB->get_record('user', array('id'=>$teacher->id))) {
                                        continue;
                                    }
                                    $header->coursename = implode(', ', $course_array);
                                    $message_header = get_string('message_header', 'block_fn_mentor', $header);
                                    if($send_message)
                                        email_to_user($to, $supportuser, $subject, '', $message_header.$messageHTML.$message_footer);
                                }
                            }
                        }
                    }

                    //STUDENT
                    if ($notification_rule->student) {
                        $header->coursename = implode(', ', $course_array);
                        $message_header = get_string('message_header', 'block_fn_mentor', $header);
                        if ($send_message)
                            email_to_user($student, $supportuser, $subject, '', $message_header.$messageHTML.$message_footer);
                    }

                    //MENTOR
                    if ($notification_rule->mentor) {
                        $mentors = get_mentors($student_id);
                        foreach ($mentors as $mentor) {
                            if (!$to = $DB->get_record('user', array('id'=>$mentor->mentorid))) {
                                continue;
                            }
                            $header->coursename = implode(', ', $course_array);
                            $message_header = get_string('message_header', 'block_fn_mentor', $header);
                            if ($send_message)
                                email_to_user($to, $supportuser, $subject, '', $message_header.$messageHTML.$message_footer);
                        }
                    }

                    //return true;

                }

                $update_sql = "UPDATE {mentors_mentees_notification} SET crontime=? WHERE id=?";
                $DB->execute($update_sql, array(date("Y-m-d"), $notification_rule->id));
            } // END OF EACH NOTIFICATION
        }

        return true;
    }
}
