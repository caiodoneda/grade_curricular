<?php

defined('MOODLE_INTERNAL') || die();

function local_grade_curricular_extends_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat') && has_capability('local/grade_curricular:view', $PAGE->context)) {
        $category_node = $navigation->get('categorysettings');
        if(!$father_node = $category_node->get('curriculumcontrol', navigation_node::TYPE_CONTAINER)) {
            $father_node = $category_node->add(get_string('curriculumcontrol', 'local_grade_curricular'), null,
                                               navigation_node::TYPE_CONTAINER, null, 'curriculumcontrol');
        }

        $node_key = 'node200';
        $before_key = null;
        $children = $father_node->get_children_key_list();
        foreach($children AS $child) {
            if($child > $node_key) {
                $before_key = $child;
                break;
            }
        }

        $node = navigation_node::create(
                    get_string('menu_title', 'local_grade_curricular'),
                    new moodle_url('/local/grade_curricular/index.php', array('contextid' => $PAGE->context->id)),
                    navigation_node::TYPE_CUSTOM, null, $node_key);
        $father_node->add_node($node, $before_key);
    }
}
