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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once("{$CFG->libdir}/formslib.php");
require_once("./classes/grade_curricular.php");

class local_grade_curricular_modules_form extends moodleform {
    public function definition() {
        global $CFG, $DB, $PAGE;

        $mform = $this->_form;
        $grade = $this->_customdata['grade'];
        $category = $this->_customdata['category'];
        $context = $this->_customdata['context'];

        if (!$predata = $DB->get_record('grade_curricular', array('id' => $grade->id))) {
            $predata->minoptionalcourses = 0;
            $predata->maxoptionalcourses = 0;
            $predata->optionalatonetime = 0;
        }

        $courses = local_grade_curricular::get_potential_courses($category->path, $grade->id);

        $optionsopt = array();
        for ($i = 0; $i <= count($courses); $i++) {
            $optionsopt[$i] = $i;
        }

        $yesnooptions = array('1' => get_string('yes'), '0' => get_string('no'));

        // Módulos optativos.
        $mform->addElement('header', 'optative_modules', get_string('optative_modules', 'local_grade_curricular'));
        $mform->setExpanded('optative_modules', false);
        $mform->addElement('select', 'minoptionalcourses',
                           get_string('minoptionalcourses', 'local_grade_curricular'), $optionsopt);
        $mform->setType('minoptionalcourses', PARAM_INT);
        $mform->setDefault('minoptionalcourses', $predata->minoptionalcourses);
        $mform->addHelpButton('minoptionalcourses', 'minoptionalcourses', 'local_grade_curricular');

        $mform->addElement('select', 'maxoptionalcourses',
                           get_string('maxoptionalcourses', 'local_grade_curricular'), $optionsopt);
        $mform->setType('maxoptionalcourses', PARAM_INT);
        $mform->setDefault('maxoptionalcourses', $predata->maxoptionalcourses);
        $mform->addHelpButton('maxoptionalcourses', 'maxoptionalcourses', 'local_grade_curricular');

        $mform->addElement('select', 'optionalatonetime',
                           get_string('optionalatonetime', 'local_grade_curricular'), $yesnooptions);
        $mform->setType('optionalatonetime', PARAM_INT);
        $mform->setDefault('optionalatonetime', $predata->optionalatonetime);
        $mform->addHelpButton('optionalatonetime', 'optionalatonetime', 'local_grade_curricular');
        $mform->closeHeaderBefore('modules');

        $out = "<BR>";

        if (isset($SESSION->errors)) {
            $errors = $SESSION->errors;
            unset($SESSION->errors);

            $out .= $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide msg_error');
            $out .= $OUTPUT->heading(get_string('errors', 'local_grade_curricular'));
            $out .= html_writer::start_tag('UL');

            foreach ($errors as $fullname => $errs) {
                $out .= html_writer::tag('LI', $fullname);
                $out .= html_writer::start_tag('UL');

                foreach ($errs as $err) {
                    $out .= html_writer::tag('LI', $err);
                }

                $out .= html_writer::end_tag('UL');
            }

            $out .= html_writer::end_tag('UL');
            $out .= $OUTPUT->box_end();
        }

        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'modules'));
        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'contextid', 'value' => $context->id));
        $out .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'gradecurricularid', 'value' => $grade->id));

        $typeoptions = array(GC_IGNORE => get_string('ignore', 'local_grade_curricular'),
                             GC_MANDATORY => get_string('mandatory', 'local_grade_curricular'),
                             GC_OPTIONAL => get_string('optional', 'local_grade_curricular'),
                             GC_TCC => get_string('tcc', 'local_grade_curricular'));

        $attributes = array();

        if (!has_capability('local/grade_curricular:configure' , $context)) {
            $attributes['disabled'] = 'disabled';
        }

        $mform->addElement('header', 'modules', "cursos Moodle");
        $mform->setExpanded('modules', false);
        $data = array();
        foreach ($courses as $c) {
            $tab = new html_table();
            $tab->data = array();

            $tab->data[] = array(get_string('type', 'local_grade_curricular'),
                                 html_writer::select($typeoptions, "type[$c->id]", $c->type, false, $attributes));
            $tab->data[] = array(get_string('workload', 'local_grade_curricular'),
                                 html_writer::empty_tag('input', array('type' => 'text', 'name' => "workload[$c->id]",
                                                        'value' => $c->workload, 'size' => 5) + $attributes));

            $startdate = get_string('from') . ': '
                         . html_writer::select_time('days', "startdays[$c->id]", $c->inscribestartdate, 0, $attributes)
                         . html_writer::select_time('months', "startmonths[$c->id]", $c->inscribestartdate, 0, $attributes)
                         . html_writer::select_time('years', "startyears[$c->id]", $c->inscribestartdate, 0, $attributes);
            $enddate   = get_string('to') . ': '
                         . html_writer::select_time('days', "enddays[$c->id]", $c->inscribeenddate, 0, $attributes)
                         . html_writer::select_time('months', "endmonths[$c->id]", $c->inscribeenddate, 0, $attributes)
                         . html_writer::select_time('years', "endyears[$c->id]", $c->inscribeenddate, 0, $attributes);
            $tab->data[] = array(get_string('inscribeperiodo', 'local_grade_curricular'),
                           $startdate . '   ' . $enddate);

            $courseoptions = array('0' => get_string('none'));
            foreach ($courses as $copt) {
                if ($copt->id != $c->id) {
                    $courseoptions[$copt->id] = $copt->fullname;
                }
            }

            $tab->data[] = array(get_string('dependency', 'local_grade_curricular'),
                                 html_writer::select($courseoptions, "dependencies[$c->id]",
                                                     $c->coursedependencyid, false, $attributes));

            $curl = new moodle_url('/course/view.php', array('id' => $c->id));
            $data[] = array(html_writer::link($curl, format_string($c->fullname), array('target' => '_new')),
                            html_writer::table($tab));
        }
        $table = new html_table();
        $table->head  = array(get_string('coursename', 'local_grade_curricular'),
                              get_string('configurations', 'local_grade_curricular'));
        $table->colclasses = array('leftalign', 'leftalign');
        $table->id = 'inscricoes';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $data;

        $out .= html_writer::table($table);

        $mform->addElement('html', $out);

        $this->add_action_buttons(false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['maxoptionalcourses'] < $data['minoptionalcourses']) {
            $errors['maxoptionalcourses'] = get_string('maxoptionalcourseserror', 'local_grade_curricular');
            $errors['minoptionalcourses'] = get_string('minoptionalcourseserror', 'local_grade_curricular');
        }

        $gradecurricularid = required_param('gradecurricularid', PARAM_INT);
        $coursestype = required_param_array('type', PARAM_INT);

        $countopt = 0;
        foreach ($coursestype as $courseid => $typeid) {
            if ($typeid == 2) {
                $countopt++;
            }
        }

        if ($data['maxoptionalcourses'] > $countopt) {
            if (isset($errors['maxoptionalcourses'])) {
                $errors['maxoptionalcourses'] .= get_string('morethanerror', 'local_grade_curricular');
            } else {
                $errors['maxoptionalcourses'] = get_string('morethanerror', 'local_grade_curricular');
            }
        }

        if ($data['minoptionalcourses'] > $countopt) {
            if (isset($errors['minoptionalcourses'])) {
                $errors['minoptionalcourses'] .= get_string('morethanerror', 'local_grade_curricular');
            } else {
                $errors['minoptionalcourses'] = get_string('morethanerror', 'local_grade_curricular');
            }
        }

        return $errors;
    }
}
