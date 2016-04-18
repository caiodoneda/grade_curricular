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
 *
 * @package local_grade_curricular
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

require_once($CFG->dirroot . '/local/grade_curricular/classes/grade_curricular.php'); // Include the code to test.
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');
require_once($CFG->libdir . '/cronlib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot.'/completion/cron.php');

/** This class contains the test cases for the functions in grade_curricular.php. */
class grade_curricular_test extends advanced_testcase {

    protected $gradecurricular;
    protected $gradecurricularcourses;
    protected $students;
    protected $category;
    protected $courses;
    protected $completionsinfo;

    public function setUp() {
        global $CFG;

        // Enabling course completion globally.
        $CFG->enablecompletion = 1;

        $this->category = $this->getDataGenerator()->create_category();
        $this->completionsinfo = array();

        $this->gradecurricular = $this->create_fake_grade_curricular();
    }

    protected function create_courses($amount) {
        $courses = array();

        for ($i = 1; $i <= $amount; $i++) {
            $courses[$i] = $this->getDataGenerator()->create_course(
                           array('category' => $this->category->id, 'enablecompletion' => 1));
        }

        return $this->courses = $courses;
    }

    protected function create_students($amount) {
        $students = array();

        for ($i = 1; $i <= $amount; $i++) {
            $students[$i] = $this->getDataGenerator()->create_user();
        }

        return $this->students = $students;
    }

    protected function enrol_students($students = array(), $courses = array()) {
        foreach ($courses as $key => $course) {
            foreach ($students as $key => $student) {
                $this->getDataGenerator()->enrol_user($student->id, $course->id, 5); // 5 is the student roleid.
            }
        }
    }

    protected function create_courses_completions($courses) {
        global $CFG;

        foreach ($courses as $course) {
            // Creating and enabling new completion for each course.
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

    protected function delete_courses_completions() {
        foreach ($this->completionsinfo as $completion) {
            $completion->delete_course_completion_data();
        }
    }

    protected function complete_one_course($course, $student) {
        $this->setUser($student);

        $completion = $this->completions_info[$course->courseid]->get_completions($student->id);
        $completion[0]->mark_complete(time()); // Como só temos um critério, só existe a posição zero.

        // Método alternativo para completude... core_completion_external::mark_course_self_completed($course->courseid).
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

        try {
            $gradeid = $DB->insert_record('grade_curricular', $record);
        } catch (Exception $e) {
            var_dump($e);
        }

        return $DB->get_record('grade_curricular', array('id' => $gradeid));
    }

    protected function update_grade_curricular() {
        global $DB;

        return $this->gradecurricular = $DB->get_record('grade_curricular', array('id' => $this->gradecurricular->id));
    }

    protected function associate_courses_to_grade_curricular($courses, $optativeamount, $mandatoryamount) {
        global $DB;

        try {
            $DB->delete_records('grade_curricular_courses'); // Cleaning the table.
        } catch (Exception $e) {
            var_dump($e);
        }

        $record = new stdClass();
        $record->gradecurricularid = $this->gradecurricular->id;
        $record->inscribestartdate = time();
        $record->inscribeenddate = time();
        $record->coursedependencyid = 0;
        $record->timemodified = time();

        for ($i = 1; $i <= $optativeamount; $i++) {
            $record->courseid = array_pop($courses)->id;
            $record->type = 2;
            $record->workload = 1;

            try {
                $DB->insert_record('grade_curricular_courses', $record);
            } catch (Exception $e) {
                var_dump($e);
            }
        }

        for ($i = 1; $i <= $mandatoryamount; $i++) {
            $record->courseid = array_pop($courses)->id;
            $record->type = 1;
            $record->workload = 1;
            try {
                $DB->insert_record('grade_curricular_courses', $record);
            } catch (Exception $e) {
                var_dump($e);
            }
        }

        $this->gradecurricularcourses = $DB->get_records('grade_curricular_courses',
                                                          array('gradecurricularid' => $this->gradecurricular->id));
    }

    protected function update_grade_curricular_courses() {
        global $DB;

        return $this->gradecurricularcourses = $DB->get_records('grade_curricular_courses',
                                                                array('gradecurricularid' => $this->gradecurricular->id));
    }

    protected function cron_run() {
        try {
            purge_all_caches();
        } catch (Exception $e) {
            var_dump($e);
        }

        try {
            completion_cron_mark_started();
            completion_cron_criteria();
            completion_cron_completions();
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    protected function prepare_for_next_cron() {
        global $DB;

        $sql = "UPDATE {task_scheduled}
                   SET lastruntime = ?, nextruntime = ?
                 WHERE classname LIKE 'completion_regular_task' OR classname LIKE 'completion_daily_task' ";
        try {
            $DB->execute($sql, array(null, -1));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    public function test_get_courses() {
        $this->resetAfterTest(true);

        $this->create_courses($amount = 10);
        $this->associate_courses_to_grade_curricular($this->courses, $optative = 5, $mandatory = 5);

        $courses = local_grade_curricular::get_courses($this->gradecurricular->id);

        $this->assertTrue(is_array($courses));

        $ids = array();
        foreach ($courses as $key => $course) {
            $ids[] = $course->id;
        }

        $testids = array();
        foreach ($this->gradecurricularcourses as $key => $course) {
            $testids[] = $course->courseid;
        }

        $this->assertEquals($ids, $testids);
    }

    public function test_get_students_by_cohort() {
        global $DB;

        $this->resetAfterTest(true);

        $this->create_courses(10);
        $this->create_students(20);
        $this->associate_courses_to_grade_curricular($this->courses, 5, 5);

        $cohort = $this->getDataGenerator()->create_cohort();

        foreach ($this->students as $student) {
            cohort_add_member($cohort->id, $student->id);
        }

        try {
            $this->gradecurricular->inscricoesactivityid = 0;
            $this->gradecurricular->studentcohortid = $cohort->id;
            $DB->update_record('grade_curricular', $this->gradecurricular);
        } catch (Exception $e) {
            var_dump($e);
        }

        $students = local_grade_curricular::get_students($this->gradecurricular->id, '', 'id');

        $this->assertTrue(is_array($students));

        $ids = array();
        foreach ($students as $key => $student) {
            $ids[] = $student->id;
        }

        $testids = array();
        foreach ($this->students as $key => $student) {
            $testids[] = $student->id;
        }

        $this->assertEquals($ids, $testids);
    }

    public function test_get_completions_info() {
        $this->resetAfterTest(true);

        $this->create_courses($amount = 2);
        $this->create_courses_completions($this->courses);

        foreach ($this->completionsinfo as $key => $ci) {
            unset($ci->criterion);
        }

        $completions = local_grade_curricular::get_completions_info($this->courses);

        $this->assertTrue(is_array($completions));
        $this->assertContainsOnlyInstancesOf('completion_info', $completions);
        $this->assertEquals(array_values($this->completions_info), array_values($completions));
    }

    public function test_get_approved_students_var() {
        global $DB;

        $this->resetAfterTest(true);

        $optcoursesamount = [3, 2, 0];
        $mancoursesamount = [3, 2, 0];

        foreach ($mancoursesamount as $manamount) {
            foreach ($optcoursesamount as $optamount) {
                $this->create_courses($optamount + $manamount);
                $this->create_students(5);
                $this->create_courses_completions($this->courses);
                $this->enrol_students($this->students, $this->courses);
                $this->associate_courses_to_grade_curricular($this->courses, $optamount, $manamount);

                $minoptativevariation = array_unique([$optamount, max(($optamount - 1), 0), 0]);
                foreach ($minoptativevariation as $minoptvar) {
                    $this->set_grade_curricular_minoptionalcourses($minoptvar);
                    $this->update_grade_curricular();
                    $this->check_completions($minoptvar);
                }
            }
        }
    }

    protected function set_grade_curricular_minoptionalcourses($minoptative) {
        global $DB;

        try {
            $DB->set_field('grade_curricular', 'minoptionalcourses', $minoptative, array('id' => $this->gradecurricular->id));
        } catch (Exception $e) {
            var_dump($e);
        }
    }

    protected function check_completions($minoptative) {
        $mandatorycourses = array();
        $optativecourses = array();

        foreach ($this->gradecurricularcourses as $course) {
            if ($course->type == 1) {
                $mandatorycourses[] = $course;
            } else {
                $optativecourses[] = $course;
            }
        }

        $mandatorycoursestocomplete = array_unique([count($mandatorycourses), max((count($mandatorycourses) - 1), 0), 0]);
        $optativecoursestocomplete = array_unique([$minoptative, max(($minoptative - 1), 0), 0]);

        foreach ($mandatorycoursestocomplete as $mc) {
            foreach ($optativecoursestocomplete as $oc) {
                $this->complete_courses($mandatorycourses, $optativecourses, $mc, $oc);

                sleep(1);
                $this->prepare_for_next_cron();
                $this->cron_run();

                $approvedstudents = local_grade_curricular::get_approved_students($this->gradecurricular, $this->students);

                if (($mc == count($mandatorycourses)) && ($oc >= $minoptative)) {
                    $this->assertEquals(5, count($approvedstudents));
                } else {
                    $this->assertEmpty($approvedstudents);
                }

                $this->delete_courses_completions();
            }
        }
    }

    protected function complete_courses($mandatorycourses, $optativecourses, $mancoursesamount, $optcoursesamount) {
        if (!empty($mandatorycourses)) {
            for ($i = 1; $i <= $mancoursesamount; $i++) {
                $course = array_pop($mandatorycourses);
                foreach ($this->students as $s) {
                    $this->complete_one_course($course, $s);
                }
            }
        }

        if (!empty($optativecourses)) {
            for ($i = 1; $i <= $optcoursesamount; $i++) {
                $course = array_pop($optativecourses);
                foreach ($this->students as $s) {
                    $this->complete_one_course($course, $s);
                }
            }
        }
    }

    public function test_save_cfg_grade() {
        $this->resetAfterTest(true);

        $this->setAdminUser();

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->gradecurricular->id;
        $_POST['inscricoesactivityid'] = 4;
        $_POST['studentcohortid'] = 0;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $gradecurricular = $this->update_grade_curricular();

        $this->assertEquals($gradecurricular->inscricoesactivityid, 4);
        $this->assertEquals($gradecurricular->studentcohortid, 0);
        $this->assertEquals($gradecurricular->tutorroleid, 2);
        $this->assertEquals($gradecurricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missing_inscricoesactivityid() {
        $this->resetAfterTest(true);

        $this->setAdminUser();

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->gradecurricular->id;
        $_POST['studentcohortid'] = 0;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $gradecurricular = $this->update_grade_curricular();

        $this->assertEquals($gradecurricular->inscricoesactivityid, 0);
        $this->assertEquals($gradecurricular->studentcohortid, 0);
        $this->assertEquals($gradecurricular->tutorroleid, 2);
        $this->assertEquals($gradecurricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missing_studentcohortid() {
        $this->resetAfterTest(true);

        $this->setAdminUser();

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->gradecurricular->id;
        $_POST['inscricoesactivityid'] = 4;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $gradecurricular = $this->update_grade_curricular();

        $this->assertEquals($gradecurricular->inscricoesactivityid, 4);
        $this->assertEquals($gradecurricular->studentcohortid, 0);
        $this->assertEquals($gradecurricular->tutorroleid, 2);
        $this->assertEquals($gradecurricular->notecourseid, 15);
    }

    public function test_save_cfg_grade_missint_inscricoesactivityid_and_studentcohortid() {
        $this->resetAfterTest(true);

        $this->setAdminUser();

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->gradecurricular->id;
        $_POST['tutorroleid'] = 2;
        $_POST['notecourseid'] = 15;
        $_POST['sesskey'] = sesskey();

        local_grade_curricular::save_cfg_grade($context->id, (object) $_POST);

        $gradecurricular = $this->update_grade_curricular();

        $this->assertEquals($gradecurricular->inscricoesactivityid, 0);
        $this->assertEquals($gradecurricular->studentcohortid, 0);
        $this->assertEquals($gradecurricular->tutorroleid, 2);
        $this->assertEquals($gradecurricular->notecourseid, 15);
    }

    public function test_save_modules() {
        global $DB;

        $this->resetAfterTest(true);

        $this->setAdminUser();
        $this->create_courses($amount = 6);
        $this->associate_courses_to_grade_curricular($this->courses, $optative = 3, $mandatory = 3);

        $context = context_coursecat::instance($this->category->id);

        $_POST = array();
        $_POST['gradecurricularid'] = $this->gradecurricular->id;
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

        foreach ($this->gradecurricularcourses as $course) {
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

        $gradecurricular = $this->update_grade_curricular();

        $this->assertEquals($gradecurricular->inscricoesactivityid, 0);
        $this->assertEquals($gradecurricular->minoptionalcourses, 7);
        $this->assertEquals($gradecurricular->maxoptionalcourses, 15);
        $this->assertEquals($gradecurricular->optionalatonetime, 5);

        $gradecurricularcourses = $this->update_grade_curricular_courses();

        foreach ($gradecurricularcourses as $course) {
            $this->assertEquals($course->type, 1);
            $this->assertEquals($course->workload, 100);
            $this->assertEquals($course->inscribestartdate, make_timestamp(2016, 1, 7));
            $this->assertEquals($course->inscribeenddate, make_timestamp(2016, 12, 25));
        }

        // Get the first element of this array.
        $randomcourse = reset($gradecurricularcourses);
        $randomcoursekey = key($gradecurricularcourses);

        // Delete this course.
        $DB->delete_records('course', array('id' => $randomcourse->courseid));

        // Repeat the proccess to see if the course is erased from grade_curricular_courses table.
        local_grade_curricular::save_modules($context->id, $this->category, (object) $_POST);

        $gradecurricularcourses = $this->update_grade_curricular_courses();

        $this->assertArrayNotHasKey($randomcoursekey, $gradecurricularcourses);
    }
}
