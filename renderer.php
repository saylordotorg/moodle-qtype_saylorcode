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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/rendererbase.php');

/**
 * Renders a Saylor Code Studio question.
 *
 * Two things this deliberately does not do.
 *
 * It shows no per case breakdown. Producing one would mean either re-running
 * the student's code at render time, or storing each case's outcome where the
 * renderer could reach it -- and the second is how a hidden case's name ends up
 * on a page it should never appear on. The mark is shown; which specific hidden
 * test failed is not, because that is the property the whole suite protects.
 *
 * It uses a plain textarea rather than the rich editor from mod_saylorcode. That
 * editor lives in the activity plugin, and a question type reaching into an
 * activity for its UI would be the wrong dependency. The right fix is to move
 * the editor down into local_saylorcode, which both would then share. Until
 * then a textarea is honest, works without JavaScript, and is reachable by
 * keyboard and screen reader.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_saylorcode_renderer extends qtype_renderer {
    /**
     * The question and the box the student writes in.
     *
     * @param question_attempt $qa The attempt.
     * @param question_display_options $options What may be shown.
     * @return string HTML.
     */
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) {
        $question = $qa->get_question();
        $answer = $qa->get_last_qt_var('answer');

        // An untouched attempt starts from the author's starter code rather than
        // an empty box, which is the difference between a prompt and a blank
        // page for someone who has not written Java before.
        if ($answer === null) {
            $answer = $question->get_starter_code();
        }

        $inputname = $qa->get_qt_field_name('answer');
        $inputid = $qa->get_qt_field_name('answer') . '_id';

        $output = html_writer::tag(
            'div',
            $question->format_questiontext($qa),
            ['class' => 'qtext']
        );

        $label = html_writer::tag(
            'label',
            get_string('answercode', 'qtype_saylorcode', s($question->entryfilename)),
            ['for' => $inputid, 'class' => 'qtype-saylorcode-label']
        );

        $attributes = [
            'id' => $inputid,
            'name' => $inputname,
            'rows' => 18,
            'cols' => 80,
            'class' => 'qtype-saylorcode-code form-control',
            'spellcheck' => 'false',
            'autocapitalize' => 'off',
            'autocorrect' => 'off',
            'wrap' => 'off',
        ];

        if ($options->readonly) {
            $attributes['readonly'] = 'readonly';
        }

        $textarea = html_writer::tag('textarea', s($answer), $attributes);

        $output .= html_writer::tag('div', $label . $textarea, ['class' => 'ablock']);

        if (!$options->readonly) {
            $output .= html_writer::tag(
                'div',
                get_string('gradedbyrunning', 'qtype_saylorcode'),
                ['class' => 'qtype-saylorcode-note']
            );
        }

        return $output;
    }

    /**
     * What the student is told after grading.
     *
     * The mark itself is rendered by the question engine. What is added here is
     * the one thing a mark cannot say: that a grade is pending because the
     * runner could not be reached, rather than because the code was wrong.
     *
     * @param question_attempt $qa The attempt.
     * @return string HTML.
     */
    public function specific_feedback(question_attempt $qa) {
        if ($qa->get_state() == question_state::$needsgrading) {
            return html_writer::tag(
                'div',
                get_string('awaitinggrading', 'qtype_saylorcode'),
                ['class' => 'qtype-saylorcode-pending']
            );
        }

        return '';
    }

    /**
     * The author's solution, when the quiz is set to show it.
     *
     * @param question_attempt $qa The attempt.
     * @return string HTML.
     */
    public function correct_response(question_attempt $qa) {
        $question = $qa->get_question();
        $correct = $question->get_correct_response();

        if ($correct === null) {
            return '';
        }

        return html_writer::tag('p', get_string('referencesolution', 'qtype_saylorcode'))
            . html_writer::tag('pre', s($correct['answer']), ['class' => 'qtype-saylorcode-solution']);
    }
}
