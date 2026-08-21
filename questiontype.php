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
require_once($CFG->libdir . '/questionlib.php');

/**
 * The Saylor Code Studio question type.
 *
 * A question shaped front end onto the exercise library. The same exercise can
 * back a stand-alone activity, a Book embed and a quiz question, which is the
 * whole point of the library existing.
 *
 * Content resolution is delegated rather than reimplemented: the version policy,
 * the pinning and the fallback behaviour all come from local_saylorcode, so a
 * quiz question and an activity pointing at the same reference cannot disagree
 * about what the exercise is.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_saylorcode extends question_type {
    /**
     * The options table and its columns.
     *
     * Declaring these lets the base class handle saving, loading and copying
     * them onto the runtime question, which is why this class is short.
     *
     * @return array
     */
    public function extra_question_fields() {
        return [
            'qtype_saylorcode_options',
            'stableid',
            'versionpolicy',
            'pinnedversion',
            'profileid',
            'entryfilename',
            'startercode',
            'testcases',
            'referencesolution',
        ];
    }

    /**
     * Whether this question can be graded without a human.
     *
     * True, with the caveat that grading needs a runner. When one is not
     * available the question returns needsgrading rather than a mark, so an
     * outage becomes a teacher's queue instead of a student's zero.
     *
     * @return bool
     */
    public function is_manual_graded() {
        return false;
    }

    /**
     * The score a blank or random answer would earn.
     *
     * Zero: there is no guessing your way through a program that has to run.
     *
     * @param object $questiondata The question.
     * @return float
     */
    public function get_random_guess_score($questiondata) {
        return 0;
    }

    /**
     * Response classification for the statistics report.
     *
     * Deliberately empty. The obvious grouping would be by which cases passed,
     * and that would put hidden case names into a report, which is exactly what
     * the rest of this suite goes to some trouble to prevent.
     *
     * @param object $questiondata The question.
     * @return array
     */
    public function get_possible_responses($questiondata) {
        return [];
    }

    /**
     * Move any files when the question moves context.
     *
     * @param int $questionid The question.
     * @param int $oldcontextid Where it was.
     * @param int $newcontextid Where it is going.
     * @return void
     */
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    /**
     * Delete any files belonging to a question.
     *
     * @param int $questionid The question.
     * @param int $contextid Its context.
     * @return void
     */
    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }
}
