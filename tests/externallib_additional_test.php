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

namespace local_ws_enrolcohort;

use core_external\external_api;
use local_ws_enrolcohort_external;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/local/ws_enrolcohort/externallib.php');

/**
 * Additional webservice tests for local_ws_enrolcohort.
 *
 * Covers return-structure validation, status handling, get_instances() content, permission checks
 * and edge cases not covered by externallib_test.php.
 *
 * @package     local_ws_enrolcohort
 * @copyright   2026 onwards, Citricity
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_ws_enrolcohort_external
 */
final class externallib_additional_test extends \externallib_advanced_testcase {

    /**
     * Create a course, cohort and role and add a cohort enrolment instance through the webservice.
     *
     * @param array $extra Extra add_instance() parameters.
     * @return array [course, cohort, roleid, response]
     */
    private function create_instance(array $extra = []): array {
        $course = $this->getDataGenerator()->create_course();
        $cohort = $this->getDataGenerator()->create_cohort();
        $roleid = $this->getDataGenerator()->create_role();

        $response = local_ws_enrolcohort_external::add_instance(array_merge([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $roleid,
        ], $extra));

        return [$course, $cohort, $roleid, $response];
    }

    /**
     * Index the response data items by their object type.
     *
     * @param array $response
     * @return array
     */
    private function data_by_object(array $response): array {
        $byobject = [];
        foreach ($response['data'] as $item) {
            $byobject[$item['object']] = $item;
        }
        return $byobject;
    }

    /**
     * All four functions are registered and their definitions load in the external API.
     */
    public function test_external_function_definitions(): void {
        $functions = [
            'local_ws_enrolcohort_add_instance',
            'local_ws_enrolcohort_update_instance',
            'local_ws_enrolcohort_delete_instance',
            'local_ws_enrolcohort_get_instances',
        ];
        foreach ($functions as $functionname) {
            $info = external_api::external_function_info($functionname);
            $this->assertEquals($functionname, $info->name);
            $this->assertNotNull($info->parameters_desc);
            $this->assertNotNull($info->returns_desc);
        }
    }

    /**
     * The add_instance() response matches its declared return structure.
     */
    public function test_add_instance_return_structure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $response] = $this->create_instance(['name' => 'Structure test']);

        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::add_instance_returns(), $response);
        $this->assertEquals(201, $cleaned['code']);
        $this->assertGreaterThan(0, $cleaned['id']);
    }

    /**
     * The update_instance() response matches its declared return structure, for both change and no-change calls.
     */
    public function test_update_instance_return_structure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $added] = $this->create_instance();

        $response = local_ws_enrolcohort_external::update_instance(['id' => $added['id'], 'name' => 'Renamed']);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::update_instance_returns(), $response);
        $this->assertEquals(200, $cleaned['code']);

        $response = local_ws_enrolcohort_external::update_instance(['id' => $added['id']]);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::update_instance_returns(), $response);
        $this->assertEquals(tools::get_string('updateinstance:nochange'), $cleaned['message']);
    }

    /**
     * The delete_instance() response matches its declared return structure.
     */
    public function test_delete_instance_return_structure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $cohort = $this->getDataGenerator()->create_cohort();
        $roleid = $this->getDataGenerator()->create_role();

        $added = local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $roleid, 'groupid' => $group->id,
        ]);

        $response = local_ws_enrolcohort_external::delete_instance(['id' => $added['id']]);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::delete_instance_returns(), $response);

        $this->assertCount(1, $cleaned['data']);
        $enrol = $cleaned['data'][0];
        $this->assertEquals($course->id, $enrol['course']['id']);
        $this->assertEquals($cohort->id, $enrol['cohort']['id']);
        $this->assertEquals($roleid, $enrol['role']['id']);
        $this->assertEquals($group->id, $enrol['group']['id']);
    }

    /**
     * The get_instances() response matches its declared return structure and lists the course's instances.
     */
    public function test_get_instances_for_course(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, $cohort, $roleid, $added] = $this->create_instance(['name' => 'First']);

        // A second instance on the same course, same cohort, different role.
        $roleid2 = $this->getDataGenerator()->create_role();
        $added2 = local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $roleid2,
        ]);

        // An instance on another course, which must not be returned.
        $this->create_instance();

        $response = local_ws_enrolcohort_external::get_instances(['id' => $course->id]);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::get_instances_returns(), $response);

        $this->assertEquals(200, $cleaned['code']);
        $this->assertEquals($course->id, $cleaned['id']);
        $this->assertCount(2, $cleaned['data']);

        $ids = array_column($cleaned['data'], 'id');
        sort($ids);
        $expected = [$added['id'], $added2['id']];
        sort($expected);
        $this->assertEquals($expected, $ids);

        foreach ($cleaned['data'] as $item) {
            $this->assertEquals('enrol', $item['object']);
            $this->assertEquals($course->id, $item['courseid']);
            $this->assertEquals($cohort->id, $item['cohortid']);
            $this->assertEquals($course->id, $item['course']['id']);
            $this->assertEquals($cohort->id, $item['cohort']['id']);
        }

        $expectedmessage = tools::get_string('getinstance:200', [
            'courseid' => $course->id, 'numberofenrolmentinstances' => 2, 'numberofcourses' => 1,
        ]);
        $this->assertEquals($expectedmessage, $cleaned['message']);
    }

    /**
     * get_instances() on a course with no cohort instances returns an empty list.
     */
    public function test_get_instances_for_course_without_instances(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $response = local_ws_enrolcohort_external::get_instances(['id' => $course->id]);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::get_instances_returns(), $response);

        $this->assertEquals(200, $cleaned['code']);
        $this->assertEmpty($cleaned['data']);
    }

    /**
     * get_instances() with id -1 returns cohort instances from every course but no other enrolment methods.
     */
    public function test_get_instances_all_courses(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $addedids = [];
        for ($i = 0; $i < 3; $i++) {
            [, , , $added] = $this->create_instance();
            $addedids[] = $added['id'];
        }

        // A course with no cohort instance.
        $this->getDataGenerator()->create_course();

        $response = local_ws_enrolcohort_external::get_instances([
            'id' => local_ws_enrolcohort_external::GET_INSTANCES_COURSEID_ALL,
        ]);
        $cleaned = external_api::clean_returnvalue(local_ws_enrolcohort_external::get_instances_returns(), $response);

        $ids = array_column($cleaned['data'], 'id');
        sort($ids);
        sort($addedids);
        $this->assertEquals($addedids, $ids);

        $expectedmessage = tools::get_string('getinstances:200', [
            'courseid' => -1, 'numberofenrolmentinstances' => 3, 'numberofcourses' => 4,
        ]);
        $this->assertEquals($expectedmessage, $cleaned['message']);
    }

    /**
     * add_instance() honours an explicit disabled status.
     */
    public function test_add_instance_disabled_status(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $response] = $this->create_instance(['status' => ENROL_INSTANCE_DISABLED]);

        $this->assertEquals(ENROL_INSTANCE_DISABLED, $DB->get_field('enrol', 'status', ['id' => $response['id']]));
        $this->assertEquals(ENROL_INSTANCE_DISABLED, $this->data_by_object($response)['enrol']['status']);
    }

    /**
     * add_instance() stores the supplied name.
     */
    public function test_add_instance_custom_name(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $response] = $this->create_instance(['name' => 'My cohort sync']);

        $this->assertEquals('My cohort sync', $DB->get_field('enrol', 'name', ['id' => $response['id']]));
        $this->assertEquals('My cohort sync', $this->data_by_object($response)['enrol']['name']);
    }

    /**
     * Adding an instance enrols the existing cohort members with the chosen role.
     */
    public function test_add_instance_enrols_cohort_members(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $cohort = $this->getDataGenerator()->create_cohort();
        $roleid = $this->getDataGenerator()->create_role();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $user1->id);
        cohort_add_member($cohort->id, $user2->id);

        local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $roleid,
        ]);

        $context = \context_course::instance($course->id);
        foreach ([$user1, $user2] as $user) {
            $this->assertTrue(is_enrolled($context, $user));
            $this->assertTrue(user_has_role_assignment($user->id, $roleid, $context->id));
        }
    }

    /**
     * An editing teacher can add an instance, but not once enrol/cohort:config is removed from them.
     */
    public function test_add_instance_requires_capability(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $cohort = $this->getDataGenerator()->create_cohort();
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);

        $teacherroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $context = \context_course::instance($course->id);

        $this->setUser($teacher);

        // With the default editingteacher capabilities the call succeeds.
        $response = local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $studentroleid,
        ]);
        $this->assertEquals(201, $response['code']);

        // Remove one of the required capabilities and try again with a different cohort.
        assign_capability('enrol/cohort:config', CAP_PROHIBIT, $teacherroleid, $context->id, true);
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $this->expectException(\required_capability_exception::class);
        local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort2->id, 'roleid' => $studentroleid,
        ]);
    }

    /**
     * update_instance() can disable and re-enable an instance.
     */
    public function test_update_instance_status_toggle(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        [, , , $added] = $this->create_instance();

        local_ws_enrolcohort_external::update_instance(['id' => $added['id'], 'status' => ENROL_INSTANCE_DISABLED]);
        $this->assertEquals(ENROL_INSTANCE_DISABLED, $DB->get_field('enrol', 'status', ['id' => $added['id']]));

        local_ws_enrolcohort_external::update_instance(['id' => $added['id'], 'status' => ENROL_INSTANCE_ENABLED]);
        $this->assertEquals(ENROL_INSTANCE_ENABLED, $DB->get_field('enrol', 'status', ['id' => $added['id']]));
    }

    /**
     * update_instance() with groupid -1 creates a new cohort group and links it to the instance.
     */
    public function test_update_instance_create_new_group(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        [$course, , , $added] = $this->create_instance();
        $this->assertEquals(0, $DB->count_records('groups', ['courseid' => $course->id]));

        local_ws_enrolcohort_external::update_instance([
            'id' => $added['id'], 'groupid' => local_ws_enrolcohort_external::COHORT_GROUP_CREATE_NEW,
        ]);

        $groupid = $DB->get_field('enrol', 'customint2', ['id' => $added['id']]);
        $this->assertGreaterThan(0, $groupid);
        $this->assertTrue($DB->record_exists('groups', ['id' => $groupid, 'courseid' => $course->id]));
    }

    /**
     * update_instance() refuses an id that belongs to a non-cohort enrolment instance.
     */
    public function test_update_instance_non_cohort_instance(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $manual = enrol_get_instances($course->id, false);
        $manual = reset($manual);
        $this->assertEquals('manual', $manual->enrol);

        $this->expectException(exceptions\cohort_enrol_instance_not_found_exception::class);
        local_ws_enrolcohort_external::update_instance(['id' => $manual->id, 'name' => 'Nope']);
    }

    /**
     * delete_instance() refuses an id that belongs to a non-cohort enrolment instance and leaves it in place.
     */
    public function test_delete_instance_non_cohort_instance(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $manual = enrol_get_instances($course->id, false);
        $manual = reset($manual);

        try {
            local_ws_enrolcohort_external::delete_instance(['id' => $manual->id]);
            $this->fail('Expected a cohort_enrol_instance_not_found_exception.');
        } catch (exceptions\cohort_enrol_instance_not_found_exception $e) {
            $this->assertTrue($DB->record_exists('enrol', ['id' => $manual->id]));
        }
    }

    /**
     * Deleting an instance unenrols the cohort members it had enrolled.
     */
    public function test_delete_instance_unenrols_cohort_members(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $cohort = $this->getDataGenerator()->create_cohort();
        $roleid = $this->getDataGenerator()->create_role();
        $user = $this->getDataGenerator()->create_user();
        cohort_add_member($cohort->id, $user->id);

        $added = local_ws_enrolcohort_external::add_instance([
            'courseid' => $course->id, 'cohortid' => $cohort->id, 'roleid' => $roleid,
        ]);
        $context = \context_course::instance($course->id);
        $this->assertTrue(is_enrolled($context, $user));

        local_ws_enrolcohort_external::delete_instance(['id' => $added['id']]);

        $this->assertFalse(is_enrolled($context, $user));
    }
}
