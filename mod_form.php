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
 * The main mod_customgroups configuration form.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_customgroups_mod_form extends moodleform_mod {

    private function getgroupingoptions() {
        $groupings = groups_get_all_groupings($this->_course->id);
        $options = [
            0 => get_string('nogrouping', 'mod_customgroups')
        ];
        foreach ($groupings as $grouping) {
            $options[$grouping->id] = $grouping->name;
        }
        return $options;
    }

    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);
        
        if ($data['minmembers'] > 0 && $data['maxmembers'] > 0 && $data['maxmembers'] < $data['minmembers']) {
            $errors['maxmembers'] = get_string('error_maxmemberslessthanminmembers', 'mod_customgroups');
        }
        if ($data['minmemberspercountry'] > 0 && $data['maxmemberspercountry'] > 0 && $data['maxmemberspercountry'] < $data['minmemberspercountry']) {
            $errors['maxmemberspercountry'] = get_string('error_maxmemberslessthanminmemberspercountry', 'mod_customgroups');
        }
        
        return $errors;
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addElement('hidden', 'id');
        $mform->setDefault('id', $this->_instance ? $this->_instance : 0);
        $mform->setType('id', PARAM_INT);

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        $mform->addElement('checkbox', 'active', get_string('active'), get_string('openforcreatingorjoining', 'mod_customgroups'));
        $mform->setDefault('active', true);
        $mform->addHelpButton('active', 'openforcreatingorjoining', 'mod_customgroups');
        $mform->setType('active', PARAM_BOOL);
        
        $mform->addElement('date_time_selector', 'timedeactivated', get_string('activeuntil', 'mod_customgroups'), ['optional' => true]);
        $mform->addHelpButton('timedeactivated', 'activeuntil', 'mod_customgroups');
        $mform->hideIf('timedeactivated', 'active', 'notchecked');
        
        $mform->addElement('select', 'defaultgrouping', get_string('defaultgrouping', 'mod_customgroups'), $this->getgroupingoptions());
        $mform->addHelpButton('defaultgrouping', 'defaultgrouping', 'mod_customgroups');
        $mform->setType('defaultgrouping', PARAM_INT);
        
        $mform->addElement('header', 'groupconditions', get_string('groupconditions', 'mod_customgroups'));
        $mform->setExpanded('groupconditions');
        
        $mform->addElement('text', 'minmembers', get_string('minmembers', 'mod_customgroups'));
        $mform->addHelpButton('minmembers', 'minmembers', 'mod_customgroups');
        $mform->setType('minmembers', PARAM_INT);
        $mform->setDefault('minmembers', 0);
        $mform->addRule('minmembers', null, 'numeric', null, 'client');

        $mform->addElement('text', 'maxmembers', get_string('maxmembers', 'mod_customgroups'));
        $mform->addHelpButton('maxmembers', 'maxmembers', 'mod_customgroups');
        $mform->setType('maxmembers', PARAM_INT);
        $mform->setDefault('maxmembers', 0);
        $mform->addRule('maxmembers', null, 'numeric', null, 'client');

        $mform->addElement('text', 'minmemberspercountry', get_string('minmemberspercountry', 'mod_customgroups'));
        $mform->addHelpButton('minmemberspercountry', 'minmemberspercountry', 'mod_customgroups');
        $mform->setType('minmemberspercountry', PARAM_INT);
        $mform->setDefault('minmemberspercountry', 0);
        $mform->addRule('minmemberspercountry', null, 'numeric', null, 'client');

        $mform->addElement('text', 'maxmemberspercountry', get_string('maxmemberspercountry', 'mod_customgroups'));
        $mform->addHelpButton('maxmemberspercountry', 'maxmemberspercountry', 'mod_customgroups');
        $mform->setType('maxmemberspercountry', PARAM_INT);
        $mform->setDefault('maxmemberspercountry', 0);
        $mform->addRule('maxmemberspercountry', null, 'numeric', null, 'client');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
