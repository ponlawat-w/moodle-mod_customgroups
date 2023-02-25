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
 * Prints an instance of mod_customgroups.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$instance = optional_param('instance', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);

if (!$instance && !$id) {
    throw new moodle_exception('Parameters error');
}

$group = null;
if ($id) {
    $group = $DB->get_record('customgroups_groups', ['id' => $id], '*', MUST_EXIST);
    $instance = $group->module;
}
$moduleinstance = $DB->get_record('customgroups', array('id' => $instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);

if (!customgroups_isactive($moduleinstance)) {
    throw new moodle_exception('Module is not active');
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/customgroups:creategroup', $modulecontext);
if (!$id && !customgroups_cancreategroup($modulecontext, $moduleinstance->id)) {
    throw new moodle_exception('User does not have permission to create group or there is already a group created by this user in the module.');
}
if ($id && $USER->id != $group->user) {
    throw new moodle_exception('Cannot edit group because user is not group owner');
}

$redirecturl = new moodle_url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id], $group ? 'g-' . $group->id : null);

$form = null;
if ($group && $action == 'remove') {
    require_once(__DIR__ . '/classes/form/confirm_form.php');
    $titlestrkey = 'deletegroup';
    $form = new confirm_form(null, [
        'message' => get_string('confirm_removegroup', 'mod_customgroups', $group->name),
        'instance' => $moduleinstance->id,
        'id' => $group->id,
        'action' => 'remove'
    ]);
    if ($form->is_cancelled()) {
        redirect($redirecturl);
        exit;
    }
    if ($form->is_submitted()) {
        customgroups_deletegroup($group->id);
        redirect($redirecturl);
        exit;
    }
} else {
    require_once(__DIR__ . '/classes/form/editgroup_form.php');
    
    $titlestrkey = $group ? 'editgroup' : 'creategroup';
    
    $customdata = ['instance' => $instance];
    if ($group) {
        $customdata['id'] = $group->id;
        $customdata['name'] = $group->name;
        $customdata['description'] = $group->description;
        $customdata['descriptionformat'] = $group->descriptionformat;
    }
    
    $form = new editgroup_form(null, $customdata);
    if ($form->is_cancelled()) {
        redirect($redirecturl);
        exit;
    }
    if ($form->is_submitted() && $form->is_validated()) {
        $data = $form->get_data();
        if (!$data->id) {
            if ($newid = customgroups_creategroupfromform($moduleinstance->id, $course->id, $data)) {
                redirect($redirecturl . '#g-' . $newid);
                exit;
            }
            throw new moodle_exception('Cannot create group');
        }
        $group->name = $data->name;
        $group->description = $data->description['text'];
        $group->descriptionformat = $data->description['format'];
        if ($DB->update_record('customgroups_groups', $group)) {
            redirect($redirecturl);
            exit;
        }
        throw new moodle_exception('Cannot edit group');
    }
}

$PAGE->set_url('/mod/customgroups/editgroup.php', array('id' => $moduleinstance->id));
$PAGE->set_title(format_string($course->fullname) . ': ' . get_string($titlestrkey, 'mod_customgroups'));
$PAGE->set_heading(get_string($titlestrkey, 'mod_customgroups'));
$PAGE->set_context($modulecontext);
$PAGE->navbar->add(get_string($titlestrkey, 'mod_customgroups'));

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
