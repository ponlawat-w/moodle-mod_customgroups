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
 * Define all the backup steps that will be used by the backup_customgroups_activity_task
 *
 * @package    mod_customgroups
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the complete customgroups structure for backup
 */
class backup_customgroups_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        // Define the root element describing the customgroups instance.
        $customgroups = new backup_nested_element(
            'customgroups',
            ['id'],
            [
                'name',
                'active',
                'applied',
                'timecreated',
                'timemodified',
                'timedeactivated',
                'intro',
                'introformat',
                'defaultgrouping',
                'minmembers',
                'maxmembers',
                'maxmemberspercountry',
            ]
        );

        // Define sources.
        $customgroups->set_source_table('customgroups', ['id' => backup::VAR_ACTIVITYID]);

        // Define the groups element.
        $groups = new backup_nested_element('groups');
        $group = new backup_nested_element(
            'group',
            ['id'],
            [
                'name',
                'description',
                'descriptionformat',
                'userid',
                'timecreated',
            ]
        );

        // Define the joins element.
        $joins = new backup_nested_element('joins');
        $join = new backup_nested_element(
            'join',
            ['id'],
            [
                'userid',
                'timejoined',
            ]
        );

        // Build the tree.
        $customgroups->add_child($groups);
        $groups->add_child($group);
        $group->add_child($joins);
        $joins->add_child($join);

        // Define sources.
        $group->set_source_table('customgroups_groups', ['module' => backup::VAR_PARENTID]);
        $group->annotate_ids('user', 'userid');
        $join->set_source_table('customgroups_joins', ['groupid' => backup::VAR_PARENTID]);
        $join->annotate_ids('user', 'userid');

        // Define file annotations.
        $group->annotate_files('mod_customgroups', 'groupimages', 'id');

        // Return the root element (customgroups).
        return $this->prepare_activity_structure($customgroups);
    }
}
