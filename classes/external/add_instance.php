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

/**
 * The add_instance webservice function for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @copyright   2026 onwards, Citricity
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ws_enrolcohort\external;

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_ws_enrolcohort\tools;
use local_ws_enrolcohort\responses;
use local_ws_enrolcohort\exceptions\course_not_found_exception;
use local_ws_enrolcohort\exceptions\cohort_not_found_exception;
use local_ws_enrolcohort\exceptions\role_not_found_exception;
use local_ws_enrolcohort\exceptions\group_not_found_exception;
use local_ws_enrolcohort\exceptions\course_is_site_exception;
use local_ws_enrolcohort\exceptions\cohort_not_available_at_context_exception;
use local_ws_enrolcohort\exceptions\role_not_assignable_at_context_exception;
use local_ws_enrolcohort\exceptions\invalid_status_exception;
use local_ws_enrolcohort\exceptions\method_not_available_exception;
use local_ws_enrolcohort\exceptions\instance_role_synced_already_exception;

/**
 * Adds a cohort sync enrolment instance to a course.
 *
 * Used by local_ws_enrolcohort_external, which provides the constants and shared helpers referenced through self::.
 *
 * @package     local_ws_enrolcohort
 */
trait add_instance {
    /**
     * Gets the default value for an add_instance() parameter. Use properly. No error checking happens.
     *
     * @param string $parametername
     * @return mixed
     */
    public static function add_instance_get_parameter_default_value($parametername = '') {
        // Just ask for the right things and one shall receive. We shan't be making any mistakes.
        return self::add_instance_parameters()->keys[self::QUERYSTRING_INSTANCE]->keys[$parametername]->default;
    }

    /**
     * Returns description of the add_instance function parameters.
     *
     * @return external_function_parameters
     */
    public static function add_instance_parameters() {
        $courseidparam = new external_value(
            PARAM_INT,
            'The id of the course.',
            VALUE_REQUIRED
        );

        $cohortidparam = new external_value(
            PARAM_INT,
            'The id of the cohort.',
            VALUE_REQUIRED
        );

        $roleidparam = new external_value(
            PARAM_INT,
            'The id of an existing role to assign users.',
            VALUE_REQUIRED
        );

        $groupidparam = new external_value(
            PARAM_INT,
            'The id of a group to add users to.',
            VALUE_OPTIONAL,
            self::COHORT_GROUP_CREATE_NONE
        );

        $nameparam = new external_value(
            PARAM_TEXT,
            'The name of the cohort enrolment instance.',
            VALUE_OPTIONAL,
            ''
        );

        $statusparam = new external_value(
            PARAM_INT,
            'The status of the enrolment method.',
            VALUE_OPTIONAL,
            ENROL_INSTANCE_ENABLED
        );

        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'courseid'  => $courseidparam,
                'cohortid'  => $cohortidparam,
                'roleid'    => $roleidparam,
                'groupid'   => $groupidparam,
                'name'      => $nameparam,
                'status'    => $statusparam,
            ]),
        ]);
    }

    /**
     * Returns description of the add_instance() function return value.
     *
     * @return external_single_structure
     */
    public static function add_instance_returns() {
        return self::webservice_function_returns();
    }

    /**
     * Adds a cohort enrolment instance to a given course.
     *
     * @param array $params The function parameters, as described by add_instance_parameters().
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function add_instance($params) {
        global $CFG, $DB, $SITE;

        require_once("{$CFG->dirroot}/cohort/lib.php");

        // Check the call for parameters.
        $params = self::validate_parameters(self::add_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        // Other data.
        $extradata = [];

        // Get the course.
        $courseid = $params[self::QUERYSTRING_INSTANCE]['courseid'];

        // Validate the course. This is required.
        if ($courseid == $SITE->id) {
            throw new course_is_site_exception();
        } else if (!$DB->record_exists('course', ['id' => $courseid])) {
            throw new course_not_found_exception($courseid);
        }

        // Set the context to course for validation.
        $context = \context_course::instance($courseid);

        // Add info to the response object.
        $extradata[] = (new responses\course($courseid))->to_array();

        // Get the cohort. This is required.
        $cohortid = $params[self::QUERYSTRING_INSTANCE]['cohortid'];

        // Validate the cohort. This is required.
        if (!$DB->record_exists('cohort', ['id' => $cohortid])) {
            throw new cohort_not_found_exception($cohortid);
        }

        // Add some info to the response object.
        $extradata[] = (new responses\cohort($cohortid))->to_array();

        // Get the available cohorts.
        $availablecohorts = cohort_get_available_cohorts($context, 0, 0, 0);
        if (empty($availablecohorts) || !isset($availablecohorts[$cohortid])) {
            throw new cohort_not_available_at_context_exception($cohortid);
        }

        // Get the role.
        $roleid = $params[self::QUERYSTRING_INSTANCE]['roleid'];

        // Validate the role. This is required.
        $assignableroles = self::get_assignable_roles($context);

        if (!$DB->record_exists('role', ['id' => $roleid])) {
            // Role doesn't exist.
            throw new role_not_found_exception($roleid);
        } else if (empty($assignableroles) || !isset($assignableroles[$roleid])) {
            // Role is not assignable at this context.
            throw new role_not_assignable_at_context_exception($roleid);
        }

        $extradata[] = (new responses\role($roleid))->to_array();

        // Get the group.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['groupid'])) {
            $groupid = self::add_instance_get_parameter_default_value('groupid');
        } else {
            $groupid = $params[self::QUERYSTRING_INSTANCE]['groupid'];
        }

        // Validate the group. This is optional.
        if (!is_null($groupid)) {
            $groupcreatemodes = [self::COHORT_GROUP_CREATE_NONE, self::COHORT_GROUP_CREATE_NEW];
            $coursegroupexists = $DB->record_exists('groups', ['courseid' => $courseid, 'id' => $groupid]);
            if (!in_array($groupid, $groupcreatemodes) && !$coursegroupexists) {
                // Provided group id doesn't exist for this course.
                throw new group_not_found_exception($groupid);
            }
        } else {
            // Get the default value specified for the parameter groupid.
            $groupid = self::add_instance_get_parameter_default_value('groupid');
        }

        // Validate the name of the cohort enrolment instance. This is optional.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['name'])) {
            $name = self::add_instance_get_parameter_default_value('name');
        } else {
            $name = $params[self::QUERYSTRING_INSTANCE]['name'];
        }

        // Check the users capabilities to ensure that they can do this.
        if ($context instanceof \context_course) {
            // Check that the user has the required capabilities for the course context. This may not be needed.
            $requiredcapabilities = [
                'moodle/course:enrolconfig',
                'enrol/cohort:config',
                'moodle/cohort:view',
                'moodle/course:managegroups',
                'moodle/role:assign',
            ];

            foreach ($requiredcapabilities as $requiredcapability) {
                if (!has_capability($requiredcapability, $context)) {
                    throw new \required_capability_exception($context, $requiredcapability, 'nopermissions', '');
                }
            }
        }

        // Validate the status and set to a default.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['status'])) {
            $status = self::add_instance_get_parameter_default_value('status');
        } else {
            $status = $params[self::QUERYSTRING_INSTANCE]['status'];
        }

        if (is_null($status)) {
            // Set status to the default.
            $status = self::add_instance_get_parameter_default_value('status');
        } else if (!in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            throw new invalid_status_exception($status);
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('cohort')) {
            throw new method_not_available_exception();
        }

        // Prepare the data to be returned as the response.
        $extradata[] = [
            'object'    => 'data',
            'cohortid'  => $cohortid,
            'roleid'    => $roleid,
            'groupid'   => $groupid,
            'name'      => $name,
            'status'    => $status,
        ];

        // We are anticipating a success.
        $code = 201;
        $message = tools::get_string('addinstance:201', $code);

        // The initial response. The field id will be filled in later.
        $response = [
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata,
        ];

        // Prepare the fields.
        $fields = [
            'name'              => $name,
            'status'            => $status,
            'roleid'            => $roleid,
            'id'                => 0,
            'courseid'          => $courseid,
            'type'              => 'cohort',
            self::FIELD_COHORT  => $cohortid,
            self::FIELD_GROUP   => $groupid,
        ];

        // Before creation ensure that there isn't an instance already synced with this role.
        $sqlwhere = "roleid = :roleid AND customint1 = :customint1 AND courseid = :courseid AND enrol = 'cohort' AND id <> :id";
        $sqlparams = [
            'roleid'            => $roleid,
            self::FIELD_COHORT  => $cohortid,
            'courseid'          => $courseid,
            'id'                => $fields['id'],
        ];

        if ($DB->record_exists_select('enrol', $sqlwhere, $sqlparams)) {
            // Don't add instance. Send an error response.
            $instance = $DB->get_record_select('enrol', $sqlwhere, $sqlparams);

            throw new instance_role_synced_already_exception($instance->id, $roleid);
        }

        // Get the full course object.
        $course = $DB->get_record('course', ['id' => $courseid]);

        // After all that hard work we can now add the instance.
        $response['id'] = $cohortenrolment->add_instance($course, $fields);

        // Get the enrolment instance.
        $realenrolinstance = $DB->get_record('enrol', ['id' => $response['id']]);
        if (empty($realenrolinstance->name)) {
            $enrolinstancename =
                $cohortenrolment->get_instance_name($realenrolinstance) . ' - ' . tools::get_string('addinstance:usingdefaultname');
        } else {
            $enrolinstancename = $realenrolinstance->name;
        }

        // Add data about the group to the response.
        $realgroupid = $DB->get_field('enrol', self::FIELD_GROUP, ['id' => $response['id']]);
        $extradata[] = (new responses\group($realgroupid, 'group', $courseid))->to_array();

        // Add data about the enrolment instance to the response.
        $extradata[] = (new responses\enrol(
            $response['id'],
            'enrol',
            $enrolinstancename,
            $status,
            $roleid,
            $courseid,
            $cohortid,
            $realgroupid
        ))->to_array();

        // Add the additional extra data to the response.
        $response['data'] = $extradata;

        // Return some data.
        return $response;
    }
}
