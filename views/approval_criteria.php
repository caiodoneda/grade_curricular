<?php
    defined('MOODLE_INTERNAL') || die();

    $courses = local_grade_curricular::get_courses($grade->id, true);
    $courses_ob = array();
    $courses_opt = array();

    foreach($courses as $course){
            $course->type == "1" ? $courses_ob[] = $course : $courses_opt[] = $course;
    }

    $gc_approval_modules = array();

    if (isset($SESSION->pre_load)) {
        $gc_approval_criteria =  new stdClass();
        isset($SESSION->pre_load->mandatory_courses) ? $gc_approval_criteria->mandatory_courses = $SESSION->pre_load->mandatory_courses : $gc_approval_criteria->mandatory_courses = 0;
        isset($SESSION->pre_load->approval_option) ? $gc_approval_criteria->approval_option = $SESSION->pre_load->approval_option : $gc_approval_criteria->approval_option = 0;
        isset($SESSION->pre_load->average_option) ? $gc_approval_criteria->average_option = $SESSION->pre_load->average_option : $gc_approval_criteria->average_option = 0;
        isset($SESSION->pre_load->grade_option) ? $gc_approval_criteria->grade_option = $SESSION->pre_load->grade_option : $gc_approval_criteria->grade_option = 0;
        isset($SESSION->pre_load->optative_courses) ? $gc_approval_criteria->optative_courses = $SESSION->pre_load->optative_courses : $gc_approval_criteria->optative_courses = 0;
        isset($SESSION->pre_load->optative_approval_option) ? $gc_approval_criteria->optative_approval_option = $SESSION->pre_load->optative_approval_option : $gc_approval_criteria->optative_approval_option = 0;
        isset($SESSION->pre_load->optative_average_option) ? $gc_approval_criteria->optative_average_option = $SESSION->pre_load->optative_average_option : $gc_approval_criteria->optative_average_option = 0;
        isset($SESSION->pre_load->optative_grade_option) ? $gc_approval_criteria->optative_grade_option = $SESSION->pre_load->optative_grade_option : $gc_approval_criteria->optative_grade_option = 0;
        isset($SESSION->pre_load->selected) ? $gc_approval_modules = $SESSION->pre_load->selected : $gc_approval_modules = array();
        unset($SESSION->pre_load);
    } elseif ($gc_approval_criteria = $DB->get_record('grade_curricular_ap_criteria', array('gradecurricularid'=>$grade->id))) {
              $gc_approval_modules = $DB->get_records_menu('grade_curricular_ap_modules', array('approval_criteria_id'=>$gc_approval_criteria->id,
                                                                                                'selected'=>1), '', 'moduleid, weight');
    } else {
        $gc_approval_criteria =  new stdClass();
        $gc_approval_criteria->mandatory_courses = 0;
        $gc_approval_criteria->approval_option = 0;
        $gc_approval_criteria->average_option = 0;
        $gc_approval_criteria->grade_option = 0;
        $gc_approval_criteria->optative_courses = 0;
        $gc_approval_criteria->optative_approval_option = 0;
        $gc_approval_criteria->optative_average_option = 0;
        $gc_approval_criteria->optative_grade_option = 0;
        $gc_approval_modules = array();
    }

    echo "<link href='./css/approval_criteria.css' rel='stylesheet'>";

    echo html_writer::start_tag('form', array('method'=>'post', 'action'=>$baseurl));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'approval_criteria'));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'gradecurricularid', 'value'=>$grade->id));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
    echo html_writer::start_tag('div', array('class'=>'approval_criteria_form'));
    echo html_writer::start_tag('div', array('class'=>'approval_criteria_content'));

    if (!empty($courses_ob)) {
        echo html_writer::tag('h2', 'Cursos obrigatórios', array('class' => 'course_type_header'));
        echo "<div>";
        if ($gc_approval_criteria->mandatory_courses == 1) {
            echo "<input id='mandatory_courses_checkbox' type='checkbox' class='consider_checkbox' name='mandatory_courses' value = 1 checked> <label> Considerar módulos obrigatórios nos critérios de aprovação </label> </input>";
        } else {
            echo "<input id='mandatory_courses_checkbox' type='checkbox' class='consider_checkbox' name='mandatory_courses' value = 1> <label> Considerar módulos obrigatórios nos critérios de aprovação </label> </input>";
        }
        echo "</div>";

        echo html_writer::start_tag('div', array('class'=>'approval_criteria_block mandatory_block'));

        $class = '';
        if (isset($SESSION->errors['mandatory_options'])) {
            $class = 'required_fields';
        }

        echo "<div class='mandatory_chk'>";
            echo "<div>";
                if($gc_approval_criteria->approval_option == 1) {
                    echo "<input type='checkbox' name='approval_option' value=1 checked> <label class=$class> Aprovação nos módulos selecionados </label> </input>";
                } else {
                    echo "<input type='checkbox' name='approval_option' value=1> <label class=$class> Aprovação nos módulos selecionados </label> </input>";
                }
            echo "</div>";

            echo "<div>";
                if($gc_approval_criteria->average_option == 1) {
                    echo "<input class='average_option' type='checkbox' name='average_option' value=1 checked> <label class=$class> Média dos módulos selecionados </label> </input>";
                } else {
                    echo "<input class='average_option' type='checkbox' name='average_option' value=1> <label class=$class> Média dos módulos selecionados </label> </input>";
                }
            echo "</div>";
        echo "</div>";

        $grade_option = (isset($gc_approval_criteria->grade_option)) ? $gc_approval_criteria->grade_option : 0;

        echo "<div class='grade_option'>";
            echo "<label>Nota: </label> <input type='text' name='grade_option' value=" .$grade_option. " size=1></input>";
        echo "</div>";

        $table = new html_table();
        $table->head = array('', 'Peso', 'Módulo');
        $table->size = array('5%','10%','85%');

        if(isset($SESSION->errors['no_selected_modules'])) {
            $class = 'required_fields';
        }

        foreach ($courses_ob as $course) {
            $current_data = array();
            if (array_key_exists($course->id, $gc_approval_modules)) {
                $weight = $gc_approval_modules[$course->id];
                $current_data[] = html_writer::empty_tag('input', array('type'=>'checkbox', 'checked'=>'checked',
                                                         'name'=>'selected[' . $course->id . ']', 'value'=>$course->id));
            } else {
                $weight = 1;
                $current_data[] = html_writer::empty_tag('input', array('type'=>'checkbox','name'=>'selected[' . $course->id . ']',
                                                         'value'=>$course->id));
            }
            $current_data[] = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'weight[' . $course->id . ']', 'value'=>$weight, 'size'=>1));
            $current_data[] = "<label class=$class> $course->fullname </label>";

            $table->data[] = $current_data;
        }

        echo html_writer::table($table);

        echo html_writer::end_tag('div');
    }
    if (!empty($courses_opt)) {
        echo html_writer::tag('h2', 'Cursos optativos', array('class'=>'course_type_header'));

        echo "<div>";
            if($gc_approval_criteria->optative_courses == 1) {
                echo "<input id='optative_courses_checkbox' type='checkbox' class='consider_checkbox' name='optative_courses' value=1 checked> <label> Considerar módulos optativos nos critérios de aprovação </label> </input>";
            } else {
                echo "<input id='optative_courses_checkbox' type='checkbox' class='consider_checkbox' name='optative_courses' value=1> <label> Considerar módulos optativos nos critérios de aprovação </label> </input>";

            }
        echo "</div>";

        echo html_writer::start_tag('div', array('class'=>'approval_criteria_block optative_block'));
            $opt1 = $opt2 = '';

            if (isset($gc_approval_criteria->optative_approval_option)) {
                switch ($gc_approval_criteria->optative_approval_option) {
                    case 1:
                        $opt1 = 'checked';
                        break;
                    case 2:
                        $opt2 = 'checked';
                        break;
                }
            }

            $class = '';
            if (isset($SESSION->errors['optative_options'])) {
                $class = 'required_fields';
                $opt1 = '';
                $opt2 = '';
            }

            echo "<div class='optative_radio'>";
                echo "<div>";
                    echo "<input type='radio' name='optative_approval_option' value=1 ".$opt1."> <label class = ".$class."> Aprovação nos módulos cursados </label> </input>";
                echo "</div>";

                echo "<div>";
                    echo "<input class='optative_approval_option' type='radio' name='optative_approval_option' value=2 ".$opt2."> <label class = ".$class."> Média dos módulos cursados </label></input>";
                echo "</div>";
            echo "</div>";

            echo "<div class='optative_grade_option'>";
                echo "<label>Nota: </label> <input type='text' name='optative_grade_option' value=".$gc_approval_criteria->optative_grade_option. " size=1></input>";
            echo "</div>";

        echo html_writer::end_tag('div');
    }

    if (empty($courses_ob) && empty($courses_opt)) {
        echo html_writer::tag('h2', 'Não existem cursos nessa grade curricular.', array('class'=>'course_type_header'));
    }

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    echo "<input class='submit_button' type='submit' name='save_approval_criteria' value='Salvar'/>";

    if(isset($SESSION->errors['mandatory_options']) || isset($SESSION->errors['optative_options']) || isset($SESSION->errors['no_selected_modules'])) {
        echo '<label class=required_fields_msg> Ao menos uma das opções deve ser selecionada </label>';
        unset($SESSION->errors);
    }

    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'savechanges', 'value'=>'save_approval_criteria'));

    echo html_writer::end_tag('form');

    echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js'></script>";
    echo "<script src='js/approval_criteria.js'></script> ";