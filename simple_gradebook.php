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

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/lib/plagiarismlib.php');
require_once('lib.php');
require_once($CFG->dirroot . '/lib/outputrenderers.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

$courseid = optional_param('id', 2, PARAM_INT);
$group = optional_param('group', 0, PARAM_INT);
$unsubmitted = optional_param('unsubmitted', '0', PARAM_INT);

set_current_group($courseid, $group);

if ($courseid) {
    if (! $course = $DB->get_record('course', array('id' => $courseid))) {
        print_error('coursemisconf');
    }
} else {
    print_error('coursemisconf');
}


// Array of functions to call for grading purposes for modules.
$modgradesarray = array(
    'assign' => 'assign.submissions.fn.php',
    'quiz' => 'quiz.submissions.fn.php',
    'assignment' => 'assignment.submissions.fn.php',
    'forum' => 'forum.submissions.fn.php',
);

$context = context_course::instance($course->id);
$isteacher = has_capability('moodle/grade:viewall', $context);

$cobject = new stdClass();
$cobject->course = $course;

// If comes from course page.
$currentgroup = $SESSION->currentgroup[$course->id];

$students = get_enrolled_users($context, 'mod/assignment:submit', $currentgroup, 'u.*', 'u.id');

$simplegradebook = array();
$weekactivitycount = array();

foreach ($students as $key => $value) {
    $simplegradebook[$key]['name'] = $value->firstname.' '.substr($value->lastname, 0, 1).'.';
}

// Collect modules data.
$modnames = get_module_types_names();
$modnamesplural = get_module_types_names(true);
$modinfo = get_fast_modinfo($course->id);
$mods = $modinfo->get_cms();
$modnamesused = $modinfo->get_used_module_names();

$modarray = array($mods, $modnames, $modnamesplural, $modnamesused);

$cobject->mods = &$mods;
$cobject->modnames = &$modnames;
$cobject->modnamesplural = &$modnamesplural;
$cobject->modnamesused = &$modnamesused;
$cobject->sections = &$sections;


// FIND CURRENT WEEK.
$courseformatoptions = course_get_format($course)->get_format_options();
$coursenumsections = $courseformatoptions['numsections'];

$timenow = time();
$weekdate = $course->startdate;
$weekdate += 7200;

$weekofseconds = 604800;
$courseenddate = $course->startdate + ($weekofseconds * $coursenumsections);

// Calculate the current week based on today's date and the starting date of the course.
$currentweek = ($timenow > $course->startdate) ? (int) ((($timenow - $course->startdate) / $weekofseconds) + 1) : 0;
$currentweek = min($currentweek, $coursenumsections);


// Search through all the modules, pulling out grade data.
$sections = get_fast_modinfo($course->id)->get_section_info_all();

$upto = count($sections);

for ($i = 0; $i < $upto; $i++) {
    $numberofitem = 0;
    if (isset($sections[$i])) {
        $section = $sections[$i];
        if ($section->sequence) {
            $sectionmods = explode(",", $section->sequence);
            foreach ($sectionmods as $sectionmod) {
                if (empty($mods[$sectionmod])) {
                    continue;
                }

                $mod = $mods[$sectionmod];
                if (! isset($modgradesarray[$mod->modname])) {
                    continue;
                }
                // Don't count it if you can't see it.
                $mcontext = context_module::instance($mod->id);
                if (!$mod->visible && !has_capability('moodle/course:viewhiddenactivities', $mcontext)) {
                    continue;
                }
                global $DB;
                $instance = $DB->get_record($mod->modname, array("id" => $mod->instance));
                $item = $DB->get_record('grade_items',
                    array("itemtype" => 'mod', "itemmodule" => $mod->modname, "iteminstance" => $mod->instance)
                );

                $libfile = $CFG->dirroot . '/mod/' . $mod->modname . '/lib.php';
                if (file_exists($libfile)) {
                    require_once($libfile);
                    $gradefunction = $mod->modname . "_get_user_grades";


                    if ((($mod->modname != 'forum') || ($instance->assessed > 0))
                        && isset($modgradesarray[$mod->modname])) {

                        if (function_exists($gradefunction)) {
                            ++$numberofitem;
                            if ($mod->modname == 'quiz') {
                                $image = "<A target='_blank' HREF=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\" ".
                                    "TITLE=\"$instance->name\"><IMG BORDER=0 VALIGN=absmiddle ".
                                    "SRC=\"$CFG->wwwroot/blocks/fn_marking/pix/quiz.gif\" ".
                                    "HEIGHT=16 WIDTH=16 ALT=\"$mod->modfullname\"></A>";
                            } else {
                                $image = "<A target='_blank' HREF=\"$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id\" ".
                                    "TITLE=\"$instance->name\"><IMG BORDER=0 VALIGN=absmiddle ".
                                    "SRC=\"$CFG->wwwroot/mod/$mod->modname/pix/icon.gif\" ".
                                    "HEIGHT=16 WIDTH=16 ALT=\"$mod->modfullname\"></A>";
                            }

                            $weekactivitycount[$i]['mod'][] = $image;
                            foreach ($simplegradebook as $key => $value) {

                                if (($mod->modname == 'quiz')||($mod->modname == 'forum')) {

                                    if ($grade = $gradefunction($instance, $key)) {
                                        if ($item->gradepass > 0) {
                                            if ($grade[$key]->rawgrade >= $item->gradepass) {
                                                $simplegradebook[$key]['grade'][$i][$mod->id] = 'marked.gif';// Passed.
                                                $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                    'grademax' => $item->grademax
                                                );
                                            } else {
                                                $simplegradebook[$key]['grade'][$i][$mod->id] = 'incomplete.gif';// Fail.
                                                $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                    'grademax' => $item->grademax
                                                );
                                            }
                                        } else {
                                            // Graded (grade-to-pass is not set).
                                            $simplegradebook[$key]['grade'][$i][$mod->id] = 'graded_.gif';
                                            $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                'grademax' => $item->grademax
                                            );
                                        }
                                    } else {
                                        $simplegradebook[$key]['grade'][$i][$mod->id] = 'ungraded.gif';
                                        if ($unsubmitted) {
                                            $simplegradebook[$key]['avg'][] = array('grade' => 0, 'grademax' => $item->grademax);
                                        }
                                    }
                                } else if ($modstatus = block_fn_mentor_assignment_status($mod, $key, true)) {

                                    switch ($modstatus) {
                                        case 'submitted':
                                            if ($grade = $gradefunction($instance, $key)) {
                                                if ($item->gradepass > 0) {
                                                    if ($grade[$key]->rawgrade >= $item->gradepass) {
                                                        $simplegradebook[$key]['grade'][$i][$mod->id] = 'marked.gif';// Passed.
                                                        $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                            'grademax' => $item->grademax
                                                        );
                                                    } else {
                                                        $simplegradebook[$key]['grade'][$i][$mod->id] = 'incomplete.gif';// Fail.
                                                        $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                            'grademax' => $item->grademax
                                                        );
                                                    }
                                                } else {
                                                    // Graded (grade-to-pass is not set).
                                                    $simplegradebook[$key]['grade'][$i][$mod->id] = 'graded_.gif';
                                                    $simplegradebook[$key]['avg'][] = array('grade' => $grade[$key]->rawgrade,
                                                        'grademax' => $item->grademax
                                                    );
                                                }
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
                                    if ($unsubmitted) {
                                        $simplegradebook[$key]['avg'][] = array('grade' => 0, 'grademax' => $item->grademax);
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
}


echo '<div class="tablecontainer">';
// TABLE.
echo "<table class='simplegradebook'>";

echo "<tr>";
echo "<th>Name</th>";
echo "<th>%</th>";
foreach ($weekactivitycount as $weeknum => $weekactivity) {
    if ($weekactivity['numofweek']) {
        echo '<th colspan="'.$weekactivity['numofweek'].'">Week-'.$weeknum.'</th>';
    }
}
echo "</tr>";

echo "<tr>";
echo "<td class='mod-icon'></td>";
echo "<td class='mod-icon'></td>";
foreach ($weekactivitycount as $key => $value) {
    if ($value['numofweek']) {
        foreach ($value['mod'] as $imagelink) {
            echo '<td class="mod-icon">'.$imagelink.'</td>';
        }
    }
}
echo "</tr>";
$counter = 0;
foreach ($simplegradebook as $studentid => $studentreport) {
    $counter++;
    if ($counter % 2 == 0) {
        $studentclass = "even";
    } else {
        $studentclass = "odd";
    }
    echo '<tr>';
    echo '<td nowrap="nowrap" class="'.
        $studentclass.' name"><a target="_blank" href='.
        $CFG->wwwroot.'/grade/report/user/index.php?userid='.
        $studentid.'&id='.$course->id.'">'.$studentreport['name'].'</a></td>';

    $gradetot = 0;
    $grademaxtot = 0;
    $avg = 0;

    if (isset($studentreport['avg'])) {
        foreach ($studentreport['avg'] as $sgrades) {
            $gradetot += $sgrades['grade'];
            $grademaxtot += $sgrades['grademax'];
        }

        if ($grademaxtot) {
            $avg = ($gradetot / $grademaxtot) * 100;
            if ( $avg >= 50) {
                echo '<td class="green">'.round($avg, 0).'</td>';
            } else {
                echo '<td class="red">'.round($avg, 0).'</td>';
            }
        } else {
            echo '<td class="red"></td>';
        }
    } else {
        echo '<td class="red"> - </td>';
    }


    foreach ($studentreport['grade'] as $sgrades) {
        foreach ($sgrades as $sgrade) {
            echo '<td class="'.$studentclass.' icon">'.'<img src="' .
                $CFG->wwwroot . '/blocks/fn_marking/pix/'.
                $sgrade.'" height="16" width="16" alt="">'.'</td>';
        }
    }
    echo '</tr>';
}

echo "</table>";
echo "</div>";