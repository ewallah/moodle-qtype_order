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
 * Version file for the order question type.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @author rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * match editing form definition.
 */
class qtype_order_edit_form extends question_edit_form {

    public function get_per_answer_fields($mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = [];
        $repeated[] = $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = $mform->createElement('editor', 'subquestions', get_string('question'), null, $this->editoroptions);
        $repeated[] = $mform->createElement('hidden', 'subanswers', '0', null);
        $repeatedoptions['subquestions']['type'] = PARAM_RAW;
        $repeatedoptions['subanswers']['type'] = PARAM_TEXT;
        $answersoption = 'subquestions';

        return $repeated;
    }

    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    public function definition_inner($mform) {
        $mform->addElement('advcheckbox', 'horizontal', get_string('horizontal', 'qtype_order'), null, null, [0, 1]);
        $mform->setDefault('horizontal', 0);

        $mform->addElement('static', 'answersinstruct',
                get_string('availablechoices', 'qtype_match'),
                get_string('filloutthreeitems', 'qtype_order'));
        $mform->closeHeaderBefore('answersinstruct');

        $this->add_per_answer_fields($mform, get_string('questionno', 'question', '{no}'), 0);

        $this->add_combined_feedback_fields(true);
        $this->add_interactive_settings(true, true);
    }

    public function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_combined_feedback($question, true);
        $question = $this->data_preprocessing_hints($question, true, true);

        if (empty($question->options)) {
            return $question;
        }

        $question->horizontal = $question->options->horizontal;

        $key = 0;
        $cid = $this->context->id;
        foreach ($question->options->subquestions as $subquestion) {
            $question->subanswers[$key] = $subquestion->answertext;
            $draftid = file_get_submitted_draft_itemid('subquestions[' . $key . ']');
            $question->subquestions[$key] = [];
            $subid = !empty($subquestion->id) ? (int) $subquestion->id : null;
            $question->subquestions[$key]['text'] = file_prepare_draft_area($draftid, $cid, 'qtype_order', 'subquestion',
                $subid, $this->fileoptions, $subquestion->questiontext);
            $question->subquestions[$key]['format'] = $subquestion->questiontextformat;
            $question->subquestions[$key]['itemid'] = $draftid;
            $key++;
        }

        return $question;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $answers = $data['subanswers'];
        $questions = $data['subquestions'];
        $questioncount = 0;
        $answercount = 0;
        foreach ($questions as $key => $question) {
            $trimmedquestion = trim($question['text']);
            $trimmedanswer = trim($answers[$key]);
            if ($trimmedquestion != '') {
                $questioncount++;
            }
            if ($trimmedanswer != '' || $trimmedquestion != '') {
                $answercount++;
            }
            if ($trimmedquestion != '' && $trimmedanswer == '') {
                $errors['subanswers['.$key.']'] = get_string('nomatchinganswerforq', 'qtype_match', $trimmedquestion);
            }
        }
        $numberqanda = new stdClass;
        $numberqanda->q = 3;
        if ($questioncount < 1) {
            $errors['subquestions[0]'] = get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        if ($questioncount < 2) {
            $errors['subquestions[1]'] = get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        if ($questioncount < 3) {
            $errors['subquestions[2]'] = get_string('notenoughqsandas', 'qtype_match', $numberqanda);
        }
        return $errors;
    }

    public function qtype() {
        return 'order';
    }
}
