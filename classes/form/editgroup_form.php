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
 * Form class for create or edit groups
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/mod/forum/lib.php');
require_once($CFG->libdir.'/formslib.php');

class editgroup_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'instance')
            ->setValue(isset($this->_customdata['instance']) ? $this->_customdata['instance'] : null);
        $mform->setType('instance', PARAM_INT);

        $mform->addElement('hidden', 'id')
            ->setValue(isset($this->_customdata['id']) ? $this->_customdata['id'] : null);
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('text', 'name', get_string('groupname', 'mod_customgroups'), ['size' => 64])
            ->setValue(isset($this->_customdata['name']) ? $this->_customdata['name'] : null);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('editor', 'description', get_string('description'))
            ->setValue(isset($this->_customdata['description']) && isset($this->_customdata['descriptionformat']) ? [
                'text' => $this->_customdata['description'],
                'format' => $this->_customdata['descriptionformat']
            ] : null);
        $mform->setType('description', PARAM_CLEANHTML);

        $this->add_action_buttons(true, get_string(isset($this->_customdata['id']) ? 'edit' : 'add'));
    }
}
