<?php

function gc_get_cohorts($context) {
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

function gc_get_potential_editions($context, $gradeid) {
    global $DB;

    $plugins = core_component::get_plugin_list('local');
    if(!isset($plugins['inscricoes'])) {
        return array();
    }

    $ctxids = explode('/', $context->path);
    unset($ctxids[0]);
    list($in_sql, $params) = $DB->get_in_or_equal($ctxids, SQL_PARAMS_NAMED);

    $sql = "SELECT ie.id, ie.externaleditionname
              FROM {context} ctx
              JOIN {inscricoes_activities} ia ON (ia.contextid = ctx.id)
              JOIN {inscricoes_editions} ie ON (ie.activityid = ia.id)
             WHERE ctx.id {$in_sql}
               AND NOT EXISTS (SELECT 1
                                 FROM {grade_curricular} gc
                                WHERE gc.id != :gradeid
                                  AND gc.inscricoeseditionid = ie.id)
          ORDER BY ie.externaleditionname";
    $params['gradeid'] = $gradeid;
    return $DB->get_records_sql_menu($sql, $params);
}

function gc_get_grades_curriculares($category) {
    global $DB;

    $catids = explode('/', $category->path);
    unset($catids[0]);
    list($in_sql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED);

    $sql = "SELECT gc.*
              FROM {course_categories} cc
              JOIN {context} ctx ON (ctx.instanceid = cc.id AND ctx.contextlevel = :contextlevel)
              JOIN {grade_curricular} gc ON (gc.contextid = ctx.id)
             WHERE cc.id {$in_sql}
                OR cc.path LIKE '%/{$category->id}/%'";
    $params['contextlevel'] = CONTEXT_COURSECAT;
    return $DB->get_records_sql($sql, $params);
}

// return the courses that may be marked as optional or mandatory
function gc_get_potential_courses($category_path, $gradeid) {
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

function gc_get_grade_courses($gradeid, $only_active=false) {
    global $DB;

    $where = $only_active ? 'AND gcc.type IN (1, 2)' : '';

    $sql = "SELECT c.id, c.shortname, c.fullname,
                   gcc.type, gcc.workload, gcc.inscribestartdate, gcc.inscribeenddate, gcc.coursedependencyid
              FROM {grade_curricular_courses} gcc
              JOIN {course} c ON (c.id = gcc.courseid)
             WHERE gcc.gradecurricularid = :gradecurricularid
               {$where}";
    return $DB->get_records_sql($sql, array('gradecurricularid'=>$gradeid));
}

// retorna os nomes dos grupos dos cursos da grade curricular aos quais o usuário tem acesso
// juntamente com uma lista de ids dos grupos com cada nome
function gc_get_groups($grade, $userid) {
    global $DB;

    $params = array('gradeid'=>$grade->id);

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
               AND gcc.type IN (1, 2)
          GROUP BY g.name";
    return $DB->get_records_sql($sql, $params);
}

function gc_get_students($grade, $str_groupids, $days_before, $studentsorderby) {
    global $DB, $CFG;

    $roleids = explode(',', $CFG->gradebookroles);
    list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

    $timebefore = strtotime('-' . $days_before . ' days');
    $params['contextlevel'] = CONTEXT_COURSE;
    $params['gradeid'] = $grade->id;
    $params['timebefore'] = $timebefore;

    $order = $studentsorderby == 'lastaccess' ? 'last_access ASC' : 'fullname';
    $sql = "SELECT uj.id, uj.fullname, uj.str_courseids,
                   COUNT(*) as count_actions,
                   SUM(CASE WHEN l.time >= :timebefore THEN 1 ELSE 0 END) AS recent_actions,
                   MIN(l.time) as first_access,
                   MAX(l.time) as last_access
              FROM {grade_curricular} gc
              JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type IN (1, 2))
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

function gc_get_all_students($grade) {
    global $DB, $CFG;

    $roleids = explode(',', $CFG->gradebookroles);
    list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

    $params['contextlevel'] = CONTEXT_COURSE;
    $params['gradeid'] = $grade->id;

    $sql = "SELECT u.id, u.username, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                   GROUP_CONCAT(DISTINCT g.name SEPARATOR ';') as groupnames
              FROM {grade_curricular} gc
              JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type IN (1, 2))
              JOIN {context} ctx ON (ctx.instanceid = gcc.courseid AND ctx.contextlevel = :contextlevel)
              JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid {$in_sql})
              JOIN user u ON (u.id = ra.userid)
              JOIN {groups} g ON (g.courseid = gcc.courseid)
              JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = u.id)
             WHERE gc.id = :gradeid
          GROUP BY u.id
          ORDER BY firstname, lastname";
    return $DB->get_records_sql($sql, $params);
}

function gc_get_tutors($grade, $str_groupids) {
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

function gc_save($contextid, $category) {
    global $DB, $SESSION;

    $gradecurricularid = required_param('gradecurricularid', PARAM_INT);
    if($grade = $DB->get_record('grade_curricular', array('id'=>$gradecurricularid))) {
    } else {
        $grade = new stdclass();
    }

    $errors = array();

    $grade->minoptionalcourses = required_param('minoptionalcourses', PARAM_INT);
    $grade->maxoptionalcourses = required_param('maxoptionalcourses', PARAM_INT);
    $grade->optionalatonetime = required_param('optionalatonetime', PARAM_INT);
    $grade->inscricoeseditionid = required_param('inscricoeseditionid', PARAM_INT);
    $grade->studentcohortid = required_param('studentcohortid', PARAM_INT);
    $grade->tutorroleid = required_param('tutorroleid', PARAM_INT);
    $grade->notecourseid = required_param('notecourseid', PARAM_INT);
    $grade->timemodified = time();

    if($grade->minoptionalcourses > $grade->maxoptionalcourses) {
        $errors['Configuração'][] = 'Número mínimo de módulos optativos é superior ao máximo';
    }

    if($grade->inscricoeseditionid > 0 && $grade->studentcohortid > 0) {
        $grade->studentcohortid = 0;
        $errors['Configuração'][] = 'As opções de seleção de edição da atividade e do coorte de estudantes são incompatíveis. A opção de coorte de estudantes foi desativada.';
    }

    if(empty($grade->tutorroleid)) {
        $errors['Configuração'][] = 'Papel correspondente a turtor não foi selecionado';
    }

    if(isset($grade->id)) {
        $DB->update_record('grade_curricular', $grade);
    } else {
        $grade->contextid = $contextid;
        $grade->id = $DB->insert_record('grade_curricular', $grade);
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

    $courses = gc_get_potential_courses($category->path, $grade->id);

    $courseids = array();
    foreach($courses as $c) {
        if(isset($types[$c->id])) {
            $grade_course = new stdclass();
            $grade_course->gradecurricularid = $grade->id;
            $grade_course->courseid = $c->id;

            $grade_course->type = $types[$c->id];
            $grade_course->workload = $workloads[$c->id];
            if($grade_course->workload < 0 || $grade_course->workload > 360) {
                $errors[$c->fullname][] = get_string('invalid_workload', 'local_grade_curricular');
            }
            $grade_course->coursedependencyid = $dependencies[$c->id];
            if($dependencies[$c->id] > 0 && !in_array($types[$dependencies[$c->id]], array(1,2))) {
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
        $DB->delete_records('grade_curricular_courses', array('gradecurricularid'=>$grade->id));
    } else {
        list($not_in_sql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', false);
        $sql = "gradecurricularid = :gradecurricularid AND courseid {$not_in_sql}";
        $params['gradecurricularid'] = $grade->id;
        $DB->delete_records_select('grade_curricular_courses', $sql, $params);
    }
}
