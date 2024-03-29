@qtype @qtype_order
Feature: Test duplicating a quiz containing a order question
  As a teacher
  In order to re-use my courses containing order questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name   | template |
      | Test questions   | order | Moodle | moodle   |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | Moodle | 1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Backup and restore a course containing an order question
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I navigate to "Question bank" in current page administration
    And I choose "Edit question" action for "Moodle" in the question bank
    Then the following fields match these values:
      | Question name                      | Moodle |
      | Question text                      | Put these words in order. |
      | General feedback                   | The correct answer is "Modular Object Oriented Dynamic Learning Environment". |
      | id_subquestions_0                  | Modular                                                                       |
      | id_subquestions_1                  | Object                                                                        |
      | id_subquestions_2                  | Oriented                                                                      |
      | id_subquestions_3                  | Dynamic                                                                       |
      | id_subquestions_4                  | Learning                                                                      |
      | id_subquestions_5                  | Environment                                                                   |
      | For any correct response           | Well done!                                                                    |
      | For any partially correct response | Parts, but only parts, of your response are correct.                          |
      | For any incorrect response         | That is not right at all.                                                     |
