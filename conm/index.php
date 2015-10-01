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
 * This is a one-line short description of the file
 *
 * @package    mod_conm
 * @copyright  2015 Cecília
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('/../../fig.php');

$id = required_param('id', PARAM_INT); // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_conm\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strconm         = get_string('modulename', 'conm');
$strconms        = get_string('modulenameplural', 'conm');
$strsectionname  = get_string('sectionname', 'format_'.$course->format);
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/conm/index.php', array('id' =>$course-> $id));
$PAGE->set_title($course->shortname.': '.$strconms);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strconms);
echo $OUTPUT->header();

//echo $OUTPUT->heading($strconms);

if (! $conms = get_all_instances_in_course('conm', $course)) {
    notice(get_string('thereareno', 'moodle', $conms), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($conms as $conm) {
    $cm = $modinfo->cms[$conm->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($conm->section !== $currentsection) {
            if ($conm->section) {
                $printsection = get_section_name($course, $conm->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $conm->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($conm->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        // each conm file has an icon in 2.0
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon).
                '" class="activityicon" alt="'.
                get_string('modulename', $cm->modname).'" /> ';
    }
    //Dim hidden modules
    $class = $conm->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($conm->name)."</a>",
        format_module_intro('conm', $conm, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
