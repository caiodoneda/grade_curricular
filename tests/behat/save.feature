@local @local_grade_curricular
Feature: save grade curricular config
    As admin
    I should see correct configuration after saving

    Background:
        Given the following "categories" exist:
            | name       | category | idnumber |
            | Category 1 | 0        | CAT1     |
        And the following "cohorts" exist:
            | name       | reference | idnumber |
            | Cohort1    | CAT1      | CAT1     |
        And the following "courses" exist:
            | fullname | shortname | category |
            | Course 1 | C101      | CAT1 |
            | Course 2 | C102      | CAT1 |
            | Course 3 | C103      | CAT1 |
        And create a new grade curricular at "CAT1" category:       

    @javascript
    Scenario: configuração dos cursos Moodle saving
        When I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        And I follow "Expand all"

        # Setting values to the fields of the first form.
        Then I set the field "minoptionalcourses" to "1"
        And I set the field "maxoptionalcourses" to "1"

        # Setting values to the fields of the first course form (it's a table inside the courses table).
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[1]/td[2]/select" to "1"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[2]/td[2]/input" to "100"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[1]" to "1"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[2]" to "1"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[3]" to "2020"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[4]" to "10"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[5]" to "1"
        And I set the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[6]" to "2020"

        # Setting values to the fields of the second course form (it's a table inside the courses table).
        Then I set the field with xpath "//*[@id='table2']/table/tbody/tr[1]/td[2]/select" to "2"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[2]/td[2]/input" to "100"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[1]" to "1"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[2]" to "1"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[3]" to "2020"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[4]" to "10"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[5]" to "1"
        And I set the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[6]" to "2020"

        # Setting values to the fields of the third course form (it's a table inside the courses table).
        Then I set the field with xpath "//*[@id='table3']/table/tbody/tr[1]/td[2]/select" to "2"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[2]/td[2]/input" to "100"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[1]" to "1"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[2]" to "1"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[3]" to "2020"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[4]" to "10"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[5]" to "1"
        And I set the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[6]" to "2020"

        # Saving the values.
        Then I press "Save changes"

        When I follow "Expand all"

        # Checking if the first form saves the values.
        Then the field "minoptionalcourses" matches value "1"
        And the field "maxoptionalcourses" matches value "1"
        And the field "optionalatonetime" matches value "0"

        # Checking the first course table.
        And the field with xpath "//*[@id='table1']/table/tbody/tr[1]/td[2]/select" matches value "1"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[2]/td[2]/input" matches value "100"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[1]" matches value "1"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[2]" matches value "1"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[3]" matches value "2020"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[4]" matches value "10"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[5]" matches value "1"
        And the field with xpath "//*[@id='table1']/table/tbody/tr[3]/td[2]/select[6]" matches value "2020"

        # Checking the second course table.
        And the field with xpath "//*[@id='table2']/table/tbody/tr[1]/td[2]/select" matches value "2"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[2]/td[2]/input" matches value "100"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[1]" matches value "1"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[2]" matches value "1"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[3]" matches value "2020"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[4]" matches value "10"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[5]" matches value "1"
        And the field with xpath "//*[@id='table2']/table/tbody/tr[3]/td[2]/select[6]" matches value "2020"

        # Checking the third course table.
        And the field with xpath "//*[@id='table3']/table/tbody/tr[1]/td[2]/select" matches value "2"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[2]/td[2]/input" matches value "100"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[1]" matches value "1"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[2]" matches value "1"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[3]" matches value "2020"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[4]" matches value "10"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[5]" matches value "1"
        And the field with xpath "//*[@id='table3']/table/tbody/tr[3]/td[2]/select[6]" matches value "2020"

    @javascript
    Scenario: configurações adicionais saving
        When I log in as "admin"
        And I follow "Courses"
        And I follow "Category 1"
        And I expand "Controle Curricular" node
        And I follow "Grade curricular"
        And I follow "Configurações adicionais"
        And I follow "Expand all"

        # Setting the form values
        Then I set the field "id_studentcohortid" to "1"
        And I set the field "id_notecourseid" to "1"
        And I set the field "id_tutorroleid" to "1"

        Then I press "Save changes"

        When I follow "Expand all"

        # Checking the values
        Then the field "id_studentcohortid" matches value "Cohort1"
        And the field "id_notecourseid" matches value "Course 1"
        And the field "id_tutorroleid" matches value "Manager"