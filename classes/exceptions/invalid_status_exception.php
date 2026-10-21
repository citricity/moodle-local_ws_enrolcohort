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
 * Invalid status exception for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @author      Donald Barrett <donald.barrett@learningworks.co.nz>
 * @copyright   2018 onwards, LearningWorks ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_ws_enrolcohort\exceptions;

use local_ws_enrolcohort\tools;

/**
 * Thrown when an enrolment instance status is neither enabled nor disabled.
 *
 * @package     local_ws_enrolcohort
 */
class invalid_status_exception extends \moodle_exception {
    /**
     * Constructor.
     *
     * @param int|string $status The status that was supplied.
     */
    public function __construct($status = '') {
        $a = ['enabled' => ENROL_INSTANCE_ENABLED, 'disabled' => ENROL_INSTANCE_DISABLED, 'actual' => $status];
        parent::__construct('invalidstatus', tools::COMPONENT_NAME, '', null, tools::get_string('invalidstatus:message', $a));
    }
}
