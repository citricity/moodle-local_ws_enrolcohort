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
 * Enrolment instance response object for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ws_enrolcohort\responses;

/**
 * Response details for a cohort enrolment instance.
 *
 * @package     local_ws_enrolcohort
 */
class enrol extends response {
    /**
     * @var string The name provided to the enrolment instance.
     */
    protected $name;

    /**
     * @var int The status of the enrolment instance.
     */
    protected $status;

    /**
     * @var int The id of the role associated to this enrolment instance.
     */
    protected $roleid;

    /**
     * @var int The id of the course this enrolment instance is for.
     */
    protected $courseid;

    /**
     * @var int The id of the cohort.
     */
    protected $cohortid;

    /**
     * @var int The id of the group.
     */
    protected $groupid;

    /**
     * @var array|null More detail about the course associated to this enrolment instance.
     */
    protected $course;

    /**
     * @var array|null More detail about the cohort associated to this enrolment instance.
     */
    protected $cohort;

    /**
     * @var array|null More detail about the role associated to this enrolment instance.
     */
    protected $role;

    /**
     * @var array|null More detail about the group associated to this enrolment instance.
     */
    protected $group;

    /**
     * enrol constructor.
     *
     * @param int $id The id of the enrolment instance.
     * @param string $object The name of this object. Don't specify when constructing.
     * @param string $name The name of the enrolment instance.
     * @param int $status The status of the enrolment instance.
     * @param int $roleid The id of the role assigned by the enrolment instance.
     * @param int $courseid The id of the course.
     * @param int $cohortid The id of the cohort.
     * @param int $groupid The id of the group, or 0 for none.
     */
    public function __construct(
        $id = 0,
        $object = 'enrol',
        $name = '',
        $status = 0,
        $roleid = 0,
        $courseid = 0,
        $cohortid = 0,
        $groupid = 0
    ) {
        parent::__construct($id, $object);

        // Set the values for the fields.
        $this->name     = $name;
        $this->status   = $status;
        $this->roleid   = $roleid;
        $this->courseid = $courseid;
        $this->cohortid = $cohortid;
        $this->groupid  = $groupid;
    }

    /**
     * Set the details about the course for this enrolment method.
     *
     * @param array $course The course details, as returned by course::to_array().
     */
    public function set_course($course) {
        $this->course = $course;
    }

    /**
     * Set the details about the cohort for this enrolment method.
     *
     * @param array $cohort The cohort details, as returned by cohort::to_array().
     */
    public function set_cohort($cohort) {
        $this->cohort = $cohort;
    }

    /**
     * Set the details about the role for this enrolment method.
     *
     * @param array $role The role details, as returned by role::to_array().
     */
    public function set_role($role) {
        $this->role = $role;
    }

    /**
     * Set the details about the group for this enrolment method.
     *
     * @param array $group The group details, as returned by group::to_array().
     */
    public function set_group($group) {
        $this->group = $group;
    }
}
