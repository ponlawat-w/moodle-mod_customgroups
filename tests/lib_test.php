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

namespace mod_customgroups;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/customgroups/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Unit tests for mod_customgroups lib
 *
 * @package     mod_customgroups
 * @category    test
 * @copyright   2023 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversFunction('customgroups_delete_instance')]
#[\PHPUnit\Framework\Attributes\CoversFunction('customgroups_deleteallgroups')]
#[\PHPUnit\Framework\Attributes\CoversClass(\mod_customgroups\privacy\provider::class)]
final class lib_test extends \advanced_testcase {
    /**
     * Create a course with a module instance containing two groups
     *
     * @return array [$course, $instance, $otherinstance]
     */
    private function create_instance_with_groups(): array {
        $generator = $this->getDataGenerator();
        /** @var \mod_customgroups_generator $modgenerator */
        $modgenerator = $generator->get_plugin_generator('mod_customgroups');

        $course = $generator->create_course();
        $user1 = $generator->create_and_enrol($course);
        $user2 = $generator->create_and_enrol($course);
        $user3 = $generator->create_and_enrol($course);

        $instance = $modgenerator->create_instance(['course' => $course->id]);
        $modgenerator->create_group($instance, $user1->id, [$user2->id]);
        $modgenerator->create_group($instance, $user3->id);

        $otherinstance = $modgenerator->create_instance(['course' => $course->id]);
        $modgenerator->create_group($otherinstance, $user1->id);

        return [$course, $instance, $otherinstance];
    }

    /**
     * Assert whether a module instance still has any groups, joins or group images
     *
     * @param \stdClass $instance
     * @param bool $expected
     * @return void
     */
    private function assert_has_groups(\stdClass $instance, bool $expected): void {
        global $DB;
        $contextid = \core\context\module::instance($instance->cmid)->id;
        $joins = $DB->count_records_sql(
            'SELECT COUNT(*) FROM {customgroups_joins} j JOIN {customgroups_groups} g ON g.id = j.groupid WHERE g.module = ?',
            [$instance->id]
        );
        $images = get_file_storage()->get_area_files($contextid, 'mod_customgroups', 'groupimages', false, 'itemid', false);
        $this->assertSame($expected, $DB->record_exists('customgroups_groups', ['module' => $instance->id]));
        $this->assertSame($expected, $joins > 0);
        $this->assertSame($expected, count($images) > 0);
    }

    /**
     * Deleting an instance with groups succeeds and removes its groups only
     *
     * @return void
     */
    public function test_delete_instance_with_groups(): void {
        global $DB;
        $this->resetAfterTest();
        [, $instance, $otherinstance] = $this->create_instance_with_groups();

        $this->assertTrue(customgroups_delete_instance($instance->id));

        $this->assertFalse($DB->record_exists('customgroups', ['id' => $instance->id]));
        $this->assert_has_groups($instance, false);
        $this->assertTrue($DB->record_exists('customgroups', ['id' => $otherinstance->id]));
        $this->assert_has_groups($otherinstance, true);
    }

    /**
     * Deleting an instance that no longer exists succeeds so that retried deletions can complete
     *
     * @return void
     */
    public function test_delete_instance_already_deleted(): void {
        global $DB;
        $this->resetAfterTest();
        [, $instance] = $this->create_instance_with_groups();

        $this->assertTrue(customgroups_delete_instance($instance->id));
        $this->assertTrue(customgroups_delete_instance($instance->id));
        $this->assertFalse($DB->record_exists('customgroups', ['id' => $instance->id]));
    }

    /**
     * Deleting the course module with groups through course_delete_module() does not throw
     *
     * @return void
     */
    public function test_course_delete_module_with_groups(): void {
        global $DB;
        $this->resetAfterTest();
        [, $instance] = $this->create_instance_with_groups();

        course_delete_module($instance->cmid);

        $this->assertFalse($DB->record_exists('course_modules', ['id' => $instance->cmid]));
        $this->assertFalse($DB->record_exists('customgroups', ['id' => $instance->id]));
        $this->assertFalse($DB->record_exists('customgroups_groups', ['module' => $instance->id]));
    }

    /**
     * Deleting all user data in a module context keeps the module instance
     *
     * @return void
     */
    public function test_privacy_delete_data_in_module_context(): void {
        global $DB;
        $this->resetAfterTest();
        [, $instance, $otherinstance] = $this->create_instance_with_groups();

        privacy\provider::delete_data_for_all_users_in_context(\core\context\module::instance($instance->cmid));

        $this->assertTrue($DB->record_exists('customgroups', ['id' => $instance->id]));
        $this->assert_has_groups($instance, false);
        $this->assert_has_groups($otherinstance, true);

        // The activity can still be deleted afterwards.
        course_delete_module($instance->cmid);
        $this->assertFalse($DB->record_exists('course_modules', ['id' => $instance->cmid]));
    }

    /**
     * Deleting all user data in a course context keeps the module instances
     *
     * @return void
     */
    public function test_privacy_delete_data_in_course_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $instance, $otherinstance] = $this->create_instance_with_groups();

        privacy\provider::delete_data_for_all_users_in_context(\core\context\course::instance($course->id));

        foreach ([$instance, $otherinstance] as $moduleinstance) {
            $this->assertTrue($DB->record_exists('customgroups', ['id' => $moduleinstance->id]));
            $this->assert_has_groups($moduleinstance, false);
        }
    }
}
