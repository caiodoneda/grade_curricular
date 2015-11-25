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
    public static function get_students($gradeid, $groupname='', $studentsorderby='name', $search = '') {
        global $DB, $CFG;

        $grade = $DB->get_record('grade_curricular', array('id' => $gradeid), '*', MUST_EXIST);
        if($grade->inscricoesactivityid > 0) {
            $roleids = explode(',', $CFG->gradebookroles);
            list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc 
                       ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {inscricoes_activities} ia 
                       ON (ia.id = gc.inscricoesactivityid)
                     JOIN {inscricoes_cohorts} ic 
                       ON (ic.activityid = ia.id AND ic.roleid {$in_sql})
                     JOIN {cohort_members} chm 
                       ON (chm.cohortid = ic.cohortid)
                     JOIN {user} u ON (u.id = chm.userid AND u.deleted = 0)";
            $where = "WHERE gc.id = :gradeid";
            $params['gradeid'] = $gradeid;
            $params['ignore'] = GC_IGNORE;
        } else if($grade->studentcohortid > 0) {
            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc 
                       ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {cohort_members} chm
                     JOIN {user} u 
                       ON (u.id = chm.userid AND u.deleted = 0)";
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

        if (!empty($search)) {
            $where .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $search . "%'";
        }

        //Distinct é necessário devido ao fato de um usuário poder ter mais de um role_assignment em um cursos, inclusive com o mesmo papel.
        $user_fields = implode(',', get_all_user_name_fields());
        $sql = "SELECT DISTINCT u.id, u.username, {$user_fields}, CONCAT(u.firstname, ' ', u.lastname) as fullname
                 {$from}
                 {$join_group}
                 {$where}
                ORDER BY {$orderby}";
        return $DB->get_records_sql($sql, $params);
    }

    //Function used by progress report.
    public static function get_students_progress($grade, $str_groupids, $days_before, $studentsorderby) {
        global $DB, $CFG;

        $roleids = explode(',', $CFG->gradebookroles);
        list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

        $timebefore = strtotime('-' . $days_before . ' days');
        $params['contextlevel'] = CONTEXT_COURSE;
        $params['gradeid'] = $grade->id;
        $params['timebefore'] = $timebefore;
        $params['ignore'] = GC_IGNORE;

        $order = $studentsorderby == 'lastaccess' ? 'last_access ASC' : 'fullname';
        $sql = "SELECT uj.id, uj.fullname, uj.str_courseids,
                       COUNT(*) as count_actions,
                       SUM(CASE WHEN l.time >= :timebefore THEN 1 ELSE 0 END) AS recent_actions,
                       MIN(l.time) as first_access,
                       MAX(l.time) as last_access
                  FROM {grade_curricular} gc
                  JOIN {grade_curricular_courses} gcc
                    ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                  JOIN (SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                               GROUP_CONCAT(DISTINCT c.id SEPARATOR ',') as str_courseids
                          FROM {groups} g
                          JOIN {groups_members} gm ON (gm.groupid = g.id)
                          JOIN {course} c ON (c.id = g.courseid)
                          JOIN {context} ctx ON (ctx.contextlevel = :contextlevel AND ctx.instanceid = c.id)
                          JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = gm.userid AND ra.roleid {$in_sql})
                          JOIN {user} u ON (u.id = ra.userid)
                         WHERE g.id IN ({$str_groupids})
                         GROUP BY u.id
                       ) uj
             LEFT JOIN {log} l ON (l.course = gcc.courseid AND l.userid = uj.id)
                 WHERE gc.id = :gradeid
              GROUP BY uj.id
              ORDER BY {$order}";
        return $DB->get_records_sql($sql, $params);
    }

    public static function get_students_groups($gradeid, $search = '', $studentsorderby = 'name') {
        global $DB, $CFG;

        $grade = $DB->get_record('grade_curricular', array('id' => $gradeid), '*', MUST_EXIST);
        if($grade->inscricoesactivityid > 0) {
            $roleids = explode(',', $CFG->gradebookroles);
            list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc
                       ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {inscricoes_activities} ia
                       ON (ia.id = gc.inscricoesactivityid)
                     JOIN {inscricoes_cohorts} ic
                       ON (ic.activityid = ia.id AND ic.roleid {$in_sql})
                     JOIN {cohort_members} chm
                       ON (chm.cohortid = ic.cohortid)
                     JOIN {user} u ON (u.id = chm.userid AND u.deleted = 0)";
            $where = "WHERE gc.id = :gradeid";
            $params['gradeid'] = $gradeid;
            $params['ignore'] = GC_IGNORE;
        } else if($grade->studentcohortid > 0) {
            $from = "FROM {grade_curricular} gc
                     JOIN {grade_curricular_courses} gcc
                       ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
                     JOIN {cohort_members} chm
                     JOIN {user} u
                       ON (u.id = chm.userid AND u.deleted = 0)";
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


        $join_group = "JOIN {groups} g ON (g.courseid = gcc.courseid)
                       JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = u.id)";

        if ($studentsorderby == 'name') {
            $orderby = "CONCAT(u.firstname, ' ', u.lastname)";
        } else {
            $orderby = $studentsorderby;
        }

        if (!empty($search)) {
            $where .= " AND CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $search . "%'";
        }

        //Distinct é necessário devido ao fato de um usuário poder ter mais de um role_assignment em um cursos, inclusive com o mesmo papel.
        $user_fields = implode(',', get_all_user_name_fields());
        $sql = "SELECT u.id, u.username, {$user_fields}, CONCAT(u.firstname, ' ', u.lastname) as fullname, g.name as groupname
                 {$from}
                 {$join_group}
                 {$where}
                ORDER BY {$orderby}";

        $records = $DB->get_recordset_sql($sql, $params);

        $students = array();
        foreach($records as $key => $record) {
            $student = new stdClass();
            $student->id = $record->id;
            $student->username = $record->username;
            $student->firstname = $record->firstname;
            $student->lastname = $record->lastname;
            $student->fullname = $record->fullname;
            $student->groupnames = $record->groupname;

            if (array_key_exists($student->id, $students)) {
                if (strpos($students[$student->id]->groupnames, $student->groupnames) === false) {
                    $students[$student->id]->groupnames .= ', ' . $student->groupnames;
                }
            } else {
                $students[$student->id] = $student;
            }
        }

        return $students;
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
                $groupnames[format_string($group->name)] = format_string($group->name);
            }
        }

        return $groupnames;
    }

    /**
     * Returns an array of grade curriculares associated to the student and courseid
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

    public static function get_potential_courses($category_path, $gradeid) {
        global $DB;

        $catids = explode('/', $category_path);
        unset($catids[0]);
        list($in_sql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);
        $sql = "SELECT c.id, c.shortname, c.fullname,
                       gcc.id AS gradecourseid, gcc.type, gcc.workload, gcc.inscribestartdate, gcc.inscribeenddate, gcc.coursedependencyid
                  FROM {course} c
             LEFT JOIN {grade_curricular_courses} gcc ON (gcc.courseid = c.id AND gcc.gradecurricularid = :gradeid)
                 WHERE c.category {$in_sql}
              ORDER BY c.fullname";
        $params['gradeid'] = $gradeid;
        return $DB->get_records_sql($sql, $params);
    }

    public static function get_grade_courses($gradeid, $only_active=false) {
        global $DB;

        $params = array('gradecurricularid'=>$gradeid);

        $where = '';
        if ($only_active) {
            $where = 'AND gcc.type != :ignore';
            $params['ignore'] = GC_IGNORE;
        }

        $sql = "SELECT c.id, c.shortname, c.fullname,
                       gcc.type, gcc.workload, gcc.inscribestartdate, gcc.inscribeenddate, gcc.coursedependencyid
                  FROM {grade_curricular_courses} gcc
                  JOIN {course} c ON (c.id = gcc.courseid)
                 WHERE gcc.gradecurricularid = :gradecurricularid
                   {$where}
              ORDER BY c.sortorder";
        
        return $DB->get_records_sql($sql, $params);
    }

    // retorna os nomes dos grupos dos cursos da grade curricular aos quais o usuário tem acesso
    // juntamente com uma lista de ids dos grupos com cada nome
    public static function get_groups($grade, $userid) {
        global $DB;

        $params = array('gradeid'=>$grade->id, 'ignore'=>GC_IGNORE);

        $context = context::instance_by_id($grade->contextid, MUST_EXIST);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            $join = '';
        } else {
            $join = "JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = :userid)";
            $params['userid'] = $userid;
        }

        $sql = "SELECT g.name, GROUP_CONCAT(g.id SEPARATOR ',') as str_groupids
                  FROM {grade_curricular} gc
                  JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id)
                  JOIN {groups} g ON (g.courseid = gcc.courseid)
                  {$join}
                 WHERE gc.id = :gradeid
                   AND gcc.type != :ignore
              GROUP BY g.name";
        return $DB->get_records_sql($sql, $params);
    }


    public static function get_cohorts($context) {
        global $DB;

        $ctxids = explode('/', $context->path);
        unset($ctxids[0]);
        list($in_sql, $params) = $DB->get_in_or_equal($ctxids, SQL_PARAMS_NAMED);

        $sql = "SELECT ch.id, ch.name
                  FROM {cohort} ch
                 WHERE ch.contextid {$in_sql}
              ORDER BY ch.name";
        return $DB->get_records_sql_menu($sql, $params);
    }

    public static function get_potential_activities($context, $gradeid) {
        global $DB;

        $plugins = core_component::get_plugin_list('local');
        if(!isset($plugins['inscricoes'])) {
            return array();
        }

        $ctxids = explode('/', $context->path);
        unset($ctxids[0]);
        list($in_sql, $params) = $DB->get_in_or_equal($ctxids, SQL_PARAMS_NAMED);

        $sql = "SELECT ia.id, ia.externalactivityname
                  FROM {context} ctx
                  JOIN {inscricoes_activities} ia ON (ia.contextid = ctx.id)
                 WHERE ctx.id {$in_sql}
                   AND NOT EXISTS (SELECT 1
                                     FROM {grade_curricular} gc
                                    WHERE gc.id != :gradeid
                                      AND gc.inscricoesactivityid = ia.id)
              ORDER BY ia.externalactivityname";
        $params['gradeid'] = $gradeid;
        return $DB->get_records_sql_menu($sql, $params);
    }

    public static function get_grades_curriculares($category) {
        global $DB;

        $catids = explode('/', $category->path);
        unset($catids[0]);
        list($in_sql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);

        $sql = "SELECT gc.*
                  FROM {course_categories} cc
                  JOIN {context} ctx ON (ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel)
                  JOIN {grade_curricular} gc ON (gc.contextid = ctx.id)
                 WHERE cc.id {$in_sql}
                    OR cc.path LIKE '%/{$category->id}/%'
              ORDER BY cc.depth";
        $params['contextlevel'] = CONTEXT_COURSECAT;
        return $DB->get_records_sql($sql, $params);
    }

    public static function get_tutors($grade, $str_groupids) {
        global $DB;

        $roleid = $DB->get_field('grade_curricular', 'tutorroleid', array('id'=>$grade->id));

        $sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) as fullname
                  FROM {groups} g
                  JOIN {groups_members} gm ON (gm.groupid = g.id)
                  JOIN {course} c ON (c.id = g.courseid)
                  JOIN {context} ctx ON (ctx.contextlevel = :contextlevel AND ctx.instanceid = c.id)
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.userid = gm.userid AND ra.roleid = :roleid)
                  JOIN {user} u ON (u.id = ra.userid)
                 WHERE g.id IN ({$str_groupids})";
        return $DB->get_records_sql($sql, array('contextlevel'=>CONTEXT_COURSE, 'roleid'=>$roleid));
    }

    public static function get_approved_students($grade_curricular, $students = array()) {
        $courses = self::get_courses($grade_curricular->id, true);

        $approved_students = self::verify_approved_students($grade_curricular, $courses, $students);

        return $approved_students;
    }

    public static function verify_approved_students($grade_curricular, $courses, $students) {
        $approved_students = array();

        $completions_info = self::get_completions_info($courses);

        if (empty($students)) {
            $students = self::get_students($grade_curricular->id);
        }

        foreach($students AS $userid=>$user) {
            $count_optative = 0;
            $approved = true;
            $student_courses = array();

            foreach($courses AS $courseid=>$course) {
                if ($course->type == GC_OPTIONAL) {
                    $context = context_course::instance($courseid);
                    if (is_enrolled($context, $user)) {
                        if ($completions_info[$courseid]->is_course_complete($userid)){
                            if ($course->workload > 0) {
                                $student_courses[$courseid] = $course->fullname;
                            }

                            $count_optative++;
                        }
                    }
                } elseif ($course->type == GC_MANDATORY) {
                    if ($completions_info[$courseid]->is_course_complete($userid)){
                        if ($course->workload > 0) {
                            $student_courses[$courseid] = $course->fullname;
                        }
                    } else {
                        $approved = false;
                    }
                }
            }

            if ($approved && ($count_optative >= $grade_curricular->minoptionalcourses)) {
                $user->courses = $student_courses;
                $approved_students[$userid] = $user;
            }
        }

        return $approved_students;
    }

    public static function get_completions_info($courses) {
        $completions_info = array();

        foreach($courses as $id => $course) {
            $completions_info[$id] = new completion_info($course);
        }

        return $completions_info;
    }

    public static function save_modules($contextid, $category, $formdata) {
        global $DB, $SESSION;

        if (confirm_sesskey()) {
            $context = context::instance_by_id($contextid, MUST_EXIST);
            require_capability('local/grade_curricular:configure', $context);

            $gradecurricularid = required_param('gradecurricularid', PARAM_INT);

            $record = new stdclass();
            $record->contextid = $contextid;
            $record->minoptionalcourses = $formdata->minoptionalcourses;
            $record->maxoptionalcourses = $formdata->maxoptionalcourses;
            $record->optionalatonetime = $formdata->optionalatonetime;
            $record->timemodified = time();

            if ($DB->record_exists('grade_curricular', array('id' => $gradecurricularid))) {
                $record->id = $gradecurricularid;
                try {
                    $DB->update_record('grade_curricular', $record);
                } catch (Exception $e) {
                    print_error($e);
                }
            } else {
              try {
                  $record->inscricoesactivityid = 0;
                  $record->tutorroleid = 0;
                  $record->studentcohortid = 0;
                  $record->notecourseid = 0;
                  $DB->insert_record('grade_curricular', $record);
              } catch (Exception  $e) {
                  print_error($e);
              }
            }

            $types = optional_param_array('type', array(), PARAM_INT);
            $workloads = optional_param_array('workload', array(), PARAM_INT);
            $dependencies = optional_param_array('dependencies', array(), PARAM_INT);

            $startdays = optional_param_array('startdays', array(), PARAM_INT);
            $startmonths = optional_param_array('startmonths', array(), PARAM_INT);
            $startyears = optional_param_array('startyears', array(), PARAM_INT);

            $enddays = optional_param_array('enddays', array(), PARAM_INT);
            $endmonths = optional_param_array('endmonths', array(), PARAM_INT);
            $endyears = optional_param_array('endyears', array(), PARAM_INT);

            $courses = self::get_potential_courses($category->path, $gradecurricularid);

            $courseids = array();
            foreach($courses as $c) {
                if(isset($types[$c->id])) {
                    $grade_course = new stdclass();
                    $grade_course->gradecurricularid = $gradecurricularid;
                    $grade_course->courseid = $c->id;

                    $grade_course->type = $types[$c->id];
                    $grade_course->workload = $workloads[$c->id];
                    if($grade_course->workload < 0 || $grade_course->workload > 360) {
                        $errors[$c->fullname][] = get_string('invalid_workload', 'local_grade_curricular');
                    }
                    $grade_course->coursedependencyid = $dependencies[$c->id];
                    if($dependencies[$c->id] > 0 && !in_array($types[$dependencies[$c->id]], array(GC_MANDATORY, GC_OPTIONAL))) {
                        $errors[$c->fullname][] = get_string('dependecy_not_opt_dem', 'local_grade_curricular');
                    }
                    $grade_course->inscribestartdate = make_timestamp($startyears[$c->id], $startmonths[$c->id], $startdays[$c->id]);
                    $grade_course->inscribeenddate = make_timestamp($endyears[$c->id], $endmonths[$c->id], $enddays[$c->id]);
                    if($grade_course->inscribeenddate < $grade_course->inscribestartdate) {
                        $errors[$c->fullname][] = get_string('end_before_start', 'local_grade_curricular');
                    }
                    $grade_course->timemodified = time();
                    if(empty($c->gradecourseid)) {
                        $DB->insert_record('grade_curricular_courses', $grade_course);
                    } else {
                        $grade_course->id = $c->gradecourseid;
                        $DB->update_record('grade_curricular_courses', $grade_course);
                    }
                    $courseids[] = $c->id;
                }
            }
            if(!empty($errors)) {
                $SESSION->errors = $errors;
            }

            if(empty($courseids)) {
                $DB->delete_records('grade_curricular_courses', array('gradecurricularid'=>$gradecurricularid));
            } else {
                list($not_in_sql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', false);
                $sql = "gradecurricularid = :gradecurricularid AND courseid {$not_in_sql}";
                $params['gradecurricularid'] = $gradecurricularid;
                $DB->delete_records_select('grade_curricular_courses', $sql, $params);
            }
        }
    }

    public static function save_cfg_grade($contextid, $formdata) {
        global $DB, $SESSION;

        if (confirm_sesskey()) {
            $context = context::instance_by_id($contextid, MUST_EXIST);
            require_capability('local/grade_curricular:configure', $context);

            $gradecurricularid = required_param('gradecurricularid', PARAM_INT);

            //check if the inscricoesactivityid and studentcohortid are set, otherwise, save 0.
            $inscricoesactivityid = optional_param('inscricoesactivityid', -1 ,PARAM_INT);
            $studentcohortid = optional_param('studentcohortid', -1 ,PARAM_INT);
            if ($inscricoesactivityid == -1) {
                $formdata->inscricoesactivityid = 0;
            }

            if ($studentcohortid == -1) {
                $formdata->studentcohortid = 0;
            }

            $record = new stdclass();
            $record->contextid = $contextid;
            $record->inscricoesactivityid = $formdata->inscricoesactivityid;
            $record->tutorroleid = $formdata->tutorroleid;
            $record->studentcohortid = $formdata->studentcohortid;
            $record->notecourseid = $formdata->notecourseid;
            $record->timemodified = time();

            if ($DB->record_exists('grade_curricular', array('id' => $gradecurricularid))) {
                $record->id = $gradecurricularid;
                try {
                    $DB->update_record('grade_curricular', $record);
                } catch (Exception $e) {
                    print_error($e);
                }
            } else {
              try {
                  $record->minoptionalcourses = 0;
                  $record->maxoptionalcourses = 0;
                  $record->optionalatonetime = 0;
                  $DB->insert_record('grade_curricular', $record);
              } catch (Exception  $e) {
                  print_error($e);
              }
            }
        }
    }
}
