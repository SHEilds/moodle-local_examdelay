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
 * @copyright 2020 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

date_default_timezone_set('UTC');

// Replace moodle internal with files that offer the variables.
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

const ATTEMPTS_TABLE = 'quiz_attempts';
const EXAMS_TABLE = 'local_examdelay_exams';
const CHILD_TABLE = 'local_examdelay_children';
const GRADE_ITEM_TABLE = 'grade_items';

const MINUTE = 60;
const HOUR = MINUTE * 60;
const DAY = HOUR * 24;
const WEEK = DAY * 7;
const YEAR = DAY * 365;

class Exam
{
    /**
     *  A method to determine whether the current user has a previous
     *  attempt on the examination.
     *
     *  @return boolean Whether the user has attempted the exam before.
     */
    public static function has_user_attempt($instance, $user)
    {
        global $DB;

        $attempt = $DB->get_record(ATTEMPTS_TABLE, array(
            'quiz' => $instance,
            'userid' => $user
        ));

        return (!empty($attempt));
    }

    public static function get_user_attempt($instance, $user)
    {
        global $DB;

        // Get the latest attempt record.
        $attempts = $DB->get_records(
            ATTEMPTS_TABLE,
            [
                'quiz' => $instance,
                'userid' => $user
            ],
            'timefinish DESC'
        );

        if ($attempts && count($attempts) > 0)
        {
            $key = array_key_first($attempts);
            return $attempts[$key];
        }

        return [];
    }

    public static function get_exam_attempt($instance, $user)
    {
        global $CFG, $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));

        // If we can't find this exam child, we can't go any further.
        if (!$child || empty($child))
        {
            return false;
        }

        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));
        $children = $DB->get_records(CHILD_TABLE, array('parent' => $parent->id));

        $index = 0;
        $childQuery = '(';
        foreach ($children as $key => $examchild)
        {
            $childQuery .= $examchild->instance;

            if ($index !== count($children) - 1)
            {
                $childQuery .= ',';
            }

            $index++;
        }
        $childQuery .= ')';

        if ($childQuery !== '()')
        {
            $latestAttempt = $DB->get_record_sql(
                'SELECT * FROM ' . $CFG->prefix . ATTEMPTS_TABLE . ' WHERE quiz IN ' .
                    $childQuery .
                    ' AND userid = :userid AND state = :state AND timefinish < UNIX_TIMESTAMP() ORDER BY timefinish DESC LIMIT 1',
                array(
                    'userid' => $user,
                    'state' => "finished"
                )
            );

            if (!empty($latestAttempt))
            {
                return $latestAttempt;
            }
        }

        return false;
    }

    public static function get_exam_passed($instance, $user)
    {
        global $DB;

        $gradeItem = $DB->get_record(GRADE_ITEM_TABLE, array(
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $instance
        ));

        if (empty($gradeItem)) {
            // TODO:- Document error and output somewhere.
            return false;
        }

        $passGrade = $gradeItem->gradepass;

        $attempts = $DB->get_records(ATTEMPTS_TABLE, array(
            'quiz' => $instance,
            'userid' => $user,
            'state' => 'finished'
        ));

        foreach ($attempts as $attempt)
        {
            if ($attempt->sumgrades >= $passGrade) 
            {
                return true;
            }
        }

        return false;
    }

    public static function get_time_left_instance($instance)
    {
        global $DB, $USER;

        $lastAttempt = Exam::get_exam_attempt($instance, $USER->id);

        if (empty($lastAttempt))
        {
            return false;
        }
        else
        {
            $delay = Exam::get_delay_setting_interval($instance);

            $submitted = new \DateTime("@$lastAttempt->timefinish");
            $submitted->setTimezone(new \DateTimeZone("UTC"));

            $available = $submitted->add($delay);

            $present = new \DateTime('now');
            $present->setTimezone(new \DateTimeZone("UTC"));

            if ($available < $present)
            {
                return false;
            }
            else
            {
                $diff = $present->diff($available);

                return $diff;
            }
        }
    }

    public static function get_time_left_cmid($cmid)
    {
        global $DB, $USER;

        // $cm = get_coursemodule_from_id('quiz', $cmid);
        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', IGNORE_MISSING);

        if (!empty($cm))
        {
            $instance = $cm->instance;
            return Exam::get_time_left_instance($instance);
        }
        else
        {
            return false;
        }
    }

    public static function get_child($instance)
    {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        return $child ?? null;
    }

    public static function update_child($childId, $parentId)
    {
        global $DB;

        $child = new stdClass();
        $child->id = $childId;
        $child->parent = $parentId;

        $DB->update_record(CHILD_TABLE, $child);
    }

    /**
     *  A method to check whether an examination is ready to be re-attempted by the
     *  current user.
     *
     *  @param Object Attempt object from the database.
     *  @return boolean Whether the exam is ready to be re-attempted by the current user.
     */
    public static function is_ready($attempt)
    {
        $delay = Exam::get_delay_setting_interval($attempt->quiz);

        $finished = new \DateTime("@$attempt->timefinish");
        $finished->setTimezone(new \DateTimeZone("UTC"));
        $present = new \DateTime('now');
        $ready = $finished->add($delay);

        return ($ready < $present);
    }

    /**
     *  A method to check, in the database, whether a quiz is classed as an exam or not.
     *
     *  @param integer The instance of the quiz (exam) in question.
     *  @return boolean Whether the quiz is classified as an examination or not.
     */
    public static function is_exam($instance)
    {
        global $DB;

        $exam = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        return !empty($exam);
    }

    /**
     *  A method to check, in the database, whether an exam has any attempts on it.
     *
     *  @param integer The instance of the quiz (exam) in question.
     *  @return boolean Whether the quiz has any attempts on it at all.
     */
    public static function has_attempts($instance)
    {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('id' => $instance));
        return ($child->attempts > 0) ? true : false;
    }

    public static function get_parent($instance)
    {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));

        if (empty($child))
        {
            return false;
        }

        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $child->parent));

        if (empty($parent))
        {
            return false;
        }

        return $parent;
    }

    public static function get_parents()
    {
        global $DB;

        $parents = $DB->get_records(EXAMS_TABLE);

        return $parents ?? [];
    }

    public static function create_parent($name, $delay, $children = array())
    {
        global $DB, $PAGE;

        $exam = new \stdClass();
        $exam->name = $name;
        $exam->delay = $delay;

        $recordid = $DB->insert_record(EXAMS_TABLE, $exam);

        return $recordid;
    }

    public static function delete_parent($id)
    {
        global $DB;

        $DB->delete_records(EXAMS_TABLE, array('id' => $id));
        $DB->delete_records(CHILD_TABLE, array('parent' => $id));
    }

    /**
     * Create an instance of an Exam child.
     */
    public static function create($instance, $parent)
    {
        global $DB;

        $child = new \stdClass();
        $child->instance = $instance;
        $child->parent = $parent;

        $parent = $DB->get_record(EXAMS_TABLE, array('id' => $parent));

        if (!empty($parent))
        {
            $id = $DB->insert_record(CHILD_TABLE, $child);
            return $DB->get_record(CHILD_TABLE, ['id' => $id]);
        }

        return null;
    }

    public static function get_all_exams()
    {
        global $CFG, $DB;

        $examParents = $DB->get_records_sql("SELECT * FROM " . $CFG->prefix . EXAMS_TABLE);
        return $examParents ?? [];
    }

    public static function get_all_children()
    {
        global $CFG, $DB;

        $children = $DB->get_records_sql("SELECT * FROM " . $CFG->prefix . CHILD_TABLE);
        return $children ?? [];
    }

    public static function get_related_exams($instance)
    {
        global $DB;

        $child = $DB->get_record(CHILD_TABLE, array('instance' => $instance));
        $children = $DB->get_records(CHILD_TABLE, array('parent' => $child->parent));

        return $children ?? [];
    }

    public static function get_related_attempts($user, $parent)
    {
        global $DB;

        $children = $DB->get_records(CHILD_TABLE, array('parent' => $parent->id));
        $attempts = [];

        foreach ($children as $child)
        {
            $attempt = Exam::get_exam_attempt($child, $user);

            if (!empty($attempt))
            {
                $attempts[] = $attempt;
            }
        }

        return $attempts;
    }

    public static function delete_from_instance($instance)
    {
        global $DB;        
        $DB->delete_records(CHILD_TABLE, array('instance' => $instance));
    }

    public static function delete_unfinished_attempts($instance, $user)
    {
        global $DB;

        $DB->delete_records(ATTEMPTS_TABLE, array(
            'quiz' => $instance,
            'userid' => $user,
            'state' => 'inprogress'
        ));
    }

    public static function update_parent($id, $name, $delay)
    {
        global $DB;

        $parent = $DB->get_record(EXAMS_TABLE, [
            'id' => $id
        ]);

        if ($parent)
        {
            $parent->name = $name;
            $parent->delay = $delay;

            $DB->update_record(EXAMS_TABLE, $parent);
        }
    }

    // Returns the delay config time, in seconds.
    public static function get_delay_setting($instance)
    {
        $exam = Exam::get_parent($instance);
        return $exam->delay;
    }

    public static function get_delay_setting_interval($instance)
    {
        $delay = Exam::get_delay_setting($instance);
        return new DateInterval('PT' . $delay . 'S');
    }

    public static function get_delay_setting_string_instance($instance)
    {
        $delay = Exam::get_delay_setting($instance);
        return Exam::get_delay_setting_string($delay);
    }

    public static function get_delay_setting_string($delay)
    {
        // Create two dummy date-times because DateInterval 
        // doesn't recalculate overflow values. 
        $now = new DateTime();
        $then = new DateTime();
        // Add the delay to the dummy dates so we can 
        // calculate the diff as the delay.
        $then->add(new DateInterval('PT' . $delay . 'S'));
        // Get the new delay via diff.
        $delayInterval = $then->diff($now);

        $periodString = "";
        // Minutes.
        if ($delay < MINUTE)
        {
            $s = $delay > 1 ? 's' : '';
            $periodString = "$delay second$s";
        }
        else if ($delay >= MINUTE && $delay < HOUR)
        {
            // If more than one minute set multiple.
            $s = Exam::quotient($delay, MINUTE) > 1 ? 's' : '';
            $periodString = "$delayInterval->i minute$s";
        }
        // Hours.
        else if ($delay >= HOUR && $delay < DAY)
        {
            // If more than one hour set multiple.
            $s = Exam::quotient($delay, HOUR) > 1 ? 's' : '';
            $periodString = "$delayInterval->h hour$s";
        }
        // Weeks + Days.
        else if ($delay >= DAY)
        {
            if ($delay >= WEEK && $delay < YEAR)
            {
                $quotient = Exam::quotient($delay, WEEK);
                $remainder = Exam::remainder($delay, WEEK, DAY);

                // If more than one week set multiple.
                $ws = $quotient > 1 ? 's' : '';

                $periodString = "$quotient week$ws";
                if ($remainder > 0)
                {
                    $ds = $remainder > 1 ? 's' : '';
                    $periodString .= " and $remainder day$ds";
                }
            }
            else if ($delay >= YEAR)
            {
                $quotient = Exam::quotient($delay, YEAR);
                $remainder = Exam::remainder($delay, YEAR, DAY);

                $ys = $quotient > 1 ? 's' : '';

                $periodString = "$quotient year$ys";
                if ($remainder > 0)
                {
                    $ds = $remainder > 1 ? 's' : '';
                    $periodString .= " and $remainder day$ds";
                }
            }
            else
            {
                // If more than one day set multiple.
                $s = (Exam::quotient($delay, DAY) > 1) ? 's' : '';
                $periodString = "$delayInterval->d day$s";
            }
        }

        return $periodString;
    }

    public static function cmid_to_cm($cmid)
    {
        $cm = get_coursemodule_from_id('quiz', $cmid);
        return $cm;
    }

    public static function cmid_to_instance($cmid)
    {
        global $DB;

        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', IGNORE_MISSING);
        if (empty($cm))
        {
            $cm = "false";
        }

        return json_encode($cm);
    }

    private static function quotient($time, $timespan)
    {
        return floor($time / $timespan);
    }

    private static function remainder($time, $timespan, $remainderTime)
    {
        return floor(($time % $timespan) / $remainderTime);
    }
}
