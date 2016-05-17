@local @local_grade_curricular
Feature: save grade curricular config
    As admin
    I should see correct configuration after saving

    Background:
        Given the following "categories" exist:
            | name       | category | idnumber |
            | Category 1 | 0        | CAT1     |
        Given the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C101      | CAT1 |
            | Course 2 | C102      | CAT1 |
            | Course 3 | C103      | CAT1 |
        And I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        And I press "Continue"
        And I log out

    @javascript
    Scenario: configuração dos cursos Moodle saving
        When I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        And I follow "Expand all"
        Then I set the field "minoptionalcourses" to "1"
        And I set the field "maxoptionalcourses" to "1"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[1]/td[2]/select" to "1"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[1]/td[2]/select" to "2"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[1]/td[2]/select" to "2"

        Then I press "Save changes"

        When I follow "Expand all"
        Then I should see "Número mínimo de cursos Moodle optativos:"
        And the field "minoptionalcourses" matches value "1"
        And I should see "Número máximo de cursos Moodle optativos:"
        And the field "maxoptionalcourses" matches value "1"
        And I should see "Selecionar cursos Moodle optativos em bloco:"
        And the field "optionalatonetime" matches value "0"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[1]/td[2]/select" matches value "1"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[1]/td[2]/select" matches value "2"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[1]/td[2]/select" matches value "2"

    @javascript
    Scenario: configurações adicionais saving
        When I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        And I follow "Configurações adicionais"
        And I follow "Expand all"
        Then I set the field "id_inscricoesactivityid" to "0"
        And I set the field "id_studentcohortid" to "0"
        And I set the field "id_notecourseid" to "1"
        And I set the field "id_tutorroleid" to "1"

        Then I press "Save changes"

        When I follow "Expand all"
        Then the field "id_studentcohortid" matches value "0"
        And the field "id_studentcohortid" matches value "0"
        And the field "id_notecourseid" matches value "Course 1"
        And the field "id_tutorroleid" matches value "Manager"
