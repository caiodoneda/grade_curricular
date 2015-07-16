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

class local_grade_curricular_modules_form extends moodleform {
     public function definition() {
        global $CFG, $DB, $PAGE;
        
        $mform = $this->_form;
        $grade = $this->_customdata['grade'];
        $category = $this->_customdata['category'];
        $context = $this->_customdata['context'];

        $courses = local_grade_curricular::get_potential_courses($category->path, $grade->id);

        $out = "";
        
        if(isset($SESSION->errors)) {
            $errors = $SESSION->errors;
            unset($SESSION->errors);

            $out .= $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide msg_error');
            $out .= $OUTPUT->heading(get_string('errors', 'local_grade_curricular'));
            $out .= html_writer::start_tag('UL');
            foreach($errors AS $fullname=>$errs) {
                $out .= html_writer::tag('LI', $fullname);
                $out .= html_writer::start_tag('UL');
                foreach($errs AS $err) {
                    $out .= html_writer::tag('LI', $err);
                }
                $out .= html_writer::end_tag('UL');
            }
            $out .= html_writer::end_tag('UL');
            $out .= $OUTPUT->box_end();
        }

        $out .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'modules'));
        $out .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $out .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$context->id));
        $out .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'gradecurricularid', 'value'=>$grade->id));

        $type_options = array(GC_IGNORE    => get_string('ignore', 'local_grade_curricular'),
                              GC_MANDATORY => get_string('mandatory', 'local_grade_curricular'),
                              GC_OPTIONAL  => get_string('optional', 'local_grade_curricular'),
                              GC_TCC       => get_string('tcc', 'local_grade_curricular'));

        $attributes = array();
        if(! has_capability('local/grade_curricular:configure' , $context)) {
            $attributes['disabled'] = 'disabled';
        }

        $data = array();
        foreach($courses as $c) {
            $tab = new html_table();
            $tab->data = array();

            $tab->data[] = array(get_string('type', 'local_grade_curricular'),
                                 html_writer::select($type_options, "type[$c->id]", $c->type, false, $attributes));
            $tab->data[] = array(get_string('workload', 'local_grade_curricular'),
                                 html_writer::empty_tag('input', array('type'=>'text', 'name'=>"workload[$c->id]", 'value'=>$c->workload, 'size'=>5) + $attributes));

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

            $course_options = array('0' => get_string('none'));
            foreach($courses as $copt) {
                if($copt->id != $c->id) {
                    $course_options[$copt->id] = $copt->fullname;
                }
            }

            $tab->data[] = array(get_string('dependency', 'local_grade_curricular'),
                                 html_writer::select($course_options, "dependencies[$c->id]", $c->coursedependencyid, false, $attributes));

            $curl = new moodle_url('/course/view.php', array('id'=>$c->id));
            $data[] = array(html_writer::link($curl, format_string($c->fullname), array('target'=>'_new')),
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
}
