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
 * The get_instances webservice function for local_ws_enrolcohort.
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
use local_ws_enrolcohort\exceptions\course_is_site_exception;

/**
 * Lists the cohort sync enrolment instances for a course, or for all courses.
 *
 * Used by local_ws_enrolcohort_external, which provides the constants and shared helpers referenced through self::.
 *
 * @package     local_ws_enrolcohort
 */
trait get_instances {
    /**
     * Returns description of the get_instances() function return value.
     *
     * @return external_function_parameters
     */
    public static function get_instances_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_COURSE => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The id of a course to get enrolment instances for.', VALUE_REQUIRED),
            ]),
        ]);
    }

    /**
     * Returns description of the get_instances() function return value.
     *
     * @return external_single_structure
     */
    public static function get_instances_returns() {
        return self::webservice_function_enrolment_instance_returns();
    }

    /**
     * Gets the enrolment instances for a course or the entire site.
     *
     * @param array $params The function parameters, as described by get_instances_parameters().
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function get_instances($params) {
        global $DB, $SITE;

        // Check the parameters.
        $params = self::validate_parameters(self::get_instances_parameters(), [self::QUERYSTRING_COURSE => $params]);

        $extradata = [];

        // Get the courseid aka id.
        $courseid = $params[self::QUERYSTRING_COURSE]['id'];

        // Validate the courseid.
        if ($courseid == $SITE->id) {
            throw new course_is_site_exception();
        } else if (!$DB->record_exists('course', ['id' => $courseid]) && $courseid != -1) {
            throw new course_not_found_exception($courseid);
        }

        // Get the courses.
        if ($courseid == self::GET_INSTANCES_COURSEID_ALL) {
            $courses = $DB->get_records('course');
        } else {
            $courses = $DB->get_records('course', ['id' => $courseid]);
        }

        $foundsitecourse = false;
        $instancecount = 0;

        foreach ($courses as $course) {
            // Skip the site course.
            if ($course->id == $SITE->id) {
                $foundsitecourse = true;
                continue;
            }

            // Skip courses that don't have cohort enrolment methods.
            if (!$DB->record_exists('enrol', ['enrol' => 'cohort', 'courseid' => $course->id])) {
                continue;
            }

            // Get the cohort enrolment instances for this course.
            $enrolmentinstances = $DB->get_records('enrol', ['enrol' => 'cohort', 'courseid' => $course->id]);

            foreach ($enrolmentinstances as $enrolmentinstance) {
                // Make some info about the enrolment instance.
                $ei = new responses\enrol(
                    $enrolmentinstance->id,
                    'enrol',
                    $enrolmentinstance->name,
                    $enrolmentinstance->status,
                    $enrolmentinstance->roleid,
                    $enrolmentinstance->courseid,
                    $enrolmentinstance->{self::FIELD_COHORT},
                    $enrolmentinstance->{self::FIELD_GROUP}
                );

                // Get other details about the enrolment instance.
                $ei->set_course((new responses\course($enrolmentinstance->courseid))->to_array());
                $ei->set_cohort((new responses\cohort($enrolmentinstance->{self::FIELD_COHORT}))->to_array());
                $ei->set_role((new responses\role($enrolmentinstance->roleid))->to_array());

                // Made an object for this so it doesn't exceed 132 characters.
                $groupresponse = new responses\group(
                    $enrolmentinstance->{self::FIELD_GROUP},
                    'group',
                    $enrolmentinstance->courseid
                );

                $ei->set_group($groupresponse->to_array());

                $extradata[] = $ei->to_array();

                $instancecount += 1;
            }
        }

        // Count things and make langstring placeholders.
        $numberofcourses = count($courses);
        if ($foundsitecourse) {
            $numberofcourses -= 1;
        }

        $a = [
            'courseid'                      => $courseid,
            'numberofenrolmentinstances'    => $instancecount,
            'numberofcourses'               => $numberofcourses,
        ];

        if ($courseid == self::GET_INSTANCES_COURSEID_ALL) {
            $message = tools::get_string('getinstances:200', $a);
        } else {
            $message = tools::get_string('getinstance:200', $a);
        }

        // Prepare the response.
        $response = [
            'id'        => $courseid,
            'code'      => 200,
            'message'   => $message,
            'data'      => $extradata,
        ];

        return $response;
    }
}
