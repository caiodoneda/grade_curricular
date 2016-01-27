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
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

/** This class contains the test cases for the functions in grade_curricular.php. */
class grade_curricular_test extends advanced_testcase {
    
	protected $grade_curricular;
	protected $grade_curricular_courses;
	protected $students;
	protected $category;
	protected $courses;
	protected $completions_info;

    public function setUp() {
    	global $DB, $CFG;

        //enabling course completion globally
        $CFG->enablecompletion = 1;

    	$this->category = $this->getDataGenerator()->create_category();
    	$this->completions_info = array();
                
        $this->grade_curricular = $this->create_fake_grade_curricular();
    }

    protected function create_courses($amount) {
        for ($i = 1; $i <= $amount; $i++) {
            $this->courses[$i] = $this->getDataGenerator()->create_course(array('category' => $this->category->id));
        }
    }

    protected function create_students($amount) {
        for ($i = 1; $i <= $amount; $i++) {
            $this->students[$i] = $this->getDataGenerator()->create_user();
        }
    }

    protected function enrol_students($students = array(), $courses = array()) {
        foreach ($courses as $key => $course) {
            foreach ($students as $key => $student) {
                $this->getDataGenerator()->enrol_user($student->id, $course->id, 5); //5 is the student roleid.
            }
        }
    }

    protected function create_courses_completions($courses) {
        foreach ($courses as $course) {
            //Creating and enabling new completion for each course.
            $this->completions_info[$course->id] = new completion_info($course);
            $course->enablecompletion = 1;
            $DB->update_record('course', $course);

            $data = new stdClass();
            $data->criteria_self = 1; //Just setting any value to not be empty. The class completion_criteria will vaerify this value.
            $data->id = $course->id;
            $criterion = new completion_criteria_self();
            $criterion->update_config($data);
            $this->completions_info[$course->id]->criterion = $criterion;//maybe we don't need this.
        }
    }

    protected function complete_courses($courses = array(), $students = array()) {     
        foreach ($courses as $course) {
            foreach ($students as $student) {
                $student_completion = $this->completions_info[$course->id]->get_completion($student->id, 1); //1 = COMPLETION_CRITERIA_TYPE_SELF
                $student_completion->mark_complete();
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

    protected function associate_courses_to_grade_curricular($courses, $optative_amount, $mandatory_amount) {
        global $DB;

        $record = new stdClass();
        $record->gradecurricularid = $this->grade_curricular->id;
        $record->inscribestartdate = time();
        $record->inscribeenddate = time();
        $record->coursedependencyid = 0;
        $record->timemodified = time();
        
        for ($i = 1; $i <= $optative_amount; $i++) {
            $record->courseid = $courses[$i]->id;
            $record->type = 2;
            $record->workload = 1;
            $DB->insert_record('grade_curricular_courses', $record);
        }

        for ($i = 1; $i <= $mandatory_amount; $i++) {
            $record->courseid = $courses[$i]->id;
            $record->type = 1;
            $record->workload = 1;
            $DB->insert_record('grade_curricular_courses', $record);
        }

        $this->grade_curricular_courses = $DB->get_records('grade_curricular_courses', array('gradecurricularid'=>$this->grade_curricular->id));
    }

    public function test_get_courses() {
        $this->resetAfterTest(true);
        $this->create_courses(10);
        $this->associate_courses_to_grade_curricular($this->courses, 5, 5);
        $this->assertEquals(10, count($this->grade_curricular_courses));
    }

    // public function test_is_grade_curricular_created() {
    //     global $DB;

    //     $this->resetAfterTest(true);
    //     $this->assertTrue($DB->record_exists('grade_curricular', array('id'=>$this->grade_curricular->id)));    
    // }

    // public function test_courses_are_associated() {
    //     global $DB;

    //     $this->resetAfterTest(true);
    //     foreach ($this->courses as $key => $course) {
    //         $this->assertTrue($DB->record_exists('grade_curricular_courses', 
    //                           array('gradecurricularid'=>$this->grade_curricular->id, 
    //                                 'courseid'=>$course->id)));     
    //     }
    // }

    // public function test_check_for_completions() {
    //     $this->resetAfterTest(true);

    //     foreach ($this->courses as $key => $course) {
    //         $completion = new completion_info($course);
    //         foreach ($this->students as $key => $student) {
    //             $student_completion = $completion->get_completion($student->id, 1); //1 = COMPLETION_CRITERIA_TYPE_SELF
    //             $this->assertTrue($student_completion->is_complete());      
    //         }
    //     }
    // }


}