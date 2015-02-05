<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/grade_curricular/lib.php');

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
                OR cc.path LIKE '%/{$category->id}/%'
          ORDER BY cc.depth";
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
               {$where}";
    return $DB->get_records_sql($sql, $params);
}

// retorna os nomes dos grupos dos cursos da grade curricular aos quais o usuário tem acesso
// juntamente com uma lista de ids dos grupos com cada nome
function gc_get_groups($grade, $userid) {
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

function gc_get_students($grade, $str_groupids, $days_before, $studentsorderby) {
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

function gc_get_all_students($grade) {
    global $DB, $CFG;

    $roleids = explode(',', $CFG->gradebookroles);
    list($in_sql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);

    $params['contextlevel'] = CONTEXT_COURSE;
    $params['gradeid'] = $grade->id;
    $params['ignore'] = GC_IGNORE;

    $sql = "SELECT u.id, u.username, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                   GROUP_CONCAT(DISTINCT g.name SEPARATOR ';') as groupnames
              FROM {grade_curricular} gc
              JOIN {grade_curricular_courses} gcc ON (gcc.gradecurricularid = gc.id AND gcc.type != :ignore)
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

function gc_save_modules($contextid, $category) {
    global $DB, $SESSION;

    if (confirm_sesskey()) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        require_capability('local/grade_curricular:configure', $context);

        $gradecurricularid = required_param('gradecurricularid', PARAM_INT);

        $types = optional_param_array('type', array(), PARAM_INT);
        $workloads = optional_param_array('workload', array(), PARAM_INT);
        $dependencies = optional_param_array('dependencies', array(), PARAM_INT);

        $startdays = optional_param_array('startdays', array(), PARAM_INT);
        $startmonths = optional_param_array('startmonths', array(), PARAM_INT);
        $startyears = optional_param_array('startyears', array(), PARAM_INT);

        $enddays = optional_param_array('enddays', array(), PARAM_INT);
        $endmonths = optional_param_array('endmonths', array(), PARAM_INT);
        $endyears = optional_param_array('endyears', array(), PARAM_INT);

        $courses = gc_get_potential_courses($category->path, $gradecurricularid);

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

function gc_save_grade_options($contextid) {
    global $DB, $SESSION;

    if (confirm_sesskey()) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        require_capability('local/grade_curricular:configure', $context);

        $gradecurricularid = required_param('gradecurricularid', PARAM_INT);
        $grade = $DB->get_record('grade_curricular', array('id'=>$gradecurricularid), '*', MUST_EXIST);

        $errors = array();

        $grade->minoptionalcourses = required_param('minoptionalcourses', PARAM_INT);
        $grade->maxoptionalcourses = required_param('maxoptionalcourses', PARAM_INT);
        $grade->optionalatonetime = required_param('optionalatonetime', PARAM_INT);
        $grade->inscricoeseditionid = optional_param('inscricoeseditionid', 0, PARAM_INT);
        $grade->studentcohortid = optional_param('studentcohortid', 0, PARAM_INT);
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

        $DB->update_record('grade_curricular', $grade);
    }
}

function gc_save_approval_criteria($contextid) {
    global $DB, $SESSION;

    if (confirm_sesskey()) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        require_capability('local/grade_curricular:configure', $context);

        $record = new stdClass();
        $record->gradecurricularid = required_param('gradecurricularid', PARAM_INT);
        $record->mandatory_courses = optional_param('mandatory_courses', 0, PARAM_INT);
        $record->approval_option = optional_param('approval_option', 0, PARAM_INT);
        $record->average_option = optional_param('average_option', 0, PARAM_INT);
        $record->grade_option = optional_param('grade_option', 0, PARAM_INT);
        $record->optative_courses = optional_param('optative_courses', 0, PARAM_INT);
        $record->optative_approval_option = optional_param('optative_approval_option', '', PARAM_INT);
        $record->optative_grade_option = optional_param('optative_grade_option', 0, PARAM_INT);
        $selected_modules = optional_param_array('selected', array(), PARAM_INT);

        $selected_modules = optional_param_array('selected', array(), PARAM_INT);
        $weights = optional_param_array('weight', array(), PARAM_INT);
        
        $errors = array();

        if ($record->mandatory_courses) {
            if ($record->approval_option == 0 && $record->average_option == 0) {
                $errors['mandatory_options'] = "Ao menos uma destas opções deve ser marcada";
            } elseif(sizeof($selected_modules) == 0) {
                $errors['no_selected_modules'] = 'Ao menos uma destas opções deve ser selecionada';
            }

            if ($record->grade_option < 0) $record->grade_option = 0;
            if ($record->grade_option > 10) $record->grade_option = 10;
        }

        if ($record->optative_courses) {
            if ($record->optative_approval_option === "") {
                $errors['optative_options'] = "Ao menos uma destas opções deve ser marcada";
            }

            if ($record->optative_grade_option < 0) $record->optative_grade_option = 0;
            if ($record->optative_grade_option > 10) $record->optative_grade_option = 10;
        }


        if (empty($errors)) {
            $approval_criteria_id = 0;
            if ($approval_criteria = $DB->get_record('grade_curricular_ap_criteria', array('gradecurricularid'=>$record->gradecurricularid))) {
                $record->id = $approval_criteria->id;

                try {
                    $DB->update_record('grade_curricular_ap_criteria', $record);
                } catch (Exception $e) {
                    var_dump($e); exit;
                }

                $approval_criteria_id = $approval_criteria->id;
            } else {
                try {
                   $approval_criteria_id = $DB->insert_record('grade_curricular_ap_criteria', $record);
                } catch (Exception $e) {
                    var_dump($e); exit;
                }
            }
        } else {
            $SESSION->errors = $errors;
            $pre_load_selected_modules = $selected_modules;
            foreach ($pre_load_selected_modules as $courseid => $value) {
                  $pre_load_selected_modules[$courseid] = $weights[$courseid];
            }
            
            $record->selected = $pre_load_selected_modules;
            $SESSION->pre_load = $record;

            redirect(new moodle_url('/local/grade_curricular/index.php', array('contextid'=>$contextid, 'action'=>'approval_criteria')));
        }

        //saving selected modules and its weights;

        $saved_modules = $DB->get_records_menu('grade_curricular_ap_modules', array('approval_criteria_id'=>$approval_criteria_id), '', 'id, moduleid');

        if (!empty($selected_modules)) {
            foreach ($selected_modules as $sm) {
                $module = new stdClass();
                $module->moduleid = $sm;
                $module->weight = $weights[$sm] > 0 ? $weights[$sm] : 0;
                $module->selected = 1;

                if ($module_to_update = $DB->get_record('grade_curricular_ap_modules', array('approval_criteria_id'=>$approval_criteria_id, 'moduleid'=>$sm))) {
                    $module->id = $module_to_update->id;
                    $module->approval_criteria_id = $module_to_update->approval_criteria_id;
                    $DB->update_record('grade_curricular_ap_modules', $module);
                    unset($saved_modules[$module->id]);
                } else {
                    $module->approval_criteria_id = $approval_criteria_id;
                    $DB->insert_record('grade_curricular_ap_modules', $module);
                }
            }
        }

        foreach ($saved_modules as $moduleid) {
            $DB->set_field('grade_curricular_ap_modules', 'selected', 0, array('moduleid'=>$moduleid));
        }
    }

    return $errors;
}

//Certificates functions
function gc_get_students_to_certificate($grade_curricular, $students = array()) {
    global $DB;

    $approved_students = get_approved_students($grade_curricular, $students);

    $modules_to_cert = array();
    
    if ($sending_criteria = $DB->get_record('cert_sending_criteria', array('gradecurricularid'=>$grade_curricular->id))) {
        $modules_to_cert = $DB->get_records('cert_modules_to_cert', array('sending_criteria_id'=>$sending_criteria->id));
    }

    //obrigatórios
    $mandatory_courses = array();    
    foreach ($modules_to_cert as $mc) {
        $mandatory_courses[$mc->moduleid] = $DB->get_record('course', array('id'=>$mc->moduleid));
    }
    
    $approved_students = verify_approval($approved_students, $grade_curricular, $mandatory_courses, GC_MANDATORY);
    
    //optativos
    $courses = gc_get_grade_courses($grade_curricular->id, true);
    
    $optative_courses = array();
    foreach ($courses as $courseid => $course) {
        if ($course->type == GC_OPTIONAL) $optative_courses[$courseid] = $course;
    }

    $approved_students = verify_approval($approved_students, $grade_curricular, $optative_courses, GC_OPTIONAL);

    return $approved_students;
}

function verify_approval($approved_students, $grade_curricular, $courses, $modules_type) {
    global $DB;
        
    $completions_info = get_completions_info($courses);
    
    foreach ($approved_students as $as) {
        if (!isset($approved_students[$as->id]->mandatory_approved_courses)) $approved_students[$as->id]->mandatory_approved_courses = array();
        if (!isset($approved_students[$as->id]->mandatory_not_approved_courses)) $approved_students[$as->id]->mandatory_not_approved_courses = array();
        if (!isset($approved_students[$as->id]->optative_approved_courses)) $approved_students[$as->id]->optative_approved_courses = array();
        if (!isset($approved_students[$as->id]->optative_not_approved_courses)) $approved_students[$as->id]->optative_not_approved_courses = array();
        
        foreach ($courses as $c) {
            if ($completions_info[$c->id]->is_course_complete($as->id)){
                if ($modules_type == GC_MANDATORY) {
                    $approved_students[$as->id]->mandatory_approved_courses[$c->id] = $c->fullname;
                } elseif ($modules_type == GC_OPTIONAL) {
                    $approved_students[$as->id]->optative_approved_courses[$c->id] = $c->fullname;
                }
            } else {
                if ($modules_type == GC_MANDATORY) {
                    $approved_students[$as->id]->mandatory_not_approved_courses[$c->id] = $c->fullname;
                } elseif ($modules_type == GC_OPTIONAL) {
                    $approved_students[$as->id]->optative_not_approved_courses[$c->id] = $c->fullname;
                }
            }
        }
    }

    return $approved_students;
}

function get_approved_students($grade_curricular, $students) {
    global $DB;

    $mandatory_modules = $optative_modules = $approval_criteria = $approval_modules = array();
    
    $courses = gc_get_grade_courses($grade_curricular->id, true);
    
    if ($approval_criteria = $DB->get_record('grade_curricular_ap_criteria', array('gradecurricularid'=>$grade_curricular->id))) { 
        $approval_modules = $DB->get_records_menu('grade_curricular_ap_modules', array(
                                                  'approval_criteria_id'=>$approval_criteria->id, 
                                                  'selected'=>1), '', 'moduleid, weight');
    } else {
        //TODO Se isso acontecer, provavelmente a grade curricular não foi corretamente configurada.
        return array();    
    }
    
    foreach ($courses as $courseid => $course) {
        if ($course->type == GC_MANDATORY) {
            if (array_key_exists($courseid, $approval_modules)) {
                $mandatory_modules[$courseid] = $course;
            }
        } elseif ($course->type == GC_OPTIONAL) {
            $optative_modules[$courseid] = $course;
        }
    }
    
    $data_to_send = $mandatory_data = $optative_data = array();
    
    //Verifica se existem cursos obrigatórios, e se os mesmos foram marcados para serem considerados nos critérios de aprovação.
    if (!empty($mandatory_modules) && ($approval_criteria->mandatory_courses))
        $mandatory_data = get_approved_students_by_module_type($grade_curricular, $mandatory_modules, $module_type = GC_MANDATORY, $approval_criteria, $approval_modules, $students);
    //Verifica se existem cursos optativos, e se os mesmos foram marcados para serem considerados nos critérios de aprovação.
    if (!empty($optative_modules) && ($approval_criteria->optative_courses))
        $optative_data = get_approved_students_by_module_type($grade_curricular, $optative_modules, $module_type = GC_OPTIONAL, $approval_criteria, $approval_modules, $students);

    $approved_students = array();
    
    //Se os cursos optativos e obrigatórios vão ser considerados.
    if ($approval_criteria->mandatory_courses && $approval_criteria->optative_courses) {
        //Se existem cursos optativos e obrigatórios.
        if (!empty($mandatory_modules) && !empty($optative_modules))
            $approved_students = array_intersect_key($mandatory_data, $optative_data);
        //Se existem apenas cursos obrigatórios.
        elseif (!empty($mandatory_modules))
            $approved_students = $mandatory_data;
        //Se existem apenas cursos optativos.  
        elseif (!empty($optative_modules))
            $approved_students = $optative_data;

        //Se não existem cursos optativos e obrigatórios.  
        return $approved_students;
    
    //Se apenas os cursos obrigatórios vão ser considerados.
    } elseif ($approval_criteria->mandatory_courses) {
        //Se existem cursos obrigatórios.
        if (!empty($mandatory_modules))
            $approved_students = $mandatory_data; 

        //Se não existem cursos obrigatórios.  
        return $approved_students;

    //Se apenas os cursos optativos vão ser considerados.  
    } elseif ($approval_criteria->optative_courses) {
        //Se existem cursos optativos.
        if (!empty($optative_modules))
            $approved_students = $optative_data;

        //Se não existem cursos optativos.  
        return $approved_students;
    }

    return $approved_students;
}

function prepare_course_grade(&$courses, $approval_modules) {
    $grade_items = array();
    
    foreach($courses AS $courseid=>$course) {
        $course->grade_item = grade_item::fetch_course_item($courseid);
        
        // Se o módulo for do tipo um, então é obrigatório, e caso seu peso não esteja definido, o seu valor será igual a zero.
        // Se o módulo for do tipo dois, então é optativo, e seu peso é igual a um.
        if ($course->type == GC_MANDATORY) {
            $course->grade_item->aggregationcoef = isset($approval_modules[$courseid]) ? $approval_modules[$courseid] : 0;        
        } elseif ($course->type == GC_OPTIONAL) {
            $course->grade_item->aggregationcoef = 1;        
        }

        $grade_items[$course->grade_item->id] =& $course->grade_item;
    }

    return $grade_items;
}

function get_approved_students_by_module_type($grade_curricular, $courses, $module_type, $approval_criteria, $approval_modules = array(), $students) {
    global $DB;
    
    $completions_info = get_completions_info($courses);
      
    $grade_items = prepare_course_grade($courses, $approval_modules);

    $grade_category = new grade_category();
    $grade_category->aggregation = GRADE_AGGREGATE_WEIGHTED_MEAN;
    
    if (empty($students)) {
        $students = gc_get_all_students($grade_curricular);
    }
    
    $approved_on_selected_modules = array();
    $approved_with_score = array(); 
    
    foreach($students AS $userid=>$user) {
        $approved_courses = array();
        $not_approved_courses = array();
        $count_optional = 0;
        $approved = true;

        $grade_values = array();
        foreach($courses AS $courseid=>$course) {
            $course_grade = new grade_grade(array('itemid'=>$course->grade_item->id, 'userid'=>$userid));
            $finalgrade = $course_grade->finalgrade;
            $grade = grade_format_gradevalue($finalgrade, $course->grade_item, true);
            $grade_values[$course->grade_item->id] = grade_grade::standardise_score($finalgrade, 
                                                                                    $course->grade_item->grademin, 
                                                                                    $course->grade_item->grademax, 0, 10);
            
            if ($module_type == GC_OPTIONAL) {
                $context = context_course::instance($courseid); 
                if (is_enrolled($context, $user)) {
                    if ($completions_info[$courseid]->is_course_complete($userid)){
                        $approved_courses[$courseid] = $course->fullname;
                        $count_optional++;
                    } else {
                        $not_approved_courses[$courseid] = $course->fullname;
                        $approved = false;
                    }
                }
            } elseif ($module_type == GC_MANDATORY) {
                if ($completions_info[$courseid]->is_course_complete($userid)){
                    $approved_courses[$courseid] = $course->fullname;
                } else {
                    $not_approved_courses[$courseid] = $course->fullname;
                    $approved = false;
                }
            }
        }
          
        if ($approved) { 
            if ($module_type == GC_OPTIONAL) {
                if ($count_optional >= $grade_curricular->minoptionalcourses) {
                    $approved_on_selected_modules[$userid] = $user;
                } else {
                    $not_approved_on_selected_modules[$userid] = $user;
                }
            } else {
                $approved_on_selected_modules[$userid] = $user;
            }
        } else {
            $not_approved_on_selected_modules[$userid] = $user;
        }
           
        asort($grade_values, SORT_NUMERIC);
        $aggregated_grade = $grade_category->aggregate_values($grade_values, $grade_items);
        $aggregated_grade = round($aggregated_grade, 1);
        
        if ($module_type == GC_MANDATORY) {
            if ($aggregated_grade >= $approval_criteria->grade_option) {
                $approved_with_score[$userid] = $user;  
            } else {
                $not_approved_with_score[$userid] = $user;  
            }
        } elseif ($module_type == GC_OPTIONAL) {
            if ($aggregated_grade >= $approval_criteria->optative_grade_option) {
                $approved_with_score[$userid] = $user;  
            } else {
                $not_approved_with_score[$userid] = $user;  
            }
        }
    }
      
    $users_to_send = array();
      
    //Verificar quais usuários serão enviados.
    if ($module_type == GC_MANDATORY) {
        if ($approval_criteria->approval_option && $approval_criteria->average_option) 
            $users_to_send = array_intersect_key($approved_on_selected_modules, $approved_with_score);
        elseif ($approval_criteria->approval_option)
            $users_to_send = $approved_on_selected_modules;
        elseif ($approval_criteria->average_option)
            $users_to_send = $approved_with_score;
    } elseif ($module_type == GC_OPTIONAL) {
        if ($approval_criteria->optative_approval_option == 1)
            $users_to_send = $approved_on_selected_modules;
        elseif ($approval_criteria->optative_approval_option == 2)
            $users_to_send = $approved_with_score;
    }
      
    return $users_to_send;
}

function get_completions_info($courses) {
    $completions_info = array();

    foreach($courses AS $id=>$course) {
        $completions_info[$id] = new completion_info($course);
    }

    return $completions_info;
}
