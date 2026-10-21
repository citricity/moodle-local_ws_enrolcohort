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
 * The delete_instance webservice function for local_ws_enrolcohort.
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
use local_ws_enrolcohort\exceptions\method_not_available_exception;
use local_ws_enrolcohort\exceptions\instance_not_found_exception;

/**
 * Deletes a cohort sync enrolment instance.
 *
 * Used by local_ws_enrolcohort_external, which provides the constants and shared helpers referenced through self::.
 *
 * @package     local_ws_enrolcohort
 */
trait delete_instance {
    /**
     * Returns description of the delete_instance() function parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_instance_parameters() {
        return new external_function_parameters([
            self::QUERYSTRING_INSTANCE => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The id of the enrolment instance to delete.', VALUE_REQUIRED),
            ]),
        ]);
    }

    /**
     * Returns description of the delete_instance() function return value.
     *
     * @return external_single_structure
     */
    public static function delete_instance_returns() {
        return self::webservice_function_enrolment_instance_returns();
    }

    /**
     * Deletes a cohort enrolment instance.
     *
     * @param array $params The function parameters, as described by delete_instance_parameters().
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function delete_instance($params) {
        global $DB;

        // Check the parameters.
        $params = self::validate_parameters(self::delete_instance_parameters(), [self::QUERYSTRING_INSTANCE => $params]);

        $extradata = [];

        // Get the enrolment instance id.
        $id = $params[self::QUERYSTRING_INSTANCE]['id'];

        // Validate the enrolment instance id.
        if (!$DB->record_exists('enrol', ['id' => $id, 'enrol' => 'cohort'])) {
            throw new instance_not_found_exception($id);
        }

        // This is the important one. Check if the cohort enrolment instance is available for use.
        if (!$cohortenrolment = enrol_get_plugin('cohort')) {
            throw new method_not_available_exception();
        }

        // Set the HTTP response code.
        $code = 200;

        // Get the enrolment instance Nelly ;).
        $ei = $DB->get_record('enrol', ['id' => $id, 'enrol' => 'cohort']);

        // Make a E.I. response.
        $eiresponse = new responses\enrol(
            $id,
            'enrol',
            $ei->name,
            $ei->status,
            $ei->roleid,
            $ei->courseid,
            $ei->{self::FIELD_COHORT},
            $ei->{self::FIELD_GROUP}
        );

        // Get other details about the deleted enrolment instance.
        $eiresponse->set_course((new responses\course($ei->courseid))->to_array());
        $eiresponse->set_cohort((new responses\cohort($ei->{self::FIELD_COHORT}))->to_array());
        $eiresponse->set_role((new responses\role($ei->roleid))->to_array());
        $eiresponse->set_group((new responses\group($ei->{self::FIELD_GROUP}, 'group', $ei->courseid))->to_array());

        // Add the E.I. to the response.
        $extradata[] = $eiresponse->to_array();

        // Actuals delete the enrolment instance.
        $cohortenrolment->delete_instance($ei);

        // The following is a message of success.
        $message = tools::get_string('deleteinstance:200');

        // Prepare the response and then send it.
        $response = [
            'id'        => $id,
            'code'      => $code,
            'message'   => $message,
            'data'      => $extradata,
        ];

        return $response;
    }
}
