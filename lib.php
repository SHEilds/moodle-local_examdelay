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
 * Library functions
 *
 * @package   local_examdelay
 * @copyright 2017 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// namespace local_examdelay;

defined('MOODLE_INTERNAL') || die();
global $CFG, $PAGE, $USER, $DB, $COURSE;

// require_once("$CFG->dirroot/local/examdelay/edit.php");
require_once("$CFG->dirroot/local/examdelay/exam.php");
require_once("$CFG->dirroot/message/lib.php");

// Define the DELAY constant from config.
$config = get_config('local_examdelay');
$examdelay = "PT".$config->examdelay."S" ?? "PT0S";
define('DELAY', $examdelay);

$adminpagetypes = array('mod-quiz-mod', 'mod-quiz-edit');
$clientpagetypes = array('mod-quiz-view', 'mod-quiz-attempt');

/**
* Insert the 'Exam Delay' link into the navigation.
*/
function local_examdelay_extends_settings_navigation($settings)
{
    local_examdelay_extend_settings_navigation($settings);
}

function local_examdelay_extend_settings_navigation($settings)
{
    global $PAGE, $CFG;

    $adminpagetypes = array('mod-quiz-mod', 'mod-quiz-edit');
    $clientpagetypes = array('mod-quiz-view', 'mod-quiz-attempt');

    if (in_array($PAGE->pagetype, array_merge($adminpagetypes, $clientpagetypes))) {
        if (has_capability('mod/quiz:manage', $PAGE->cm->context)) {
            $branch = $settings->get('modulesettings');
            $branch->add(
                "Exam Delay",
                new moodle_url("/local/examdelay/edit.php", array('id' => $PAGE->cm->instance)),
                navigation_node::TYPE_SETTING, null, 'mod_exam_edit'
            );
        }
    }
}

// Only process the logic if the user lands on a Quiz page of any kind.
if (in_array($PAGE->pagetype, array_merge($adminpagetypes, $clientpagetypes))) {
    // Load page-dependant javascript for view editing.
    if ($PAGE->pagetype === 'mod-quiz-mod') {
        $PAGE->requires->js("/local/examdelay/mod.js");
    }

    // Perform client checks on quiz load.
    if (in_array($PAGE->pagetype, $clientpagetypes)) {
        $user = $USER->id;
        $instance = $PAGE->cm->instance;

        print_object(array('place'=>'holder'));
        $isex = Exam::is_exam($instance) ? "IS EXAM" : "IS NOT EXAM";
        print_object($isex);

        if (Exam::is_exam($instance)) {
            // Test if the user has made an attempt on this exam before.
            $latestAttempt = Exam::get_exam_attempt($instance, $user);
            $parent = Exam::get_parent($instance);

            $latempt = !empty($latestAttempt) ? "NOT EMPTY" : "EMPTY";
            print_object($latempt);
            print_object($latestAttempt);
            print_object(Exam::get_parent($instance));

            // If the user has made an attempt before, make sure it's not on
            // this instance; also check if their delay is over.
            if ($parent !== false) {
                if (!empty($latestAttempt) && $latestAttempt !== false) {
                    $context = \context_module::instance($PAGE->cm->id);
                    $previousAttempt = Exam::get_user_attempt($instance, $user);

                    // If the student's previous attempt is finished.
                    if (!empty($previousAttempt)) {
                        if ($previousAttempt->state === "finished" || $previousAttempt->state === "inprogress") {
                            // Let Moodle handle this case.
                        } else {
                            // If the exam has been touched before and is not ready to be re-attempted, redirect the user.
                            if (!Exam::is_ready($latestAttempt)) {
                                if (!has_capability('mod/quiz:manage', $context)) {
                                    $url = new \moodle_url("/local/examdelay/examerror.php", array(
                                        'id' => $instance,
                                        'cmid' => $PAGE->cm->id,
                                        'error' => 1,
                                        'course' => $COURSE->id
                                    ));
                                    redirect($url);
                                }
                            }
                        }
                    } else {
                        // If the exam has not been touched before and is not ready to be re-attempted, redirect the user.
                        if (!Exam::is_ready($latestAttempt, $parent)) {
                            if (!has_capability('mod/quiz:manage', $context)) {
                                $url = new \moodle_url("/local/examdelay/examerror.php", array(
                                    'id' => $instance,
                                    'cmid' => $PAGE->cm->id,
                                    'error' => 1,
                                    'course' => $COURSE->id
                                ));
                                header("Location: {$url->out(false)}");
                            }
                        }
                    }
                }
            } else {
                $url = new \moodle_url("/local/examdelay/examerror.php", array(
                    'id' => $instance,
                    'cmid' => $PAGE->cm->id,
                    'error' => 3,
                    'course' => $COURSE->id
                ));
                redirect($url);
            }
        }
    }
}

if ($PAGE->pagetype == 'course-view-topics') {
    $PAGE->requires->js("/local/examdelay/course.js");
}