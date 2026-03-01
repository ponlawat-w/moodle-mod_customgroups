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
 * Event group_joined
 *
 * @package    mod_customgroups
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class group_joined extends \core\event\base {
    /**
     * Set basic properties for the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'customgroups_joins';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Get event name
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventgroupjoined', 'mod_customgroups');
    }

    /**
     * Get event description
     *
     * @return string
     */
    public function get_description() {
        return "The user with the id '{$this->userid}' joined a custom group with id {$this->other['groupid']}.";
    }

    /**
     * Get event URL
     *
     * @return \core\url
     */
    public function get_url() {
        return new \core\url('/mod/customgroups/view.php', [
            'id' => $this->get_context()->instanceid,
            'g' => $this->other['groupid'],
        ]);
    }

    /**
     * Create an event instance from join record
     *
     * @param \stdClass $record
     * @param int $newid
     * @param \core\context\module $modcontext
     * @return \mod_customgroups\event\group_joined
     */
    public static function createfromrecord(\stdClass $record, int $newid, \core\context\module $modcontext) {
        return static::create([
            'context' => $modcontext,
            'objectid' => $newid,
            'other' => ['groupid' => $record->groupid],
        ]);
    }
}
