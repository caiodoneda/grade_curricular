<?php

echo "<link href='./css/modules.css' rel='stylesheet'>";

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/grade_curricular:configure', $context);
    gc_save($contextid, $category);
    redirect($baseurl, get_string('changessaved'), 1);
}

$courses = gc_get_potential_courses($category->path, $grade->id);

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

echo html_writer::start_tag('form', array('action'=>$baseurl->out_omit_querystring(), 'method'=>'post'));

echo html_writer::start_tag('div');
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'gradecurricularid', 'value'=>$grade->id));

$type_options = array(0=>get_string('not_classified', 'local_grade_curricular'),
                      1=>get_string('mandatory', 'local_grade_curricular'),
                      2=>get_string('optional', 'local_grade_curricular'),
                      3=>get_string('ignore', 'local_grade_curricular'));

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

echo html_writer::table($table);

echo html_writer::end_tag('div');

echo "<input class='submit_button' type='submit' name='savechanges' value='Salvar'/>";

echo html_writer::end_tag('form');
echo html_writer::end_tag('DIV');