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
 * Exam Unavailable
 *
 * @package   local_examdelay
 * @copyright 2017 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace local_examdelay;

date_default_timezone_set('UTC');

require_once(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/user/profile/lib.php");
require_once("$CFG->dirroot/local/examdelay/exam.php");

const DEF = 0;
const NOTREADY = 1;
const ATTEMPTED = 2;
const NOTEXISTS = 3;

$instance = required_param('id', PARAM_INT);
$error = required_param('error', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$course = required_param('course', PARAM_INT);

$latestAttempt = Exam::get_exam_attempt($instance, $USER->id);
$timeleft = Exam::get_time_left_instance($instance);
$parent = Exam::get_parent($instance);

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Exam Not Ready");
$PAGE->set_heading("Exam Attempt");
$PAGE->set_url($CFG->wwwroot."/local/examdelay/examerror.php?id=$instance&error=$error&cmid=$cmid&course=$course");
$PAGE->set_periodic_refresh_delay(5);

// Print error to the user.
switch($error) {
    case NOTREADY:
        $error = NOTREADY;
        if ($timeleft == false) {
            $url = new \moodle_url("/course/view.php", array(
                'id' => $course
            ));
            redirect($url);
        } else {
            $dateTimeEmpty = new \DateTime('@0');
            $dateTimeFull  = new \DateTime("@$parent->delay");
            $delay = $dateTimeEmpty->diff($dateTimeFull)->format('%d days, %h hours, %i minutes and %s seconds');

            $timestring = "now";
            if ($timeleft !== false) {
                $timestring = "in " . $timeleft->format('%d days, %h hours, %i minutes and %s seconds');
            }

            $message = "<p>If you have failed your first attempt of the exam, you must wait <b>@delay</b> and you can then attempt the next exam.</p>";
            if (!empty($parent->message) && $parent->message !== "NULL") {
                $message = $parent->message;
                $message = str_replace('@delay', $delay, $message);
            }

            echo $OUTPUT->header();
            echo $message . "<p style='text-align: center;'>Please try the next exam <b>$timestring</b>.</p>";
        }
        break;
    case ATTEMPTED:
        $error = ATTEMPTED;
        echo $OUTPUT->header();
        echo "<p>This exam has already been attempted, please try the next one.</p>";
        break;
    case NOTEXISTS:
        $error = NOTEXISTS;
        echo $OUTPUT->header();
        echo "<p>This exam no longer exists somehow, please contact an administrator.</p>";
        break;
    default:
        $error = DEF;
        echo $OUTPUT->header();
        echo "<p>Something went wrong trying to attempt an exam.</p>";
        break;
}

// Print continue button.
echo "<form method='post' action='$CFG->wwwroot/course/view.php'>".
        "<div style='margin:auto;text-align:center;'>".
            "<input type='submit' value='Continue'>".
            "<input type='hidden' name='id' value='$course'>".
        "</div>".
     "</form>";

echo $OUTPUT->footer();