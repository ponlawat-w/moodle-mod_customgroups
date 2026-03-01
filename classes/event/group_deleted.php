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
 * Event group_deleted
 *
 * @package    mod_customgroups
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_deleted extends \core\event\base {
    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'customgroups_groups';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventgroupdeleted', 'mod_customgroups');
    }

    /**
     * Get event description
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' deleted a group with id '{$this->objectid}' from custom group module.";
    }

    /**
     * Create event instance from ID
     *
     * @param integer $id
     * @param \core\context\module $modcontext
     * @return \mod_customgroups\event\group_deleted
     */
    public static function createfromid(int $id, \core\context\module $modcontext) {
        return static::create([
            'context' => $modcontext,
            'objectid' => $id,
        ]);
    }
}
