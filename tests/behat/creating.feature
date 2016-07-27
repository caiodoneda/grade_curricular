@local @local_grade_curricular
Feature: Create a grade curricular,
    As admin
    I should see the configuration forms

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
    Scenario: Grade curricular creation working
        When I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        Then I should see "cursos Moodle optativos"
        And I should see "cursos Moodle"
        When I follow "Expand all"
        Then I should see "Número mínimo de cursos Moodle optativos:"
        And the field "minoptionalcourses" matches value "0"
        And I should see "Número máximo de cursos Moodle optativos:"
        And the field "maxoptionalcourses" matches value "0"
        And I should see "Selecionar cursos Moodle optativos em bloco:"
        And the field "optionalatonetime" matches value "0"
        And I should see "Course 1"
        And I should see "Course 2"
        And I should see "Course 3"
        When I follow "Configurações adicionais"
        And I follow "Expand all"
        Then I should see "Coorte de estudantes:"
        And the field "studentcohortid" matches value "-- Nenhum cohort selecionado"
        Then I should see "curso Moodle onde serão guardadas as anotações dos tutores:"
        And the field "notecourseid" matches value "-- Não serão feitas anotações pelos tutores"
        Then I should see "Papel de tutor:"
        And the field "tutorroleid" matches value "None"