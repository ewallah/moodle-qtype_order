@qtype @qtype_order
Feature: Preview an order question
  As a teacher
  In order to check my order questions will work for students
  I need to preview them

  Background:
    Given the following "users" exist:
      | username |
      | teacher1 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name      | template | layouttype |
      | Test questions   | order | order-001 | moodle   | 0          |

  @javascript @_switch_window
  Scenario: Preview an order question and submit a correct response.
    When I am on the "order-001" "core_question > preview" page logged in as teacher1
    And I expand all fieldsets
    And I set the field "How questions behave" to "Immediate feedback"
    And I press "Save preview options and start again"
    And I press "Submit and finish"
    And I should see "The correct answer is"
    And I switch to the main window
