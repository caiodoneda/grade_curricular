<?php

echo "<link href='./css/gradecurricular.css' rel='stylesheet'>";

$courses = gc_get_potential_courses($category->path, $grade->id);

$attributes = array();
if(! has_capability('local/grade_curricular:configure' , $context)) {
    $attributes['disabled'] = 'disabled';
}

echo html_writer::start_tag('DIV', array('class'=>'local_grade_curricular'));

if(isset($SESSION->errors)) {
    $errors = $SESSION->errors;
    unset($SESSION->errors);

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide msg_error');
    echo $OUTPUT->heading(get_string('errors', 'local_grade_curricular'));
    echo html_writer::start_tag('UL');
    foreach($errors AS $fullname=>$errs) {
        echo html_writer::tag('LI', $fullname);
        echo html_writer::start_tag('UL');
        foreach($errs AS $err) {
            echo html_writer::tag('LI', $err);
        }
        echo html_writer::end_tag('UL');
    }
    echo html_writer::end_tag('UL');
    echo $OUTPUT->box_end();
}

echo html_writer::start_tag('form', array('method'=>'post', 'action'=>$baseurl));

echo html_writer::start_tag('div');
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'gradecurricular'));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'gradecurricularid', 'value'=>$grade->id));
echo html_writer::end_tag('div');

$options_opt = array();
for($i=0; $i <= count($courses); $i++) {
    $options_opt[$i] = $i;
}

echo html_writer::start_tag('div', array('class' => 'gradecurricular_form'));
echo html_writer::start_tag('div', array('class' => 'gradecurricular_content'));

echo html_writer::tag('label', 'Módulos optativos', array('class' => 'block_msg'));
echo html_writer::start_tag('div', array('class' => 'block'));
  echo html_writer::start_tag('div');
    echo html_writer::tag('label', get_string('minoptionalcourses', 'local_grade_curricular'));
    echo html_writer::tag('label', get_string('maxoptionalcourses', 'local_grade_curricular'));
    echo html_writer::tag('label', get_string('optionalatonetime', 'local_grade_curricular'));
  echo html_writer::end_tag('div');
  
  echo html_writer::start_tag('div');  
    echo html_writer::select($options_opt, "minoptionalcourses", $grade->minoptionalcourses, false, $attributes);
    echo html_writer::select($options_opt, "maxoptionalcourses", $grade->maxoptionalcourses, false, $attributes);
    $yesno_options = array('1'=>get_string('yes'), '0'=>get_string('no'));
    echo html_writer::select($yesno_options, "optionalatonetime", $grade->optionalatonetime, false, $attributes);
  echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::tag('label', 'Sistema de inscrições', array('class' => 'block_msg'));
echo html_writer::start_tag('div', array('class' => 'block'));
  echo html_writer::start_tag('div');
    echo html_writer::tag('label', get_string('edition', 'local_grade_curricular')); 
  echo html_writer::end_tag('div');
  
  echo html_writer::start_tag('div');
    
    $plugins = core_component::get_plugin_list('local');
    if(isset($plugins['inscricoes'])) {
        $editions_opt = array(0=>get_string('no_edition', 'local_grade_curricular'));
        $editions_opt += gc_get_potential_editions($context, $grade->id);
        echo html_writer::select($editions_opt, "inscricoeseditionid", $grade->inscricoeseditionid, false, $attributes);
    } else {
        echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'inscricoeseditionid', 'value'=>0));
    }
  echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::tag('label', 'Seleção de estudantes', array('class' => 'block_msg'));
echo html_writer::start_tag('div', array('class' => 'block'));
  echo html_writer::start_tag('div');
    echo html_writer::tag('label', get_string('studentcohort', 'local_grade_curricular'));
  echo html_writer::end_tag('div');
  
  echo html_writer::start_tag('div');    
    $cohorts_opt = gc_get_cohorts($context);
    $cohorts_opt[0] = get_string('no_cohort', 'local_grade_curricular');
    echo html_writer::select($cohorts_opt, "studentcohortid", $grade->studentcohortid, false, $attributes);
  echo html_writer::end_tag('div');  
echo html_writer::end_tag('div');

echo html_writer::tag('label', 'Anotações de tutores sobre estudantes', array('class' => 'block_msg'));
echo html_writer::start_tag('div', array('class' => 'block'));
  echo html_writer::start_tag('div');
    echo html_writer::tag('label', get_string('notecourse', 'local_grade_curricular'));
    echo html_writer::tag('label', get_string('tutorrole', 'local_grade_curricular'));
  echo html_writer::end_tag('div');
  
  echo html_writer::start_tag('div');    
    $courses_opt = array();
    $courses_opt[0] = get_string('no_notecourse', 'local_grade_curricular');
    foreach($courses as $c) {
        $courses_opt[$c->id] = $c->fullname;
    }
    echo html_writer::select($courses_opt, "notecourseid", $grade->notecourseid, false, $attributes);
  
    $all_roles = role_get_names();
    $ctx_roles = get_roles_for_contextlevels(CONTEXT_COURSE);
    $roles_opt = array();
    foreach($ctx_roles AS $id=>$roleid) {
        $roles_opt[$roleid] = $all_roles[$roleid]->localname;
    }

    echo html_writer::select($roles_opt, "tutorroleid", $grade->tutorroleid, false, $attributes);
  echo html_writer::end_tag('div');  
echo html_writer::end_tag('div');

echo html_writer::end_tag('div'); //content
echo html_writer::end_tag('div'); //form-div 

echo "<input class='submit_button' type='submit' name='save_grade_options' value='Salvar'/>";
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'savechanges', 'value'=>'save_grade_options'));

echo html_writer::end_tag('form');
echo html_writer::end_tag('DIV');

echo "<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>";
echo "<script src='js/gradecurricular.js'></script> ";