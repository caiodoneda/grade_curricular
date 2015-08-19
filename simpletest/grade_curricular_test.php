<?php
/**
 * Unit tests for (some of) mod/quiz/editlib.php.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package question
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

require_once($CFG->dirroot . '/local/grade_curricular/classes/grade_curricular.php'); // Include the code to test

/** This class contains the test cases for the functions in editlib.php. */
class grade_curricular_test extends advanced_testcase {
    /**
     *
     */
    function test_something() {
        $this->assertEquals(true, true);
    }
}
?>