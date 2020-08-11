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
 * Exam Form
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

class examcreate_form extends moodleform
{
    public function definition()
    {
        global $CFG;

        $mform = $this->_form;

        $hiddenFields = $this->_customdata['hidden'] ?? [];

        $mform->addElement(
            'header',
            'examcreateformheader',
            get_string('examcreateformheader', 'local_examdelay')
        );

        $mform->addElement(
            'text',
            'name',
            get_string('examname', 'local_examdelay')
        );
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement(
            'duration',
            'delay',
            get_string('examdelay', 'local_examdelay')
        );

        $mform->addElement(
            'submit',
            'createsubmit',
            get_string('submitexamparent', 'local_examdelay')
        );

        // Add all hidden fields as key=name.
        foreach ($hiddenFields as $key => $value)
        {
            $mform->addElement('hidden', $key, $value);
            $mform->setType($key, PARAM_RAW);
        }

        // Collapse this section.
        $mform->setExpanded('examcreateformheader', false);
    }

    function validation($data, $files)
    {
        $errors = [];

        if (empty($data['name']))
        {
            $errors['name'] = "Exam name cannot be empty.";
        }

        return $errors;
    }
}
