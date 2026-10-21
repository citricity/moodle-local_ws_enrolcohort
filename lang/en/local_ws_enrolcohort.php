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
 * Strings for language 'en' for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No direct access.
defined('MOODLE_INTERNAL') || die();

$string['addinstance:201'] = 'Cohort enrolment instance added.';
$string['addinstance:usingdefaultname'] = 'Using system generated name.';
$string['cohortenrolmethodnotavailable'] = 'The cohort enrolment method is not available.';
$string['deleteinstance:200'] = 'Cohort enrolment instance deleted.';
$string['enrolcohortalreadysyncedwithrole'] = 'A cohort enrol instance for this role already exists';
$string['enrolcohortalreadysyncedwithrole:message'] = 'A cohort enrol instance with id {$a->enrolid} is already synchronised with the role {$a->roleid}';
$string['getinstance:200'] = 'Found {$a->numberofenrolmentinstances} cohort enrolment instances for the course with id {$a->courseid}.';
$string['getinstances:200'] = 'Found {$a->numberofenrolmentinstances} cohort enrolment instances in {$a->numberofcourses} courses (All courses).';
$string['instancegroupnone'] = 'Enrol instance group none.';
$string['invalidcourse'] = 'Course is invalid!';
$string['invalidcourse:issite'] = 'Can not add instance to the site course.';
$string['invalidstatus'] = 'Invalid enrolment instance status!';
$string['invalidstatus:message'] = 'Possible values for enrolment instance status are: {$a->enabled} - Enabled, {$a->disabled} - Disabled. Got {$a->actual}.';
$string['objectnotfound'] = 'Object does not exist!';
$string['objectnotfound:message'] = 'Could not find {$a->object} with {$a->key} {$a->value}';
$string['pluginname'] = 'Extended enrol cohort webservices';
$string['privacy:metadata'] = 'The local webservices cohort enrolment plugin does not store any personal data.';
$string['unavailableatcontext'] = 'Object is not available at this context!';
$string['unavailableatcontext:message'] = '{$a->object} with id {$a->id} is not {$a->word} at this context.';
$string['updateinstance:200'] = 'Cohort enrolment instance updated.';
$string['updateinstance:nochange'] = 'No changes were made to the cohort enrolment instance.';
