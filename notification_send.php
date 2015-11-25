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

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 0, PARAM_RAW);
$process = optional_param('process', 0, PARAM_INT);

require_login();
confirm_sesskey();

//PERMISSION
require_capability('block/fn_mentor:createnotificationrule', context_system::instance());

$title = get_string('page_title_assign_mentor', 'block_fn_mentor');
$heading = $SITE->fullname;

$PAGE->requires->jquery();
$PAGE->requires->js('/blocks/fn_mentor/js/selection.js');

$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$PAGE->set_url('/blocks/fn_mentor/notification_send.php');
$PAGE->set_pagelayout('course');
$PAGE->set_context(context_system::instance());
$PAGE->set_title($heading);
$PAGE->set_heading($heading);
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/course_overview.php'));
$PAGE->navbar->add(get_string('notification_rules', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/notification_rules.php'));



if (($action == 'send') && ($id)) {
    $notification_rule = $DB->get_record('block_fn_mentor_notification',array('id'=>$id),'*',MUST_EXIST);
}


if ($process) {
    $report = block_fn_mentor_send_notifications($notification_rule->id, true);
    echo $OUTPUT->header();

    //echo $OUTPUT->confirm($report, new moodle_url('/blocks/fn_mentor/notification_rules.php'), '/blocks/fn_mentor/notification_rules.php');

    $redirecturl = new moodle_url('/blocks/fn_mentor/notification_rules.php');

    echo '<div class="box generalbox" id="notice">
          <p>'.$report.'</p>
          <div class="buttons">
           <div class="singlebutton">
              <form action="'.$redirecturl->out().'" method="post">
                <div>
                  <input type="hidden" value="'.sesskey().'" name="sesskey"/>
                  <input class="singlebutton" type="submit" value="'.get_string('continue', 'block_fn_mentor').'"/>
                </div>
              </form>
            </div>
           </div>
         </div>';



    echo $OUTPUT->footer();
} else {
    echo $OUTPUT->header();
    echo '<span class="fn-send-confirm">';
    echo '<div id="notice" style="display: none" class="box generalbox notice2">'.get_string('messagesprocessing', 'block_fn_mentor').'<br><img style="margin-left: 60px;" src="'.$OUTPUT->pix_url('email3','block_fn_mentor').'"></div>';
    echo $OUTPUT->confirm(get_string('confirmsend', 'block_fn_mentor'),
        new moodle_url('/blocks/fn_mentor/notification_send.php', array('id' => $id, 'process' => 1)), '/blocks/fn_mentor/notification_rules.php');
    echo '</span>';
    echo $OUTPUT->footer();
}