<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Perform action of joining or leaving groups of users
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$action = required_param('action', PARAM_TEXT);
$id = required_param('id', PARAM_INT);

$group = $DB->get_record('customgroups_groups', ['id' => $id], '*', MUST_EXIST);
$moduleinstance = $DB->get_record('customgroups', array('id' => $group->module), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

if (!customgroups_isactive($moduleinstance)) {
    throw new moodle_exception('Module is not active');
}

$modulecontext = context_module::instance($cm->id);
require_capability('mod/customgroups:joingroup', $modulecontext);

$redirecturl = new moodle_url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id], $group ? 'g-' . $group->id : null);

if ($action == 'join') {
    if (!customgroups_canjoingroup($group->id, $moduleinstance)) {
        throw new moodle_exception('Cannot join group');
    }
    customgroups_joingroup($group->id);
    redirect($redirecturl);
    exit;
} else if ($action == 'leave') {
    if (customgroups_getjoinedgroupid($moduleinstance->id) != $group->id) {
        throw new moodle_exception('User is not in the group');
    }
    customgroups_leavegroup($group->id);
    redirect($redirecturl);
    exit;
} else {
    throw new moodle_exception('Invalid action');
}
