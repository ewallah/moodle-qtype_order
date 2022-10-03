@qtype @qtype_order
Feature: Test creating an order question
  As a teacher
  In order to test my students
  I need to be able to create an order question

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration

  @javascript
  Scenario: Create an order question with 6 draggable items
    When I add a "order" question filling the form with:
      | Question name                      | order-001                        |
      | Question text                      | Put the words in correct order.  |
      | General feedback                   | One two three four five six      |
      | id_subquestions_0                  | one                              |
      | id_subquestions_1                  | two                              |
      | id_subquestions_2                  | three                            |
      | For any correct response           | Your answer is correct           |
      | For any partially correct response | Your answer is partially correct |
      | For any incorrect response         | Your answer is incorrect         |
      | Hint 1                             | This is your first hint          |
      | Hint 2                             | This is your second hint         |
    Then I should see "order-001"
