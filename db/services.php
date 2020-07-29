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
 * This file contains service definitions for the jwt authentication plugin.
 *
 * @package   local_examdelay
 * @copyright 2020 Adam King
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = [
    'ajax' => [
        'functions' => [
            'local_examdelay_get_exams',
            'local_examdelay_get_children',
            'local_examdelay_exam_exists',
            'local_examdelay_get_exam',
            'local_examdelay_create_exam_parent',
            'local_examdelay_delete_exam_parent',
            'local_examdelay_update_exam',
            'local_examdelay_exam_get_time',
            'local_examdelay_exam_cmid_toinstance'
        ],
        'restrictedusers' => 0,
        'enabled' => 1
    ]
];

$functions = [
    'local_examdelay_get_exams' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'get_exams',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Get all exam instances.',
        'type' => 'read',
        'ajax' => true
    ],
    'local_examdelay_get_children' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'get_children',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Get all exam child instances.',
        'type' => 'read',
        'ajax' => true
    ],
    'local_examdelay_exam_exists' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'exam_exists',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Check whether an exam exists in a given course module instance.',
        'type' => 'read',
        'ajax' => true
    ],
    'local_examdelay_get_exam' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'get_exam',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Get an exam instance.',
        'type' => 'read',
        'ajax' => true
    ],
    'local_examdelay_create_exam_parent' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'create_exam_parent',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Create an exam parent instance.',
        'type' => 'write',
        'ajax' => true
    ],
    'local_examdelay_delete_exam_parent' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'delete_exam_parent',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Delete an exam parent instance.',
        'type' => 'write',
        'ajax' => true
    ],
    'local_examdelay_update_exam' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'update_exam',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Update an exam instance',
        'type' => 'write',
        'ajax' => true
    ],
    'local_examdelay_exam_get_time' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'exam_get_time',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Get the wait time remaining for the current user.',
        'type' => 'read',
        'ajax' => true
    ],
    'local_examdelay_exam_cmid_toinstance' => [
        'classname' => 'local_examdelay_external',
        'methodname' => 'exam_cmid_toinstance',
        'classpath' => 'local/examdelay/externallib.php',
        'description' => 'Convert a course module ID to an instance ID.',
        'type' => 'read',
        'ajax' => true
    ]
];
