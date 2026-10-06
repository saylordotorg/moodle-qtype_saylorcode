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

use local_saylorcode\local\library\exercise_resolver;
use local_saylorcode\local\runtime\profile_manager;
use local_saylorcode\local\stable_id;

/**
 * Editing form for a Saylor Code Studio question.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_saylorcode_edit_form extends question_edit_form {
    /**
     * The question type name.
     *
     * @return string
     */
    public function qtype() {
        return 'saylorcode';
    }

    /**
     * The fields specific to this question type.
     *
     * @param object $mform The form being built.
     * @return void
     */
    protected function definition_inner($mform) {
        $mform->addElement('header', 'exercisehdr', get_string('exercise', 'qtype_saylorcode'));
        $mform->setExpanded('exercisehdr', true);

        $mform->addElement('text', 'stableid', get_string('stableid', 'qtype_saylorcode'), ['size' => 24]);
        $mform->setType('stableid', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('stableid', 'stableid', 'qtype_saylorcode');

        $mform->addElement('select', 'versionpolicy', get_string('versionpolicy', 'qtype_saylorcode'), [
            exercise_resolver::POLICY_LATEST => get_string('versionlatest', 'qtype_saylorcode'),
            exercise_resolver::POLICY_PINNED => get_string('versionpinned', 'qtype_saylorcode'),
        ]);
        $mform->setDefault('versionpolicy', exercise_resolver::POLICY_PINNED);
        $mform->addHelpButton('versionpolicy', 'versionpolicy', 'qtype_saylorcode');
        $mform->hideIf('versionpolicy', 'stableid', 'eq', '');

        $mform->addElement('text', 'pinnedversion', get_string('pinnedversion', 'qtype_saylorcode'), ['size' => 6]);
        $mform->setType('pinnedversion', PARAM_INT);
        $mform->hideIf('pinnedversion', 'versionpolicy', 'neq', exercise_resolver::POLICY_PINNED);
        $mform->hideIf('pinnedversion', 'stableid', 'eq', '');

        // A question is marked by its test cases, and HTML and CSS are drawn
        // in the browser with no output to mark, so they are not offered.
        $profiles = (new profile_manager())->get_menu(false);
        $mform->addElement('select', 'profileid', get_string('profileid', 'qtype_saylorcode'), $profiles);
        $mform->setDefault('profileid', 'java17-console');

        $mform->addElement('text', 'entryfilename', get_string('entryfilename', 'qtype_saylorcode'), ['size' => 40]);
        $mform->setType('entryfilename', PARAM_FILE);
        $mform->setDefault('entryfilename', 'Main.java');

        $mform->addElement('header', 'contenthdr', get_string('owncontent', 'qtype_saylorcode'));
        $mform->addElement('static', 'contentintro', '', get_string('owncontent_help', 'qtype_saylorcode'));

        $mform->addElement(
            'textarea',
            'startercode',
            get_string('startercode', 'qtype_saylorcode'),
            ['rows' => 10, 'cols' => 80, 'spellcheck' => 'false']
        );
        $mform->setType('startercode', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'referencesolution',
            get_string('referencesolution', 'qtype_saylorcode'),
            ['rows' => 10, 'cols' => 80, 'spellcheck' => 'false']
        );
        $mform->setType('referencesolution', PARAM_RAW);

        $mform->addElement(
            'textarea',
            'testcases',
            get_string('testcases', 'qtype_saylorcode'),
            ['rows' => 8, 'cols' => 80, 'spellcheck' => 'false']
        );
        $mform->setType('testcases', PARAM_RAW);
        $mform->addHelpButton('testcases', 'testcases', 'qtype_saylorcode');
    }

    /**
     * Check the submitted question.
     *
     * @param array $fromform Submitted data.
     * @param array $files Submitted files.
     * @return array Errors keyed by element name.
     */
    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        $stableid = trim((string) ($fromform['stableid'] ?? ''));

        if ($stableid !== '' && !stable_id::is_valid($stableid)) {
            $errors['stableid'] = get_string('stableidinvalid', 'qtype_saylorcode');
        }

        // A pinned policy with no version resolves as a broken pin and quietly
        // falls back to whatever the question itself carries, which on a graded
        // quiz is the last place a silent substitution belongs.
        if (
            $stableid !== ''
                && ($fromform['versionpolicy'] ?? '') === exercise_resolver::POLICY_PINNED
                && (int) ($fromform['pinnedversion'] ?? 0) < 1
        ) {
            $errors['pinnedversion'] = get_string('pinnedversionrequired', 'qtype_saylorcode');
        }

        $cases = json_decode((string) ($fromform['testcases'] ?? ''), true);
        $hascases = is_array($cases) && $cases !== [];

        if (trim((string) ($fromform['testcases'] ?? '')) !== '' && !is_array($cases)) {
            $errors['testcases'] = get_string('testcasesinvalid', 'qtype_saylorcode');
        } else if ($hascases) {
            $problem = self::first_malformed_case($cases);

            if ($problem !== null) {
                $errors['testcases'] = $problem;
            }
        }

        // Without either a library reference or its own cases there is nothing
        // to grade against, and every attempt would land in the manual queue.
        if ($stableid === '' && !$hascases) {
            $errors['testcases'] = get_string('needsomethingtograde', 'qtype_saylorcode');
        }

        return $errors;
    }

    /**
     * The first thing wrong with a set of test cases, if anything is.
     *
     * A well formed JSON array is not the same as a usable set of cases, and
     * the difference matters more here than anywhere else in the suite. A case
     * with no expected value at all was accepted, and grading then compared the
     * program's output against an empty string: a submission that printed
     * nothing passed it, and with the default weight of one that was full
     * marks. On a graded quiz. So each entry is checked, not just the array.
     *
     * An expected value that is deliberately empty is fine. A missing one is
     * not, because nobody means it.
     *
     * @param array $cases The decoded cases.
     * @return string|null The problem, or null when they are all usable.
     */
    protected static function first_malformed_case(array $cases): ?string {
        foreach ($cases as $index => $case) {
            $position = $index + 1;

            if (!is_array($case)) {
                return get_string('casenotanobject', 'qtype_saylorcode', $position);
            }

            if (!array_key_exists('expected', $case)) {
                return get_string('casenoexpected', 'qtype_saylorcode', $position);
            }

            if (array_key_exists('weight', $case) && (float) $case['weight'] <= 0) {
                return get_string('caseweightnotpositive', 'qtype_saylorcode', $position);
            }
        }

        return null;
    }
}
