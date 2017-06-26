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

namespace local_examdelay;

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

$timestring = "ready";
if ($timeleft !== false) {
    $timestring = $timeleft->format('%d days, %h hours, %i minutes and %s seconds');
}

$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Exam Not Ready");
$PAGE->set_heading("Exam Attempt");
$PAGE->set_url($CFG->wwwroot.'/examerror.php');

echo $OUTPUT->header();

// Print error to the user.
$container = "<div style ='margin:auto;text-align:center;'>";
switch($error) {
    case NOTREADY:
        $error = NOTREADY;
        if ($timestring === "ready") {
            $container .= "<p>The exam is ready for you to re-attempt. Please choose the next available examination.</p>";
        } else {
            $container .= "<p>If you have failed your first attempt of the exam, you must wait 10 days and you can then attempt the next exam.</p>".
                "<p>Please try the next exam in <b>$timestring.</b></p>";
        }
        break;
    case ATTEMPTED:
        $error = ATTEMPTED;
        $container .= "<p>This exam has already been attempted, please try the next one.</p>";
        break;
    case NOTEXISTS:
        $error = NOTEXISTS;
        $container .= "<p>This exam no longer exists somehow, please contact an administrator.</p>";
        break;
    default:
        $error = DEF;
        $container .= "<p>Something went wrong trying to attempt an exam.</p>";
        break;
}
$container .= "</div>";

// Print continue button.
echo "<form method='post' action='$CFG->wwwroot/course/view.php'>".
        "<div style='margin:auto;text-align:center;'>".
            "<input type='submit' value='Continue'>".
            "<input type='hidden' name='id' value='$course'>".
        "</div>".
     "</form>";

echo $OUTPUT->footer();