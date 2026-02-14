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
 * Defines backup_customgroups_activity_task class
 *
 * @package   mod_customgroups
 * @copyright 2025 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/customgroups/backup/moodle2/backup_customgroups_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the Custom groups instance
 */
class backup_customgroups_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the customgroups.xml file
     */
    protected function define_my_steps() {
        $this->add_step(
            new backup_customgroups_activity_structure_step('customgroups_structure', 'customgroups.xml')
        );
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        return $content;
    }
}