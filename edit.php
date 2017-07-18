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
 * Exam Edit
 *
 * @package   local_examdelay
 * @copyright 2017 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

date_default_timezone_set('UTC');

require_once(__DIR__ . '/../../config.php');
require_once("$CFG->dirroot/user/profile/lib.php");
require_once("$CFG->dirroot/local/examdelay/exam.php");
require_once("$CFG->libdir/formslib.php");
require_once(__DIR__ . "/forms/edit.php");

$instance = required_param('id', PARAM_INT);

$PAGE->set_context(get_system_context());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Edit Exam");
$PAGE->set_heading("Exam Settings");
$PAGE->set_url($CFG->wwwroot.'/local/examdelay/edit.php');

$continue = new \moodle_url('/local/examdelay/edit.php', array('id' => $instance));

echo $OUTPUT->header();

$formArgs = array(
    'url' => "/local/examdelay/edit.php?id=$instance",
    'args' => array('instance' => $instance)
);

// Forms are designed to maintain the instance querystring.
$createParentForm = new examdelay_create_form($formArgs['url'], $formArgs['args']);
$selectParentForm = new examdelay_edit_form($formArgs['url'], $formArgs['args']);
$deleteParentForm = new examdelay_delete_form($formArgs['url'], $formArgs['args']);

// Exam Edit Form.
echo '<h1>Edit Instance</h1>';
if ($selectParentForm->is_cancelled()) {
	redirect($continue);
} elseif ($fromform = $selectParentForm->get_data()) {
    // Process the data from the form.
	$formdata = $selectParentForm->get_data();

    $is_exam = Exam::is_exam($instance);
    if ($is_exam == true) {
        if ($formdata->exammode == false) {
            Exam::delete_from_instance($instance);
        } else {
            Exam::update_child($instance, $formdata->parentselect);
            Exam::update_parent($formdata->parentselect, $formdata->examdelay);
        }
    } else {
        if ($formdata->exammode == true) {
            if (!empty($formdata->parentselect)) {
                Exam::create($instance, $formdata->parentselect);
            } else {
                echo "<div class='alert alert-block alert-error'>
                          <p>Error: No parent selected for exam.</p>
                      </div>";
            }
        } else {
            return true;
        }
    }

    $selectParentForm->display();
} else {
    $selectParentForm->display();
}

// Parent Create Form.
echo '<h1>Create Exam</h1>';
if ($createParentForm->is_cancelled()) {
	redirect($continue);
} elseif ($fromform = $createParentForm->get_data()) {
    // Process the data from the form.
	$formdata = $createParentForm->get_data();
    Exam::create_parent($formdata->parentname);

    $createParentForm->display();
} else {
    $createParentForm->display();
}

// Delete Exam Form.
echo '<h1>Delete Exam</h1>';
if ($deleteParentForm->is_cancelled()) {
    redirect($continue);
} elseif ($fromform = $deleteParentForm->get_data()) {
    if (!empty($fromform->deleteparent)) {
        Exam::delete_parent($fromform->deleteparent);
    }

    $deleteParentForm->display();
} else {
    $deleteParentForm->display();
}

echo $OUTPUT->footer();