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
 * Restore support for the Saylor Code Studio question type.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_qtype_saylorcode_plugin extends restore_qtype_plugin {
    /**
     * The paths this plugin restores.
     *
     * @return array
     */
    protected function define_question_plugin_structure() {
        return [
            new restore_path_element('saylorcode', $this->get_pathfor('/saylorcode')),
        ];
    }

    /**
     * Restore one question's options.
     *
     * @param array $data The parsed data.
     * @return void
     */
    public function process_saylorcode($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $newquestionid = $this->get_new_parentid('question');
        $questioncreated = $this->get_mappingid('question_created', $this->get_old_parentid('question'));

        // Only questions being created need their options written. A question
        // that already existed keeps the options it has.
        if (!$questioncreated) {
            return;
        }

        $data->questionid = $newquestionid;
        $newitemid = $DB->insert_record('qtype_saylorcode_options', $data);
        $this->set_mapping('qtype_saylorcode_options', $oldid, $newitemid);
    }
}
