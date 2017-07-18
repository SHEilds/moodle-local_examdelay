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
 * Javascript request handler
 *
 * @package   local_examdelay
 * @copyright 2017 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_examdelay;

define('AJAX_SCRIPT', true);
require_once __DIR__.'/../../config.php';
require_once $CFG->dirroot . '/user/profile/lib.php';
require_once __DIR__ . '/exam.php';

if (!empty($_GET)) {
    $cmid = (isset($_GET["id"])) ? $_GET["id"] : null;
    $query = (isset($_GET["query"])) ? $_GET["query"] : null;
    $exam = (isset($_GET["exam"])) ? $_GET["exam"] : null;
    $table = (isset($_GET["table"])) ? $_GET["table"] : null;
    $target = (isset($_GET["target"])) ? $_GET["target"] : null;

    $cm = null;
    $instance = null;
    if (!empty($cmid)) {
        $cm = Exam::cmid_to_cm($cmid);
        if (!empty($cm)) {
            $instance = $cm->instance;
        }
    }

    if (!empty($query)) {
        switch ($query) {
            case "exists":
                echo exam_exists_request($instance);
                break;
            case "getparents":
                echo exam_get_request($table, $target);
                break;
            case "getCurrentParent":
                echo exam_get_current($instance);
                break;
            case "getchildren":
                echo exam_get_children();
                break;
            case "time":
                echo exam_get_time($instance);
                break;
            case "cmidtoinstance":
                echo exam_cmid_toinstance($cmid);
                break;
            default:
                echo "debugget";
                break;
        }
    }
}

if (!empty($_POST)) {
    $id = (isset($_POST["id"])) ? $_POST["id"] : null;
    $exam = (isset($_POST["exam"])) ? $_POST["exam"] : null;
    $name = (isset($_POST["name"])) ? $_POST["name"] : null;
    $query = (isset($_POST["query"])) ? $_POST["query"] : null;
    $parent = (isset($_POST["parent"])) ? $_POST["parent"] : null;

    $cm = null;
    $instance = null;
    if (!empty($id)) {
        $cm = Exam::cmid_to_cm($id);
        $instance = $cm->instance;
    }

    if (!empty($query)) {
        switch ($query) {
            case "update":
                echo exam_update_request($instance, $exam, $parent);
                break;
            case "createparent":
                echo Exam::create_parent($name);
                break;
            case "deleteparent":
                Exam::delete_parent($id);
                break;
            default:
                echo "debugpost";
                break;
        }
    }
}

function exam_get_request($table, $target) {
    $exams = Exam::get_all_exams();
    return json_encode($exams);
}

function exam_exists_request($instance) {
    return (Exam::is_exam($instance)) ? "true" : "false";
}

function exam_update_request($instance, $exam, $parent) {
    global $PAGE;

    $is_exam = Exam::is_exam($instance);

    if ($is_exam == true) {
        if ($exam == "false") {
            Exam::delete_from_instance($instance);
            echo "deleted";
        } else {
            // Update instead of delete.
            return Exam::update_child($instance, $parent);
        }
    } else {
        if ($exam == "true") {
            echo Exam::create($instance, $parent);
        } else {
            return true;
        }
    }
}

function exam_get_current($instance) {
    return Exam::get_parent($instance);
}

function exam_get_time($instance) {
    // return Exam::get_time_left();
    if (Exam::is_exam($instance)) {
        $timeleft = Exam::get_time_left_instance($instance);
        return $timeleft->format('%d days, %h hours');
    } else {
        return "false";
    }

}

function exam_get_children() {
    global $USER;

    $children = Exam::get_all_children();

    // foreach ($children as $key => $child) {
    //     $timeleft = Exam::get_time_left_instance($child->instance);
    //     if ($timeleft !== false) {
    //         $child->time = $timeleft->format('%d days, %h hours');
    //     } else {
    //         $child->time = "false";
    //     }
    // }

    return json_encode($children);
}

function exam_cmid_toinstance($cmid) {
    $timeleft = Exam::get_time_left_cmid($cmid);
    return ($timeleft !== false) ? $timeleft : "false";
}