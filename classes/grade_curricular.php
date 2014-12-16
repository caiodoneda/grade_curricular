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
 * Event handler definition
 *
 * @package local_grade_curricular
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_grade_curricular {

    public static function get_courses($gradeid, $only_active=true) {
        global $DB;

        $where = $only_active ? 'AND gcc.type NOT IN (0, 3)' : '';

        $sql = "SELECT c.id, c.shortname, c.fullname, c.visible, c.showgrades,
                       gcc.type, gcc.workload, gcc.inscribestartdate, gcc.inscribeenddate, gcc.coursedependencyid
                  FROM {grade_curricular_courses} gcc
                  JOIN {course} c ON (c.id = gcc.courseid)
                 WHERE gcc.gradecurricularid = :gradecurricularid
                   {$where}";
        return $DB->get_records_sql($sql, array('gradecurricularid'=>$gradeid));
    }

    public static function get_students_in_all_courses($gradeid, $groupname='') {
        global $DB, $CFG;

        $roleids = explode(',', $CFG->gradebookroles);
        list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

        $count = 0;
        foreach (self::get_courses($gradeid) AS $c) {
            if ($c->type != 4) { // TCC
                $count++;
            }
        }
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['gradeid'] = $gradeid;
        $params['ncourses'] = $count;

        $join_group = '';
        if (!empty($groupname)) {
            $join_group = "JOIN {groups} g ON (g.courseid = gcc.courseid AND g.name = :groupname)
                           JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = u.id)";
            $params['groupname'] = $groupname;
        }

        $sql = "SELECT u.id, u.username, u.firstname, u.lastname, COUNT(*) as ncourses
                  FROM {grade_curricular} gc
                  JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type NOT IN (0, 3))
                  JOIN {context} ctx ON (ctx.instanceid = gcc.courseid AND ctx.contextlevel = :contextlevel)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$in_sql})
                  JOIN user u ON (u.id = ra.userid)
                  {$join_group}
                 WHERE gc.id = :gradeid
              GROUP BY u.id
              HAVING ncourses >= :ncourses";
        return $DB->get_records_sql($sql, $params);
    }
}
