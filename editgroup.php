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

$instance = required_param('instance', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);

$moduleinstance = $DB->get_record('customgroups', array('id' => $instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/customgroups:creategroup', $modulecontext);
if (!customgroups_cancreategroup($modulecontext, $moduleinstance->id)) {
    throw new moodle_exception('User does not have permission to create group or there is already a group created by this user in the module.');
}

require_once(__DIR__ . '/classes/form/editgroup_form.php');

$form = new editgroup_form(null, ['instance' => $instance]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id]));
    exit;
}
if ($form->is_submitted() && $form->is_validated()) {
    $data = $form->get_data();
    if (!$data->id) {
        if (customgroups_creategroupfromform($moduleinstance->id, $course->id, $data)) {
            redirect(new moodle_url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id])); // TODO: Change to group page
            exit;
        }
        throw new moodle_exception('Cannot create group');
    }
    // TODO: Update group
    throw new moodle_exception('NotImplemented');
}

$PAGE->set_url('/mod/customgroups/create.php', array('id' => $moduleinstance->id));
$PAGE->set_title(format_string($course->fullname) . ': ' . get_string('creategroup', 'mod_customgroups'));
$PAGE->set_heading(get_string('creategroup', 'mod_customgroups'));
$PAGE->set_context($modulecontext);
$PAGE->navbar->add(get_string('creategroup', 'mod_customgroups'));

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
