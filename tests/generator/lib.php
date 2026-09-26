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
 * mod_customgroups data generator
 *
 * @package     mod_customgroups
 * @category    test
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_customgroups_generator extends testing_module_generator {
    /**
     * Create a module instance
     *
     * @param array|stdClass $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        $defaults = [
            'active' => 1,
            'defaultgrouping' => 0,
            'minmembers' => 0,
            'maxmembers' => 0,
            'maxmemberspercountry' => 0,
        ];
        foreach ($defaults as $key => $value) {
            if (!isset($record->$key)) {
                $record->$key = $value;
            }
        }
        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a custom group with an image, joined by its creator and the given members
     *
     * @param stdClass $instance Module instance returned by create_instance()
     * @param int $userid Creator user ID
     * @param int[] $memberids Other members' user IDs
     * @return int Group ID
     */
    public function create_group($instance, $userid, array $memberids = []) {
        global $DB;
        $groupid = $DB->insert_record('customgroups_groups', [
            'module' => $instance->id,
            'course' => $instance->course,
            'name' => 'Custom group ' . random_string(),
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'userid' => $userid,
            'timecreated' => time(),
        ]);
        foreach (array_merge([$userid], $memberids) as $memberid) {
            $DB->insert_record('customgroups_joins', [
                'groupid' => $groupid,
                'userid' => $memberid,
                'timejoined' => time(),
            ]);
        }
        get_file_storage()->create_file_from_string([
            'contextid' => \core\context\module::instance($instance->cmid)->id,
            'component' => 'mod_customgroups',
            'filearea' => 'groupimages',
            'itemid' => $groupid,
            'filepath' => '/',
            'filename' => 'image.png',
        ], 'image');
        return $groupid;
    }
}
