<?php

require('../../config.php');

require_once($CFG->dirroot.'/local/grade_curricular/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid, MUST_EXIST);

if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}

require_capability('local/grade_curricular:view', $context);

$baseurl = new moodle_url('/local/grade_curricular/index.php', array('contextid'=>$contextid));
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$context->instanceid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('pluginname', 'local_grade_curricular'));
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_grade_curricular'));
echo html_writer::empty_tag('BR');


$tab_items = array('modules', 'gradecurricular', 'approval_criteria');
$tabs = array();

foreach($tab_items AS $act) {
    $url = clone $baseurl;
    $url->param('action', $act);
    $tabs[$act] = new tabobject($act, $url, get_string($act, 'local_grade_curricular'));
}

$action = optional_param('action', '', PARAM_TEXT);
$action = isset($tabs[$action]) ? $action : 'modules';

print_tabs(array($tabs), $action);

switch ($action) {
    case 'modules':
        require_once('views/modules.php');
        break;
    case 'gradecurricular':
        require_once('views/gradecurricular.php');
        break;
    case 'approval_criteria':
        require_once('views/approval_criteria.php');
        break;
}


echo $OUTPUT->footer();
