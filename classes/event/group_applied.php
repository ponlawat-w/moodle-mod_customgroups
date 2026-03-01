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

namespace mod_customgroups\event;

/**
 * Event group_applied
 *
 * @package    mod_customgroups
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_applied extends \core\event\base {
    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'customgroups';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventgroupsapplied', 'mod_customgroups');
    }

    /**
     * Get event description
     *
     * @return string
     */
    public function get_description() {
        return "The user with the id '{$this->userid}' applied custom group"
            . " with id '{$this->objectid}' to course id '{$this->courseid}'";
    }

    /**
     * Get event URL
     *
     * @return \core\url
     */
    public function get_url() {
        return new \core\url('/group/index.php', ['id' => $this->courseid]);
    }

    /**
     * Create an event instance from module instance ID
     *
     * @param int $instanceid
     * @return \mod_customgroups\event\group_applied
     */
    public static function createfrommoduleinstanceid($instanceid) {
        $cm = get_coursemodule_from_instance('customgroups', $instanceid);
        return static::create([
            'context' => \core\context\module::instance($cm->id),
            'objectid' => $instanceid,
        ]);
    }
}
