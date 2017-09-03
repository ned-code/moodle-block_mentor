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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');
require_once($CFG->dirroot.'/user/editlib.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/lib.php');
require_once($CFG->dirroot.'/blocks/fn_mentor/import_form.php');

$iid         = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);

core_php_time_limit::raise(60 * 60); // 1 hour should be enough.
raise_memory_limit(MEMORY_HUGE);

$struserupdated             = get_string('useraccountupdated', 'tool_uploaduser');
$strusernotupdated          = get_string('usernotupdatederror', 'error');
$strusernotupdatednotexists = get_string('usernotupdatednotexists', 'error');
$struseruptodate            = get_string('useraccountuptodate', 'tool_uploaduser');
$stremailduplicate          = get_string('useremailduplicate', 'error');
$duplicateemaildetected     = get_string('duplicateemaildetected', 'block_fn_mentor');
$errorstr                   = get_string('error');
$stryes                     = get_string('yes');
$strno                      = get_string('no');
$stryesnooptions = array(0 => $strno, 1 => $stryes);

$today = time();
$today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);

require_login();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

if (!is_siteadmin()) {
    print_error('permissionerror', 'block_fn_mentor');
    die;
}

// Experimental NED columns.
$includeextranedcolumns = get_config('block_fn_mentor', 'includeextranedcolumns');
$themes = array_keys(get_list_of_themes());

// Array of all valid fields for validation.
$stdfields = array('firstname', 'lastname', 'email', 'mentor_role', 'mentor_group',
    'site_group', 'cohort', 'themefront', 'themecourse', 'subnumber'
);

$prffields = array();

if ($proffields = $DB->get_records('user_info_field')) {
    foreach ($proffields as $key => $proffield) {
        $profilefieldname = 'profile_field_'.$proffield->shortname;
        $prffields[] = $profilefieldname;
        $proffields[$profilefieldname] = $proffield;
        unset($proffields[$key]);
    }
}

$returnurl = new moodle_url('/blocks/fn_mentor/importexport.php');

// HTTPS is required in this page when $CFG->loginhttps enabled.
$PAGE->https_required();

$PAGE->set_url('/blocks/fn_mentor/import.php');
$PAGE->set_pagelayout('course');
$PAGE->set_context(context_system::instance());
$PAGE->verify_https_required();

$editaccount = get_string('import', 'block_fn_mentor');
$login       = get_string('login');

$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('importexport', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/importexport.php'));
$PAGE->navbar->add($editaccount);

$name = get_string('import', 'block_fn_mentor');
$title = get_string('import', 'block_fn_mentor');

$PAGE->set_title($title);
$PAGE->set_heading($SITE->fullname);

if (empty($iid)) {
    $mform1 = new user_upload_form1();

    if ($formdata = $mform1->get_data()) {

        $iid = csv_import_reader::get_new_iid('uploaduser');
        $cir = new csv_import_reader($iid, 'uploaduser');

        $content = $mform1->get_file_content('userfile');

        $readcount = $cir->load_csv_content($content, 'UTF-8', 'comma');
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            print_error('csvloaderror', '', $returnurl, $csvloaderror);
        }
        $filecolumns = block_fn_mentor_uu_validate_user_upload_columns($cir, $stdfields, $prffields, $returnurl);
    } else {
        echo $OUTPUT->header();

        $currenttab = 'importexport';
        require('tabs.php');

        echo $OUTPUT->heading(get_string('uploadusers', 'block_fn_mentor'));

        $toform = new stdClass();
        $mform1->set_data($toform);
        $mform1->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    $cir = new csv_import_reader($iid, 'uploaduser');
    $filecolumns = block_fn_mentor_uu_validate_user_upload_columns($cir, $stdfields, $prffields, $returnurl);
}

$mform2 = new user_uploaduser_form2(null,
    array('columns' => $filecolumns, 'data' => array('iid' => $iid, 'previewrows' => $previewrows))
);

// If a file has been uploaded, then process it.
if ($formdata = $mform2->is_cancelled()) {
    $cir->cleanup(true);
    redirect($returnurl);

} else if ($formdata = $mform2->get_data()) {

    echo $OUTPUT->header();

    $currenttab = 'importexport';
    require('tabs.php');

    echo $OUTPUT->heading(get_string('uploadusersresult', 'tool_uploaduser'));

    $mentorsystemroleid = get_config('block_fn_mentor', 'mentor_role_system');
    $mentoruserroleid = get_config('block_fn_mentor', 'mentor_role_user');

    $contextsystem = context_system::instance();

    $usersuptodate = 0;
    $userserrors   = 0;
    $usersskipped  = 0;

    // Caches.
    $ccache         = array();
    $cohorts        = array();
    $mentorgroups   = array();

    // Frontpage course.
    $frontpage = $DB->get_record('course', array('id' => SITEID));
    $shortname = $frontpage->shortname;

    $ccache[$shortname] = $frontpage;
    $ccache[$shortname]->groups = null;

    // Init csv import helper.
    $cir->init();
    $linenum = 1; // Column header is first line.

    // Init upload progress tracker.
    $upt = new uu_progress_tracker();
    $upt->columns = array('line', 'id', 'username', 'firstname', 'lastname', 'email', 'enrolments', 'suspended');
    $ci = 0;
    echo '<table id="uuresults" class="generaltable boxaligncenter flexible-wrap" summary="'.
        get_string('uploadusersresult', 'tool_uploaduser').'">';
    echo '<tr class="heading r0">';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('uucsvline', 'tool_uploaduser').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">ID</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('username').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('firstname').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('lastname').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('email').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('enrolments', 'enrol').'</th>';
    echo '<th class="header c'.$ci++.'" scope="col">'.get_string('suspended', 'auth').'</th>';
    echo '</tr>';

    while ($line = $cir->next()) {
        $upt->flush();
        $linenum++;

        $upt->track('line', $linenum);

        $user = new stdClass();

        // Add fields to user object.
        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // This should not happen.
                continue;
            }
            $key = $filecolumns[$keynum];
            $user->$key = trim($value);

            if (in_array($key, $upt->columns)) {
                $upt->track($key, s($value), 'normal');
            }
        }
        // TODO: email check.
        $error = false;
        if (!isset($user->email) || $user->email === '') {
            $upt->track('email', $errorstr, 'error');
            $error = true;
        }
        if ($error) {
            $userserrors++;
            continue;
        }
        if ($DB->count_records_select('user', 'email = ?', array($user->email)) > 1) {
            $upt->track('email', $duplicateemaildetected, 'error');
            $error = true;
        }
        if ($error) {
            $userserrors++;
            continue;
        }
        if ($existinguser = $DB->get_record('user', array('email' => $user->email))) {
            $upt->track('id', $existinguser->id, 'normal', false);
        }
        // Can we process with update?
        $skip = false;
        if (!$existinguser) {
            $usersskipped++;
            $skip = true;
        }

        if ($skip) {
            continue;
        }

        $user->id = $existinguser->id;

        $upt->track('username', html_writer::link(new moodle_url('/user/profile.php',
            array('id' => $existinguser->id)), s($existinguser->username)), 'normal', false);
        $upt->track('suspended', $stryesnooptions[$existinguser->suspended], 'normal', false);

        // Load existing profile data.
        profile_load_data($existinguser);

        // No user information changed.
        $usersuptodate++;

        // Mentor role.
        if (!empty($user->mentor_role)) {
            if (!($user->mentor_role == 'mentor') && !($user->mentor_role == 'mentee')) {
                $upt->track('enrolments', get_string('unknownrole', 'error', s($user->mentor_role)), 'error');
            } else if ($mentorsystemroleid && $user->mentor_role == 'mentor') {
                role_assign($mentorsystemroleid, $user->id, $contextsystem->id);
                $upt->track('enrolments', get_string('assignedsysrole', 'tool_uploaduser', $user->mentor_role));
            }
        }

        if ($mentoruserroleid && !empty($user->mentor_role) && $user->mentor_role == 'mentee') {
            foreach ($filecolumns as $column) {
                if (!preg_match('/^mentor\d*$/', $column)) {
                    continue;
                }
                if (!empty($user->$column)) {
                    if ($mentor = $DB->get_record('user', array('email' => $user->$column, 'deleted' => 0))) {
                        if (block_fn_mentor_is_mentor($mentor->id)) {
                            $usercontext = context_user::instance($user->id);
                            role_assign($mentoruserroleid, $mentor->id, $usercontext->id);
                        }
                    }
                }
            }
        }
        // Mentor group.
        foreach ($filecolumns as $column) {
            if (!preg_match('/^mentor_group\d*$/', $column)) {
                continue;
            }
            if (!empty($user->$column)) {
                $addmentorgroup = $user->$column;
                if (!isset($mentorgroups[$addmentorgroup])) {
                    if (is_number($addmentorgroup)) {
                        $mentorgroup = $DB->get_record('block_fn_mentor_group', array('id' => $addmentorgroup));
                    } else {
                        $mentorgroup = $DB->get_record('block_fn_mentor_group', array('idnumber' => $addmentorgroup));
                        if (empty($mentorgroup)) {
                            $newgroup = new stdClass();
                            $newgroup->name = $addmentorgroup;
                            $newgroup->idnumber = $addmentorgroup;
                            $newgroup->timecreated = time();
                            $mentorgroupid = $DB->insert_record('block_fn_mentor_group', $newgroup);
                            $mentorgroup = $DB->get_record('block_fn_mentor_group', array('id' => $mentorgroupid));
                        }
                    }

                    if (empty($mentorgroup)) {
                        $mentorgroups[$addmentorgroup] = get_string('unknownmentorgroup', 'block_fn_mentor', s($addmentorgroup));
                    } else {
                        $mentorgroups[$addmentorgroup] = $mentorgroup;
                    }
                }

                if (is_object($mentorgroups[$addmentorgroup])) {
                    $mentorgroup = $mentorgroups[$addmentorgroup];
                    if (block_fn_mentor_is_mentor($user->id)) {
                        block_fn_mentor_add_group_member($user->id, $mentorgroup->id, 'M');
                        $upt->track('enrolments', get_string('useraddedtomentorgroup', 'block_fn_mentor', s($mentorgroup->name)));
                    } else {
                        $upt->track('enrolments', get_string('userisnotmentor', 'block_fn_mentor', s($addmentorgroup)), 'error');
                    }
                } else {
                    $upt->track('enrolments', $mentorgroups[$addmentorgroup], 'error');
                }
            }
        }

        // Cohort.
        foreach ($filecolumns as $column) {
            if (!preg_match('/^cohort\d*$/', $column)) {
                continue;
            }
            if (!empty($user->$column)) {
                $addcohort = $user->$column;
                if (!isset($cohorts[$addcohort])) {
                    if (is_number($addcohort)) {
                        // Only non-numeric idnumbers!
                        $cohort = $DB->get_record('cohort', array('id' => $addcohort));
                    } else {
                        $cohort = $DB->get_record('cohort', array('idnumber' => $addcohort));
                        if (empty($cohort) && has_capability('moodle/cohort:manage', context_system::instance())) {
                            // Cohort was not found. Create a new one.
                            $cohortid = cohort_add_cohort((object)array(
                                'idnumber' => $addcohort,
                                'name' => $addcohort,
                                'contextid' => context_system::instance()->id
                            ));
                            $cohort = $DB->get_record('cohort', array('id' => $cohortid));
                        }
                    }

                    if (empty($cohort)) {
                        $cohorts[$addcohort] = get_string('unknowncohort', 'core_cohort', s($addcohort));
                    } else if (!empty($cohort->component)) {
                        // Cohorts synchronised with external sources must not be modified!
                        $cohorts[$addcohort] = get_string('external', 'core_cohort');
                    } else {
                        $cohorts[$addcohort] = $cohort;
                    }
                }

                if (is_object($cohorts[$addcohort])) {
                    $cohort = $cohorts[$addcohort];
                    if (!$DB->record_exists('cohort_members', array('cohortid' => $cohort->id, 'userid' => $user->id))) {
                        cohort_add_member($cohort->id, $user->id);
                        // We might add special column later, for now let's abuse enrolments.
                        $upt->track('enrolments', get_string('useraddedtocohort', 'block_fn_mentor', s($cohort->name)));
                    }
                } else {
                    // Error message.
                    $upt->track('enrolments', $cohorts[$addcohort], 'error');
                }
            }
        }

        // Site group.
        foreach ($filecolumns as $column) {
            if (!preg_match('/^site_group\d*$/', $column)) {
                continue;
            }
            if (!empty($user->$column)) {
                // Build group cache.
                if (is_null($ccache[$shortname]->groups)) {
                    $ccache[$shortname]->groups = array();
                    if ($groups = groups_get_all_groups(SITEID)) {
                        foreach ($groups as $gid => $group) {
                            $ccache[$shortname]->groups[$gid] = new stdClass();
                            $ccache[$shortname]->groups[$gid]->id   = $gid;
                            $ccache[$shortname]->groups[$gid]->name = $group->name;
                            if (!is_numeric($group->name)) {
                                $ccache[$shortname]->groups[$group->name] = new stdClass();
                                $ccache[$shortname]->groups[$group->name]->id   = $gid;
                                $ccache[$shortname]->groups[$group->name]->name = $group->name;
                            }
                        }
                    }
                }
                // Group exists?
                $addgroup = $user->$column;
                if (!array_key_exists($addgroup, $ccache[$shortname]->groups)) {
                    // If group doesn't exist,  create it.
                    $newgroupdata = new stdClass();
                    $newgroupdata->name = $addgroup;
                    $newgroupdata->courseid = $ccache[$shortname]->id;
                    $newgroupdata->description = '';
                    $gid = groups_create_group($newgroupdata);
                    if ($gid) {
                        $ccache[$shortname]->groups[$addgroup] = new stdClass();
                        $ccache[$shortname]->groups[$addgroup]->id   = $gid;
                        $ccache[$shortname]->groups[$addgroup]->name = $newgroupdata->name;
                    } else {
                        $upt->track('enrolments', get_string('unknowngroup', 'error', s($addgroup)), 'error');
                        continue;
                    }
                }
                $gid   = $ccache[$shortname]->groups[$addgroup]->id;
                $gname = $ccache[$shortname]->groups[$addgroup]->name;

                try {
                    if (groups_add_member($gid, $user->id)) {
                        $upt->track('enrolments', get_string('addedtogroup', '', s($gname)));
                    } else {
                        $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                    }
                } catch (moodle_exception $e) {
                    $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                    continue;
                }
            }
        }

        // Assign mentor.
        foreach ($filecolumns as $column) {
            if (!preg_match('/^mentor\d*$/', $column)) {
                continue;
            }
            if (!empty($user->$column)) {
                $mentoremail = $user->$column;
                if ($mentoruser = $DB->get_record('user', array('email' => $mentoremail, 'deleted' => 0))) {
                    if (block_fn_mentor_assign_mentor_to_user($mentoruser->id, $user->id)) {
                        $upt->track('enrolments', get_string('assignedtomentor', 'block_fn_mentor', s($mentoremail)));
                    }
                } else {
                    $upt->track('enrolments', get_string('assignedtomentornot', 'block_fn_mentor', s($mentoremail)), 'error');
                }
            }
        }

        // Extra NED columns.
        if ($includeextranedcolumns) {
            foreach ($filecolumns as $column) {
                if (($column == 'subnumber' || $column == 'themefront' || $column == 'themecourse') && !empty($user->$column)) {
                    $field = 'profile_field_' . $column;
                    $value = $user->$column;
                    $user->$field = $value;

                    if (isset($proffields[$field])) {
                        require_once($CFG->dirroot . '/user/profile/field/' . $proffields[$field]->datatype . '/field.class.php');
                        $profilefieldclass = 'profile_field_' . $proffields[$field]->datatype;
                        $profilefield = new $profilefieldclass($proffields[$field]->id, $user->id);
                        $profilefield->edit_save_data($user);
                    }
                }
            }
        }
    }
    $upt->close();
    $cir->close();
    $cir->cleanup(true);

    echo $OUTPUT->box_start('boxwidthnarrow boxaligncenter generalbox', 'uploadresults');
    echo '<p>';
    if ($usersskipped) {
        echo get_string('usersskipped', 'tool_uploaduser').': '.$usersskipped.'<br />';
    }
    echo get_string('errors', 'tool_uploaduser').': '.$userserrors.'</p>';
    echo $OUTPUT->box_end();
    echo $OUTPUT->continue_button($returnurl);
    echo $OUTPUT->footer();
    die;
}



echo $OUTPUT->header();

$currenttab = 'importexport';
require('tabs.php');

echo $OUTPUT->heading(get_string('uploaduserspreview', 'tool_uploaduser'));
$data = array();
$cir->init();
$linenum = 1;
$noerror = true;
while ($linenum <= $previewrows and $fields = $cir->next()) {
    $linenum++;
    $rowcols = array();
    $rowcols['line'] = $linenum;
    foreach ($fields as $key => $field) {
        $rowcols[$filecolumns[$key]] = s(trim($field));
    }
    $rowcols['status'] = array();

    if (isset($rowcols['username'])) {
        $stdusername = clean_param($rowcols['username'], PARAM_USERNAME);
        if ($rowcols['username'] !== $stdusername) {
            $rowcols['status'][] = get_string('invalidusernameupload');
        }
        if ($userid = $DB->get_field('user', 'id',
            array('username' => $stdusername, 'mnethostid' => $CFG->mnet_localhost_id))) {
            $rowcols['username'] = html_writer::link(new moodle_url('/user/profile.php',
                array('id' => $userid)), $rowcols['username']);
        }
    }

    if (isset($rowcols['email'])) {
        if (!validate_email($rowcols['email'])) {
            $rowcols['status'][] = get_string('invalidemail');
        }

        $select = $DB->sql_like('email', ':email', false, true, false, '|');
        $params = array('email' => $DB->sql_like_escape($rowcols['email'], '|'));
        if ($DB->record_exists_select('user', $select , $params)) {
            $rowcols['status'][] = $stremailduplicate;
        }
    }
    $noerror = true;
    $rowcols['status'] = '';
    $data[] = $rowcols;
}
if ($fields = $cir->next()) {
    $data[] = array_fill(0, count($fields) + 2, '...');
}
$cir->close();

$table = new html_table();
$table->id = "uupreview";
$table->attributes['class'] = 'generaltable';
$table->summary = get_string('uploaduserspreview', 'tool_uploaduser');
$table->head = array();
$table->data = $data;

$table->head[] = get_string('uucsvline', 'tool_uploaduser');
foreach ($filecolumns as $column) {
    $table->head[] = $column;
}
$table->head[] = get_string('status');

echo html_writer::tag('div', html_writer::table($table), array('class' => 'flexible-wrap'));

// Print the form if valid values are available.
if ($noerror) {
    $toform = new stdClass();
    $mform2->set_data($toform);
    $mform2->display();
}
echo $OUTPUT->footer();