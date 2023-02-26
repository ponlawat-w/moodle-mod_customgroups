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
 * Library of interface functions and constants.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../group/lib.php');

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function customgroups_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_customgroups into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @return int The id of the newly inserted record.
 */
function customgroups_add_instance($moduleinstance) {
    global $DB;

    $instance = new stdClass();
    $instance->course = $moduleinstance->course;
    $instance->name = $moduleinstance->name;
    $instance->active = isset($moduleinstance->active) ? ($moduleinstance->active ? 1 : 0) : 0;
    $instance->applied = 0;
    $instance->timecreated = time();
    $instance->timemodified = time();
    $instance->timedeactivated = isset($moduleinstance->timedeactivated) ? $moduleinstance->timedeactivated : null;
    $instance->intro = $moduleinstance->intro;
    $instance->introformat = $moduleinstance->introformat;
    $instance->defaultgrouping = $moduleinstance->defaultgrouping;
    $instance->minmembers = $moduleinstance->minmembers;
    $instance->maxmembers = $moduleinstance->maxmembers;
    $instance->maxmemberspercountry = $moduleinstance->maxmemberspercountry;

    $id = $DB->insert_record('customgroups', $instance);

    return $id;
}

/**
 * Updates an instance of the mod_customgroups in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 */
function customgroups_update_instance($moduleinstance) {
    global $DB;

    $moduleinstancefromdb = $DB->get_record('customgroups', ['id' => $moduleinstance->id], '*', MUST_EXIST);

    $instance = new stdClass();
    $instance->id = $moduleinstance->id;
    $instance->course = $moduleinstance->course;
    $instance->name = $moduleinstance->name;
    $instance->intro = $moduleinstance->intro;
    $instance->introformat = $moduleinstance->introformat;
    $instance->timemodified = time();
    if (!$moduleinstancefromdb->applied) {
        $instance->active = isset($moduleinstance->active) ? ($moduleinstance->active ? 1 : 0) : 0;
        $instance->timedeactivated = isset($moduleinstance->timedeactivated) ? $moduleinstance->timedeactivated : null;
        $instance->defaultgrouping = $moduleinstance->defaultgrouping;
        $instance->minmembers = $moduleinstance->minmembers;
        $instance->maxmembers = $moduleinstance->maxmembers;
        $instance->maxmemberspercountry = $moduleinstance->maxmemberspercountry;
    }

    return $DB->update_record('customgroups', $instance);
}

/**
 * Removes an instance of the mod_customgroups from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function customgroups_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('customgroups', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $groups = $DB->get_records('customgroups_groups', ['module' => $id]);
    foreach ($groups as $group) {
        if (!customgroups_deletegroup($group->id)) {
            return false;
        }
    }
    if (!$DB->delete_records('customgroups', array('id' => $id))) {
        return false;
    }

    return true;
}

/**
 * Return true if module instance is active
 *
 * @param stdClass $instance
 * @return bool
 */
function customgroups_isactive($instance) {
    return $instance->active && (!$instance->timedeactivated || time() < $instance->timedeactivated);
}

/**
 * Returns true if user can create a new group in a module instance
 *
 * @param context_module $modcontext
 * @param int $instanceid
 * @param int $userid
 * @return bool
 */
function customgroups_cancreategroup($modcontext, $instanceid, $userid = 0) {
    global $DB, $USER;
    $user = $userid ? $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST) : $USER;
    if (!has_capability('mod/customgroups:creategroup', $modcontext, $user)) {
        return false;
    }
    if ($DB->count_records('customgroups_groups', ['module' => $instanceid, 'user' => $user->id])) {
        return false;
    }
    if (customgroups_getjoinedgroupid($instanceid, $userid)) {
        return false;
    }
    return true;
}

/**
 * Create a new group from form data
 * THIS FUNCTION DOES NOT CHECK WHETHER MODULE IS ACTIVE OR NOT
 *
 * @param int $instance
 * @param int $courseid
 * @param object $data
 * @return int
 */
function customgroups_creategroupfromform($instance, $courseid, $data) {
    global $DB, $USER;

    $group = new stdClass();
    $group->module = $instance;
    $group->course = $courseid;
    $group->name = $data->name;
    $group->description = $data->description['text'];
    $group->descriptionformat = $data->description['format'];
    $group->user = $USER->id;
    $group->timecreated = time();

    $id = $DB->insert_record('customgroups_groups', $group);
    customgroups_joingroup($id);
    return $id;
}

/**
 * Check if user can join group
 *
 * @param int $groupid
 * @param stdClass $instance
 * @param stdClass $user
 * @return bool
 */
function customgroups_canjoingroup($groupid, $instance, $user = null) {
    global $DB, $USER;
    $user = $user ? $user : $USER;

    if (!customgroups_isactive($instance)) {
        return false;
    }
    if (customgroups_getjoinedgroupid($instance->id, $user->id)) {
        return false;
    }
    if ($instance->maxmembers && $DB->count_records('customgroups_joins', ['groupid' => $groupid]) >= $instance->maxmembers) {
        return false;
    }
    if ($instance->maxmemberspercountry && $DB->get_record_sql(
            'SELECT COUNT(*) countrymemberscount FROM {customgroups_joins} j JOIN {user} u ON j.user = u.id WHERE j.groupid = ? AND u.country = ?',
            [$groupid, $user->country]
        )->countrymemberscount >= $instance->maxmemberspercountry) {
        return false;
    }
    return true;
}

/**
 * True if user has joined any group in the given module instance ID
 *
 * @param int $instanceid
 * @param int $userid
 * @return bool
 */
function customgroups_isjoined($instanceid, $userid = 0) {
    global $DB, $USER;
    $userid = $userid ? $userid : $USER->id;
    return $DB->get_record_sql(
        'SELECT COUNT(*) joinscount FROM {customgroups_joins} j JOIN {customgroups_groups} g ON j.groupid = g.id WHERE g.module = ? AND j.user = ?',
        [$instanceid, $userid]
    )->joinscount > 0;
}

/**
 * Get duser joined group ID
 *
 * @param int $instanceid
 * @param int $userid
 * @return int|null
 */
function customgroups_getjoinedgroupid($instanceid, $userid = 0) {
    global $DB, $USER;
    $userid = $userid ? $userid : $USER->id;
    $record = $DB->get_record_sql(
        'SELECT j.groupid groupid FROM {customgroups_joins} j JOIN {customgroups_groups} g ON j.groupid = g.id WHERE g.module = ? AND j.user = ?',
        [$instanceid, $userid]
    );
    return $record ? $record->groupid : null;
}

/**
 * Join user to a group
 * THIS METHOD DOES NOT CHECK MODULE CONDITIONS
 *
 * @param int $groupid
 * @return int
 */
function customgroups_joingroup($groupid, $userid = 0) {
    global $DB, $USER;

    $userid = $userid ? $userid : $USER->id;

    $record = new stdClass();
    $record->groupid = $groupid;
    $record->user = $userid;
    $record->timejoined = time();

    return $DB->insert_record('customgroups_joins', $record);
}

/**
 * Leave user from a group
 *
 * @param int $groupid
 * @param int $userid
 * @return bool
 */
function customgroups_leavegroup($groupid, $userid = 0) {
    global $DB, $USER;

    $userid = $userid ? $userid : $USER->id;

    return $DB->delete_records('customgroups_joins', ['groupid' => $groupid, 'user' => $userid]);
}

/**
 * Delete a custom group
 *
 * @param int $groupid
 */
function customgroups_deletegroup($groupid) {
    global $DB;
    if (!$DB->delete_records('customgroups_joins', ['groupid' => $groupid])) {
        return false;
    }
    return $DB->delete_records('customgroups_groups', ['id' => $groupid]);
}

/**
 * Test if group can be applied to course
 *
 * @param stdClass $moduleinstance
 * @param int $groupid
 * @return bool
 */
function customgroups_canapply($moduleinstance, $groupid) {
    global $DB;
    if (!$moduleinstance->minmembers) {
        return $DB->count_records('customgroups_joins', ['groupid' => $groupid]) > 0;
    }
    return $DB->count_records('customgroups_joins', ['groupid' => $groupid] >= $moduleinstance->minmembers);
}

/**
 * Apply group from module instance to course
 * THIS FUNCTION DOES NOT CHECK IF A GROUP CAN BE APPLIED UNDER MODULE CONDITIONS
 *
 * @param stdClass $moduleinstance
 * @param stdClass $group
 */
function customgroups_applygroup($moduleinstance, $group) {
    global $DB;
    $groupdata = new stdClass();
    $groupdata->courseid = $moduleinstance->course;
    $groupdata->name = $group->name;
    $newgroupid = groups_create_group($groupdata);
    if (!$newgroupid) {
        throw new moodle_exception('Cannot create group: ' . $group->id . ' - ' . $group->name);
    }
    if ($moduleinstance->defaultgrouping) {
        groups_assign_grouping($moduleinstance->defaultgrouping, $newgroupid);
    }
    $joins = $DB->get_records('customgroups_joins', ['groupid' => $group->id]);
    foreach ($joins as $join) {
        groups_add_member($newgroupid, $join->user);
    }
}

/**
 * Apply groups tha can be applied from module instance to course.
 * THIS FUNCTION DOES NOT DEACTIVATE THE MODULE
 *
 * @param stdClass $moduleinstance
 */
function customgroups_applygroups($moduleinstance) {
    global $DB;
    $groups = $DB->get_records('customgroups_groups', ['module' => $moduleinstance->id]);
    foreach ($groups as $group) {
        if (!customgroups_canapply($moduleinstance, $group->id)) {
            continue;
        }
        customgroups_applygroup($moduleinstance, $group);
    }
}

/**
 * Apply module instance groups to course and deactive the module
 *
 * @param stdClass $moduleinstance
 */
function customgroups_applymodule($moduleinstance) {
    global $DB;
    customgroups_applygroups($moduleinstance);
    $moduleinstance->active = 0;
    $moduleinstance->applied = 1;
    $DB->update_record('customgroups', $moduleinstance);
}
