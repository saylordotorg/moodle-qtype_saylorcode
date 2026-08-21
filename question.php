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

use local_saylorcode\local\runner\execution_gate;
use local_saylorcode\local\runner\execution_request;
use local_saylorcode\local\runner\execution_state;
use local_saylorcode\local\runner\jobe_provider;
use local_saylorcode\local\runtime\profile_manager;

/**
 * A coding exercise, answered by writing code and graded by running tests.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_saylorcode_question extends question_graded_automatically {
    /** @var string Library reference, empty when the question carries its own content. */
    public $stableid = '';

    /** @var string latest or pinned. */
    public $versionpolicy = 'latest';

    /** @var int|null The pinned version. */
    public $pinnedversion = null;

    /** @var string Runtime profile id. */
    public $profileid = 'java17-console';

    /** @var string The file the student edits. */
    public $entryfilename = 'Main.java';

    /** @var string Code the student starts from. */
    public $startercode = '';

    /** @var string JSON test cases. */
    public $testcases = '';

    /** @var string The author's own solution. */
    public $referencesolution = '';

    /**
     * @var \local_saylorcode\local\runner\provider_interface|null Injectable so a
     * test can grade without a runner. Null means build one from site config.
     */
    public $provider = null;

    /**
     * What the student submits.
     *
     * @return array
     */
    public function get_expected_data() {
        return ['answer' => PARAM_RAW];
    }

    /**
     * Whether there is anything to grade.
     *
     * @param array $response The response.
     * @return bool
     */
    public function is_complete_response(array $response) {
        return trim((string) ($response['answer'] ?? '')) !== '';
    }

    /**
     * What to say when there is nothing to grade.
     *
     * @param array $response The response.
     * @return string
     */
    public function get_validation_error(array $response) {
        return get_string('pleasewritecode', 'qtype_saylorcode');
    }

    /**
     * Whether two responses are the same code.
     *
     * @param array $prevresponse The earlier response.
     * @param array $newresponse The later response.
     * @return bool
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer');
    }

    /**
     * A short description of the response for reports.
     *
     * The code itself, trimmed. Deliberately not the outcome: a summary is
     * shown in places a hidden test's existence should not leak into.
     *
     * @param array $response The response.
     * @return string|null
     */
    public function summarise_response(array $response) {
        $answer = trim((string) ($response['answer'] ?? ''));

        if ($answer === '') {
            return null;
        }

        return shorten_text(preg_replace('/\s+/', ' ', $answer), 200);
    }

    /**
     * The author's solution, where there is one.
     *
     * @return array|null
     */
    public function get_correct_response() {
        $solution = trim((string) $this->referencesolution);

        return $solution === '' ? null : ['answer' => $solution];
    }

    /**
     * Run the tests and score the response.
     *
     * The important case here is the unhappy one. Grading needs a runner, and a
     * runner can be down, saturated, or slow. Returning zero then would mark a
     * student wrong for an outage they had no part in and no way to see, and on
     * a quiz that grade is the record. So an execution that could not be
     * performed is handed to the teacher as needing grading rather than being
     * guessed at: the behaviour sets the state verbatim from what is returned
     * here, so needsgrading puts the attempt in the manual queue.
     *
     * Only a run that actually happened produces a mark.
     *
     * @param array $response The response.
     * @return array [fraction, question_state]
     */
    public function grade_response(array $response) {
        $cases = $this->get_test_cases();

        // A question with no tests cannot be scored automatically. Better in
        // the teacher's queue than silently right or silently wrong.
        if (empty($cases)) {
            return [null, question_state::$needsgrading];
        }

        $gate = new execution_gate((int) $this->get_grading_userid());
        $lease = $gate->acquire();

        if ($lease === null) {
            // Saturation is not the student's fault and must not become their
            // mark. Their code is saved; the grade waits.
            return [null, question_state::$needsgrading];
        }

        try {
            $outcome = $this->run_tests((string) ($response['answer'] ?? ''), $cases);
        } finally {
            $gate->release($lease);
        }

        if ($outcome === null) {
            return [null, question_state::$needsgrading];
        }

        [$fraction, $graded] = $outcome;

        if (!$graded) {
            return [null, question_state::$needsgrading];
        }

        return [$fraction, question_state::graded_state_for_fraction($fraction)];
    }

    /**
     * Execute the student's code against the cases.
     *
     * @param string $answer The submitted code.
     * @param array $cases The test cases.
     * @return array|null [fraction, graded] or null when the runner could not be used.
     */
    protected function run_tests(string $answer, array $cases): ?array {
        $provider = $this->provider ?? jobe_provider::create_from_config();

        // A profile the site does not have, or has disabled, cannot be graded
        // against. That is a configuration problem rather than a wrong answer.
        if ((new profile_manager())->get_profile($this->profileid) === null) {
            return null;
        }

        $earned = 0.0;
        $total = 0.0;

        foreach ($cases as $case) {
            $weight = (float) ($case['weight'] ?? 1.0);
            if ($weight <= 0) {
                continue;
            }
            $total += $weight;

            $request = new execution_request(
                bin2hex(random_bytes(16)),
                $this->profileid,
                execution_request::MODE_SUBMIT,
                [$this->entryfilename => $answer],
                (string) ($case['stdin'] ?? '')
            );

            try {
                $result = $provider->execute($request);
            } catch (Throwable $e) {
                // A transport failure is an outage, not a wrong answer.
                return null;
            }

            $state = $result->get_state();

            if (execution_state::is_platform_failure($state)) {
                return null;
            }

            // A compile error is a property of the submitted code, so it is a
            // real zero rather than an ungradable outcome.
            if ($state === execution_state::COMPILE_ERROR) {
                return [0.0, true];
            }

            if ($state !== execution_state::COMPLETED) {
                continue;
            }

            $expected = (string) ($case['expected'] ?? '');
            $actual = $result->export_for_student()['stdout'] ?? '';

            if ($this->output_matches((string) $actual, $expected)) {
                $earned += $weight;
            }
        }

        if ($total <= 0) {
            return null;
        }

        return [max(0.0, min(1.0, $earned / $total)), true];
    }

    /**
     * Compare program output with what the case expects.
     *
     * Trailing whitespace on each line and at the end is ignored, because a
     * missing final newline is not a wrong answer to any question anyone means
     * to ask.
     *
     * @param string $actual What the program printed.
     * @param string $expected What the case expects.
     * @return bool
     */
    protected function output_matches(string $actual, string $expected): bool {
        $normalise = static function (string $text): string {
            $text = str_replace("\r\n", "\n", $text);
            $lines = array_map('rtrim', explode("\n", $text));

            return rtrim(implode("\n", $lines));
        };

        return $normalise($actual) === $normalise($expected);
    }

    /**
     * The decoded test cases, from the library when this question names one.
     *
     * @return array
     */
    public function get_test_cases(): array {
        $resolved = $this->resolve();

        if ($resolved !== null) {
            return $resolved->get_test_cases();
        }

        $decoded = json_decode((string) $this->testcases, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The starter code, from the library when this question names one.
     *
     * @return string
     */
    public function get_starter_code(): string {
        $resolved = $this->resolve();

        return $resolved !== null ? $resolved->get_starter_code() : (string) $this->startercode;
    }

    /**
     * Resolve this question against the library.
     *
     * @return \local_saylorcode\local\library\resolved_exercise|null Null when this question is not library backed.
     */
    protected function resolve() {
        if (trim((string) $this->stableid) === '') {
            return null;
        }

        $holder = (object) [
            'stableid' => $this->stableid,
            'versionpolicy' => $this->versionpolicy,
            'pinnedversion' => $this->pinnedversion,
            'entryfilename' => $this->entryfilename,
            'startercode' => $this->startercode,
            'referencesolution' => $this->referencesolution,
            'testcases' => $this->testcases,
            'hints' => '',
        ];

        return (new \local_saylorcode\local\library\exercise_resolver())->resolve($holder);
    }

    /**
     * Whose concurrency allowance this execution counts against.
     *
     * Grading can happen in a task or on another user's request, so falling
     * back to the current user keeps the gate meaningful either way.
     *
     * @return int
     */
    protected function get_grading_userid(): int {
        global $USER;

        return (int) ($USER->id ?? 0);
    }
}
