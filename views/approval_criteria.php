<?php
    defined('MOODLE_INTERNAL') || die();

    $courses = gc_get_grade_courses($grade->id, true);
    $courses_ob = array();	
    $courses_opt = array();	

    foreach($courses as $course){
            $course->type == "1" ? $courses_ob[] = $course : $courses_opt[] = $course;
    }
    
    $gc_approval_criteria = $gc_approval_modules = array();

    $gc_approval_criteria = $DB->get_record('gc_approval_criteria', array('gradecurricularid'=>$grade->id));
    
    if (!empty($gc_approval_criteria)) {
        $gc_approval_modules = $DB->get_records_menu('gc_approval_modules', 
                               array('approval_criteria_id'=>$gc_approval_criteria->id, 'selected'=>1), '', 'moduleid, weight');
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
        echo html_writer::start_tag('div', array('class'=>'approval_criteria_block'));    
            $gc_approval_criteria->approval_option == 1 ? $approval_option_checked = 'checked' : $approval_option_checked = '';
            $gc_approval_criteria->average_option == 1 ? $average_option_checked = 'checked' : $average_option_checked = '';

            $class = '';
            if (isset($SESSION->errors['mandatory_options'])) {
                echo "<label class='error'>" .$SESSION->errors['mandatory_options']. "</label>";
                $class = 'error';
                $approval_option_checked = '';
                $average_option_checked = '';
                unset($SESSION->errors['mandatory_options']);
            } 
          
            echo "<div class='mandatory_chk " .$class. "'>";
                echo "<div>";
                  echo "<input type='checkbox' name='approval_option' value=1 " .$approval_option_checked. "> <label> Aprovação nos módulos selecionados </label> </input>";
                echo "</div>";
                
                echo "<div>";
                  echo "<input type='checkbox' name='average_option' value=1 " .$average_option_checked. "> <label> Média dos módulos selecionados </label> </input>";
                echo "</div>";
            echo "</div>";   
            
            $table = new html_table();
            $table->head = array('', 'Peso', 'Módulo');
            $table->align = array('center', 'center', 'center');
            $table->size = array('5%','10%','85%');
            //$table->attributes['style'] = "width:50%;";

            foreach($courses_ob as $course){
                $current_data = array();
                
                if (array_key_exists($course->id, $gc_approval_modules)) {
                    $current_data[] = html_writer::empty_tag('input', array('type'=>'checkbox', 'checked'=>'checked', 
                                                             'name'=>'selected[' . $course->id . ']', 'value'=>$course->id));
                    $weight = $gc_approval_modules[$course->id];
                } else {
                    $current_data[] = html_writer::empty_tag('input', array('type'=>'checkbox','name'=>'selected[' . $course->id . ']', 
                                                             'value'=>$course->id));
                    $weight = 1;
                }
                $current_data[] = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'weight[' . $course->id . ']', 'value'=>$weight, 'size'=>1));
                $current_data[] = $course->fullname;

                $table->data[] = $current_data;
            }

            echo html_writer::table($table);

        echo html_writer::end_tag('div');
    }

    if (!empty($courses_opt)) {
        echo html_writer::tag('h2', 'Cursos optativos', array('class'=>'course_type_header'));
        
        echo html_writer::start_tag('div', array('class'=>'approval_criteria_block'));
            $opt1 = $opt2 = $opt3 = '';
            switch ($gc_approval_criteria->optative_approval_option) {
                case 0:
                    $opt1 = 'checked';
                    break;
                case 1:
                    $opt2 = 'checked';
                    break;
                case 2:
                    $opt3 = 'checked';
                    break;
            }

            $class = '';
            if (isset($SESSION->errors['optative_options'])) {
                echo "<label class='error'>" .$SESSION->errors['optative_options']. "</label>";
                $class = 'error';
                $opt1 = '';
                $opt2 = '';
                $opt3 = '';
                unset($SESSION->errors['optative_options']);
            } 

            echo "<div class='optative_radio " .$class. "'>";
                
                echo "<div class='option_label'> Opções: </div>";
               
                echo "<div>";
                  echo "<input type='radio' name='optative_approval_option' value=0 ".$opt1."> <label> Nenhuma </label> </input>";
                echo "</div>";
                
                echo "<div>";
                  echo "<input type='radio' name='optative_approval_option' value=1 ".$opt2."> Aprovação nos módulos cursados </input>";
                echo "</div>";
                
                echo "<div>";
                  echo "<input type='radio' name='optative_approval_option' value=2 ".$opt3."> Média dos módulos cursados </input>";
                echo "</div>";
            
            echo "</div>";

        echo html_writer::end_tag('div');
    }

    if (empty($courses_ob) && empty($courses_opt)) {
        echo html_writer::tag('h2', 'Não existem cursos nessa grade curricular.', array('class'=>'course_type_header'));
    }
    
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    
    echo "<input class='submit_button' type='submit' name='save_approval_criteria' value='Salvar'/>";
    echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'savechanges', 'value'=>'save_approval_criteria'));

    echo html_writer::end_tag('form');
