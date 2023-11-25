<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for the order question type class.
 *
 * @package   qtype_order
 * @copyright 2018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace qtype_order;

/**
 * Unit tests for the order question type class.
 *
 * @copyright 20018 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questiontype_test extends \advanced_testcase {
    /** @var qtype_order instance of the question type class to test. */
    protected $qtype;

    /**
     * Init.
     */
    protected function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
        require_once($CFG->dirroot . '/question/type/order/questiontype.php');
        require_once($CFG->dirroot . '/question/type/edit_question_form.php');
        require_once($CFG->dirroot . '/question/type/order/edit_order_form.php');
        $this->qtype = new \qtype_order();
    }

    /**
     * Teardown.
     */
    protected function tearDown(): void {
        $this->qtype = null;
    }

    /**
     * Test name.
     * @covers \qtype_order
     */
    public function test_name(): void {
        $this->assertEquals($this->qtype->name(), 'order');
    }

    /**
     * Can analyse responses.
     * @covers \qtype_order
     */
    public function test_can_analyse_responses(): void {
        $this->assertTrue($this->qtype->can_analyse_responses());
    }

    /**
     * Test saving.
     * @covers \qtype_order
     * @covers \qtype_order_edit_form
     */
    public function test_question_saving(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $questiondata = \test_question_maker::get_question_data('order');
        $formdata = \test_question_maker::get_question_form_data('order');

        /** @var core_question_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $generator->create_question_category([]);

        $formdata->category = "{$cat->id},{$cat->contextid}";

        \qtype_order_edit_form::mock_submit((array) $formdata);

        $form = \qtype_order_test_helper::get_question_editing_form($cat, $questiondata);
        $this->assertTrue($form->is_validated());

        $fromform = $form->get_data();

        $save = $this->qtype->save_question($questiondata, $fromform);
        $actual = \question_bank::load_question_data($save->id);
        $this->assertEquals($actual->generalfeedback, $questiondata->generalfeedback);

    }

}
