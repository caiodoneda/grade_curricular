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
require_once($CFG->libdir . '/cronlib.php');

/** This class contains the test cases for the functions in grade_curricular.php. */
class grade_curricular_test extends advanced_testcase {

    protected $grade_curricular;
    protected $grade_curricular_courses;
    protected $students;
    protected $category;
    protected $courses;
    protected $completions_info;

    public function setUp() {
        $this->category = $this->getDataGenerator()->create_category();
        $this->completions_info = array();

        $this->grade_curricular = $this->create_fake_grade_curricular();
    }

    protected function create_courses($amount) {
        for ($i = 1; $i <= $amount; $i++) {
            $this->courses[$i] = $this->getDataGenerator()->create_course(array('category' => $this->category->id, 'enablecompletion' => 1));
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
        global $CFG;

        //enabling course completion globally
        $CFG->enablecompletion = 1;

        foreach ($courses as $course) {
            //Creating and enabling new completion for each course.
            $this->completions_info[$course->id] = new completion_info($course);

            $criteriadata = new stdClass();
            $criteriadata->id = $course->id;
            $criteriadata->criteria_activity = array();

            // Self completion.
            $criteriadata->criteria_self = COMPLETION_CRITERIA_TYPE_SELF;
            $criterion = new completion_criteria_self();
            $criterion->update_config($criteriadata);

            // Handle overall aggregation.
            $aggdata = array(
                'course'        => $course->id,
                'criteriatype'  => null
            );

            $aggregation = new completion_aggregation($aggdata);
            $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
            $aggregation->save();
        }
    }

    protected function complete_course($course, $student) {
        $this->setUser($student);

        core_completion_external::mark_course_self_completed($course->id);
    }

    protected function create_fake_grade_curricular() {
        global $DB;

        $context = context_coursecat::instance($this->category->id);

        $record = new stdClass();
        $record->contextid = $context->id;
        $record->minoptionalcourses = 2;
        $record->maxoptionalcourses = 5;
        $record->optionalatonetime = 0;
        $record->inscricoesactivityid = 0;
        $record->tutorroleid = 1;
        $record->studentcohortid = 0;
        $record->notecourseid = 0;
        $record->timemodified = time();

        $gradeid = $DB->insert_record('grade_curricular', $record);

        return $DB->get_record('grade_curricular', array('id'=>$gradeid));
    }

    protected function update_grade_curricular() {
        global $DB;

        return $this->grade_curricular = $DB->get_record('grade_curricular', array('id'=>$this->grade_curricular->id));
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
            $record->courseid = array_pop($courses)->id;
            $record->type = 2;
            $record->workload = 1;
            $DB->insert_record('grade_curricular_courses', $record);
        }

        for ($i = 1; $i <= $mandatory_amount; $i++) {
            $record->courseid = array_pop($courses)->id;
            $record->type = 1;
            $record->workload = 1;
            $DB->insert_record('grade_curricular_courses', $record);
        }

        $this->grade_curricular_courses = $DB->get_records('grade_curricular_courses', array('gradecurricularid'=>$this->grade_curricular->id));
    }

    protected function update_grade_curricular_courses() {
        global $DB;

        return $this->grade_curricular_courses = $DB->get_records('grade_curricular_courses', array('gradecurricularid'=>$this->grade_curricular->id));
    }

    public function test_get_courses() {
        $this->resetAfterTest(true);
        $this->create_courses($amount = 10);
        $this->associate_courses_to_grade_curricular($this->courses, $optative = 5, $mandatory = 5);

        $courses = local_grade_curricular::get_courses($this->grade_curricular->id);

        $this->assertTrue(is_array($courses));

        $ids = array();
        foreach ($courses as $key => $course) {
            $ids[] = $course->id;
        }

        $testids = array();
        foreach ($this->grade_curricular_courses as $key => $course) {
            $testids[] = $course->courseid;
        }

        $this->assertEquals($ids, $testids);
    }

    public function test_get_completions_info() {
        $this->resetAfterTest(true);
        $this->create_courses($amount = 2);
        $this->create_courses_completions($this->courses);

        foreach ($this->completions_info as $key => $ci) {
            unset($ci->criterion);
        }

        $completions = local_grade_curricular::get_completions_info($this->courses);

        $this->assertTrue(is_array($completions));
        $this->assertContainsOnlyInstancesOf('completion_info', $completions);
        $this->assertEquals(array_values($this->completions_info), array_values($completions));
    }

    //A grande curricular de testes exige um mínimo de dois cursos optativos, e que todos os obrigatórios sejam completados.
    public function test_get_approved_students() {
        $this->resetAfterTest(true);
        $this->create_courses($amount = 6);
        $this->associate_courses_to_grade_curricular($this->courses, $optative = 3, $mandatory = 3);
        $this->create_students(10);
        $this->enrol_students($this->students, $this->courses);
        $this->create_courses_completions($this->courses);

        $courses = local_grade_curricular::get_courses($this->grade_curricular->id);

        //5 estudantes completaram todos os cursos.
        for ($i = 1; $i <= 5; $i++) {
            foreach ($courses as $course) {
                $this->complete_course($course, $this->students[$i]);
            }
        }

        //2 estudantes foram reprovados por não completarem 1 obrigatório.
        for ($i = 6; $i <= 7; $i++) {
            $count = 0;
            foreach ($courses as $key => $course) {
                if ($course->type == 1) {
                    if ($count >= 1) {
                        $this->complete_course($course, $this->students[$i]);
                    }

                    $count += 1;
                } else {
                    $this->complete_course($course, $this->students[$i]);
                }
            }
        }
        //3 estudantes foram reprovados por não atingirem o critério mínimo de optativos.
        for ($i = 8; $i <= 10; $i++) {
            $count = 0;
            foreach ($courses as $key => $course) {
                if ($course->type == 2) {
                    if ($count >= 2) {
                        $this->complete_course($course, $this->students[$i]);
                    }

                    $count += 1;
                } else {
                    $this->complete_course($course, $this->students[$i]);
                }
            }
        }

        cron_run();

        $approved_students = local_grade_curricular::get_approved_students($this->grade_curricular, $this->students);
        $this->assertEquals(5, count($approved_students));
    }

    public function test_save_cfg_grade() {
        $this->resetAfterTest(true);
        $this->setAdminUser();//TODO Create a specific user

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->grade_curricular->id;
        $_POST['inscricoesactivityid'] = 4;
        $_POST['studentcohortid'] = 0;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $grade_curricular = $this->update_grade_curricular();

        $this->assertEquals($grade_curricular->inscricoesactivityid, 4);
        $this->assertEquals($grade_curricular->studentcohortid, 0);
        $this->assertEquals($grade_curricular->tutorroleid, 2);
        $this->assertEquals($grade_curricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missing_inscricoesactivityid() {
        $this->resetAfterTest(true);
        $this->setAdminUser();//TODO Create a specific user

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->grade_curricular->id;
        $_POST['studentcohortid'] = 0;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $grade_curricular = $this->update_grade_curricular();

        $this->assertEquals($grade_curricular->inscricoesactivityid, 0);
        $this->assertEquals($grade_curricular->studentcohortid, 0);
        $this->assertEquals($grade_curricular->tutorroleid, 2);
        $this->assertEquals($grade_curricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missing_studentcohortid() {
        $this->resetAfterTest(true);
        $this->setAdminUser();//TODO Create a specific user

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->grade_curricular->id;
        $_POST['inscricoesactivityid'] = 4;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $grade_curricular = $this->update_grade_curricular();

        $this->assertEquals($grade_curricular->inscricoesactivityid, 4);
        $this->assertEquals($grade_curricular->studentcohortid, 0);
        $this->assertEquals($grade_curricular->tutorroleid, 2);
        $this->assertEquals($grade_curricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missint_inscricoesactivityid_and_studentcohortid() {
        $this->resetAfterTest(true);
        $this->setAdminUser();//TODO Create a specific user

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->grade_curricular->id;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $grade_curricular = $this->update_grade_curricular();

        $this->assertEquals($grade_curricular->inscricoesactivityid, 0);
        $this->assertEquals($grade_curricular->studentcohortid, 0);
        $this->assertEquals($grade_curricular->tutorroleid, 2);
        $this->assertEquals($grade_curricular->notecourseid, 15);
    }

    public function test_save_modules() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();//TODO Create a specific user
        $this->create_courses($amount = 6);
        $this->associate_courses_to_grade_curricular($this->courses, $optative = 3, $mandatory = 3);

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->grade_curricular->id;
        $_POST['minoptionalcourses'] = 7;
        $_POST['maxoptionalcourses'] = 15;
        $_POST['optionalatonetime'] = 5;

        $_POST['sesskey'] = sesskey();

        $_POST['type'] = array();
        $_POST['workload'] = array();
        $_POST['dependencies'] = array();
        $_POST['startdays'] = array();
        $_POST['startmonths'] = array();
        $_POST['startyears'] = array();
        $_POST['enddays'] = array();
        $_POST['endmonths'] = array();
        $_POST['endyears'] = array();

        foreach ($this->grade_curricular_courses as $course) {
            $_POST['type'][$course->courseid] = 1;
            $_POST['workload'][$course->courseid] = 100;
            $_POST['dependencies'][$course->courseid] = 0;
            $_POST['startdays'][$course->courseid] = 7;
            $_POST['startmonths'][$course->courseid] = 1;
            $_POST['startyears'][$course->courseid] = 2016;
            $_POST['enddays'][$course->courseid] = 25;
            $_POST['endmonths'][$course->courseid] = 12;
            $_POST['endyears'][$course->courseid] = 2016;
        }

        local_grade_curricular::save_modules($context->id, $this->category, (object) $_POST);

        $grade_curricular = $this->update_grade_curricular();

        $this->assertEquals($grade_curricular->inscricoesactivityid, 0);
        $this->assertEquals($grade_curricular->minoptionalcourses, 7);
        $this->assertEquals($grade_curricular->maxoptionalcourses, 15);
        $this->assertEquals($grade_curricular->optionalatonetime, 5);

        $grade_curricular_courses = $this->update_grade_curricular_courses();

        foreach ($grade_curricular_courses as $course) {
            $this->assertEquals($course->type, 1);
            $this->assertEquals($course->workload, 100);
            $this->assertEquals($course->inscribestartdate, make_timestamp(2016, 1, 7));
            $this->assertEquals($course->inscribeenddate, make_timestamp(2016, 12, 25));
        }

        $random_course = reset($grade_curricular_courses); //get the first element of this array
        $random_course_key = key($grade_curricular_courses);

        $DB->delete_records('course', array('id'=>$random_course->courseid)); //delete this course.

        local_grade_curricular::save_modules($context->id, $this->category, (object) $_POST); //Repeat the proccess to see if the course is erased from grade_curricular_courses table

        $grade_curricular_courses = $this->update_grade_curricular_courses();

        $this->assertArrayNotHasKey($random_course_key, $grade_curricular_courses);
    }
}