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
 * Upgrade logics of the plugin
 *
 * @package     mod_customgroups
 * @copyright   2025 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function
 *
 * @param $oldversion
 * @return bool
 */
function xmldb_customgroups_upgrade($oldversion) {
    global $DB;
    /** @var \moodle_database $DB */
    $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2023032900) {
        $tablenames = ['customgroups_groups', 'customgroups_joins'];
        foreach ($tablenames as $tablename) {
            $table = new xmldb_table($tablename);
            $field = new xmldb_field('user', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null);
            $key = new xmldb_key('fk_user', XMLDB_KEY_FOREIGN, ['user'], 'user', 'id');

            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, 'userid');
            }
            if (method_exists($dbman, 'find_key_name') && $dbman->find_key_name($table, $key)) {
                $dbman->drop_key($table, $key);
            }
            $key->setName('fk_userid');
            if (method_exists($dbman, 'find_key_name') && !$dbman->find_key_name($table, $key)) {
                $dbman->add_key($table, $key);
            }
        }

        upgrade_mod_savepoint(true, 2023032900, 'customgroups');
    }

    return true;
}
