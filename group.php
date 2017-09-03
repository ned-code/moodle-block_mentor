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

// Paging options.
$page      = optional_param('page', 0, PARAM_INT);
$perpage   = optional_param('perpage', 20, PARAM_INT);
$sort      = optional_param('sort', 'name', PARAM_ALPHANUM);
$dir       = optional_param('dir', 'ASC', PARAM_ALPHA);
// Action.
$action    = optional_param('action', false, PARAM_ALPHA);
$search    = optional_param('search', '', PARAM_TEXT);

require_login(null, false);
$contextsystem = context_system::instance();

// Permission.
require_capability('block/fn_mentor:assignmentor', context_system::instance());

$thispageurl = new moodle_url('/blocks/fn_mentor/group.php');

$PAGE->set_url($thispageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_context($contextsystem);
$PAGE->requires->css('/blocks/fn_mentor/css/styles.css');

$name = get_string('managegroups', 'block_fn_mentor');
$title = get_string('managegroups', 'block_fn_mentor');
$heading = $SITE->fullname;

// Breadcrumb.
$PAGE->navbar->add(get_string('pluginname', 'block_fn_mentor'));
$PAGE->navbar->add(get_string('managementorgroups', 'block_fn_mentor'), new moodle_url('/blocks/fn_mentor/group_members.php'));
$PAGE->navbar->add($name);

$PAGE->set_title($title);
$PAGE->set_heading($heading);

$datacolumns = array(
    'id' => 'g.id',
    'name' => 'g.name',
    'idnumber' => 'g.idnumber'
);

// Sort.
$order = '';
if ($sort) {
    $order = " ORDER BY $datacolumns[$sort] $dir";
}

// Count records for paging.
$countsql = "SELECT COUNT(1) FROM {block_fn_mentor_group} g";
$totalcount = $DB->count_records_sql($countsql);

// Table columns.
$columns = array(
    'rowcount',
    'name',
    'idnumber',
    'action'
);

$sql = "SELECT g.*
          FROM {block_fn_mentor_group} g
               $order";


foreach ($columns as $column) {
    if (($column == 'rowcount') || ($column == 'action')) {
        $string[$column] = '';
    } else {
        $string[$column] = get_string($column, 'block_fn_mentor');
    }
    if ($sort != $column) {
        $columnicon = "";
        if ($column == "name") {
            $columndir = "ASC";
        } else {
            $columndir = "ASC";
        }
    } else {
        $columndir = $dir == "ASC" ? "DESC" : "ASC";
        if ($column == "minpoint") {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        } else {
            $columnicon = ($dir == "ASC") ? "sort_asc" : "sort_desc";
        }
        $columnicon = "<img class='iconsort' src=\"" . $OUTPUT->pix_url('t/' . $columnicon) . "\" alt=\"\" />";

    }
    if (($column == 'rowcount') || ($column == 'action')) {
        $$column = $string[$column];
    } else {
        $sorturl = $thispageurl;
        $sorturl->param('perpage', $perpage);
        $sorturl->param('sort', $column);
        $sorturl->param('dir', $columndir);
        $sorturl->param('search', $search);

        $$column = html_writer::link($sorturl->out(false), $string[$column]).$columnicon;
    }
}

$table = new html_table();

$table->head = array();
$table->wrap = array();
foreach ($columns as $column) {
    $table->head[$column] = $$column;
    $table->wrap[$column] = '';
}

// Override cell wrap.
$table->wrap['action'] = 'nowrap';

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
            case 'action':
                // Edit.
                $actionurl = new moodle_url('/blocks/fn_mentor/group_edit.php', array('id' => $tablerow->id ));
                $actiontext = get_string('edit', 'block_fn_mentor');
                $actionicon = html_writer::img($OUTPUT->pix_url('cog', 'block_fn_mentor'), $actiontext, array('width' => '16', 'height' => '16'));
                $actionlinks .= html_writer::link($actionurl->out(), $actionicon,
                        array('class' => 'actionlink', 'title' => $actiontext)).' ';
                // Delete.
                $actionurl = new moodle_url('/blocks/fn_mentor/group_delete.php', array('id' => $tablerow->id ));
                $actiontext = get_string('delete', 'block_fn_mentor');
                $actionicon = html_writer::img($OUTPUT->pix_url('delete', 'block_fn_mentor'), $actiontext, array('width' => '16', 'height' => '16'));
                $actionlinks .= html_writer::link($actionurl->out(), $actionicon,
                        array('class' => 'actionlink', 'title' => $actiontext)).' ';

                $$varname = new html_table_cell($actionlinks);
                break;
            default:
                $$varname = new html_table_cell($tablerow->$column);
        }
    }

    $row->cells = array();
    foreach ($columns as $column) {
        $varname = 'cell' . $column;
        $row->cells[$column] = $$varname;
    }
    $table->data[] = $row;

}

echo $OUTPUT->header();

$currenttab = 'managementorgroups';
require('tabs.php');

echo html_writer::start_div('page-content-wrapper', array('id' => 'page-content'));

$pagingurl = new moodle_url('/blocks/fn_mentor/group.php',
    array(
        'perpage' => $perpage,
        'sort' => $sort,
        'dir' => $dir,
        'search' => $search
    )
);

$pagingbar = new paging_bar($totalcount, $page, $perpage, $pagingurl, 'page');

echo $OUTPUT->render($pagingbar);
echo html_writer::table($table);
echo $OUTPUT->render($pagingbar);

// Add record form.
$formurl = new moodle_url('/blocks/fn_mentor/group_edit.php', array('action' => 'add'));
$submitbutton  = html_writer::tag('button', get_string('creategroup', 'block_fn_mentor'), array(
    'class' => 'btn btn-secondary spark-add-record-btn',
    'type' => 'submit',
    'value' => 'submit',
));
$formadd = html_writer::tag('form', $submitbutton, array(
    'action' => $formurl->out(),
    'method' => 'post',
    'class' => 'spark-add-record-form',
    'autocomplete' => 'off'
));

// Close.
$formurl = new moodle_url('/blocks/fn_mentor/group_members.php');
$submitbutton  = html_writer::tag('button', get_string('close', 'block_fn_mentor'), array(
    'class' => 'btn btn-secondary spark-close-btn',
    'type' => 'submit',
    'value' => 'submit',
));
$formclose = html_writer::tag('form', $submitbutton, array(
    'action' => $formurl->out(),
    'method' => 'post',
    'class' => 'spark-close-form',
    'autocomplete' => 'off'
));

echo html_writer::div($formadd.' '.$formclose, 'add-record-btn-wrapper', array('id' => 'add-record-btn'));

echo html_writer::end_div(); // Main wrapper.
echo $OUTPUT->footer();
