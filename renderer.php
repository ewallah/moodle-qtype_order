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
 * Renderer for the order question type.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author     adrianeboyd@gmail.com
 * @author     rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Generates the output for order questions.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author     adrianeboyd@gmail.com
 * @author     rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_order_renderer extends qtype_with_combined_feedback_renderer {

    /**
     * Can use drag and drop.
     *
     * @return bool
     */
    protected function can_use_drag_and_drop() {
        global $USER;

        $ie = core_useragent::check_browser_version('MSIE', 6.0);
        $ff = core_useragent::check_browser_version('Gecko', 20051106);
        $op = core_useragent::check_browser_version('Opera', 9.0);
        $sa = core_useragent::check_browser_version('Safari', 412);
        $ch = core_useragent::check_browser_version('Chrome', 6);
        if ((!$ie && !$ff && !$op && !$sa && !$ch) || !empty($USER->screenreader)) {
            return false;
        }
        return true;
    }

    /**
     * Generate the display of the formulation part of the question. This is the
     * area that contains the question text, and the controls for students to
     * input their answers. Some question types also embed bits of feedback, for
     * example ticks and crosses, in this area.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $o = html_writer::tag('div', $question->format_questiontext($qa), ['class' => 'qtext']);

        $o .= html_writer::start_tag('div', ['id' => 'ablock_'.$question->id, 'class' => 'ablock']);
        $o .= $this->construct_ablock_select($qa, $options);
        $o .= html_writer::end_tag('div');

        if ($this->can_use_drag_and_drop()) {
            $o .= html_writer::tag('div', '', ['class' => 'clearer']);
        }

        if ($qa->get_state() == question_state::$invalid) {
            $o .= html_writer::nonempty_tag('div', $question->get_validation_error($response), ['class' => 'validationerror']);
        }

        if ($this->can_use_drag_and_drop()) {
            $initparams = new stdClass();
            $initparams->qid = $question->id;
            $initparams->stemscount = count($question->get_stem_order());
            $initparams->ablockcontent = $this->construct_ablock_dragable($qa, $options);
            $initparams->readonly = $options->readonly;

            $this->page->requires->js_init_call('M.order.Init', [$initparams], false,
                ['name' => 'order',
                 'fullpath' => '/question/type/order/order.js',
                 'requires' => ['yui2-yahoo', 'yui2-event', 'yui2-dom', 'yui2-dragdrop', 'yui2-animation'], ]);
        }
        return $o;
    }


    /**
     * Construct a block select.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    private function construct_ablock_select(question_attempt $qa, question_display_options $options) {

        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $stemorder = $question->get_stem_order();
        $choices = $this->format_choices($question);

        $o = html_writer::start_tag('table', ['class' => 'answer']);
        $o .= html_writer::start_tag('tbody');

        $parity = 0;
        foreach ($stemorder as $key => $stemid) {
            $o .= html_writer::start_tag('tr', ['class' => 'r' . $parity]);
            $o .= html_writer::tag('td', $question->format_text(
                    $question->stems[$stemid], $question->stemformat[$stemid],
                    $qa, 'qtype_order', 'subquestion', $stemid),
                    ['class' => 'text']);

            $classes = 'control';
            $feedback = $this->get_feedback_class_image($qa, $options, $key);
            if ($feedback->class) {
                $classes .= ' '.$feedback->class;
            }
            $selected = $this->get_selected($question, $response, $key);
            $o .= html_writer::tag('td',
                    html_writer::select($choices, $qa->get_qt_field_name($question->get_field_name($key)), $selected,
                            ['0' => 'choose'], ['disabled' => $options->readonly]) .
                    ' ' . $feedback->image, ['class' => $classes]);

            $o .= html_writer::end_tag('tr');
            $parity = 1 - $parity;
        }
        $o .= html_writer::end_tag('tbody');
        $o .= html_writer::end_tag('table');
        return $o;
    }


    /**
     * Construct a block draggable.
     *
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    private function construct_ablock_dragable(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $o = '';
        $stemorder = $question->get_stem_order();
        $selectedstemorder = $this->get_selected_stemorder($qa);
        foreach ($selectedstemorder as $key => $stemid) {
            $stemorderkey = array_search($stemid, $stemorder);
            $attributes = ['id' => 'li_'.$question->id.'_'.$stemorderkey,
                           'name' => $qa->get_qt_field_name($question->get_field_name($stemorderkey)), ];
            $feedback = $this->get_feedback_class_image($qa, $options, $stemorderkey);
            if ($feedback->class) {
                $attributes['class'] = $feedback->class;
            }
            $stemcontent = $question->format_text(
                    $question->stems[$stemid], $question->stemformat[$stemid],
                    $qa, 'qtype_order', 'subquestion', $stemid);
            $o .= html_writer::tag('li', $stemcontent.' '.$feedback->image, $attributes);
        }
        $classes = 'draglist';
        if ($options->readonly) {
            $classes .= ' readonly';
        }
        if ($question->horizontal) {
            $classes .= ' inline';
        }
        $fieldname = $question->get_dontknow_field_name();
        if (array_key_exists($fieldname, $response) && $response[$fieldname]) {
            $classes .= ' deactivateddraglist';
        }
        $o .= html_writer::tag('div', '', ['class' => 'clearer']);
        $o = html_writer::tag('ul', $o, ['id' => 'ul_'.$question->id, 'class' => $classes]);
        $attributes = [
                'id'        => 'ch_'.$question->id,
                'name'      => $qa->get_qt_field_name($fieldname),
                'type'      => 'checkbox',
                'onClick'   => "M.order.OnClickDontKnow($question->id)", ];
        if (array_key_exists($fieldname, $response) && $response[$fieldname]) {
            $attributes['checked'] = 'on';
        }
        $o .= html_writer::empty_tag('input', $attributes);
        $o .= ' ' . get_string('defaultresponse', 'qtype_order');

        foreach ($selectedstemorder as $key => $stemid) {
            $stemorderkey = array_search($stemid, $stemorder);
            $attributes = [
                    'type'  => 'hidden',
                    'id'    => $qa->get_qt_field_name($question->get_field_name($stemorderkey)),
                    'name'  => $qa->get_qt_field_name($question->get_field_name($stemorderkey)),
                    'value' => $key + 1, ];
            $o .= html_writer::empty_tag('input', $attributes);
        }
        return $o;
    }

    /**
     * Collect selected response.
     *
     * @param object $question
     * @param object $response
     * @param string $key
     * @return int|string name
     */
    private function get_selected($question, $response, $key) {
        if (array_key_exists($question->get_field_name($key), $response)) {
            return $response[$question->get_field_name($key)];
        } else {
            return 0;
        }
    }

    /**
     * Feedback class image.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param string $key
     * @return object
     */
    private function get_feedback_class_image(question_attempt $qa, question_display_options $options, $key) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $stemorder = $question->get_stem_order();
        $stemid = $stemorder[$key];
        $ret = new stdClass();
        $ret->class = null;
        $ret->image = '';
        $selected = $this->get_selected($question, $response, $key);
        $fraction = (int) ($selected && $selected == $question->get_right_choice_for($stemid));
        if ($options->correctness && $selected) {
            $ret->class = $this->feedback_class($fraction);
            $ret->image = $this->feedback_image($fraction);
        }
        return $ret;
    }

    /**
     * Get selected stemorder.
     *
     * @param object $qa
     * @return array
     */
    private function get_selected_stemorder($qa) {
        $question = $qa->get_question();
        $response = $qa->get_last_qt_data();
        $selectedstemorder = [];
        foreach ($question->get_stem_order() as $key => $stemid) {
            $choicenum = $this->get_selected($question, $response, $key);
            if ($choicenum == 0) {
                return $question->get_stem_order();
            }
            $selectedstemorder[$choicenum - 1] = $stemid;
        }
        ksort($selectedstemorder);
        return $selectedstemorder;
    }

    /**
     * Specific feedback.
     *
     * @param question_attempt $qa
     * @return string
     */
    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    /**
     * Format choices.
     *
     * @param object $question
     * @return array
     */
    public function format_choices($question) {
        $choices = [];
        foreach ($question->get_choice_order() as $key => $choiceid) {
            $choices[$key] = htmlspecialchars($question->choices[$choiceid]);
        }
        return $choices;
    }

    /**
     * Correct response.
     *
     * @param question_attempt $qa
     * @return string
     */
    public function correct_response(question_attempt $qa) {
        if ($qa->get_state()->is_correct()) {
            return '';
        }
        $question = $qa->get_question();
        $choices = $question->get_choice_order();
        if (count($choices)) {
            $table = new html_table();
            $table->attributes['class'] = 'generaltable correctanswertable';
            $subqids = array_values($choices);
            foreach ($subqids as $subqid) {
                $table->data[][] = $question->format_text($question->stems[$subqid], $question->stemformat[$subqid], $qa,
                        'qtype_order', 'subquestion', $subqid);
            }
            return get_string('correctansweris', 'qtype_match', html_writer::table($table));
        }
        return '';
    }
}
