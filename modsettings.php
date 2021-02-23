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
 * Exam Module Settings Page
 *
 * @package   local_examdelay
 * @copyright 2020 Adam King, SHEilds eLearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/adminlib.php");
require_once("$CFG->libdir/formslib.php");
require_once('forms/exameditform.php');
require_once('forms/examform.php');
require_once('forms/modform.php');
require_once('exam.php');

global $DB;

$instance = required_param('instance', PARAM_INT); // Quiz instance ID.
$returnuri = required_param('return', PARAM_TEXT); // URI to return to.
$returnparams = optional_param('params', '', PARAM_TEXT); // Query-built URI param string.

require_login();

$PAGE->set_context(context_system::instance());
$context = context_user::instance($USER->id);

$PAGE->set_pagelayout('admin');
$PAGE->set_title("Exam Delay Settings");
$PAGE->set_heading("");
$PAGE->set_url($CFG->wwwroot . '/local/examdelay/modsettings.php');

if (!has_capability('mod/quiz:manage', $context)) die();

$parent = Exam::get_parent($instance);
$child = Exam::get_child($instance);
$isExam = Exam::is_exam($instance);

$parents = Exam::get_parents();
$formParams = [
    'parents' => $parents,
    'hidden' => [
        'instance' => $instance,
        'return' => $returnuri,
        'params' => $returnparams
    ]
];

$examEditForm = null;
if ($isExam && $parent && $child)
{
    // Add parent and child IDs to hidden fields and load edit form.
    $hidden = $formParams['hidden'];
    $formParams['hidden'] = array_merge($hidden, [
        'parentid' => $parent->id,
        'childid' => $child->id
    ]);

    $examEditForm = new examedit_form(null, $formParams);
}

$examCreateForm = new examcreate_form(null, $formParams);
$modForm = new examdelaymod_form(null, $formParams);

// Exam Create form.
if ($examCreateForm->is_cancelled())
{
    // Shouldn't be possible.
    redirectFrom($returnuri, $returnparams);
}
else if ($exam = $examCreateForm->get_data())
{
    // Handle create.
    if ($exam->name)
    {
        $id = Exam::create_parent($exam->name, $exam->delay);
        refresh($formParams);
    }
    else
    {
        // TODO:- Tell the user they did not enter a name.
    }
}
else
{
}

// Mod settings form.
if ($modForm->is_cancelled())
{
    redirectFrom($returnuri, $returnparams);
}
else if ($modData = $modForm->get_data())
{
    if (isset($modData->deleteparent) && $modData->deleteparent)
    {
        Exam::delete_parent($modData->selectparent);
        refresh($formParams);
    }
    else
    {
        // Save settings.
        if (!isset($modData->selectparent) || $modData->selectparent < 1)
        {
            // TODO:- Tell user to create exam first.
        }
        else
        {
            $examState = $modData->exam;

            if ($examState)
            {
                // Create the child if it does not exist.
                if (!$isExam)
                {
                    $child = Exam::create($modData->instance, $modData->selectparent);
                }

                Exam::update_child($child->id, $modData->selectparent);
            }
            else
            {
                Exam::delete_from_instance($modData->instance);
            }

            refresh($formParams);
        }
    }
}
else
{
    if ($isExam && isset($child))
    {
        $formData = (object)[
            'exam' => $isExam,
            'selectparent' => $child->parent
        ];

        $modForm->set_data($formData);
    }
}

// Exam edit form.
if ($examEditForm)
{
    if ($examEditForm->is_cancelled())
    {
        // Shouldn't be possible.
    }
    else if ($examEditData = $examEditForm->get_data())
    {
        Exam::update_parent(
            $examEditData->parentid,
            $examEditData->name,
            $examEditData->delay
        );

        refresh($formParams);
    }
    else
    {
        $formData = (object)[
            'name' => $parent->name,
            'delay' => $parent->delay
        ];

        $examEditForm->set_data($formData);
    }
}

echo $OUTPUT->header();



$examCreateForm->display();
if ($isExam)
{
    $examEditForm->display();
}
$modForm->display();
echo $OUTPUT->footer();

function refresh($params)
{
    global $PAGE;

    $uri = new moodle_url(
        $PAGE->url->get_path(),
        $params['hidden']
    );

    redirect($uri);
}

function redirectFrom($uri, $params)
{
    $params = queryparams_toarray($params) ?? [];
    $uri = new moodle_url($uri, $params);

    redirect($uri);
}

function queryparams_toarray($rawparams)
{
    $params = [];

    if (!empty($rawparams))
    {
        $rawparams = str_replace('&amp;', '&', $rawparams);
        parse_str($rawparams, $params);
    }

    return $params;
}
