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
    $instance->minmemberspercountry = $moduleinstance->minmemberspercountry;
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

    $instance = new stdClass();
    $instance->id = $moduleinstance->id;
    $instance->course = $moduleinstance->course;
    $instance->name = $moduleinstance->name;
    $instance->active = isset($moduleinstance->active) ? ($moduleinstance->active ? 1 : 0) : 0;
    $instance->timemodified = time();
    $instance->timedeactivated = isset($moduleinstance->timedeactivated) ? $moduleinstance->timedeactivated : null;
    $instance->intro = $moduleinstance->intro;
    $instance->introformat = $moduleinstance->introformat;
    $instance->defaultgrouping = $moduleinstance->defaultgrouping;
    $instance->minmembers = $moduleinstance->minmembers;
    $instance->maxmembers = $moduleinstance->maxmembers;
    $instance->minmemberspercountry = $moduleinstance->minmemberspercountry;
    $instance->maxmemberspercountry = $moduleinstance->maxmemberspercountry;

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
 * Join user to the group
 * THIS METHOD DOES NOT CHECK MODULE CONDITIONS
 *
 * @param int $groupid
 * @return int
 */
function customgroups_joingroup($groupid) {
    global $DB, $USER;

    $record = new stdClass();
    $record->groupid = $groupid;
    $record->user = $USER->id;
    $record->timejoined = time();

    return $DB->insert_record('customgroups_joins', $record);
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
