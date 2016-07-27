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
 * This file keeps track of upgrades to
 * the local_grade_curricular plugin
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package local-grade_curricular
 * @copyright 2014 onwards Antonio Carlos Mariani
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_grade_curricular_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2014121800) {
        // Forum savepoint reached.
        $DB->execute("UPDATE {grade_curricular_courses} SET type = 0 WHERE type=3");

        $table = new xmldb_table('grade_curricular_ap_criteria');
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file(dirname(__FILE__). '/install.xml', 'grade_curricular_ap_criteria');
        }

        $table = new xmldb_table('grade_curricular_ap_modules');
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file(dirname(__FILE__). '/install.xml', 'grade_curricular_ap_modules');
        }

        upgrade_plugin_savepoint(true, '2014121800', 'local', 'grade_curricular');
    }

    if ($oldversion < 2015021900) {
        $table = new xmldb_table('grade_curricular');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('inscricoeseditionid', XMLDB_TYPE_INTEGER, '10');
            if ($dbman->field_exists($table, $field)) {
                $dbman->rename_field($table, $field, 'inscricoesactivityid');
            }
        }

        upgrade_plugin_savepoint(true, '2015021900', 'local', 'grade_curricular');
    }

    if ($oldversion < 2015092804) {
        $table = new xmldb_table('grade_curricular_ap_criteria');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('grade_curricular_ap_modules');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('grade_curricular_backup');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('grade_curricular_copia');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('grade_curricular_courses_backup');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, '2015092804', 'local', 'grade_curricular');
    }

    return true;
}
