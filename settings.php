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
 * Plugin administration pages are defined here.
 *
 * @package     mod_customgroups
 * @category    admin
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configtext(
            'mod_customgroups/defaultminmembers',
            get_string('admin_defaultminmembers', 'mod_customgroups'),
            get_string('admin_defaultminmembers_description', 'mod_customgroups'),
            0, PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'mod_customgroups/defaultmaxmembers',
            get_string('admin_defaultmaxmembers', 'mod_customgroups'),
            get_string('admin_defaultmaxmembers_description', 'mod_customgroups'),
            0, PARAM_INT
        ));
        $settings->add(new admin_setting_configtext(
            'mod_customgroups/defaultmaxmemberspercountry',
            get_string('admin_defaultmaxmemberspercountry', 'mod_customgroups'),
            get_string('admin_defaultmaxmemberspercountry_description', 'mod_customgroups'),
            0, PARAM_INT
        ));
    }
}
