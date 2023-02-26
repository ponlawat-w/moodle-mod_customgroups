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
 * Applied groups to the course
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$instance = required_param('instance', PARAM_INT);

$moduleinstance = $DB->get_record('customgroups', array('id' => $instance), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);
require_capability('mod/customgroups:applygroups', $modulecontext);

if ($moduleinstance->applied) {
    throw new moodle_exception('Module already applied to course');
}

$redirecturl = new moodle_url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id]);

require_once(__DIR__ . '/classes/form/confirm_form.php');
$form = new confirm_form(null, [
    'title' => get_string('applygroups', 'mod_customgroups'),
    'message' => get_string('confirm_applygroups', 'mod_customgroups'),
    'instance' => $moduleinstance->id
]);
if ($form->is_cancelled()) {
    redirect($redirecturl);
    exit;
}
if ($form->is_submitted()) {
    customgroups_applymodule($moduleinstance);
    redirect($redirecturl);
    exit;
}

$PAGE->set_url('/mod/customgroups/applygroups.php', array('id' => $moduleinstance->id));
$PAGE->set_title(format_string($course->fullname) . ': ' . get_string('applygroups', 'mod_customgroups'));
$PAGE->set_heading(get_string('applygroups', 'mod_customgroups'));
$PAGE->set_context($modulecontext);
$PAGE->navbar->add(get_string('applygroups', 'mod_customgroups'));

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
