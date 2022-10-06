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
 * Order question.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author     adrianeboyd@gmail.com
 * @author     rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/match/question.php');

/**
 * Order question.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author     adrianeboyd@gmail.com
 * @author     rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_order_question extends qtype_match_question {


    /**
     * Start a new attempt at this question, storing any information that will be needed later in the step.
     *
     * @param question_attempt_step $step The first step of the question_attempt being started. Can be used to store state.
     * @param int $variant which variant of this question to start. Will be between 1 and get_num_variants inclusive.
     */
    public function start_attempt(question_attempt_step $step, $variant) {
        parent::start_attempt($step, $variant);
        $choiceorder = array_keys($this->choices);
        $step->set_qt_var('_choiceorder', implode(',', $choiceorder));
        $this->set_choiceorder($choiceorder);
    }

    /**
     * Return the number of subparts of this response that are right.
     * @param array $response a response
     * @return array with two elements, the number of correct subparts, and the total number of subparts.
     */
    public function get_num_parts_right(array $response) {
        $fieldname = $this->get_dontknow_field_name();
        if (array_key_exists($fieldname, $response) && $response[$fieldname]) {
            return [0, count($this->stemorder)];
        }
        return parent::get_num_parts_right($response);
    }

    /**
     * Get expected data
     *
     * @return array
     */
    public function get_expected_data() {
        $vars = parent::get_expected_data();
        $vars[$this->get_dontknow_field_name()] = PARAM_ALPHA;
        return $vars;
    }

    /**
     * Get field name
     *
     * @param string $key
     * @return string
     */
    public function get_field_name($key) {
        return $this->field($key);
    }

    /**
     * Get dont know field name
     *
     * @return string
     */
    public function get_dontknow_field_name() {
        return 'dontknow'.$this->id;
    }

    /**
     * Checks whether the users is allow to be served a particular file.
     * @param question_attempt $qa the question attempt being displayed.
     * @param question_display_options $options the options that control display of the question.
     * @param string $component the name of the component we are serving files for.
     * @param string $filearea the name of the file area.
     * @param array $args the remaining bits of the file path.
     * @param bool $forcedownload whether the user must be forced to download the file.
     * @return bool true if the user can access this file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'qtype_order' && $filearea == 'subquestion') {
            $subqid = reset($args); // Itemid is sub question id.
            return array_key_exists($subqid, $this->stems);
        } else if ($component == 'question' &&
           in_array($filearea, ['correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'])) {
            return $this->check_combined_feedback_file_access($qa, $options, $filearea);
        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }
}
