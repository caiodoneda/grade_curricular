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

require_once($CFG->dirroot.'/local/grade_curricular/lib.php');

class local_grade_curricular {

    /**
     * Returns the courses related to the grade_curricular
     *
     * @param int $gradeid
     * @param boolean $only_active return all courses except type GC_IGNORE
     * @return array
     */
    public static function get_courses($gradeid, $only_active=true) {
        global $DB;

        $where = '';
        $params = array('gradecurricularid' => $gradeid);
        if ($only_active) {
            $where = 'AND gcc.type != :ignore';
            $params['ignore'] = GC_IGNORE;
        }

        $sql = "SELECT c.id, c.shortname, c.fullname, c.visible, c.showgrades,
                       gcc.type, gcc.workload, gcc.inscribestartdate, gcc.inscribeenddate, gcc.coursedependencyid
                  FROM {grade_curricular_courses} gcc
                  JOIN {course} c ON (c.id = gcc.courseid)
                 WHERE gcc.gradecurricularid = :gradecurricularid
                   {$where}";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns students from the cohort associated to the gradecurricular or
     * subscribed on at least one of the grade_curricular courses on the same category as the grade_curricular if there is no associated cohort
     *
     * @param int $gradeid
     * @param string groupname
     * @return array
     */
    public static function get_students($gradeid, $groupname='', $studentsorderby='name') {
        global $DB, $CFG;

        $grade = $DB->get_record('grade_curricular', array('id' => $gradeid), '*', MUST_EXIST);
        if($grade->inscricoeseditionid > 0) {
            $edition = $DB->get_record('inscricoes_editions', array('id' => $grade->inscricoeseditionid), '*', MUST_EXIST);
            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {inscricoes_editions} ie
                     JOIN {cohort} ch ON (ch.idnumber = :cohort_idnumber)
                     JOIN {cohort_members} chm ON (chm.cohortid = ch.id)
                     JOIN {user} u ON (u.id = chm.userid AND u.deleted = 0)";
            $where = "WHERE gc.id = :gradeid
                        AND ie.id = :editionid";
            $params = array('editionid' => $edition->id,
                            'gradeid'   => $gradeid,
                            'ignore'    => GC_IGNORE,
                            'cohort_idnumber' => local_inscricoes::cohort_idnumber_sql('ie', $edition));
        } else if($grade->studentcohortid > 0) {
            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {cohort_members} chm
                     JOIN {user} u ON (u.id = chm.userid AND u.deleted = 0)";
            $where = "WHERE gc.id = :gradeid
                        AND chm.cohortid = :cohortid";
            $params = array('cohortid' => $grade->studentcohortid,
                            'gradeid'  => $gradeid,
                            'ignore'   => GC_IGNORE);
        } else {
            $roleids = explode(',', $CFG->gradebookroles);
            list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

            $from = "FROM {grade_curricular} gc
                     JOIN {context} cctx ON (cctx.id = gc.contextid)
                     JOIN {course_categories} cc ON (cc.id = cctx.instanceid)
                     JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {course} c ON (c.id = gcc.courseid AND c.category = cc.id)
                     JOIN {context} ctx ON (ctx.instanceid = gcc.courseid AND ctx.contextlevel = :contextlevel)
                     JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$in_sql})
                     JOIN user u ON (u.id = ra.userid AND u.deleted = 0)";
            $where = "WHERE gc.id = :gradeid";
            $params['contextlevel'] = CONTEXT_COURSE;
            $params['gradeid'] = $gradeid;
            $params['ignore'] = GC_IGNORE;
        }

        $join_group = '';
        if (!empty($groupname)) {
            $join_group = "JOIN {groups} g ON (g.courseid = gcc.courseid AND g.name = :groupname)
                           JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = u.id)";
            $params['groupname'] = $groupname;
        }

        if ($studentsorderby == 'name') {
            $orderby = "CONCAT(u.firstname, ' ', u.lastname)";
        } else {
            $orderby = $studentsorderby;
        }

        $user_fields = implode(',', get_all_user_name_fields());
        $sql = "SELECT DISTINCT u.id, u.username, {$user_fields}
                 {$from}
                 {$join_group}
                 {$where}
                ORDER BY {$orderby}";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Returns the names from groups included in the grade_curricular courses
     *
     * @param int $gradeid
     * @return array
     */
    public static function get_groupnames($gradeid) {
        global $USER;

        $groupnames = array();

        $courses = self::get_courses($gradeid);
        foreach ($courses AS $course) {
            $context = context_course::instance($course->id);
            if (has_capability('moodle/site:accessallgroups', $context)) {
                $allowedgroups = groups_get_all_groups($course->id);
            } else {
                $allowedgroups = groups_get_all_groups($course->id, $USER->id);
            }
            foreach ($allowedgroups AS $group) {
                $groupnames[format_string($group->name)] = true;
            }
        }

        $keys = array_keys($groupnames);
        sort($keys);
        return $keys;
    }

    /**
     * Returns an array of grade curriculares associated to the student and includes courseid
     *
     * @param int $userid
     * @param int $courseid
     * @return array
     */
    public static function get_instances_for_student($userid, $courseid=0) {
        global $CFG, $DB;

        $roleids = explode(',', $CFG->gradebookroles);
        list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

        $where_course = '';
        if(!empty($courseid)) {
            $where_course = 'AND c.id = :courseid';
            $params['courseid'] = $courseid;
        }

        $sql = "SELECT DISTINCT gc.*, cc.id as categoryid, cc.name, cc.path, cc.depth
                  FROM {grade_curricular} gc
                  JOIN {context} cctx ON (cctx.id = gc.contextid AND cctx.contextlevel = :contextcoursecat)
                  JOIN {course_categories} cc ON (cc.id = cctx.instanceid)
                  JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                  JOIN {course} c ON (c.id = gcc.courseid)
                  JOIN {context} ctx ON (ctx.instanceid = gcc.courseid AND ctx.contextlevel = :contextcourse)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$in_sql})
                  JOIN user u ON (u.id = ra.userid)
                 WHERE u.id = :userid
                   {$where_course}
                ORDER BY cc.depth, cc.name";
            $params['contextcourse'] = CONTEXT_COURSE;
            $params['contextcoursecat'] = CONTEXT_COURSECAT;
            $params['userid'] = $userid;
            $params['ignore'] = GC_IGNORE;
        return $DB->get_records_sql($sql, $params);
    }

}
