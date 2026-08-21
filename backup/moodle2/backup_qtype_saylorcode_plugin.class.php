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
 * Backup support for the Saylor Code Studio question type.
 *
 * @package    qtype_saylorcode
 * @copyright  2026 Saylor Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_qtype_saylorcode_plugin extends backup_qtype_plugin {
    /**
     * Describe what to include for each question of this type.
     *
     * @return backup_plugin_element
     */
    protected function define_question_plugin_structure() {
        $plugin = $this->get_plugin_element(null, '../../qtype', 'saylorcode');
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        $options = new backup_nested_element('saylorcode', ['id'], [
            'stableid',
            'versionpolicy',
            'pinnedversion',
            'profileid',
            'entryfilename',
            'startercode',
            'testcases',
            'referencesolution',
        ]);
        $pluginwrapper->add_child($options);

        $options->set_source_table('qtype_saylorcode_options', ['questionid' => backup::VAR_PARENTID]);

        return $plugin;
    }
}
