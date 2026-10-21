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

namespace local_ws_enrolcohort\privacy;

use core_privacy\local\metadata\null_provider;

/**
 * Privacy provider tests for local_ws_enrolcohort.
 *
 * @package     local_ws_enrolcohort
 * @copyright   2026 onwards, Citricity
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_ws_enrolcohort\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The plugin declares that it stores no personal data, with a reason string that exists.
     */
    public function test_null_provider(): void {
        $this->assertInstanceOf(null_provider::class, new provider());

        $reason = provider::get_reason();
        $this->assertTrue(get_string_manager()->string_exists($reason, 'local_ws_enrolcohort'));
        $this->assertNotEmpty(get_string($reason, 'local_ws_enrolcohort'));
    }
}
