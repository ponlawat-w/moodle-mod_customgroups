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
 * Plugin strings are defined here.
 *
 * @package     mod_customgroups
 * @category    string
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Custom Groups by Students';
$string['modulename'] = 'Students\' Custom Groups Module';
$string['modulenameplural'] = 'Students\' Custom Groups Modules';
$string['pluginadministration'] = 'Students\' Custom Groups Module Administration';
$string['customgroups:applygroups'] = 'Apply Groups to Course';
$string['customgroups:addinstance'] = 'Add a Module Instance';
$string['customgroups:creategroup'] = 'Create a New Group';
$string['customgroups:joingroup'] = 'Join a Group';

$string['timelinesettings'] = 'Timeline Settings';
$string['openforcreatingorjoining'] = 'Open for creating or joining groups';
$string['openforcreatingorjoining_help'] = 'If checked, students can create a new groups or join existing groups.';
$string['activeuntil'] = 'Active until';
$string['activeuntil_help'] = 'If enabled, the ability to create or join groups will be deactived after a specfic time.';
$string['defaultgrouping'] = 'Default Grouping';
$string['defaultgrouping_help'] = 'After applying students\' created groups to the course, all the groups will be in the selected grouping.';
$string['nogrouping'] = 'No grouping';
$string['groupconditions'] = 'Group Conditions';
$string['minmembers'] = 'Minimum members';
$string['minmembers_help'] = 'Minimum number of members per group, 0 for unlimited';
$string['maxmembers'] = 'Maximum members';
$string['maxmembers_help'] = 'Maximum number of members per group, 0 for unlimited';
$string['maxmemberspercountry'] = 'Maximum members per country';
$string['maxmemberspercountry_help'] = 'Maximum number of members for each country in a group, 0 for unlimited';

$string['error_maxmemberslessthanminmembers'] = 'The maximum number of members must be greater than the minimum number.';
$string['error_maxmemberslessthanmaxmemberspercountry'] = 'The maximum number of members must be greater than per country.';

$string['creategroup'] = 'Create a new group';
$string['applygroups'] = 'Apply groups to course';
$string['editgroup'] = 'Edit group';
$string['deletegroup'] = 'Delete group';
$string['applied'] = 'Groups in this activity have been applied to the course.';
$string['inactive'] = 'This activity is no longer active.';
$string['join'] = 'Join this group';
$string['leave'] = 'Leave this group';
$string['joined'] = 'You are member of this group';
$string['members'] = 'Joined Members';
$string['owner'] = 'Owner';
$string['viewgroups'] = 'View Groups';
$string['deletemodule'] = 'Delete Module';

$string['groupname'] = 'Group Name';

$string['confirmation'] = 'Confirmation';
$string['confirm_removegroup'] = 'Are you sure you want to remove group {$a}?';
$string['confirm_applygroups'] = 'Are you sure you want to apply groups to course? The activity module will be deactivated after applying.';
