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
 * Prints a particular instance of conm
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_conm
 * @copyright  2015 Cecília Tivir
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n        = optional_param('n', 0, PARAM_INT);  // ... conm instance ID - it should be named as the first character of the module.
$redirect = optional_param('redirect', 0, PARAM_BOOL);

/*if ($id) {
    $cm         = get_coursemodule_from_id('conm', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $conm       = $DB->get_record('conm', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $conm       = $DB->get_record('conm', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $conm->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('conm', $conm->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}
*/
if ($n) {
    if (!$conm = $DB->get_record('conm', array('id'=>$n))) {
        conm_redirect_if_migrated($n, 0);
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('conm', $conm->id, $conm->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('conm', $id)) {
        conm_redirect_if_migrated(0, $id);
        print_error('invalidcoursemodule');
    }
    $conm = $DB->get_record('conm', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

/*require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/conm:view', $context);*/
require_login($course, true, $cm);

$event = \mod_conm\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context'  => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $conm);
$event->add_record_snapshot('conm', $conm);
$event->trigger();

/*$params = array(
    'context'  => $context,
    'objectid' => $conm->id
);
$event = \mod_conm\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('conm', $conm);
$event->trigger();*/

// Print the page header.


$PAGE->set_url('/mod/conm/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$conm->name);
$PAGE->set_heading($course->fullname);
//$PAGE->set_activity_record($conm);

$output = $PAGE->get_renderer('mod_folder');

echo $output->header();

echo $output->heading(format_string($conm->name), 2);

//echo $output->display_folder($conm);

echo $output->footer();
/*
$PAGE->set_title(format_string($conm->name));
$PAGE->set_heading($course->shortname);
$PAGE->set_cacheable(false);

// Output starts here.
echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($conm->intro) {
    echo $OUTPUT->box(format_module_intro('conm', $conm, $cm->id), 'generalbox mod_introbox', 'intro');
}

// Replace the following lines with you own code.
echo $OUTPUT->heading('Yay! It works!');

// Finish the page.
echo $OUTPUT->footer();*/
