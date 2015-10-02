<?php
/**
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package local_grade_curricular
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

require_once($CFG->dirroot . '/local/grade_curricular/classes/grade_curricular.php'); // Include the code to test
require_once($CFG->dirroot . '/completion/completion_completion.php');

/** This class contains the test cases for the functions in grade_curricular.php. */
class grade_curricular_test extends advanced_testcase {
    
	protected $grade_curricular;
	protected $grade_curricular_courses;
	protected $students;
	protected $category;
	protected $courses;
	protected $completions_info;

    public function setUp() {
    	global $DB;

    	$this->category = $this->getDataGenerator()->create_category();
    	$this->completions_info = array();

    	for ($i = 1; $i <= 10; $i++) {
    		$this->students[$i] = $this->getDataGenerator()->create_user();
    		$this->courses[$i] = $this->getDataGenerator()->create_course(array('category' => $this->category->id));
			$this->completions_info[$this->courses[$i]->id] =  new completion_info($this->courses[$i]);

    	}

    	//enrolling all the students in all the courses.
    	foreach ($this->students as $key => $student) {
    		foreach ($this->courses as $key => $course) {
    			$this->getDataGenerator()->enrol_user($student->id, $course->id, 5);
    		}
    	}
    	
    	$this->grade_curricular = $this->create_fake_grade_curricular();
    	$this->grade_curricular_courses = $this->associate_courses_to_grade_curricular();

    	foreach ($this->students as $key => $student) {
    		foreach ($this->courses as $key => $course) {
    			$this->setUser($student);
    			$this->completions_info[$course->id]->userid = $student->id;
    			$this->completions_info[$course->id]->mark_complete(time());
    		}
    	}
    }

    protected function create_fake_grade_curricular() {
    	global $DB;

    	$context = context_coursecat::instance($this->category->id);

    	$record = new stdClass();
    	$record->contextid = $context->id;
    	$record->minoptionalcourses = 2;
    	$record->maxoptionalcourses = 5; 
    	$record->optionalatonetime = 0; 
    	$record->inscricoesactivityid = 5; 
    	$record->tutorroleid = 1;
    	$record->studentcohortid = 0; 
    	$record->notecourseid = 0; 
    	$record->timemodified = time();

    	$gradeid = $DB->insert_record('grade_curricular', $record);

    	return $DB->get_record('grade_curricular', array('id'=>$gradeid));
    }

    protected function associate_courses_to_grade_curricular() {
    	global $DB;

	    $record = new stdClass();
	    $record->gradecurricularid = $this->grade_curricular->id;
	    $record->inscribestartdate = time();
	    $record->inscribeenddate = time();
	    $record->coursedependencyid = 0;
	    $record->timemodified = time();

	    //The conditions are used to generate mandatory and optative courses, also we set differente workloads. 
	    // ps: courses with workload = 0 are not sent to certificate system.
	    foreach ($this->courses as $key => $course) {
		    $record->courseid = $course->id;
		    if ($key < 5) {
		    	$record->type = 1;
		    } else {
		    	$record->type = 2;
		    }

		    if ($key % 2 == 0) {
		    	$record->workload = 1;
		    } else {
		    	$record->workload = 0;
		    }

		    $DB->insert_record('grade_curricular_courses', $record);
    	}

    	return $DB->get_records('grade_curricular_courses', array('gradecurricularid'=>$this->grade_curricular->id));
    }

    public function test_is_grade_curricular_created() {
    	global $DB;

    	$this->resetAfterTest(true);
    	$this->assertTrue($DB->record_exists('grade_curricular', array('id'=>$this->grade_curricular->id)));  	
    }

    public function test_courses_are_associated() {
    	global $DB;

    	$this->resetAfterTest(true);
    	foreach ($this->courses as $key => $course) {
    		$this->assertTrue($DB->record_exists('grade_curricular_courses', 
    			              array('gradecurricularid'=>$this->grade_curricular->id, 
    			              	    'courseid'=>$course->id)));  	
    	}
    }

    public function test_check_for_completions() {
    	$this->resetAfterTest(true);

    	foreach ($this->students as $key => $student) {
    		foreach ($this->courses as $key => $course) {
    			$this->setUser($student);
    			$this->assertTrue($this->completions_info[$course->id]->is_complete());
    		}
    	}
    }

}