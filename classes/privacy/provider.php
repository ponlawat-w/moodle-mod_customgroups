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
 * Privacy provider.
 *
 * @package     mod_customgroups
 * @copyright   2025 Ponlawat WEERAPANPISIT <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_customgroups\privacy;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib.php');

/**
 * Privacy provider
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata
     *
     * @param \core_privacy\local\metadata\collection $collection
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(
        \core_privacy\local\metadata\collection $collection
    ): \core_privacy\local\metadata\collection {
        $collection->add_database_table(
            'customgroups_groups',
            [
                'module' => 'privacy:metadata:customgroups_groups:module',
                'course' => 'privacy:metadata:customgroups_groups:course',
                'name' => 'privacy:metadata:customgroups_groups:name',
                'description' => 'privacy:metadata:customgroups_groups:description',
                'userid' => 'privacy:metadata:customgroups_groups:userid',
            ],
            'privacy:metadata:customgroups_groups'
        );
        $collection->add_database_table(
            'customgroups_joins',
            [
                'groupid' => 'privacy:metadata:customgroups_joins:groupid',
                'userid' => 'privacy:metadata:customgroups_joins:userid',
            ],
            'privacy:metadata:customgroups_joins'
        );
        return $collection;
    }

    /**
     * Get the list of contexts of a user.
     *
     * @param int $userid
     * @return \core_privacy\local\request\contextlist
     */
    public static function get_contexts_for_userid(int $userid): \core_privacy\local\request\contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_from_sql(
            <<<SQL
                SELECT c.id
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'customgroups'
                    JOIN {customgroups} cg ON cg.id = cm.instance
                    JOIN {customgroups_groups} cgg ON cgg.module = cg.id
                WHERE cgg.userid = ?
            SQL,
            [CONTEXT_MODULE, $userid]
        );
        $contextlist->add_from_sql(
            <<<SQL
                SELECT c.id
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'customgroups'
                    JOIN {customgroups} cg ON cg.id = cm.instance
                    JOIN {customgroups_groups} cgg ON cgg.module = cg.id
                    JOIN {customgroups_joins} cgj ON cgj.groupid = cgg.id
                WHERE cgj.userid = ?
            SQL,
            [CONTEXT_MODULE, $userid]
        );
        return $contextlist;
    }

    /**
     * Get users in given context
     *
     * @param \core_privacy\local\request\userlist $userlist
     * @return void
     */
    public static function get_users_in_context(\core_privacy\local\request\userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $userlist->add_from_sql(
            'userid',
            <<<SQL
                SELECT cgg.userid
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'customgroups'
                    JOIN {customgroups} cg ON cg.id = cm.instance
                    JOIN {customgroups_groups} cgg ON cgg.module = cg.id
                WHERE cm.id = ?
            SQL,
            [CONTEXT_MODULE, $context->instanceid]
        );
        $userlist->add_from_sql(
            'userid',
            <<<SQL
                SELECT cgj.userid
                FROM {context} c
                    JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = ?
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'customgroups'
                    JOIN {customgroups} cg ON cg.id = cm.instance
                    JOIN {customgroups_groups} cgg ON cgg.module = cg.id
                    JOIN {customgroups_joins} cgj ON cgj.groupid = cgg.id
                WHERE cm.id = ?
            SQL,
            [CONTEXT_MODULE, $context->instanceid]
        );
    }

    /**
     * Export all user data
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(\core_privacy\local\request\approved_contextlist $contextlist) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;

        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $writer = \core_privacy\local\request\writer::with_context($context);

            $coursemodule = get_coursemodule_from_id('customgroups', $context->instanceid);
            if (!$coursemodule) {
                return;
            }

            $createdgroups = $DB->get_records(
                'customgroups_groups',
                ['module' => $coursemodule->instance, 'userid' => $user->id],
                null,
                'id, name, description, descriptionformat, timecreated'
            );
            foreach ($createdgroups as $createdgroup) {
                $createdgroup->timecreated = userdate(
                    $createdgroup->timecreated,
                    get_string('strftimedatetimeaccurate', 'langconfig')
                );
                $writer
                    ->export_data([get_string('createdgroup', 'mod_customgroups')], $createdgroup)
                    ->export_area_files(
                        [
                            get_string('createdgroup', 'mod_customgroups'),
                            get_string('groupimage', 'mod_customgroups'),
                        ],
                        'mod_customgroups',
                        'groupimages',
                        $createdgroup->id
                    );
            }

            $joinedgroups = $DB->get_recordset_sql(
                <<<SQL
                    SELECT
                        cgj.timejoined,
                        cgj.groupid,
                        cgg.name groupname
                    FROM {customgroups_joins} cgj
                    JOIN {customgroups_groups} cgg ON cgg.id = cgj.groupid
                    WHERE cgg.module = ? AND cgj.userid = ?
                SQL,
                [$coursemodule->instance, $user->id]
            );
            foreach ($joinedgroups as $joinedgroup) {
                $joinedgroup->timejoined = userdate($joinedgroup->timejoined, get_string('strftimedatetimeaccurate', 'langconfig'));
                $writer->export_data([get_string('joinedgroup', 'mod_customgroups')], $joinedgroup);
            }
            $joinedgroups->close();
        }
    }

    /**
     * Delete all users data in a context
     *
     * @param \core\context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\core\context $context) {
        if ($context->contextlevel == CONTEXT_MODULE) {
            $coursemodule = get_coursemodule_from_id('customgroups', $context->instanceid);
            if (!$coursemodule) {
                return;
            }
            return customgroups_delete_instance($coursemodule->instance);
        }

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $coursemodules = get_coursemodules_in_course('customgroups', $context->instanceid);
        foreach ($coursemodules as $coursemodule) {
            customgroups_delete_instance($coursemodule->instance);
        }
    }

    /**
     * Delete data of userid in a module context
     *
     * @param \core\context\module $context
     * @param int $userid
     * @return void
     */
    private static function delete_data_for_user_in_module_context(\core\context\module $context, int $userid) {
        global $DB;
        /** @var \moodle_database $DB */
        $DB;
        $coursemodule = get_coursemodule_from_id('customgroups', $context->instanceid);
        if (!$coursemodule) {
            return;
        }
        $createdgroups = $DB->get_records(
            'customgroups_groups',
            ['module' => $coursemodule->instance, 'userid' => $userid],
            null,
            'id'
        );
        foreach ($createdgroups as $createdgroup) {
            customgroups_deletegroup($context, $createdgroup->id);
        }
        $DB->execute(
            <<<SQL
                DELETE FROM {customgroups_joins}
                WHERE id IN (
                    SELECT cgj.id
                    FROM {customgroups_joins} cgj
                    JOIN {customgroups_groups} cgg ON cgg.id = cgj.groupid
                    WHERE cgj.userid = ? AND cgg.module = ?
                )
            SQL,
            [$userid, $coursemodule->instance]
        );
    }

    /**
     * Delete data for user
     *
     * @param \core_privacy\local\request\approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist) {
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            /** @var \core\context\module $context */
            $context;
            self::delete_data_for_user_in_module_context($context, $user->id);
        }
    }

    /**
     * Delete data for users
     *
     * @param \core_privacy\local\request\approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(\core_privacy\local\request\approved_userlist $userlist) {
        foreach ($userlist->get_userids() as $userid) {
            foreach ($userlist->get_context() as $context) {
                self::delete_data_for_user_in_module_context($context, $userid);
            }
        }
    }
}
