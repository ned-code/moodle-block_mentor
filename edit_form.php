<?php

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/../../config.php');


class block_fn_mentor_edit_form extends block_edit_form {

    protected function specific_definition($mform) {

        //$mform->addElement('header', 'configheader', get_string('blocksettings', 'block_fn_mentor'));

        $yesno = array(0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'config_show_mentor_sort', get_string('show_mentor_sort', 'block_fn_mentor'), $yesno);
        $mform->setDefault('config_show_mentor_sort', 0);
    }

}
