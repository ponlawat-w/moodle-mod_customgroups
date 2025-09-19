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
 * Prints an instance of mod_customgroups.
 *
 * @package     mod_customgroups
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$instance = optional_param('instance', 0, PARAM_INT);

$groupid = optional_param('g', null, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('customgroups', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('customgroups', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('customgroups', array('id' => $instance), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = \core\context\module::instance($cm->id);

$active = customgroups_isactive($moduleinstance);

$groups = $DB->get_records(
    'customgroups_groups',
    array_merge(
        ['module' => $moduleinstance->id],
        is_null($groupid) ? [] : ['id' => $groupid]
    ),
    'name ASC'
);
if ($groupid && !count($groups)) {
    throw new \core\exception\moodle_exception('Group not found');
}
$joinedgroupid = customgroups_getjoinedgroupid($moduleinstance->id);
$groupsdata = [];
foreach ($groups as $group) {
    $joins = $DB->get_records('customgroups_joins', ['groupid' => $group->id]);
    $users = [];
    foreach ($joins as $join) {
        $users[] = [
            'url' => new \core\url('/user/view.php', ['id' => $join->userid, 'course' => $course->id]),
            'name' => fullname($DB->get_record('user', ['id' => $join->userid], '*', MUST_EXIST)),
            'owner' => $join->userid == $group->userid
        ];
    }
    $joinscount = count($joins);
    $countries = null;
    $countpercountries = [];
    if ($moduleinstance->maxmemberspercountry) {
        $countries = [];
        $countpercountries = customgroups_getmemberscountbycountry($group->id);
        foreach ($countpercountries as $country => $count) {
            $countries[] = [
                'name' => get_string($country, 'countries'),
                'count' => $count,
                'class' => $country == $USER->country && $count >= $moduleinstance->maxmemberspercountry ? 'text-danger' : ''
            ];
        }
    }
    $warningtexts = [];
    if (!$joinedgroupid) {
        if ($moduleinstance->maxmembers && $joinscount >= $moduleinstance->maxmembers) {
            $warningtexts[] = ['text' => get_string('cannotjoin_groupreachedmaxmembers', 'mod_customgroups', $moduleinstance->maxmembers)];
        }
        if ($moduleinstance->maxmemberspercountry && isset($countpercountries[$USER->country]) && $countpercountries[$USER->country] >= $moduleinstance->maxmemberspercountry) {
            $warningtexts[] = ['text' =>
                get_string('cannotjoin_groupreachedmaxmemberspercountry', 'mod_customgroups', [
                    'country' => get_string($USER->country, 'countries'),
                    'maxmembers' => $moduleinstance->maxmemberspercountry
                ])
            ];
        }
    }
    if ($moduleinstance->minmembers && $joinscount < $moduleinstance->minmembers) {
        $warningtexts[] = ['text' => get_string('minmembersnotsatisfied', 'mod_customgroups', $moduleinstance->minmembers)];
    }
    $groupsdata[] = [
        'id' => $group->id,
        'name' => $group->name,
        'description' => $group->description,
        'joinscount' => $joinscount,
        'joined' => $group->id == $joinedgroupid,
        'joinable' => customgroups_canjoingroup($group->id, $moduleinstance),
        'leaveable' => $active && ($joinedgroupid == $group->id && $group->userid != $USER->id),
        'editable' => $active && ($group->userid == $USER->id),
        'viewurl' => new \core\url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id, 'g' => $group->id]),
        'editurl' => new \core\url('/mod/customgroups/editgroup.php', ['id' => $group->id]),
        'removeurl' => new \core\url('/mod/customgroups/editgroup.php', ['action' => 'remove', 'id' => $group->id]),
        'users' => $users,
        'countries' => $countries,
        'warningtexts' => $warningtexts
    ];
}

$data = [];
$data['active'] = $active;
$data['groupview'] = !is_null($groupid);
$data['actionurl'] = new \core\url('/mod/customgroups/groupaction.php');
$data['groupurl'] = new \core\url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id]);
$data['applied'] = $moduleinstance->applied;
$data['minmembers'] = $moduleinstance->minmembers;
$data['maxmembers'] = $moduleinstance->maxmembers;
$data['maxmemberspercountry'] = $moduleinstance->maxmemberspercountry;
$data['cancreategroup'] = customgroups_cancreategroup($modulecontext, $moduleinstance->id);
/** @var \context|false $modulecontext */
$data['hasapplycap'] = $modulecontext ? has_capability('mod/customgroups:applygroups', $modulecontext) : false;
$data['canapplygroups'] = !$moduleinstance->applied && $data['hasapplycap'];
$data['creategroupurl'] = new \core\url('/mod/customgroups/editgroup.php', ['instance' => $moduleinstance->id]);
$data['applygroupsurl'] = new \core\url('/mod/customgroups/applygroups.php', ['instance' => $moduleinstance->id]);
$data['groups'] = $groupsdata;
$data['viewgroupurl'] = $moduleinstance->applied ? new \core\url('/group/index.php', ['id' => $course->id]) : null;
$data['deletemoduleurl'] = $moduleinstance->applied ? new \core\url('/course/mod.php', ['delete' => $cm->id]) : null;

$PAGE->set_url('/mod/customgroups/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_customgroups/view', $data);

echo $OUTPUT->footer();
