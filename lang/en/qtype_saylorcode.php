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
 * Language strings for the Saylor Code Studio question type.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['answercode'] = 'Your code ({$a})';
$string['awaitinggrading'] = 'This answer has not been marked yet. Your code was saved. It could not be run when you submitted, so a teacher will mark it rather than the system guessing.';
$string['casenoexpected'] = 'Test case {$a} has no expected value. Give it one, even an empty string if the program should print nothing: a missing value would be compared against nothing at all, and any submission would pass it.';
$string['casenotanobject'] = 'Entry {$a} in the test cases is not an object.';
$string['caseweightnotpositive'] = 'Test case {$a} has a weight of zero or less, so it could never affect the mark.';
$string['entryfilename'] = 'File the student edits';
$string['exercise'] = 'Exercise';
$string['gradedbyrunning'] = 'Your code will be compiled and run against a set of tests when you submit.';
$string['needsomethingtograde'] = 'This question needs either a library reference or its own test cases. Without one of them there is nothing to grade against, and every attempt would wait for a teacher.';
$string['owncontent'] = 'Content held on this question';
$string['owncontent_help'] = 'Used only when the question names no library exercise, or names one that has no published version yet. A question backed by the library ignores these fields.';
$string['pinnedversion'] = 'Pinned version';
$string['pinnedversionrequired'] = 'A pinned question needs a version number. Without one it falls back to whatever this question carries, which is not what pinning is for.';
$string['pleasewritecode'] = 'Write some code before submitting.';
$string['pluginname'] = 'Saylor Code Studio';
$string['pluginname_help'] = 'A coding exercise. The student writes code, and it is graded by compiling and running it against test cases. Point the question at a library exercise so the same exercise can also appear in an activity or a book, or give the question its own starter code and tests.';
$string['pluginname_link'] = 'question/type/saylorcode';
$string['pluginnameadding'] = 'Adding a Saylor Code Studio question';
$string['pluginnameediting'] = 'Editing a Saylor Code Studio question';
$string['pluginnamesummary'] = 'The student writes code, which is compiled and run against test cases to mark it. Exercises can be shared with activities and book embeds through the Saylor Code Studio library.';
$string['privacy:metadata'] = 'The Saylor Code Studio question type stores no personal data of its own. The code a student writes is held by the question engine as their response, and the execution records belong to the service layer.';
$string['profileid'] = 'Language';
$string['referencesolution'] = 'Reference solution';
$string['stableid'] = 'Library reference';
$string['stableid_help'] = 'The identifier of a library exercise, such as CS101-U01-E01. Leave it empty to keep this question\'s content on the question itself.';
$string['stableidinvalid'] = 'That is not a valid exercise reference. The expected form is COURSE-Unn-Enn, for example CS101-U05-E03.';
$string['startercode'] = 'Starter code';
$string['testcases'] = 'Test cases, as a JSON array';
$string['testcases_help'] = 'Each entry needs at least a name and an expected value, and may set ispublic to false to hide it. A hidden case still counts towards the mark, and a student is never told its name, its input or its expected value.';
$string['testcasesinvalid'] = 'This must be a JSON array, or empty.';
$string['versionlatest'] = 'Use the latest published version';
$string['versionpinned'] = 'Pin to one version';
$string['versionpolicy'] = 'Version';
$string['versionpolicy_help'] = 'Pinning is the default here, unlike elsewhere. A quiz question usually carries a mark, and a graded question should not change under a student because someone published a new version of the exercise mid-term.';
