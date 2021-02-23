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
 * Exam Module Form
 *
 * @package   local_examdelay
 * @copyright 2020 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL'))
{
    die('Direct access to this script is forbidden!');
}

require_once($CFG->libdir . '/formslib.php');

class examdelaymod_form extends moodleform
{
    public function definition()
    {
        global $CFG;

        $mform = $this->_form;

        // Render data.
        $parents = $this->_customdata['parents'];
        $hiddenFields = $this->_customdata['hidden'] ?? [];

        // Create parent select array.
        $parentSelect = [];
        $parentSelect[] = '';
        foreach ($parents as $id => $parent)
        {
            $delayString = Exam::get_delay_setting_string($parent->delay);
            $parentSelect[$id] = "{$parent->name} ({$delayString})";
        }

        $mform->addElement(
            'header',
            'exammodformheader',
            get_string('exammodformheader', 'local_examdelay')
        );

        $mform->addElement(
            'advcheckbox',
            'exam',
            get_string('isexam', 'local_examdelay'),
            '',
            [
                'class' => 'checkbox-custom checkbox-primary my-2 r1'
            ]
        );

        $parentSelectGroup = [];
        $parentSelectGroup[] = &$mform->createElement(
            'select',
            'selectparent',
            get_string('examparent', 'local_examdelay'),
            $parentSelect
        );
        $mform->setDefault('selectparent', null);

        $parentSelectGroup[] = &$mform->createElement(
            'submit',
            'deleteparent',
            get_string('deleteexamparent', 'local_examdelay')
        );

        $mform->addGroup(
            $parentSelectGroup,
            'parentSelectGroup',
            get_string('examparent', 'local_examdelay'),
            ' ',
            false
        );

        // Add all hidden fields as key=name.
        foreach ($hiddenFields as $key => $value)
        {
            $mform->addElement('hidden', $key, $value);
            $mform->setType($key, PARAM_RAW);
        }

        $this->add_action_buttons(true, 'Save');
    }

    function validation($data, $files)
    {
        $errors = [];

        $exam = $data['exam'] ?? 0;
        $parent = $data['selectparent'] ?? 0;

        // User ticked exam without a parent present.
        if ($exam && !$parent)
        {
            $errors['parentSelectGroup'] = "No exam parent selected.";
        }

        return $errors;
    }
}
