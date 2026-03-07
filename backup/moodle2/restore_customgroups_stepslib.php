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
 * Define all the restore steps that will be used by the restore_customgroups_activity_task
 *
 * @package    mod_customgroups
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one customgroups activity
 */
class restore_customgroups_activity_structure_step extends restore_activity_structure_step {
    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('customgroups', '/activity/customgroups');
        $paths[] = new restore_path_element('group', '/activity/customgroups/groups/group');
        $paths[] = new restore_path_element('join', '/activity/customgroups/groups/group/joins/join');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_customgroups($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert the customgroups record.
        $newitemid = $DB->insert_record('customgroups', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process a group restore.
     *
     * @param array $data The data in object form
     * @return void
     */
    protected function process_group($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->module = $this->get_new_parentid('customgroups');
        $data->course = $this->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('customgroups_groups', $data);
        $this->set_mapping('group', $oldid, $newitemid, true);
    }

    /**
     * Process a join restore.
     *
     * @param array $data The data in object form
     * @return void
     */
    protected function process_join($data) {
        global $DB;

        $data = (object)$data;
        $data->groupid = $this->get_new_parentid('group');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('customgroups_joins', $data);
    }

    /**
     * Actions to be executed after the restore is complete
     */
    protected function after_execute() {
        $this->add_related_files('mod_customgroups', 'groupimages', 'group');
    }
}
