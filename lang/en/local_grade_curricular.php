<?php // $Id$

$string['pluginname'] = 'Grade Curricular';
$string['pluginname_desc'] = 'Define a grade curricular de um curso';
$string['menu_title'] = 'Grade curricular';
$string['curriculumcontrol'] = 'Controle Curricular';

$string['grade_curricular:view'] = 'Visualizar grade curricular';
$string['grade_curricular:configure'] = 'Configurar grade curricular';

$string['another'] = 'Há grade curricular definida nas categorias listadas abaixo,
    as quais estão hierarquicamente acima ou abaixo da categoria atual.';
$string['no_grade_curricular'] = 'Não há grade curricular definida nesta categoria ou em categoria acima.';
$string['createconfirm'] = 'Criar uma nova grade curricular associada a esta categoria?';

$string['errors'] = 'Há valores inválidos para os campos indicados abaixo';
$string['invalid_workload'] = 'Carga horária deve estar no intervalo [0..360]';
$string['dependecy_not_opt_dem'] = 'Pré-requisito deve ser um curso Moodle obrigatório ou optativo';
$string['end_before_start'] = 'Data de final de inscrições é anterior à de início';

$string['configure_courses'] = 'Configuração dos cursos Moodle';
$string['inscricoesactivityid'] = 'Sistema de inscrições ';
$string['studentrole'] = 'Papel de estudantes: ';
$string['minoptionalcourses'] = 'Número mínimo de cursos Moodle optativos: ';
$string['maxoptionalcourses'] = 'Número máximo de cursos Moodle optativos: ';
$string['optionalatonetime'] = 'Selecionar cursos Moodle optativos em bloco: ';
$string['tutors_notes'] = 'Anotações de tutores sobre estudantes';
$string['studentcohort'] = 'Coorte de estudantes: ';
$string['tutorrole'] = 'Papel de tutor: ';
$string['notecourse'] = 'curso Moodle onde serão guardadas as anotações dos tutores: ';
$string['no_notecourse'] = '-- Não serão feitas anotações pelos tutores';
$string['students_selection'] = 'Seleção de estudantes ';

$string['mandatory'] = 'obrigatório';
$string['optative_modules'] = 'cursos Moodle optativos ';
$string['shorttype_1'] = 'obr';
$string['type_1'] = 'obrigatório';
$string['optional'] = 'optativo';
$string['type_2'] = 'optativo';
$string['shorttype_2'] = 'opt';
$string['tcc'] = 'Trabalho de conclusão de curso';
$string['type_3'] = 'Trabalho de conclusão de curso';
$string['shorttype_3'] = 'TCC';
$string['ignore'] = 'não considerar';

$string['activity'] = 'Atividade no Sistema de Inscrições: ';
$string['no_activity'] = '-- Não relacionar a atividade';
$string['no_cohort'] = '-- Nenhum cohort selecionado';

$string['coursename'] = 'Nome do curso Moodle';
$string['type'] = 'Tipo';
$string['workload'] = 'Carga (horas, créditos, etc)';
$string['dependency'] = 'Pré-requisito p/ inscrição';
$string['inscribeperiodo'] = 'Périodo de inscrições';
$string['configurations'] = 'Configurações';
$string['tutor'] = 'Tutor';
$string['tutors'] = 'Tutores';

//tabs names

$string['modules'] = 'Configuração dos cursos Moodle';
$string['gradecurricular_additional'] = 'Configurações adicionais';

//help buttons
$string['minoptionalcourses_help'] = 'Número mínimo de cursos optativos que o estudante tem que se inscrever e ser aprovado, para que seja aprovado na grade curricular.';
$string['maxoptionalcourses_help'] = 'Número máximo de cursos optativos em que o estudante pode se inscrever.';
$string['optionalatonetime_help'] = 'Help';
$string['studentcohort_help'] = 'Help';
$string['tutorrole_help'] = 'Help';
$string['inscricoesactivityid_help'] = 'Sistema de inscrições ';
$string['students_selection_help'] = 'Seleção de estudantes ';
$string['notecourse_help'] = 'Anotações de tutores sobre estudantes';

//errors
$string['minoptionalcourseserror'] = 'Esse valor precisa ser menor ou igual ao valor definido no campo "Número máximo de cursos Moodle optativos".';
$string['maxoptionalcourseserror'] = 'Esse valor precisa ser maior ou igual ao número mínimo de cursos Moodle optativos. ';

$string['morethanerror'] = 'Esse valor precisa ser menor ou igual ao número de cursos Moodle optativos existentes.';