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

namespace local_examdelay;

date_default_timezone_set('UTC');

// Replace moodle internal with files that offer the variables.
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

const RELATIONS_TABLE = 'local_examdelay_relations';
const ATTEMPTS_TABLE = 'quiz_attempts';
const EXAMS_TABLE = 'local_examdelay_exams';
const CHILD_TABLE = 'local_examdelay_children';

// Define the DELAY constant from config.
$config = get_config('local_examdelay');
$examdelay = "PT".$config->examdelay."S" ?? "PT0S";
define('DELAY', $examdelay);

class Exam {
    /**
     *  A method to determine whether the current user has a previous
     *  attempt on the examination.
     *
     *  @return boolean Whether the user has attempted the exam before.
     */
    public static function has_user_attempt($instance, $user) {
        global $DB;

        $attempt = $DB->get_record(ATTEMPTS_TABLE, array(
            'quiz' => $instance,
            'userid' => $user
        ));

        return (!empty($attempt)) ? true : false;
    }

    public static function get_user_attempt($instance, $user) {
        global $DB;

        $attempt = $DB->get_record(ATTEMPTS_TABLE, array(
            'quiz' => $instance,
            'userid' => $user
        ));

        return $attempt;
    }

    public static function get_exam_attempt($instance, $user) {
        global $DB;

        $attempt = false;
        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));
        $children = $DB->get_records(CHILD_TABLE, array('parent' => $parent->id));

        $index = 0;
        $childQuery = '(';
        foreach ($children as $key => $child) {
            $childQuery .= $child->instance;

            if ($index !== count($children) - 1) {
                $childQuery .= ',';
            }

            $index++;
        }
        $childQuery .= ')';

        if ($childQuery !== '()') {
            if (!empty($child)) {
                $latestAttempt = $DB->get_record_sql(
                    'SELECT * FROM mdl_'.ATTEMPTS_TABLE.' WHERE quiz IN '.
                    $childQuery.
                    ' AND userid = :userid AND state = :state AND timefinish < UNIX_TIMESTAMP() ORDER BY timefinish DESC LIMIT 1',
                    array(
                        'userid' => $user,
                        'state' => "finished"
                    ));

                if (!empty($latestAttempt)) {
                    $attempt = $latestAttempt;
                }
            }
        }

        return $attempt;
    }

    public static function get_time_left_instance($instance) {
        global $DB, $USER;

        $lastAttempt = Exam::get_exam_attempt($instance, $USER->id);

        if (empty($lastAttempt)) {
            return false;
        } else {
            $submitted = new \DateTime("@$lastAttempt->timefinish");
            $submitted->setTimezone(new \DateTimeZone("UTC"));

            $available = $submitted->add(new \DateInterval(DELAY));

            $present = new \DateTime('now');
            $present->setTimezone(new \DateTimeZone("UTC"));

            if ($available < $present) {
                return false;
            } else {
                $diff = $present->diff($available);

                return $diff;
            }
        }
    }

    public static function get_time_left_cmid($cmid) {
        global $DB, $USER;

        // $cm = get_coursemodule_from_id('quiz', $cmid);
        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', IGNORE_MISSING);

        if (!empty($cm)) {
            $instance = $cm->instance;
            return Exam::get_time_left_instance($instance);
        } else {
            return "false";
        }
    }

    public static function get_child($instance) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        return $child;
    }

    /**
     *  A method to check whether an examination is ready to be re-attempted by the
     *  current user.
     *
     *  @param Object Attempt object from the database.
     *  @return boolean Whether the exam is ready to be re-attempted by the current user.
     */
    public static function is_ready($attempt) {
        global $DB;

        $finished = new \DateTime("@$attempt->timefinish");
        $finished->setTimezone(new \DateTimeZone("UTC"));
        $present = new \DateTime('now');
        $ready = $finished->add(new \DateInterval(DELAY));

        return ($ready < $present) ? true : false;
    }

    /**
     *  A method to check, in the database, whether a quiz is classed as an exam or not.
     *
     *  @param integer The instance of the quiz (exam) in question.
     *  @return boolean Whether the quiz is classified as an examination or not.
     */
    public static function is_exam($instance) {
        global $DB;

        $exam = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        return !empty($exam) ? true : false;
    }

    /**
     *  A method to check, in the database, whether an exam has any attempts on it.
     *
     *  @param integer The instance of the quiz (exam) in question.
     *  @return boolean Whether the quiz has any attempts on it at all.
     */
    public static function has_attempts($instance) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('id' => $instance));
        return ($child->attempts > 0) ? true : false;
    }

    public static function get_parent($instance) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));

        if (empty($child)) {
            return false;
        }

        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));

        if (empty($parent)) {
            return false;
        }

        return $parent;
    }

    public static function create_parent($name, $children = array()) {
        global $DB, $PAGE;

        $exam = new \stdClass();
        $exam->name = $name;

        $recordid = $DB->insert_record(EXAMS_TABLE, $exam);

        return $recordid;
    }

    public static function delete_parent($id) {
        global $DB;

        $DB->delete_records(EXAMS_TABLE, array('id' => $id));
        $DB->delete_records(CHILD_TABLE, array('parent' => $id));
        $DB->delete_records(RELATIONS_TABLE, array('parent' => $id));
    }

    public static function create($instance, $parent) {
        global $DB;

        $recordid = -1;

        $child = new \stdClass();
        $child->instance = $instance;
        $child->parent = $parent;

        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $parent));

        if (!empty($parent)) {
            $recordid = $DB->insert_record(CHILD_TABLE, $child);

            $relationship = new \stdClass();
            $relationship->parent = $parent->id;
            $relationship->child = $recordid;

            $DB->insert_record(RELATIONS_TABLE, $relationship);
            return json_encode($relationship);
        }

        return $recordid;
    }

    public static function get_all_exams() {
        global $DB;

        $examParents = $DB->get_records_sql("SELECT * FROM mdl_".EXAMS_TABLE);

        if (empty($examParents)) {
            $examParents = array();
        }

        return $examParents;
    }

    public static function get_all_children() {
        global $DB;

        $children = $DB->get_records_sql("SELECT * FROM mdl_".CHILD_TABLE);
        return $children;
    }

    public static function get_related_exams($instance) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        $children = $DB->get_records(CHILD_TABLE, array('parent' => $child->parent));

        return $children;
    }

    public static function get_related_attempts($user, $parent) {
        global $DB;

        $children = $DB->get_records(CHILD_TABLE, array('parent' => $parent->id));
        $attempts = array();

        foreach ($children as $child) {
            $attempt = Exam::get_exam_attempt($child, $user);

            if (!empty($attempt)) {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }

    public static function delete_from_instance($instance) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));

        if (!empty($child)) {
            $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));
            $DB->delete_records(RELATIONS_TABLE, array('child' => $child->id));
            $DB->delete_records(CHILD_TABLE, array('instance' => $instance));
        }
    }

    public static function update_child($instance, $parent) {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));

        $relationship = new \stdClass();
        $relationship->child = $child->id;
        $relationship->parent = $parent->id;



        $DB->delete_records(RELATIONS_TABLE, array('child' => $child->id, 'parent' => $parent->id));
        $DB->insert_record(RELATIONS_TABLE, $relationship);
    }

    public static function cmid_to_cm($id) {
        $cm = get_coursemodule_from_id('quiz', $id);
        return $cm;
    }

    public static function cmid_to_instance($id) {
        global $DB;

        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', IGNORE_MISSING);
        if (empty($cm)) {
             $cm = "false";
        }

        return json_encode($cm);
    }
}