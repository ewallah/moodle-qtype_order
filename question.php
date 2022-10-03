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

    public function start_attempt(question_attempt_step $step, $variant) {
        parent::start_attempt($step, $variant);
        $choiceorder = array_keys($this->choices);
        $step->set_qt_var('_choiceorder', implode(',', $choiceorder));
        $this->set_choiceorder($choiceorder);
    }

    public function get_num_parts_right(array $response) {
        $fieldname = $this->get_dontknow_field_name();
        if (array_key_exists($fieldname, $response) and $response[$fieldname]) {
            return [0, count($this->stemorder)];
        }
        return parent::get_num_parts_right($response);
    }

    public function get_expected_data() {
        $vars = parent::get_expected_data();
        $vars[$this->get_dontknow_field_name()] = PARAM_ALPHA;
        return $vars;
    }

    public function get_field_name($key) {
        return $this->field($key);
    }

    public function get_dontknow_field_name() {
        return 'dontknow'.$this->id;
    }

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
