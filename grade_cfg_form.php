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

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once("{$CFG->libdir}/formslib.php");
require_once("./classes/grade_curricular.php");

class local_grade_curricular_grade_cfg_form extends moodleform {
    public function definition() {
        global $CFG, $DB, $PAGE;

        $mform = $this->_form;
        $category = $this->_customdata['category'];
        $grade = $this->_customdata['grade'];
        $context = $this->_customdata['context'];

        $predata = new stdclass();

        if (!$predata = $DB->get_record('grade_curricular', array('id' => $grade->id))) {
            $predata->minoptionalcourses = 0;
            $predata->maxoptionalcourses = 0;
            $predata->inscricoesactivityid = 0;
            $predata->optionalatonetime = 0;
            $predata->tutorroleid = 0;
            $predata->studentcohortid = 0;
            $predata->notecourseid = 0;
        }

        $mform->addelement('html', html_writer::empty_tag('input',
                                   array('type' => 'hidden', 'name' => 'action', 'value' => 'gradecurricular_additional')));
        $mform->addelement('html', html_writer::empty_tag('input',
                                   array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey())));
        $mform->addelement('html', html_writer::empty_tag('input',
                                   array('type' => 'hidden', 'name' => 'contextid', 'value' => $context->id)));
        $mform->addelement('html', html_writer::empty_tag('input',
                                   array('type' => 'hidden', 'name' => 'gradecurricularid', 'value' => $grade->id)));

        $attributes = array();

        if (!has_capability('local/grade_curricular:configure' , $context)) {
            $attributes['disabled'] = 'disabled';
        }

        $plugins = core_component::get_plugin_list('local');

        // Seleção de estudantes.
        $mform->addElement('header', 'students_selection', get_string('students_selection', 'local_grade_curricular'));
        $mform->setExpanded('students_selection', false);

        if (isset($plugins['inscricoes'])) {
            $activitiesopt[0] = get_string('no_activity', 'local_grade_curricular');
            $activitiesopt += local_grade_curricular::get_potential_activities($context, $grade->id);
            $mform->addElement('select', 'inscricoesactivityid', get_string('activity', 'local_grade_curricular'),
                               $activitiesopt);
            $mform->setType('inscricoesactivityid', PARAM_INT);
            $mform->disabledIf('studentcohortid', 'inscricoesactivityid', 'neq', 0);
            $mform->setDefault('inscricoesactivityid', $predata->inscricoesactivityid);
            $mform->addHelpButton('inscricoesactivityid', 'inscricoesactivityid', 'local_grade_curricular');

            $mform->addElement('html', html_writer::tag('label', 'ou', array('style' => 'margin-top:15px; margin-left:200px')));
        } else {
            $mform->addElement('hidden', 'inscricoesactivityid', 0);
            $mform->setType('inscricoesactivityid', PARAM_INT);
        }

        $mform->closeHeaderBefore('tutors_notes');

        $cohortsopt[0] = get_string('no_cohort', 'local_grade_curricular');
        $cohortsopt += local_grade_curricular::get_cohorts($context);
        $mform->addElement('select', 'studentcohortid',
                           get_string('studentcohort', 'local_grade_curricular'), $cohortsopt);
        $mform->setType('studentcohortid', PARAM_INT);
        $mform->setDefault('studentcohortid', $predata->studentcohortid);
        $mform->addHelpButton('studentcohortid', 'studentcohort', 'local_grade_curricular');
        $mform->disabledIf('inscricoesactivityid', 'studentcohortid', 'neq', 0);
        $mform->closeHeaderBefore('tutors_notes');

        $courses = local_grade_curricular::get_potential_courses($category->path, $grade->id);

        $coursesopt = array();
        $coursesopt[0] = get_string('no_notecourse', 'local_grade_curricular');
        foreach ($courses as $c) {
            $coursesopt[$c->id] = $c->fullname;
        }

        $allroles = role_get_names();
        $ctxroles = get_roles_for_contextlevels(CONTEXT_COURSE);
        $rolesopt = array();

        foreach ($ctxroles as $id => $roleid) {
            $rolesopt[$roleid] = $allroles[$roleid]->localname;
        }

        $rolesopt[0] = get_string('none');

        // Anotações de tutores sobre estudantes.
        $mform->addElement('header', 'tutors_notes', get_string('tutors_notes', 'local_grade_curricular'));
        $mform->setExpanded('tutors_notes', false);
        $mform->addElement('select', 'notecourseid', get_string('notecourse', 'local_grade_curricular'), $coursesopt);
        $mform->setType('notecourseid', PARAM_INT);
        $mform->setDefault('notecourseid', $predata->notecourseid);
        $mform->addHelpButton('notecourseid', 'notecourse', 'local_grade_curricular');

        $mform->addElement('select', 'tutorroleid', get_string('tutorrole', 'local_grade_curricular'), $rolesopt);
        $mform->setType('tutorroleid', PARAM_INT);
        $mform->setDefault('tutorroleid', $predata->tutorroleid);
        $mform->addHelpButton('tutorroleid', 'tutorrole', 'local_grade_curricular');

        $this->add_action_buttons(false);
    }
}
