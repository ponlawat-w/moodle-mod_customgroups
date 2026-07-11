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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');

$instance = optional_param('instance', 0, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);

if (!$instance && !$id) {
    throw new \core\exception\moodle_exception('invalidparameters', 'mod_customgroups');
}

$group = null;
if ($id) {
    $group = $DB->get_record('customgroups_groups', ['id' => $id], '*', MUST_EXIST);
    $instance = $group->module;
}
$moduleinstance = $DB->get_record('customgroups', ['id' => $instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);

if (!customgroups_isactive($moduleinstance)) {
    throw new \core\exception\moodle_exception('modulenotactive', 'mod_customgroups');
}

/** @var \context|false $modulecontext */
$modulecontext = \core\context\module::instance($cm->id);
require_capability('mod/customgroups:creategroup', $modulecontext);
if (!$id && !customgroups_cancreategroup($modulecontext, $moduleinstance->id)) {
    throw new \core\exception\moodle_exception('nopermissiontocreategroup', 'mod_customgroups');
}
if ($id && $USER->id != $group->userid) {
    throw new \core\exception\moodle_exception('notownercannotcreate', 'mod_customgroups');
}

$redirecturl = new \core\url(
    '/mod/customgroups/view.php',
    array_merge(
        ['instance' => $moduleinstance->id],
        $group ? ['g' => $group->id] : []
    )
);

/** @var \core\context\module $modulecontext */
$modulecontext;

$form = null;
if ($group && $action == 'remove') {
    $titlestrkey = 'deletegroup';
    $form = new \mod_customgroups\form\confirm_form(null, [
        'message' => get_string('confirm_removegroup', 'mod_customgroups', $group->name),
        'instance' => $moduleinstance->id,
        'id' => $group->id,
        'action' => 'remove',
    ]);
    if ($form->is_cancelled()) {
        redirect($redirecturl);
        exit;
    }
    if ($form->is_submitted()) {
        $redirecturl = new \core\url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id]);
        customgroups_deletegroup($modulecontext, $group->id);
        redirect($redirecturl);
        exit;
    }
} else {
    $titlestrkey = $group ? 'editgroup' : 'creategroup';
    $customdata = ['instance' => $instance];
    if ($group) {
        $customdata['id'] = $group->id;
        $customdata['name'] = $group->name;
        $customdata['description'] = $group->description;
        $customdata['descriptionformat'] = $group->descriptionformat;
        $customdata['image'] = file_get_submitted_draft_itemid('image');
        file_prepare_draft_area(
            $customdata['image'],
            $modulecontext->id,
            'mod_customgroups',
            'groupimages',
            $group->id,
            [
                'subdirs' => 0,
                'maxfiles' => 1,
                'accepted_types' => ['image'],
            ]
        );
    }

    $form = new \mod_customgroups\form\editgroup_form(null, $customdata);
    if ($form->is_cancelled()) {
        redirect($redirecturl);
        exit;
    }
    if ($form->is_submitted() && $form->is_validated()) {
        $data = $form->get_data();
        if (!$data->id) {
            if ($newid = customgroups_creategroupfromform($moduleinstance->id, $course->id, $data, $modulecontext)) {
                file_save_draft_area_files(
                    $data->image,
                    $modulecontext->id,
                    'mod_customgroups',
                    'groupimages',
                    $newid
                );
                redirect($redirecturl . '#g-' . $newid);
                exit;
            }
            throw new \core\exception\moodle_exception('cannotcreategroup', 'mod_customgroups');
        }
        $group->name = $data->name;
        $group->description = $data->description['text'];
        $group->descriptionformat = $data->description['format'];
        if ($DB->update_record('customgroups_groups', $group)) {
            \mod_customgroups\event\group_updated::createfromrecord($group, $modulecontext)->trigger();
            customgroups_deleteexistingimages($modulecontext, $group->id);
            file_save_draft_area_files(
                $data->image,
                $modulecontext->id,
                'mod_customgroups',
                'groupimages',
                $group->id
            );
            redirect($redirecturl);
            exit;
        }
        throw new \core\exception\moodle_exception('cannoteditgroup', 'mod_customgroups');
    }
}

$PAGE->set_url('/mod/customgroups/editgroup.php', ['id' => $moduleinstance->id]);
$PAGE->set_title(format_string($course->fullname) . ': ' . get_string($titlestrkey, 'mod_customgroups'));
$PAGE->set_heading(get_string($titlestrkey, 'mod_customgroups'));
$PAGE->set_context($modulecontext);
$PAGE->navbar->add(get_string($titlestrkey, 'mod_customgroups'));

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
