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
 * Exam Edit Form
 *
 * @package   local_examdelay
 * @copyright 2017 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 class examdelay_edit_form extends moodleform {
    public function definition() {
        global $CFG, $DB;

        $instance = $this->_customdata['instance'];
        $exammode = Exam::is_exam($instance) ? 1 : 0;
        $parent = Exam::get_parent($instance);
        $parents = Exam::get_all_exams();

        $values = array();
        foreach ($parents as $parenttmp) {
            $values[$parenttmp->id] = $parenttmp->name;
        }

        $mform = $this->_form;
        $mform->addElement('selectyesno', 'exammode', get_string('exammode', 'local_examdelay'));
        $mform->addElement('select', 'parentselect', get_string('parentname', 'local_examdelay'), $values);
        $mform->addElement('duration', 'examdelay', get_string('examdelay', 'local_examdelay'));

        if ($parent !== false) {
            $mform->addElement('editor', 'exammessage', "Error Message")->setValue(array('text' => $parent->message));
        } else {
            $mform->addElement('editor', 'exammessage', "Error Message")->setValue(
                array('text' => "<p>If you have failed your first attempt of the exam, you must ".
                                "wait <b>@delay</b> and you can then attempt the next exam.</p>")
            );
        }

        $mform->setType('exammessage', PARAM_RAW);
        $mform->setDefault('exammode', $exammode);

        if ($parent !== false) {
            $mform->setDefault('parentselect', $parent->id);
            $mform->setDefault('examdelay', $parent->delay);
        }

        $this->add_action_buttons(true, "Submit");
    }
}

class examdelay_create_form extends moodleform {
    public function definition() {
        global $CFG;

        $instance = $this->_customdata['instance'];

        $mform = $this->_form;
        $mform->addElement('text', 'parentname', get_string('parentname', 'local_examdelay'));
        $mform->setType('parentname', PARAM_TEXT);
        $this->add_action_buttons(true, "Create");
    }
}

class examdelay_delete_form extends moodleform {
    public function definition() {
        global $CFG;

        $instance = $this->_customdata['instance'];
        $parents = Exam::get_all_exams();

        $values = array();
        foreach ($parents as $parent) {
            $values[$parent->id] = $parent->name;
        }

        $mform = $this->_form;
        $mform->addElement('select', 'deleteparent', get_string('deleteparent', 'local_examdelay'), $values);
        $this->add_action_buttons(true, "Delete");
    }
}