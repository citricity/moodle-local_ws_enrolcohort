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
 * The update_instance webservice function for local_ws_enrolcohort.
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
use local_ws_enrolcohort\exceptions\role_not_found_exception;
use local_ws_enrolcohort\exceptions\group_not_found_exception;
use local_ws_enrolcohort\exceptions\role_not_assignable_at_context_exception;
use local_ws_enrolcohort\exceptions\invalid_status_exception;
use local_ws_enrolcohort\exceptions\cohort_enrol_method_not_available_exception;
use local_ws_enrolcohort\exceptions\cohort_enrol_instance_not_found_exception;

/**
 * Updates an existing cohort sync enrolment instance.
 *
 * Used by local_ws_enrolcohort_external, which provides the constants and shared helpers referenced through self::.
 *
 * @package     local_ws_enrolcohort
 */
trait update_instance {
    /**
     * Gets the default value for an add_instance() parameter. Use properly. No error checking happens.
     *
     * @param string $parametername
     * @return mixed
     */
    public static function update_instance_get_parameter_default_value($parametername = '') {
        // Just ask for the right things and one shall receive. We shan't be making any mistakes.
        return self::update_instance_parameters()->keys[self::QUERYSTRING_INSTANCE]->keys[$parametername]->default;
    }

    /**
     * Returns description of the update_instance() function parameters.
     *
     * @return external_function_parameters
     */
    public static function update_instance_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'id'      => new external_value(PARAM_INT, 'The id of the enrolment instance.', VALUE_REQUIRED),
                'name'    => new external_value(PARAM_TEXT, 'The name you want to give the enrolment instance.', VALUE_OPTIONAL),
                'status'  => new external_value(PARAM_INT, 'The status of the enrolment method.', VALUE_OPTIONAL),
                'roleid'  => new external_value(PARAM_INT, 'The id of an existing role to assign users.', VALUE_OPTIONAL),
                'groupid' => new external_value(PARAM_INT, 'The id of a group to add users to.', VALUE_OPTIONAL),
            ]),
        ]);
    }

    /**
     * Returns description of the update_instance function return value.
     *
     * @return external_single_structure
     */
    public static function update_instance_returns() {
        return self::webservice_function_returns();
    }

    /**
     * Updates an existing cohort enrolment instance.
     *
     * @param array $params The function parameters, as described by update_instance_parameters().
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function update_instance($params) {
        global $DB, $SITE;

        // Check the call for parameters.
        $params = self::validate_parameters(self::update_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        // Other data.
        $extradata = [];

        // A place to put the updated enrolment data.
        $data = new \stdClass();

        // Preset the context.
        $context = null;

        // Get the enrolment instance id.
        $id = $params[self::QUERYSTRING_INSTANCE]['id'];

        // Validate the enrolment instance.
        $sqlwhere = "enrol = 'cohort' AND id = :id AND courseid <> :courseid";
        $sqlparams = ['id' => $id, 'courseid' => $SITE->id];

        $enrolmentinstance = null;

        if (!$DB->record_exists_select('enrol', $sqlwhere, $sqlparams)) {
            throw new cohort_enrol_instance_not_found_exception($id);
        } else {
            $enrolmentinstance = $DB->get_record_select('enrol', $sqlwhere, $sqlparams);
            $context = \context_course::instance($enrolmentinstance->courseid);
        }

        // Get the enrolment instance name.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['name'])) {
            $name = $enrolmentinstance->name;
        } else {
            $name = $params[self::QUERYSTRING_INSTANCE]['name'];
        }

        if (!empty($name)) {
            $data->name = $name;
        }

        // Get the enrolment instance status.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['status'])) {
            $status = $enrolmentinstance->status;
        } else {
            $status = $params[self::QUERYSTRING_INSTANCE]['status'];
        }

        // Validate the enrolment instance status.
        if (!is_null($status) && !in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            throw new invalid_status_exception($status);
        } else if (!is_null($status) && in_array($status, [ENROL_INSTANCE_ENABLED, ENROL_INSTANCE_DISABLED])) {
            $data->status = $status;
        }

        // Get the enrolment instance role id.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['roleid'])) {
            $roleid = $enrolmentinstance->roleid;
        } else {
            $roleid = $params[self::QUERYSTRING_INSTANCE]['roleid'];
        }

        if (!empty($roleid)) {
            // Validate the role. This is required.
            $assignableroles = self::get_assignable_roles($context);

            if (!$DB->record_exists('role', ['id' => $roleid])) {
                // Role doesn't exist.
                throw new role_not_found_exception($roleid);
            } else if (empty($assignableroles) || !isset($assignableroles[$roleid])) {
                // Role is not assignable at this context.
                throw new role_not_assignable_at_context_exception($roleid);
            } else {
                $data->roleid = $roleid;
            }
        }

        // Get the group id.
        if (!isset($params[self::QUERYSTRING_INSTANCE]['groupid'])) {
            $groupid = $enrolmentinstance->{self::FIELD_GROUP};
        } else {
            $groupid = $params[self::QUERYSTRING_INSTANCE]['groupid'];
        }
        // Validate the group id.
        if (!empty($groupid)) {
            $groupcreatemodes = [self::COHORT_GROUP_CREATE_NONE, self::COHORT_GROUP_CREATE_NEW];
            $groupexistsforcourse = $DB->record_exists('groups', ['courseid' => $enrolmentinstance->courseid, 'id' => $groupid]);
            if (!in_array($groupid, $groupcreatemodes) && !is_null($enrolmentinstance) && !$groupexistsforcourse) {
                // Provided group id doesn't exist for this course.
                throw new group_not_found_exception($groupid);
            }
        }

        $data->{self::FIELD_GROUP} = $groupid;

        // Return the supplied parameters.
        $extradata[] = [
            'id'        => $id,
            'object'    => 'params',
            'roleid'    => $roleid,
            'name'      => $name,
            'status'    => $status,
            'groupid'   => $groupid,
        ];

        // Check for things that are the same.
        if ($enrolmentinstance instanceof \stdClass && $context instanceof \context_course) {
            foreach ($data as $property => $value) {
                if (isset($enrolmentinstance->$property) && $enrolmentinstance->$property != $value) {
                    // Add role detail to the response object.
                    if (\core_text::strtolower($property) == 'roleid') {
                        $extradata[] = (new responses\role($value))->to_array();
                    }
                }
            }
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('cohort')) {
            throw new cohort_enrol_method_not_available_exception();
        }

        // Check whether only the id was passed.
        $noupdations = count($params[self::QUERYSTRING_INSTANCE]) === 1 && isset($params[self::QUERYSTRING_INSTANCE]['id']);

        // Response message.
        if ($noupdations) {
            $message = tools::get_string('updateinstance:nochange');
        } else {
            $message = tools::get_string('updateinstance:200');

            // Add the cohort id to the data.
            $data->{self::FIELD_COHORT} = $enrolmentinstance->{self::FIELD_COHORT};

            $previousgroupid = $enrolmentinstance->{self::FIELD_GROUP};

            // Haven't even updated the enrolment instance and we are celebrating.
            $cohortenrolment->update_instance($enrolmentinstance, $data);

            // Add the course to the response object.
            $extradata[] = (new responses\course($enrolmentinstance->courseid))->to_array();

            // Add the cohort detail to the response object.
            $extradata[] = (new responses\cohort($enrolmentinstance->{self::FIELD_COHORT}))->to_array();

            // Get the new group and add to the response object.
            if ($groupid == COHORT_CREATE_GROUP) {
                $extradata[] = (new responses\group($data->{self::FIELD_GROUP}, 'group', $enrolmentinstance->courseid))->to_array();
            } else if ($groupid != $previousgroupid) {
                $extradata[] = (new responses\group($groupid, 'group', $enrolmentinstance->courseid))->to_array();
            }

            // Add detail about the enrolment instance.
            $extradata[] = (new responses\enrol(
                $id,
                'enrol',
                $enrolmentinstance->name,
                $enrolmentinstance->status,
                $enrolmentinstance->roleid,
                $enrolmentinstance->courseid,
                $enrolmentinstance->{self::FIELD_COHORT},
                $enrolmentinstance->{self::FIELD_GROUP}
            ))->to_array();
        }

        // The HTTP status code.
        $code = 200;

        // Prepare the response.
        $response = [
            'id'        => $id,
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata,
        ];

        return $response;
    }
}
