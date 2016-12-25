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
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/blocks/fn_mentor/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$usinghtmleditor = false;

$id = required_param('id', PARAM_INT); // Course ID.
$menteeid = required_param('menteeid', PARAM_INT); // User ID.
$show = optional_param('show', 'notloggedin', PARAM_ALPHA);

// Paging options.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 2, PARAM_INT);
$PAGE->set_url('/blocks/fn_mentor/listactivities.php', array('id' => $id, 'show' => $show, 'navlevel' => 'top'));


if (!$course = $DB->get_record("course", array("id" => $id))) {
    print_error("Course ID was incorrect");
}

require_login(null, false);
$context = context_course::instance($course->id);
$canview = has_capability('block/fn_mentor:viewactivitylist', $context);
$PAGE->set_context($context);

if (!$canview) {
    print_error("Students can not use this page!");
}

// Array of functions to call for grading purposes for modules.
$modgradesarray = array(
    'assign' => 'assign.submissions.fn.php',
    'quiz' => 'quiz.submissions.fn.php',
    'assignment' => 'assignment.submissions.fn.php',
    'forum' => 'forum.submissions.fn.php',
);

$completedactivities = 0;
$incompletedactivities = 0;
$savedactivities = 0;
$notattemptedactivities = 0;
$waitingforgradeactivities = 0;
$savedactivities = 0;

$completion = new completion_info($course);
$activities = $completion->get_activities();

if ($completion->is_enabled() && !empty($completion)) {

    foreach ($activities as $activity) {
        if (!$activity->visible) {
            continue;
        }
        // Skip non tracked activities.
        if ($activity->completion == COMPLETION_TRACKING_NONE) {
            continue;
        }
        if (! isset($modgradesarray[$activity->modname])) {
            continue;
        }
        // Don't count it if you can't see it.
        $mcontext = context_module::instance($activity->id);
        if (!$activity->visible && !has_capability('moodle/course:viewhiddenactivities', $mcontext)) {
            continue;
        }
        $instance = $DB->get_record($activity->modname, array("id" => $activity->instance));
        $item = $DB->get_record('grade_items',
            array("itemtype" => 'mod', "itemmodule" => $activity->modname, "iteminstance" => $activity->instance)
        );

        $libfile = $CFG->dirroot . '/mod/' . $activity->modname . '/lib.php';

        if (file_exists($libfile)) {
            require_once($libfile);
            $gradefunction = $activity->modname . "_get_user_grades";

            if ((($activity->modname != 'forum') || ($instance->assessed > 0))
                && isset($modgradesarray[$activity->modname])) {

                if (function_exists($gradefunction)) {

                    if (($activity->modname == 'quiz') || ($activity->modname == 'forum')) {

                        if ($grade = $gradefunction($instance, $menteeid)) {
                            if ($item->gradepass > 0) {
                                if ($grade[$menteeid]->rawgrade >= $item->gradepass) {
                                    // Passed
                                    ++$completedactivities;
                                } else {
                                    // Failed
                                    ++$incompletedactivities;
                                }
                            } else {
                                // Graded
                                ++$completedactivities;
                            }
                        } else {
                            // Ungraded
                            ++$notattemptedactivities;
                        }
                    } else if ($modstatus = block_fn_mentor_assignment_status($activity, $menteeid, true)) {
                        switch ($modstatus) {
                            case 'submitted':
                                if ($instance->grade == 0) {
                                    // Graded
                                    ++$completedactivities;
                                } elseif ($grade = $gradefunction($instance, $menteeid)) {
                                    if ($item->gradepass > 0) {
                                        if ($grade[$menteeid]->rawgrade >= $item->gradepass) {
                                            // Passed
                                            ++$completedactivities;
                                        } else {
                                            // Fail.
                                            ++$incompletedactivities;
                                        }
                                    } else {
                                        // Graded
                                        ++$completedactivities;
                                    }
                                }
                                break;

                            case 'saved':
                                // Saved
                                ++$savedactivities;
                                break;

                            case 'waitinggrade':
                                // Waiting for grade
                                ++$waitingforgradeactivities;
                                break;
                        }
                    } else {
                        // Ungraded
                        ++$notattemptedactivities;
                    }
                }
            }
        }
    }
}


// Switch to show soecific assignment.
switch ($show) {

    case 'completed':

        $activitiesresults = $completedactivities;
        $name = get_string('breadcrumb:activitiescompleted', 'block_fn_mentor');
        $title = get_string('title:completed', 'block_fn_mentor') . " (Total:" . $completedactivities . " Activities)";
        break;

    case 'incompleted':

        $activitiesresults = $incompletedactivities;
        $name = get_string('breadcrumb:activitiesincompleted', 'block_fn_mentor');
        $title = get_string('title:incompleted', 'block_fn_mentor') . " (Total:" . $incompletedactivities . " Activities)";
        break;

    case 'draft':

        $activitiesresults = $savedactivities;
        $name = get_string('breadcrumb:draft', 'block_fn_mentor');
        $title = get_string('title:saved', 'block_fn_mentor') . " (Total:" . $savedactivities . " Activities)";
        break;

    case 'notattempted':
        $activitiesresults = $notattemptedactivities;
        $name = get_string('breadcrumb:notattempted', 'block_fn_mentor');
        $title = get_string('title:notattempted', 'block_fn_mentor') . " (Total:" . $notattemptedactivities . " Activities)";
        break;

    case 'waitingforgrade':
        $activitiesresults = $waitingforgradeactivities;
        $name = get_string('breadcrumb:waitingforgrade', 'block_fn_mentor');
        $title = get_string('title:waitingforgrade', 'block_fn_mentor') . " (Total:" . $waitingforgradeactivities . " Activities)";
        break;
    default:
        break;
}

// Print header.
$PAGE->navbar->add($name);
$heading = $course->fullname;
$PAGE->set_title($title);
$PAGE->set_heading($heading);
echo $OUTPUT->header();

echo "<div id='mark-interface'>";
echo "<h4 class='head-title' style='padding-bottom:12px;'>$title</h4>\n";

$totalcount = $activitiesresults;

echo '<table width="96%" class="markingcontainerList" border="0" cellpadding="0" cellspacing="0" align="center">' .
    '<tr><td class="intd">';

echo '<table  width="100%" border="0" cellpadding="0" cellspacing="0">';
if (($show == 'completed' || $show == 'incompleted' || $show == 'draft'
        || $show == 'notattempted' || $show == 'waitingforgrade') && $totalcount > 0 && $activities > 0) {
    echo "<tr>";
    echo "<th align='center' width='15%'><strong>Activity type </strong></th>";
    echo "<th align='left' width='67%' style='text-align:left;'><strong>Activity or Resource Name </strong></th>";
    echo "</tr>";
} else {
    echo '<div style="text-align:center; padding:12px;">';
    echo "No activity with status " . get_string($show, 'block_fn_mentor') . "";
    echo "</div>";
}

if ($show == 'completed') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }

            $data = $completion->get_data($activity, false, $menteeid, null);
            $activitystate = $data->completionstate;

            // Check no grade assignments.
            $shownogradeassignment = false;
            if ($activity->modname == 'assign') {
                if ($assignment = $DB->get_record('assign', array('id' => $activity->instance))) {
                    if ($assignment->grade == 0) {
                        if ($submission = $DB->get_records('assign_submission', array(
                            'assignment' => $assignment->id, 'userid' => $menteeid), 'attemptnumber DESC', '*', 0, 1)
                        ) {
                           $shownogradeassignment = true;
                        }
                    }
                }
            }


            if ($activitystate == 1 || $activitystate == 2 || $shownogradeassignment) {
                echo "<tr><td align='center'>\n";
                $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                    "HEIGHT=\"16\" WIDTH=\"16\" >";
                echo ($modtype == 'assign') ? 'assignment' : $modtype;
                echo "</td>\n";
                echo "<td align='left'><a href='" . $CFG->wwwroot .
                    "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                    $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                    $activity->name . "</a></td>\n";
            }
        }
    }
} else if ($show == 'incompleted') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $menteeid, null);
            $activitystate = $data->completionstate;
            $assignmentstatus = block_fn_mentor_assignment_status($activity, $menteeid);
            if ($activitystate == 3) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {

                    if ($assignmentstatus == 'submitted') {
                        echo "<tr><td align='center'>\n";
                        $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                        $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                            "HEIGHT=\"16\" WIDTH=\"16\" >";
                        echo ($modtype == 'assign') ? 'assignment' : $modtype;
                        echo "</td>\n";
                        echo "<td align='left'><a href='" .
                            $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                            $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                            $activity->name . "</a></td>\n";

                    } else {
                        continue;
                    }
                } else {
                    echo "<tr><td align='center'>\n";
                    $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                    $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                        "HEIGHT=\"16\" WIDTH=\"16\" >";
                    echo ($modtype == 'assign') ? 'assignment' : $modtype;
                    echo "</td>\n";
                    echo "<td align='left'><a href='" .
                        $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                        $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                        $activity->name . "</a></td>\n";
                }
            }
        }
    }
} else if ($show == 'notattempted') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $menteeid, null);
            $activitystate = $data->completionstate;
            $assignmentstatus = block_fn_mentor_assignment_status($activity, $menteeid);
            if ($activitystate == 0) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {
                    continue;
                }
                echo "<tr><td align='center'>\n";
                $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                    "HEIGHT=\"16\" WIDTH=\"16\" >";
                echo ($modtype == 'assign') ? 'assignment' : $modtype;
                echo "</td>\n";
                echo "<td align='left'><a href='" .
                    $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                    $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                    $activity->name . "</a></td>\n";
            }
        }
    }
} else if ($show == 'waitingforgrade') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $menteeid, null);
            $activitystate = $data->completionstate;
            $assignmentstatus = block_fn_mentor_assignment_status($activity, $menteeid);
            if (($activitystate == 0)||($activitystate == 1)||($activitystate == 2)||($activitystate == 3)) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {
                    if (isset($assignmentstatus)) {
                        if ($assignmentstatus == 'waitinggrade') {
                            echo "<tr><td align='center'>\n";
                            $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                            $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                                "HEIGHT=\"16\" WIDTH=\"16\" >";
                            echo ($modtype == 'assign') ? 'assignment' : $modtype;
                            echo "</td>\n";
                            echo "<td align='left'><a href='" .
                                $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                                $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                                $activity->name . "</a></td>\n";
                        }
                    }
                }
            }
        }
    }
} else if ($show == 'draft') {
    if ($activities) {
        foreach ($activities as $activity) {
            if (!$activity->visible) {
                continue;
            }
            $data = $completion->get_data($activity, true, $menteeid, null);
            $activitystate = $data->completionstate;
            $assignmentstatus = block_fn_mentor_assignment_status($activity, $menteeid);
            if (($activitystate == 0) || ($activitystate == 1) || ($activitystate == 2) || ($activitystate == 3)) {
                if (($activity->module == 1)
                        && ($activity->modname == 'assignment' || $activity->modname == 'assign')
                        && ($activity->completion == 2)
                        && $assignmentstatus) {
                    if (isset($assignmentstatus)) {
                        if ($assignmentstatus == 'saved') {
                            echo "<tr><td align='center'>\n";
                            $modtype = $DB->get_field('modules', 'name', array('id' => $activity->module));
                            $modicon = "<IMG BORDER=0 VALIGN=absmiddle SRC=\"$CFG->wwwroot/mod/$modtype/pix/icon.png\" ".
                                "HEIGHT=\"16\" WIDTH=\"16\" >";
                            echo ($modtype == 'assign') ? 'assignment' : $modtype;
                            echo "</td>\n";
                            echo "<td align='left'><a href='" .
                                $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid'>$modicon</a><a href='" .
                                $CFG->wwwroot . "/mod/$modtype/view.php?id=$data->coursemoduleid' style=\"padding-left:4px\">" .
                                $activity->name . "</a></td>\n";
                        }
                    }
                }
            }
        }
    }
}
echo"</table>\n";

echo '</td></tr></table>';

echo "</div>";
echo $OUTPUT->footer($course);
