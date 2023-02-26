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
 * Form class for asking confirmation
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/formslib.php');

class confirm_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('html',
            html_writer::tag(
                'h1',
                isset($this->_customdata['title']) ? $this->_customdata['title'] : get_string('confirmation', 'mod_customgroups')
            ));
        $mform->addElement('html', html_writer::tag('p', $this->_customdata['message']));

        foreach($this->_customdata as $key => $value) {
            if ($key == 'message' || $key == 'title') {
                continue;
            }
            $mform->addElement('hidden', $key)->setValue($value);
            $mform->setType($key, PARAM_RAW);
        }

        $this->add_action_buttons(true, get_string('yes'));
    }
}
