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
 * stuff for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Webservice functions to add, update, delete and list cohort sync enrolment instances.
 *
 * @package     local_ws_enrolcohort
 */
class local_ws_enrolcohort_external extends external_api {
    use \local_ws_enrolcohort\external\add_instance;
    use \local_ws_enrolcohort\external\update_instance;
    use \local_ws_enrolcohort\external\delete_instance;
    use \local_ws_enrolcohort\external\get_instances;

    /**
     * Query string key for enrolment instance parameters, i.e. https://example.url?instance[key]=value.
     */
    const QUERYSTRING_INSTANCE  = 'instance';

    /**
     * Query string key for course parameters, i.e. https://example.url?course[key]=value.
     */
    const QUERYSTRING_COURSE    = 'course';

    /**
     * Group mode: do not add users to a group. Values are as per the add instance mform.
     */
    const COHORT_GROUP_CREATE_NONE = 0;

    /**
     * Group mode: create a new group for the cohort. Values are as per the add instance mform.
     */
    const COHORT_GROUP_CREATE_NEW = -1;

    /**
     * The value that says to the webservice function get_instances() to get all cohort enrolment instances.
     */
    const GET_INSTANCES_COURSEID_ALL = -1;

    /**
     * The enrol table field that holds the group id.
     */
    const FIELD_GROUP   = 'customint2';

    /**
     * The enrol table field that holds the cohort id.
     */
    const FIELD_COHORT  = 'customint1';

    /**
     * A constant that defines a webservice function call that has errors.
     */
    const WEBSERVICE_FUNCTION_CALL_HAS_ERRORS_ID = -1;

    /**
     * All the webservices functions defined in this external lib return the same stuff.
     * This is some dry code.
     *
     * @return external_single_structure
     */
    public static function webservice_function_returns() {
        return new external_single_structure([
            'id'        => new external_value(PARAM_INT, 'The id of the enrolment instance'),
            'code'      => new external_value(PARAM_INT, 'HTTP status code'),
            'message'   => new external_value(PARAM_TEXT, 'Human readable response message'),
            'data' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'object'    => new external_value(PARAM_TEXT, 'The object this is describing'),
                        'id'        => new external_value(PARAM_INT, 'The id of the object', VALUE_OPTIONAL),
                        'name'      => new external_value(PARAM_TEXT, 'The name of the object', VALUE_OPTIONAL),
                        'courseid'  => new external_value(PARAM_INT, 'The id of the related course', VALUE_OPTIONAL),
                        'cohortid'  => new external_value(PARAM_INT, 'The id of the cohort', VALUE_OPTIONAL),
                        'roleid'    => new external_value(PARAM_INT, 'The id of the related role', VALUE_OPTIONAL),
                        'groupid'   => new external_value(PARAM_INT, 'The id of the group', VALUE_OPTIONAL),
                        'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the object', VALUE_OPTIONAL),
                        'shortname' => new external_value(PARAM_TEXT, 'The shortname of the object', VALUE_OPTIONAL),
                        'status'    => new external_value(PARAM_INT, 'The status of the object', VALUE_OPTIONAL),
                        'active'    => new external_value(PARAM_TEXT, 'Enrolment instance is active or not', VALUE_OPTIONAL),
                        'visible'   => new external_value(PARAM_INT, 'The visibility of the object', VALUE_OPTIONAL),
                        'format'    => new external_value(PARAM_PLUGIN, 'The course format', VALUE_OPTIONAL),
                    ],
                    'extra details',
                    VALUE_OPTIONAL
                )
            ),
        ]);
    }

    /**
     * The functions get_instances and delete_instance will return the following structure that shows details of
     * the cohort enrolment instance including the related course, cohort, role assigned, and group.
     *
     * @return external_single_structure
     */
    public static function webservice_function_enrolment_instance_returns() {
        // Definition of extra details for course in get enrolment instance/s.
        $coursedetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the course'),
                'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the course'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the course'),
                'shortname' => new external_value(PARAM_TEXT, 'The shortname of the course'),
                'visible'   => new external_value(PARAM_INT, 'The visibility of the course'),
                'format'    => new external_value(PARAM_PLUGIN, 'The course format'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for cohort in get enrolment instance/s.
        $cohortdetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the cohort'),
                'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the cohort'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the cohort'),
                'visible'   => new external_value(PARAM_INT, 'The visibility of the cohort'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for role in get enrolment instance/s.
        $roledetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the role'),
                'shortname' => new external_value(PARAM_TEXT, 'The shortname of the role'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        // Definition of extra details for role in get enrolment instance/s.
        $groupdetails = new external_single_structure(
            [
                'object'    => new external_value(PARAM_TEXT, 'The type of object'),
                'id'        => new external_value(PARAM_INT, 'The id of the group'),
                'courseid'  => new external_value(PARAM_INT, 'The id of the course this group belongs to'),
                'name'      => new external_value(PARAM_TEXT, 'The name of the group'),
            ],
            'More detail about the course associated to a cohort enrolment instance',
            VALUE_OPTIONAL
        );

        return new external_single_structure([
            'id'        => new external_value(PARAM_INT, 'The id of the enrolment instance'),
            'code'      => new external_value(PARAM_INT, 'HTTP status code'),
            'message'   => new external_value(PARAM_TEXT, 'Human readable response message'),
            'data' => new external_multiple_structure(
                new external_single_structure(
                    [
                        'object'    => new external_value(PARAM_TEXT, 'The object this is describing'),
                        'id'        => new external_value(PARAM_INT, 'The id of the object', VALUE_OPTIONAL),
                        'name'      => new external_value(PARAM_TEXT, 'The name of the object', VALUE_OPTIONAL),
                        'courseid'  => new external_value(PARAM_INT, 'The id of the related course', VALUE_OPTIONAL),
                        'cohortid'  => new external_value(PARAM_INT, 'The id of the cohort', VALUE_OPTIONAL),
                        'roleid'    => new external_value(PARAM_INT, 'The id of the related role', VALUE_OPTIONAL),
                        'groupid'   => new external_value(PARAM_INT, 'The id of the group', VALUE_OPTIONAL),
                        'idnumber'  => new external_value(PARAM_RAW, 'The idnumber of the object', VALUE_OPTIONAL),
                        'shortname' => new external_value(PARAM_TEXT, 'The shortname of the object', VALUE_OPTIONAL),
                        'status'    => new external_value(PARAM_INT, 'The status of the object', VALUE_OPTIONAL),
                        'active'    => new external_value(PARAM_TEXT, 'Enrolment instance is active or not', VALUE_OPTIONAL),
                        'visible'   => new external_value(PARAM_INT, 'The visibility of the object', VALUE_OPTIONAL),
                        'format'    => new external_value(PARAM_PLUGIN, 'The course format', VALUE_OPTIONAL),
                        'course'    => $coursedetails,
                        'cohort'    => $cohortdetails,
                        'role'      => $roledetails,
                        'group'     => $groupdetails,
                    ],
                    'extra details',
                    VALUE_OPTIONAL
                )
            ),
        ]);
    }

    /**
     * This function gets assignable roles for a course context.
     *
     * @param \context|null $context The context to get roles for. Anything but a course context returns no roles.
     * @return array
     */
    public static function get_assignable_roles($context = null) {
        return $context instanceof \context_course ? get_assignable_roles($context) : [];
    }
}
