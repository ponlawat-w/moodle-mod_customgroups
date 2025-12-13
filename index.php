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
 * Display information about all the mod_customgroups modules in the requested course.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/customgroups/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_customgroups');
echo $OUTPUT->heading($modulenameplural);

$customgroupss = get_all_instances_in_course('customgroups', $course);

if (empty($customgroupss)) {
    notice(
        get_string(
            'no$customgroupsinstances',
            'mod_customgroups'
        ),
        new \core\url('/course/view.php', ['id' => $course->id])
    );
}

$table = new core_table\output\html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head  = [get_string('week'), get_string('name')];
    $table->align = ['center', 'left'];
} else if ($course->format == 'topics') {
    $table->head  = [get_string('topic'), get_string('name')];
    $table->align = ['center', 'left', 'left', 'left'];
} else {
    $table->head  = [get_string('name')];
    $table->align = ['left', 'left', 'left'];
}

foreach ($customgroupss as $customgroups) {
    if (!$customgroups->visible) {
        $link = \core\output\html_writer::link(
            new \core\url('/mod/customgroups/view.php', ['id' => $customgroups->coursemodule]),
            format_string($customgroups->name, true),
            ['class' => 'dimmed']
        );
    } else {
        $link = \core\output\html_writer::link(
            new \core\url('/mod/customgroups/view.php', ['id' => $customgroups->coursemodule]),
            format_string($customgroups->name, true)
        );
    }

    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = [$customgroups->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo \core\output\html_writer::table($table);
echo $OUTPUT->footer();
