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
        
        $courses = local_grade_curricular::get_potential_courses($category->path, $grade->id);

        $attributes = array();
        
        if (!has_capability('local/grade_curricular:configure' , $context)) {
            $attributes['disabled'] = 'disabled';
        }

        $options_opt = array();
        for ($i=0; $i <= count($courses); $i++) {
            $options_opt[$i] = $i;
        }

        $yesno_options = array('1'=>get_string('yes'), '0'=>get_string('no'));

        $mform->addElement('header', 'optional_modules', get_string('optional_modules', 'local_grade_curricular'));
        $mform->addElement('select', 'minoptionalcourses', get_string('minoptionalcourses', 'local_grade_curricular'), 
                           $options_opt);
        $mform->addElement('select', 'maxoptionalcourses', get_string('maxoptionalcourses', 'local_grade_curricular'),
                           $options_opt);
        $mform->addElement('select', 'optionalatonetime', get_string('optionalatonetime', 'local_grade_curricular'),
                           $yesno_options);
        $mform->closeHeaderBefore('sistema_de_inscricoes');

        
        $plugins = core_component::get_plugin_list('local');
        
        $mform->addElement('header', 'sistema_de_inscricoes', get_string('sistema_de_inscricoes', 'local_grade_curricular'));
        if (isset($plugins['inscricoes'])) {
            $activities_opt = array(0=>get_string('no_activity', 'local_grade_curricular'));
            $activities_opt += local_grade_curricular::get_potential_activities($context, $grade->id);
            $mform->addElement('select', 'inscricoesactivityid', get_string('activity', 'local_grade_curricular'), 
                               $activities_opt);
            $mform->disabledIf('studentcohortid', 'inscricoesactivityid', 'neq', 0);
        } else {
            $mform->addElement('hidden', 'inscricoesactivityid', 0);
        }
        $mform->closeHeaderBefore('students_selection');


        $cohorts_opt = local_grade_curricular::get_cohorts($context);
        $cohorts_opt[0] = get_string('no_cohort', 'local_grade_curricular');
        $mform->addElement('header', 'students_selection', get_string('students_selection', 'local_grade_curricular'));
        $mform->addElement('select', 'studentcohortid', get_string('studentcohort', 'local_grade_curricular'), $cohorts_opt);
        $mform->disabledIf('inscricoesactivityid', 'studentcohortid', 'neq', 0);
        $mform->closeHeaderBefore('tutors_notes');

        
        $courses_opt = array();
        $courses_opt[0] = get_string('no_notecourse', 'local_grade_curricular');
        foreach($courses as $c) {
            $courses_opt[$c->id] = $c->fullname;
        }

        $all_roles = role_get_names();
        $ctx_roles = get_roles_for_contextlevels(CONTEXT_COURSE);
        $roles_opt = array();
        foreach($ctx_roles AS $id=>$roleid) {
            $roles_opt[$roleid] = $all_roles[$roleid]->localname;
        }

        $mform->addElement('header', 'tutors_notes', get_string('tutors_notes', 'local_grade_curricular'));
        $mform->addElement('select', 'notecourseid', get_string('notecourse', 'local_grade_curricular'), $courses_opt);
        $mform->addElement('select', 'tutorroleid', get_string('tutorrole', 'local_grade_curricular'), $roles_opt);

        $this->add_action_buttons(false);
    }
}
