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

require('../../config.php');

require_once($CFG->dirroot.'/local/grade_curricular/classes/grade_curricular.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid, MUST_EXIST);

if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}

require_capability('local/grade_curricular:view', $context);

$baseurl = new moodle_url('/local/grade_curricular/index.php', array('contextid' => $contextid));
$returnurl = new moodle_url('/course/index.php', array('categoryid' => $context->instanceid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('pluginname', 'local_grade_curricular'));
$PAGE->set_heading($COURSE->fullname);

$category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);

$grades = local_grade_curricular::get_grades_curriculares($category);
$grade = false;
foreach ($grades as $gr) {
    if ($gr->contextid == $contextid) {
        $grade = $gr;
        break;
    }
}

if (!$grade) {
    if (optional_param('createconfirm', false, PARAM_BOOL) && confirm_sesskey() &&
        has_capability('local/grade_curricular:configure' , $context)) {

        $grade = new stdclass();
        $grade->contextid = $contextid;
        $grade->minoptionalcourses = 0;
        $grade->maxoptionalcourses = 0;
        $grade->optionalatonetime = 0;
        $grade->inscricoesactivityid = 0;
        $grade->tutorroleid = 0;
        $grade->studentcohortid = 0;
        $grade->notecourseid = 0;
        $grade->timemodified = time();
        $grade->id = $DB->insert_record('grade_curricular', $grade);
    } else if (empty($grades)) {
        echo $OUTPUT->header();

        if (has_capability('local/grade_curricular:configure' , $context)) {
            $yesurl = new moodle_url('/local/grade_curricular/index.php',
                                     array('contextid' => $contextid, 'createconfirm' => 1, 'sesskey' => sesskey()));
            $message = get_string('createconfirm', 'local_grade_curricular');
            echo $OUTPUT->confirm($message, $yesurl, $returnurl);
        } else {
            echo $OUTPUT->heading(get_string('no_grade_curricular', 'local_grade_curricular'));
        }

        echo $OUTPUT->footer();
        exit;
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo get_string('another', 'local_grade_curricular');
        echo html_writer::start_tag('UL');

        foreach ($grades as $gr) {
            $contextcat = context::instance_by_id($gr->contextid, MUST_EXIST);
            $cat = $DB->get_record('course_categories', array('id' => $contextcat->instanceid));
            $url = new moodle_url('/local/grade_curricular/index.php', array('contextid' => $gr->contextid));
            echo html_writer::tag('LI', html_writer::link($url, $cat->name));
        }

        echo html_writer::end_tag('UL');

        $yesurl = new moodle_url('/local/grade_curricular/index.php',
                                 array('contextid' => $contextid, 'createconfirm' => 1, 'sesskey' => sesskey()));
        $message = get_string('createconfirm', 'local_grade_curricular');
        echo $OUTPUT->confirm($message, $yesurl, $returnurl);

        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }
}

$tabitems = array('modules', 'gradecurricular_additional');
$tabs = array();

foreach ($tabitems as $act) {
    $url = clone $baseurl;
    $url->param('action', $act);
    $tabs[$act] = new tabobject($act, $url, get_string($act, 'local_grade_curricular'));
}

$action = optional_param('action', '', PARAM_TEXT);
$action = isset($tabs[$action]) ? $action : 'modules';

switch ($action) {
    case 'modules':
        require_once('./modules_form.php');

        $toform = array('category' => $category, 'grade' => $grade, 'context' => $context);

        $mform = new local_grade_curricular_modules_form(null, $toform);

        if ($formdata = $mform->get_data()) {
            local_grade_curricular::save_modules($contextid, $category, $formdata);
            redirect(new moodle_url('/local/grade_curricular/index.php', array('contextid' => $contextid, 'action' => 'modules')));
        }

        break;
    case 'gradecurricular_additional':
        require_once('./grade_cfg_form.php');

        $toform = array('category' => $category, 'grade' => $grade, 'context' => $context);

        $mform = new local_grade_curricular_grade_cfg_form(null, $toform);

        if ($formdata = $mform->get_data()) {
            local_grade_curricular::save_cfg_grade($contextid, $formdata);
            redirect(new moodle_url('/local/grade_curricular/index.php',
                     array('contextid' => $contextid, 'action' => 'gradecurricular_additional')));
        }

        break;
}

echo $OUTPUT->header();

print_tabs(array($tabs), $action);
$mform->display();

echo $OUTPUT->footer();
