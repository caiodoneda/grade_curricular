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

defined('MOODLE_INTERNAL') || die();

define('GC_IGNORE', 0);
define('GC_MANDATORY', 1);
define('GC_OPTIONAL', 2);
define('GC_TCC', 3);

function local_grade_curricular_extend_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat') && has_capability('local/grade_curricular:view', $PAGE->context)) {
        $categorynode = $navigation->get('categorysettings');
        if (!$fathernode = $categorynode->get('curriculumcontrol', navigation_node::TYPE_CONTAINER)) {
             $fathernode = $categorynode->add(get_string('curriculumcontrol', 'local_grade_curricular'), null,
                                               navigation_node::TYPE_CONTAINER, null, 'curriculumcontrol');
        }

        $nodekey = 'node200';
        $beforekey = null;
        $children = $fathernode->get_children_key_list();
        foreach ($children as $child) {
            if ($child > $nodekey) {
                $beforekey = $child;
                break;
            }
        }

        $node = navigation_node::create(get_string('menu_title', 'local_grade_curricular'),
                                        new moodle_url('/local/grade_curricular/index.php',
                                        array('contextid' => $PAGE->context->id)),
                                        navigation_node::TYPE_CUSTOM, null, $nodekey);
        $fathernode->add_node($node, $beforekey);
    }
}
