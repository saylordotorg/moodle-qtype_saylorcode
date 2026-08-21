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

namespace qtype_saylorcode;

use local_saylorcode\local\runner\execution_state;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/saylorcode/question.php');
require_once(__DIR__ . '/fixtures/scripted_provider.php');

/**
 * Grading a Saylor Code Studio question.
 *
 * The scoring arithmetic is the easy half. The half worth testing is what
 * happens when the runner cannot answer, because a quiz mark is a record: a
 * student who wrote correct code during an outage must not end up with a zero
 * that looks exactly like a wrong answer.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qtype_saylorcode_question
 */
final class grading_test extends \advanced_testcase {
    /**
     * A question with its own cases and a scripted runner.
     *
     * @param array $cases Test cases.
     * @param scripted_provider|null $provider The runner to use.
     * @return \qtype_saylorcode_question
     */
    protected function question(array $cases, ?scripted_provider $provider = null) {
        $question = new \qtype_saylorcode_question();
        $question->stableid = '';
        $question->profileid = 'java17-console';
        $question->entryfilename = 'Main.java';
        $question->startercode = '';
        $question->testcases = json_encode($cases);
        $question->referencesolution = '';
        $question->provider = $provider;

        return $question;
    }

    /**
     * Two cases, both passing, is full marks.
     *
     * @return void
     */
    public function test_all_cases_passing_is_full_marks(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $question = $this->question(
            [
                ['name' => 'one', 'expected' => "1\n", 'weight' => 1],
                ['name' => 'two', 'expected' => "2\n", 'weight' => 1],
            ],
            new scripted_provider(["1\n", "2\n"])
        );

        [$fraction, $state] = $question->grade_response(['answer' => 'code']);

        $this->assertEquals(1.0, $fraction);
        $this->assertEquals(\question_state::$gradedright, $state);
    }

    /**
     * Half the weight earned is half the mark.
     *
     * @return void
     */
    public function test_partial_credit_follows_weight(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $question = $this->question(
            [
                ['name' => 'one', 'expected' => "1\n", 'weight' => 1],
                ['name' => 'two', 'expected' => "2\n", 'weight' => 3],
            ],
            new scripted_provider(["1\n", "wrong\n"])
        );

        [$fraction] = $question->grade_response(['answer' => 'code']);

        $this->assertEquals(0.25, $fraction);
    }

    /**
     * A missing final newline is not a wrong answer.
     *
     * @return void
     */
    public function test_trailing_whitespace_is_not_a_wrong_answer(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $question = $this->question(
            [['name' => 'one', 'expected' => "hello\n", 'weight' => 1]],
            new scripted_provider(['hello'])
        );

        [$fraction] = $question->grade_response(['answer' => 'code']);

        $this->assertEquals(1.0, $fraction);
    }

    /**
     * A runner that cannot be reached does not produce a mark.
     *
     * This is the important one. Returning zero here would mark a student wrong
     * for an outage, with nothing on the page to distinguish it from code that
     * genuinely failed, and on a quiz that grade is what gets recorded.
     *
     * @return void
     */
    public function test_a_runner_outage_is_not_a_zero(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $provider = new scripted_provider(["1\n"]);
        $provider->throw = true;

        $question = $this->question(
            [['name' => 'one', 'expected' => "1\n", 'weight' => 1]],
            $provider
        );

        [$fraction, $state] = $question->grade_response(['answer' => 'correct code']);

        $this->assertNull($fraction, 'An outage produced a mark.');
        $this->assertEquals(
            \question_state::$needsgrading,
            $state,
            'An outage graded the student instead of asking a teacher.'
        );
    }

    /**
     * A runner reporting itself unavailable also does not produce a mark.
     *
     * @return void
     */
    public function test_a_platform_failure_is_not_a_zero(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $provider = new scripted_provider(['']);
        $provider->state = execution_state::RUNNER_UNAVAILABLE;

        $question = $this->question(
            [['name' => 'one', 'expected' => "1\n", 'weight' => 1]],
            $provider
        );

        [$fraction, $state] = $question->grade_response(['answer' => 'code']);

        $this->assertNull($fraction);
        $this->assertEquals(\question_state::$needsgrading, $state);
    }

    /**
     * A compile error is the student's, so it is a real zero.
     *
     * The distinction matters: code that does not compile is a wrong answer,
     * while a runner that cannot compile it is not an answer at all.
     *
     * @return void
     */
    public function test_a_compile_error_is_a_real_zero(): void {
        $this->resetAfterTest();
        set_config('jobeurl', 'http://runner.example', 'local_saylorcode');

        $provider = new scripted_provider(['']);
        $provider->state = execution_state::COMPILE_ERROR;

        $question = $this->question(
            [['name' => 'one', 'expected' => "1\n", 'weight' => 1]],
            $provider
        );

        [$fraction, $state] = $question->grade_response(['answer' => 'nonsense']);

        $this->assertEquals(0.0, $fraction);
        $this->assertEquals(\question_state::$gradedwrong, $state);
    }

    /**
     * A question with no cases waits for a teacher.
     *
     * @return void
     */
    public function test_no_cases_means_needs_grading(): void {
        $this->resetAfterTest();

        $question = $this->question([], new scripted_provider());

        [$fraction, $state] = $question->grade_response(['answer' => 'code']);

        $this->assertNull($fraction);
        $this->assertEquals(\question_state::$needsgrading, $state);
    }

    /**
     * An empty answer is not a complete response.
     *
     * @return void
     */
    public function test_an_empty_answer_is_incomplete(): void {
        $this->resetAfterTest();

        $question = $this->question([['name' => 'one', 'expected' => "1\n"]]);

        $this->assertFalse($question->is_complete_response(['answer' => '   ']));
        $this->assertTrue($question->is_complete_response(['answer' => 'class Main {}']));
        $this->assertNotEmpty($question->get_validation_error(['answer' => '']));
    }

    /**
     * The response summary is the code, not the outcome.
     *
     * A summary is shown in places that must not disclose which hidden case
     * failed, so it carries no grading detail at all.
     *
     * @return void
     */
    public function test_the_summary_is_the_code(): void {
        $this->resetAfterTest();

        $question = $this->question([['name' => 'secret case', 'expected' => "1\n", 'ispublic' => false]]);
        $summary = $question->summarise_response(['answer' => "class Main {\n  // hi\n}"]);

        $this->assertStringContainsString('class Main', $summary);
        $this->assertStringNotContainsString('secret case', $summary);
    }
}
