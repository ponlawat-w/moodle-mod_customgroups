<?php

defined('MOODLE_INTERNAL') or die();

function xmldb_customgroups_upgrade($oldversion) {
    /**
     * @var \moodle_database $DB
     */
    global $DB;

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
