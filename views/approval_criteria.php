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
    echo html_writer::empty_tag('hidden', array('action'=>'approval_criteria'));
    echo html_writer::start_tag('div', array('class'=>'approval_criteria_content'));
    
    if (!empty($courses_ob)) {
        echo html_writer::tag('h2', 'Cursos obrigatórios');
        
        echo "<div class='mandatory_chk'>";
            echo "<div>";
              echo "<input type='checkbox' name='approval_option'> Aprovação nos módulos selecionados </input>";
            echo "</div>";
            
            echo "<div>";
              echo "<input type='checkbox' name='average_option'> Média dos módulos selecionados </input>";
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
            $current_data[] = html_writer::empty_tag('input', array('type'=>'text', 'name'=>'peso[' . $course->id . ']', 'value'=>$weight, 'size'=>1));
            $current_data[] = $course->fullname;

            $table->data[] = $current_data;
        }

        echo html_writer::table($table);
    }

    if (!empty($courses_opt)) {
        echo html_writer::tag('h2', 'Cursos optativos');
        
        echo "<div class='optative_radio'>";
            
            echo "<div class='option_label'> Opções: </div>";
           
            echo "<div>";
              echo "<input type='radio' name='optative_approval_option'> Nenhuma </input>";
            echo "</div>";
            
            echo "<div>";
              echo "<input type='radio' name='optative_approval_option'> Aprovação nos módulos cursados </input>";
            echo "</div>";
            
            echo "<div>";
              echo "<input type='radio' name='optative_approval_option'> Média dos módulos cursados </input>";
            echo "</div>";
        
        echo "</div>";
    }
    
    echo html_writer::end_tag('div');
    
    echo "<input class='submit_button' type='submit' name='submit' value='Salvar'/>";
    
    echo html_writer::end_tag('form');
