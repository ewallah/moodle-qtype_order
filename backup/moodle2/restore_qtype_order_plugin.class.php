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
 * Restoring order question type.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @author rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restoring order question type.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @author rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_order_plugin extends restore_qtype_plugin {

    /**
     * Returns the paths to be handled by the plugin at question level
     * @return array
     */
    protected function define_question_plugin_structure() {

        $paths = [];
        // Add own qtype stuff.
        $elename = 'orderoptions';
        $elepath = $this->get_pathfor('/orderoptions'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'order';
        $elepath = $this->get_pathfor('/orders/order'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);
        return $paths; // And we return the interesting paths.
    }

    /**
     * Process the qtype/orderoptions element.
     * @param arrray $data
     */
    public function process_orderoptions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped.
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // If the question has been created by restore, we need to create its question_order too.
        if ($questioncreated) {
            // Adjust some columns.
            $data->question = $newquestionid;
            if (!isset($data->correctfeedback)) {
                $data->correctfeedback = " ";
            }
            if (!isset($data->partiallycorrectfeedback)) {
                $data->partiallycorrectfeedback = " ";
            }
            if (!isset($data->incorrectfeedback)) {
                $data->incorrectfeedback = " ";
            }
            // Insert record.
            $newitemid = $DB->insert_record('question_order', $data);
            // Create mapping.
            $this->set_mapping('question_order', $oldid, $newitemid);
        }
    }

    /**
     * Process the qtype/orders/order element
     * @param arrray $data
     */
    public function process_order($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Detect if the question is created or mapped.
        $oldquestionid   = $this->get_old_parentid('question');
        $newquestionid   = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $oldquestionid) ? true : false;

        // If the question has been created by restore, we need to create its question_order_sub too.
        if ($questioncreated) {
            // Adjust some columns.
            $data->question = $newquestionid;
            // Insert record.
            $newitemid = $DB->insert_record('question_order_sub', $data);
            // Create mapping (there are files and states based on this).
            $this->set_mapping('question_order_sub', $oldid, $newitemid);

        } else {
            // Look for ordering subquestion (by question, questiontext and answertext).
            $sub = $DB->get_record_select('question_order_sub', 'question = ? AND ' .
                    $DB->sql_compare_text('questiontext') . ' = ' .
                    $DB->sql_compare_text('?').
                    $DB->sql_compare_text('AND answertext') . ' = ' .
                    $DB->sql_compare_text('?'),
                    [$newquestionid, $data->questiontext, $data->answertext],
                    'id', IGNORE_MULTIPLE);
            // Found, let's create the mapping.
            if ($sub) {
                $this->set_mapping('question_order_sub', $oldid, $sub->id);
            } else {
                // Something went really wrong, cannot map subquestion for one order question.
                throw restore_step_exception('error_question_order_sub_missing_in_db', $data);
            }
        }
    }

    /**
     * This method is executed once the whole restore_structure_step
     * has ended processing the whole xml structure. Its name is:
     * "after_execute_" + connectionpoint ("question")
     *
     * For order qtype we use it to restore the subquestions column,
     * containing one list of question_order_sub ids
     */
    public function after_execute_question() {
        global $DB;
        // Now that all the question_order_subs have been restored, let's process
        // the created question_order subquestions (list of question_order_sub ids).
        $rs = $DB->get_recordset_sql("SELECT qm.id, qm.subquestions
                                        FROM {question_order} qm
                                        JOIN {backup_ids_temp} bi ON bi.newitemid = qm.question
                                       WHERE bi.backupid = ?
                                         AND bi.itemname = 'question_created'", [$this->get_restoreid()]);
        foreach ($rs as $rec) {
            $subquestionsarr = explode(',', $rec->subquestions);
            foreach ($subquestionsarr as $key => $subquestion) {
                $subquestionsarr[$key] = $this->get_mappingid('question_order_sub', $subquestion);
            }
            $subquestions = implode(',', $subquestionsarr);
            $DB->set_field('question_order', 'subquestions', $subquestions, ['id' => $rec->id]);
        }
        $rs->close();
    }

    /**
     * Given one question_states record, return the answer
     * recoded pointing to all the restored stuff for order questions
     *
     * answer is one comma separated list of hypen separated pairs
     * containing question_order_sub->id and question_order_sub->code
     * @param stdClass $state
     * @return string
     */
    public function recode_state_answer($state) {
        $answer = $state->answer;
        $resultarr = [];

        $responses = explode(',', $answer);
        $defaultresponse = array_pop($responses);

        foreach ($responses as $pair) {
            $pairarr = explode('-', $pair);
            $id = $pairarr[0];
            $code = $pairarr[1];
            $newid = $this->get_mappingid('question_order_sub', $id);
            $resultarr[] = implode('-', [$newid, $code]);
        }

        $resultarr[] = $defaultresponse;
        return implode(',', $resultarr);
    }

    /**
     * Return the contents of this qtype to be processed by the links decoder
     * @return array
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('question_order_sub', ['questiontext'], 'question_order_sub');
        return $contents;
    }

    /**
     * Recode response.
     * @param int $questionid
     * @param int $sequencenumber
     * @param array $response
     * @return string the recoded order.
     */
    public function recode_response($questionid, $sequencenumber, array $response) {
        if (array_key_exists('_choiceorder', $response)) {
            $response['_choiceorder'] = $this->recode_order($response['_choiceorder']);
        }
        if (array_key_exists('_stemorder', $response)) {
            $response['_stemorder'] = $this->recode_order($response['_stemorder']);
        }
        return $response;
    }

    /**
     * Recode the choice and/or stem order as stored in the response.
     * @param string $order the original order.
     * @return string the recoded order.
     */
    protected function recode_order($order) {
        $neworder = [];
        foreach (explode(',', $order) as $id) {
            if (($newid = $this->get_mappingid('question_order_sub', $id))) {
                $neworder[] = $newid;
            }
        }
        return implode(',', $neworder);
    }
}
