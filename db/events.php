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

// List of observers.
$observers = array(
    array(
        'eventname'   => '\core\event\user_updated',
        'callback'    => 'block_fn_mentor_observer::user_updated',
    ),
    array(
        'eventname'   => '\core\event\role_assigned',
        'callback'    => 'block_fn_mentor_observer::role_assigned',
    ),
    array(
        'eventname'   => '\core\event\role_unassigned',
        'callback'    => 'block_fn_mentor_observer::role_unassigned',
    ),
    array(
        'eventname'   => '\core\event\user_profile_viewed',
        'callback'    => 'block_fn_mentor_observer::user_profile_viewed',
    ),
    array(
        'eventname'   => '\core\event\user_loggedin',
        'callback'    => 'block_fn_mentor_observer::user_updated',
    )
);
