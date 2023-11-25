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
 * Backup for the order question type.
 *
 * @package    qtype_order
 * @copyright  2007 Adriane Boyd
 * @author adrianeboyd@gmail.com
 * @author rdebleu@eWallah.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup order questions
 */
class backup_qtype_order_plugin extends backup_qtype_plugin {

    /**
     * Returns the qtype information to attach to question element
     * @return stdClass
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill.
        $plugin = $this->get_plugin_element(null, '../../qtype', 'order');

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        // Now create the qtype own structures.
        $orderoptions = new backup_nested_element('orderoptions', ['id'], [
            'subquestions', 'horizontal', 'correctfeedback', 'correctfeedbackformat',
            'partiallycorrectfeedback', 'partiallycorrectfeedbackformat',
            'incorrectfeedback', 'incorrectfeedbackformat', 'shownumcorrect', ]);

        $orders = new backup_nested_element('orders');

        $order = new backup_nested_element('order', ['id'], ['code', 'questiontext', 'questiontextformat', 'answertext']);

        // Now the own qtype tree.
        $pluginwrapper->add_child($orderoptions);
        $pluginwrapper->add_child($orders);
        $orders->add_child($order);

        // Set source to populate the data.
        $orderoptions->set_source_table('question_order', ['question' => backup::VAR_PARENTID]);
        $order->set_source_table('question_order_sub', ['question' => backup::VAR_PARENTID]);

        // Don't need to annotate ids nor files.
        return $plugin;
    }

    /**
     * Returns one array with filearea => mappingname elements for the qtype
     *
     * @return array
     */
    public static function get_qtype_fileareas() {
        return [
            'correctfeedback' => 'question_order',
            'partiallycorrectfeedback' => 'question_order',
            'incorrectfeedback' => 'question_order',
            'subquestion'   => 'question_order_sub',
            'subanswer'     => 'question_order_sub', ];
    }
}
