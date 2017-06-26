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

defined('MOODLE_INTERNAL') || die();

function xmldb_local_examdelay_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    $database = "mdl_local_examdelay_";

    if($oldversion === 2017060600) {
        $children = $DB->get_records_sql("SELECT * FROM {$database}relations");
        // Update the Exam database to remove the children column.
        $dbman->drop_field("{$database}exams", "children");
        // Drop the existing relations table.
        $dbman->drop_table("{$database}relations");

        // Define the new relations table.
        $relationsTable = new xmldb_table('local_examdelay_relations');
        $relationsTable->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $relationsTable->add_field('parent', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, null);
        $relationsTable->add_field('child', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, null);
        $relationsTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add the new relations table.
        $dbman->create_table("{$database}relations");

        // Define the new children table.
        $relationsTable = new xmldb_table('local_examdelay_children');
        $relationsTable->add_field('id', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $relationsTable->add_field('instance', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, null);
        $relationsTable->add_field('parent', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, false, null);
        $relationsTable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Add the new children table.
        $dbman->create_table("{$database}children");

        // Re-populate the new tables using the old data.
        foreach ($children as $child) {
            $DB->insert_record("{$database}children", $child);

            $relation = new stdClass();
            $relations->child = $child->id;
            $relation->parent = $child->parent;
            $DB->insert_record("{$database}relations", $relation);
        }

        // Mark examdelay save point.
        upgrade_plugin_savepoint(true, 2017060600, 'local', 'examdelay');
    }
}