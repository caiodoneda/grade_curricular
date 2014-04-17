<?php

require('../../config.php');

require_once($CFG->dirroot.'/local/grade_curricular/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

$contextid = required_param('contextid', PARAM_INT);

require_login();
$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/grade_curricular:view', $context);

$baseurl = new moodle_url('/local/grade_curricular/index.php', array('contextid'=>$contextid));
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$context->instanceid));

$category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);

if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/grade_curricular:configure', $context);
    gc_save($contextid, $category);
    redirect($baseurl, get_string('changessaved'), 1);
}

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('pluginname', 'local_grade_curricular'));
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_grade_curricular') . ': ' . $category->name);
echo html_writer::empty_tag('BR');

$grades = gc_get_grades_curriculares($category);
$grade = false;
foreach($grades AS $gr) {
    if($gr->contextid == $contextid) {
        $grade = $gr;
        break;
    }
}

if(!$grade) {
    if(empty($grades)) {
        if(has_capability('local/grade_curricular:configure' , $context)) {
            if (optional_param('createconfirm', false, PARAM_BOOL) &&  confirm_sesskey()) {
                $grade = new stdclass();
                $grade->id = 0;
                $grade->contextid = $contextid;
                $grade->minoptionalcourses = 0;
                $grade->maxoptionalcourses = 0;
                $grade->optionalatonetime = 0;
                $grade->inscricoeseditionid = 0;
                $grade->tutorroleid = 0;
                $grade->studentcohortid = 0;
                $grade->notecourseid = 0;
            } else {
                $yesurl = new moodle_url('/local/grade_curricular/index.php', array('contextid'=>$contextid, 'createconfirm'=>1,
                                         'sesskey'=>sesskey()));
                $message = get_string('createconfirm', 'local_grade_curricular');
                echo $OUTPUT->confirm($message, $yesurl, $returnurl);
                echo $OUTPUT->footer();
                exit;
            }
        } else {
            echo $OUTPUT->heading(get_string('no_grade_curricular', 'local_grade_curricular'));
            echo $OUTPUT->footer();
            exit;
        }
    } else {
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo get_string('another', 'local_grade_curricular');
        echo html_writer::start_tag('UL');
        foreach($grades AS $gr) {
            $contextcat = context::instance_by_id($gr->contextid, MUST_EXIST);
            $cat = $DB->get_record('course_categories', array('id'=>$contextcat->instanceid));
            $url = new moodle_url('/local/grade_curricular/index.php', array('contextid'=>$gr->contextid));
            echo html_writer::tag('LI', html_writer::link($url, $cat->name));
        }
        echo html_writer::end_tag('UL');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }
}

$courses = gc_get_potential_courses($category->path, $grade->id);

// Show edit form

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

$options_opt = array();
for($i=0; $i <= count($courses); $i++) {
    $options_opt[$i] = $i;
}

echo html_writer::start_tag('div');
echo html_writer::tag('B', get_string('minoptionalcourses', 'local_grade_curricular'));
echo html_writer::select($options_opt, "minoptionalcourses", $grade->minoptionalcourses, false, $attributes);

echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('maxoptionalcourses', 'local_grade_curricular'));
echo html_writer::select($options_opt, "maxoptionalcourses", $grade->maxoptionalcourses, false, $attributes);

echo html_writer::empty_tag('br');
$yesno_options = array('1'=>get_string('yes'), '0'=>get_string('no'));
echo html_writer::tag('B', get_string('optionalatonetime', 'local_grade_curricular'));
echo html_writer::select($yesno_options, "optionalatonetime", $grade->optionalatonetime, false, $attributes);

$plugins = core_component::get_plugin_list('local');
if(isset($plugins['inscricoes'])) {
    $editions_opt = array(0=>get_string('no_edition', 'local_grade_curricular'));
    $editions_opt += gc_get_potential_editions($context, $grade->id);
    echo html_writer::empty_tag('br');
    echo html_writer::tag('B', get_string('edition', 'local_grade_curricular'));
    echo html_writer::select($editions_opt, "inscricoeseditionid", $grade->inscricoeseditionid, false, $attributes);
} else {
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'inscricoeseditionid', 'value'=>0));
}

$cohorts_opt = gc_get_cohorts($context);
$cohorts_opt[0] = get_string('no_cohort', 'local_grade_curricular');
echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('studentcohort', 'local_grade_curricular'));
echo html_writer::select($cohorts_opt, "studentcohortid", $grade->studentcohortid, false, $attributes);

$courses_opt = array();
$courses_opt[0] = get_string('no_notecourse', 'local_grade_curricular');
foreach($courses as $c) {
    $courses_opt[$c->id] = $c->fullname;
}
echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('notecourse', 'local_grade_curricular'));
echo html_writer::select($courses_opt, "notecourseid", $grade->notecourseid, false, $attributes);

$all_roles = role_get_names();
$ctx_roles = get_roles_for_contextlevels(CONTEXT_COURSE);
$roles_opt = array();
foreach($ctx_roles AS $id=>$roleid) {
    $roles_opt[$roleid] = $all_roles[$roleid]->localname;
}
echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('tutorrole', 'local_grade_curricular'));
echo html_writer::select($roles_opt, "tutorroleid", $grade->tutorroleid, false, $attributes);

echo html_writer::end_tag('div');

echo html_writer::empty_tag('br');
echo html_writer::start_tag('div', array('class'=>'buttons'));
echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'savechanges', 'value'=>get_string('savechanges')) + $attributes);
echo html_writer::link($returnurl, '  ' . get_string('cancel'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

echo html_writer::end_tag('DIV');
echo $OUTPUT->footer();
