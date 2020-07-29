<?php
// This file is part of JWT authentication plugin for Moodle.
//
// JWT authentication plugin for Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// JWT authentication plugin for Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with jwt authentication plugin for Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains authentication api functions for the JWT authentication plugin.
 *
 * @package   local_examdelay
 * @copyright 2020 Adam King
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once("$CFG->dirroot/local/examdelay/exam.php");

use local_examdelay\Exam;

class local_examdelay_external extends external_api
{
    public static function get_exams_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function get_exams_returns()
    {
        return new external_single_structure(
            [
                'exams' => new external_value(PARAM_TEXT), 'A JSON-encoded array of exams.'
            ]
        );
    }

    public static function get_exams()
    {
        $exams = Exam::get_all_exams();

        return [
            'exams' => json_encode($exams)
        ];
    }

    public static function get_children_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function get_children_returns()
    {
        return new external_single_structure(
            [
                'children' => new external_value(PARAM_TEXT), 'A JSON-encoded array of exam children.'
            ]
        );
    }

    public static function get_children()
    {
        $children = Exam::get_all_children();

        return [
            'children' => json_encode($children)
        ];
    }

    public static function exam_exists_parameters()
    {
        return new external_function_parameters(
            [
                'instance' => new external_value(PARAM_INT, 'An Instance ID number.')
            ]
        );
    }

    public static function exam_exists_returns()
    {
        return new external_single_structure(
            [
                'exists' => new external_value(PARAM_BOOL, 'Whether an exam exists on the given instance.')
            ]
        );
    }

    // TODO:- The is_exam method needs to return bools.
    public static function exam_exists($instance)
    {
        return [
            'exists' => Exam::is_exam($instance)
        ];
    }

    function get_exam_parameters()
    {
        return new external_function_parameters(
            [
                'instance' => new external_value(PARAM_INT, 'An Instance ID number.')
            ]
        );
    }

    function get_exam_returns()
    {
        return new external_single_structure(
            [
                'exam' => new external_value(PARAM_TEXT, 'A JSON-encoded Exam object')
            ]
        );
    }

    function get_exam($instance)
    {
        $exam = Exam::get_parent($instance);

        return [
            'exam' => json_encode($exam)
        ];
    }

    function create_exam_parent_parameters()
    {
        return new external_function_parameters(
            [
                'name' => new external_value(PARAM_TEXT, 'The exam parent name.')
            ]
        );
    }

    function create_exam_parent_returns()
    {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'The exam parent ID')
            ]
        );
    }

    function create_exam_parent($name)
    {
        return [
            'id' => Exam::create_parent($name)
        ];
    }

    function delete_exam_parent_parameters()
    {
        return new external_function_parameters(
            [
                'parent' => new external_value(PARAM_INT, 'The exam parent ID.')
            ]
        );
    }

    function delete_exam_parent_returns()
    {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_INT, 'Disregard.')
            ]
        );
    }

    function delete_exam_parent($parent)
    {
        Exam::delete_parent($parent);

        return [
            'status' => 200
        ];
    }

    function update_exam_parameters()
    {
        return new external_function_parameters(
            [
                'instance' => new external_value(PARAM_INT, 'An Instance ID number.'),
                'exam' => new external_value(PARAM_BOOL, 'A boolean for Exam status.'),
                'parent' => new external_value(PARAM_INT, 'An Exam ID')
            ]
        );
    }

    function update_exam_returns()
    {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_TEXT, 'The output status of the action.')
            ]
        );
    }

    function update_exam($instance, $exam, $parent)
    {
        $isExam = Exam::is_exam($instance);
        $status = "";

        if ($isExam)
        {
            if ($exam == false)
            {
                Exam::delete_from_instance($instance);
                $status = "delete";
            }
            else
            {
                Exam::update_child($instance, $parent);
                $status = "update";
            }
        }
        else
        {
            if ($exam == true)
            {
                Exam::create($instance, $parent);
                $status = "create";
            }
        }

        return [
            'status' => $status
        ];
    }

    function get_exam_time_parameters()
    {
        return new external_function_parameters(
            [
                'instance' => new external_value(PARAM_INT, 'An Instance ID number.'),
            ]
        );
    }

    function get_exam_time_returns()
    {
        return new external_single_structure(
            [
                'time' => new external_value(PARAM_TEXT, 'A string representation of the Exam delay.')
            ]
        );
    }

    // TODO:- Consider returning the entire object for flexibility.
    function exam_get_time($instance)
    {
        if (Exam::is_exam($instance))
        {
            $timeleft = Exam::get_time_left_instance($instance);
            return [
                'time' => ($timeleft !== false) ? $timeleft->format("%d days, %h hours") : ""
            ];
        }

        return [
            'time' => ''
        ];
    }

    function exam_cmid_toinstance_parameters()
    {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT, 'A course module ID.')
            ]
        );
    }

    function exam_cmid_toinstance_returns()
    {
        return new external_single_structure(
            [
                'instance' => new external_value(PARAM_INT, 'An instance ID number.')
            ]
        );
    }

    function exam_cmid_toinstance($cmid)
    {
        global $DB;

        $cm = $DB->get_record(
            'course_modules',
            ['id' => $cmid],
            '*',
            IGNORE_MISSING
        );

        return [
            'instance' => (!empty($cm)) ? $cm->instance : -1
        ];
    }
}
