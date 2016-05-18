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
 * Steps definitions related to mod_quiz.
 *
 * @package   local_grade_curricular
 * @category  test
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../../question/tests/behat/behat_question_base.php');

use Behat\Behat\Context\Step\Given as Given,
    Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions related to local_grade_curricular.
 *
 */
class behat_local_grade_curricular extends behat_question_base {
    /**
     * @Given /^category "([^"]*)" is associeated with the following external activity:$/
     */
    public function categoryIsAssocieatedWithTheFollowingExternalActivity($catname, TableNode $table) {
        global $DB;

        $sql = "SELECT ctx.id
                  FROM {context} AS ctx
                  JOIN {course_categories} cc
                    ON (ctx.instanceid = cc.id)
                 WHERE cc.idnumber = :catname";

        $ctxid = $DB->get_field_sql($sql, array('catname' => $catname));

        $row1 = $table->getRow(0);
        $row2 = $table->getRow(1);

        $record = new stdClass();
        $record->contextid = $ctxid;

        foreach ($row1 as $key => $value) {
            $record->$value = $row2[$key];
        }

        $record->timecreated = time();

        $DB->insert_record('inscricoes_activities', $record);
    }
}
