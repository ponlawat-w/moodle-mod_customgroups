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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$instance = optional_param('instance', 0, PARAM_INT);

$groupid = optional_param('g', null, PARAM_INT);

// Groups list pagination.
$page = optional_param('page', 0, PARAM_INT);
$perpage = max(1, optional_param('perpage', 20, PARAM_INT));

if ($id) {
    $cm = get_coursemodule_from_id('customgroups', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('customgroups', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('customgroups', ['id' => $instance], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('customgroups', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

/** @var \context $modulecontext */
$modulecontext = \core\context\module::instance($cm->id);
require_capability('mod/customgroups:view', $modulecontext);

$event = \mod_customgroups\event\course_module_viewed::create([
    'context' => $modulecontext,
    'objectid' => $moduleinstance->id,
]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('customgroups', $moduleinstance);
$event->trigger();

$active = customgroups_isactive($moduleinstance);

$PAGE->set_url('/mod/customgroups/view.php', array_merge(
    ['id' => $cm->id],
    is_null($groupid) ? [] : ['g' => $groupid],
    $page ? ['page' => $page] : []
));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

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
$modulecontext;
$data['hasapplycap'] = $modulecontext ? has_capability('mod/customgroups:applygroups', $modulecontext) : false;
$data['canapplygroups'] = !$moduleinstance->applied && $data['hasapplycap'];
$data['creategroupurl'] = new \core\url('/mod/customgroups/editgroup.php', ['instance' => $moduleinstance->id]);
$data['applygroupsurl'] = new \core\url('/mod/customgroups/applygroups.php', ['instance' => $moduleinstance->id]);
$data['viewgroupurl'] = $moduleinstance->applied ? new \core\url('/group/index.php', ['id' => $course->id]) : null;

if (is_null($groupid)) {
    $totalcount = $DB->count_records('customgroups_groups', ['module' => $moduleinstance->id]);
    $joinedgroupid = customgroups_getjoinedgroupid($moduleinstance->id);
    $canjoin = $active && has_capability('mod/customgroups:joingroup', $modulecontext);
    $params = ['module' => $moduleinstance->id];
    $countryjoinsql = '';
    $countrycountsql = '0';
    if ($moduleinstance->maxmemberspercountry) {
        $countryjoinsql = 'LEFT JOIN {user} u ON j.userid = u.id AND u.country = :country';
        $countrycountsql = 'COUNT(u.id)';
        $params['country'] = $USER->country;
    }
    // Joined group comes first; otherwise joinable groups come first, under the same conditions as customgroups_canjoingroup.
    $sortgroupsql = '0';
    if ($joinedgroupid) {
        $sortgroupsql = 'CASE WHEN g.id = :joinedgroupid THEN 0 ELSE 1 END';
        $params['joinedgroupid'] = $joinedgroupid;
    } else if ($canjoin) {
        $joinableconditions = [];
        if ($moduleinstance->maxmembers) {
            $joinableconditions[] = 'COUNT(j.id) < :maxmembers';
            $params['maxmembers'] = $moduleinstance->maxmembers;
        }
        if ($moduleinstance->maxmemberspercountry) {
            $joinableconditions[] = "{$countrycountsql} < :maxmemberspercountry";
            $params['maxmemberspercountry'] = $moduleinstance->maxmemberspercountry;
        }
        if ($joinableconditions) {
            $sortgroupsql = 'CASE WHEN ' . implode(' AND ', $joinableconditions) . ' THEN 0 ELSE 1 END';
        }
    }
    $groups = $DB->get_records_sql(
        <<<SQL
            SELECT g.id, g.name, COUNT(j.id) joinscount, {$countrycountsql} countrycount
            FROM {customgroups_groups} g
                LEFT JOIN {customgroups_joins} j ON j.groupid = g.id
                {$countryjoinsql}
            WHERE g.module = :module
            GROUP BY g.id, g.name
            ORDER BY {$sortgroupsql}, COUNT(j.id) ASC, g.name ASC, g.id ASC
        SQL,
        $params,
        $page * $perpage,
        $perpage
    );
    /** @var \core\context\module $modulecontext */
    $modulecontext;
    $imageurls = customgroups_getimageurls($modulecontext, array_keys($groups));
    $data['groups'] = [];
    foreach ($groups as $group) {
        $data['groups'][] = [
            'name' => $group->name,
            'viewurl' => new \core\url('/mod/customgroups/view.php', ['instance' => $moduleinstance->id, 'g' => $group->id]),
            'image' => $imageurls[$group->id] ?? null,
            'status' => customgroups_getgroupstatus(
                $group->id,
                $moduleinstance,
                $canjoin,
                $joinedgroupid,
                $group->joinscount,
                $group->countrycount
            ),
            'details' => customgroups_getgroupdetails($moduleinstance, $joinedgroupid, $group->joinscount, $group->countrycount),
        ];
    }
    $data['hasgroups'] = $totalcount > 0;
    $data['pagingbar'] = $OUTPUT->paging_bar(
        $totalcount,
        $page,
        $perpage,
        new \core\url('/mod/customgroups/view.php', ['id' => $cm->id, 'perpage' => $perpage])
    );
} else {
    $group = $DB->get_record('customgroups_groups', ['id' => $groupid, 'module' => $moduleinstance->id]);
    if (!$group) {
        throw new \core\exception\moodle_exception('groupnotfound', 'mod_customgroups');
    }
    $joinedgroupid = customgroups_getjoinedgroupid($moduleinstance->id);
    $joins = $DB->get_records('customgroups_joins', ['groupid' => $group->id]);
    $users = [];
    foreach ($joins as $join) {
        $users[] = [
            'url' => new \core\url('/user/view.php', ['id' => $join->userid, 'course' => $course->id]),
            'name' => fullname($DB->get_record('user', ['id' => $join->userid], '*', MUST_EXIST)),
            'owner' => $join->userid == $group->userid,
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
                'class' => $country == $USER->country && $count >= $moduleinstance->maxmemberspercountry ? 'text-danger' : '',
            ];
        }
    }
    $warningtexts = [];
    if (!$joinedgroupid) {
        if ($moduleinstance->maxmembers && $joinscount >= $moduleinstance->maxmembers) {
            $warningtexts[] = [
                'text' => get_string('cannotjoin_groupreachedmaxmembers', 'mod_customgroups', $moduleinstance->maxmembers),
            ];
        }
        if (
            $moduleinstance->maxmemberspercountry
            && isset($countpercountries[$USER->country])
            && $countpercountries[$USER->country] >= $moduleinstance->maxmemberspercountry
        ) {
            $warningtexts[] = ['text' =>
                get_string(
                    'cannotjoin_groupreachedmaxmemberspercountry',
                    'mod_customgroups',
                    [
                        'country' => get_string($USER->country, 'countries'),
                        'maxmembers' => $moduleinstance->maxmemberspercountry,
                    ]
                ),
            ];
        }
    }
    if ($moduleinstance->minmembers && $joinscount < $moduleinstance->minmembers) {
        $warningtexts[] = ['text' => get_string('minmembersnotsatisfied', 'mod_customgroups', $moduleinstance->minmembers)];
    }
    /** @var \core\context\module $modulecontext */
    $modulecontext;
    $data['group'] = [
        'id' => $group->id,
        'name' => $group->name,
        'description' => format_text($group->description, $group->descriptionformat, ['context' => $modulecontext]),
        'joinscount' => $joinscount,
        'joined' => $group->id == $joinedgroupid,
        'joinable' => customgroups_canjoingroup($group->id, $moduleinstance, $modulecontext),
        'leaveable' => $active && ($joinedgroupid == $group->id && $group->userid != $USER->id),
        'editable' => $active && ($group->userid == $USER->id),
        'editurl' => new \core\url('/mod/customgroups/editgroup.php', ['id' => $group->id]),
        'removeurl' => new \core\url('/mod/customgroups/editgroup.php', ['action' => 'remove', 'id' => $group->id]),
        'image' => customgroups_getimageurl($modulecontext, $group->id),
        'users' => $users,
        'countries' => $countries,
        'warningtexts' => $warningtexts,
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_customgroups/view', $data);

echo $OUTPUT->footer();
