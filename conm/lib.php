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
 * @package    mod_conm
 * @copyright  2015 Cecília Tivir
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * List of features supported in Conm module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function conm_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:   return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        
        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function conm_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function conm_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function conm_get_view_actions() {
    return array('view','view help');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function conm_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add resource instance.
 * @param object $conm
 * @param object $mform
 * @return int new Conm instance id
 */
function conm_add_instance(stdClass $conm, mod_conm_mod_form $mform = null) {

    require_once(dirname(__FILE__) . '/locallib.php');

    $context = context_module::instance($data->coursemodule);
    $conm    = new conm($context, null, null);

    return $conm->add_instance($data);

    return $conm->id;
}

/**
 * Updates an instance of the conm in the database
 * @param stdClass $conm An object from the form in mod_form.php
 * @param mod_conm_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function conm_update_instance(stdClass $conm, mod_conm_mod_form $mform = null) {
    
    require_once(dirname(__FILE__) . '/locallib.php');

    $context = context_module::instance($data->coursemodule);
    $conm = new conm($context, null, null);

    return $conm->update_instance($data);
}

/**
 * Removes an instance of the conm from the database
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function conm_delete_instance($id) {
    
    require_once(dirname(__FILE__) . '/locallib.php');

    $cm = get_coursemodule_from_instance('conm', $id, 0, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $conm = new conm($context, null, null);
    return $conm->delete_instance();
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $conm The conm instance record
 * @return stdClass|null
 */
function conm_user_outline($course, $user, $mod, $conm) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $conm the module instance record
 */
function conm_user_complete($course, $user, $mod, $conm) {
    global $DB;

    $logs = $DB->get_records(
        'log',
        array('userid' => $user->id,
              'module' => 'conm',
              'action' => 'view',
              'info' => $conm->id),
        'time ASC');

    if ($logs) {
        $numviews = count($logs);
        $lastlog = array_pop($logs);

        $strmostrecently = get_string('mostrecently');
        $strnumviews = get_string('numviews', '', $numviews);

        echo "$strnumviews - $strmostrecently ".userdate($lastlog->time);
    } else {
        print_string('neverseen', 'conm');
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in conm activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function conm_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link conm_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function conm_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link conm_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function conm_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function conm_cron () {
    return true;
}


/**
 * Is a given scale used by the instance of conm?
 *
 * This function returns if a scale is being used by one conm
 * if it has support for grading and scales.
 *
 * @param int $conmid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given conm instance
 */
function conm_scale_used($conmid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('conm', array('id' => $conmid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of conm.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any conm instance
 */
function conm_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('conm', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given conm instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $conm instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
/*function conm_grade_item_update(stdClass $conm, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($conm->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($conm->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $conm->grade;
        $item['grademin']  = 0;
    } else if ($conm->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$conm->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/conm', $conm->course, 'mod', 'conm',
            $conm->id, 0, null, $item);
}*/

/**
 * Delete grade item for given conm instance
 *
 * @param stdClass $conm instance object
 * @return grade_item
 */
function conm_grade_item_delete($conm) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/conm', $conm->course, 'mod', 'conm',
            $conm->id, 0, null, array('deleted' => 1));
}

/**
 * Update conm grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $conm instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function conm_update_grades(stdClass $conm, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/conm', $conm->course, 'mod', 'conm', $conm->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function conm_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for conm file areas
 *
 * @package mod_conm
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function conm_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    
        global $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return null;
    }

    // Filearea must contain a real area.
    if (!isset($areas[$filearea])) {
        return null;
    }

    if (!has_capability('moodle/course:managefiles', $context)) {
        // Students can not peek here!
        return null;
    }

    $fs = get_file_storage();
    if ($filearea === 'posters' || $filearea === 'captions' || $filearea === 'videos') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        if (!$storedfile = $fs->get_file($context->id,
                                         'mod_conm',
                                         $filearea,
                                         0,
                                         $filepath,
                                         $filename)) {
            // Not found.
            return null;
        }

        $urlbase = $CFG->wwwroot . '/pluginfile.php';

        return new file_info_stored($browser,
                                    $context,
                                    $storedfile,
                                    $urlbase,
                                    $areas[$filearea],
                                    false,
                                    true,
                                    true,
                                    false);
    }

    // Not found.
    return null;
}

/**
 * Serves the files from the conm file areas
 *
 * @package mod_conm
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the conm's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function conm_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding conm nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the conm module instance
 * @param stdClass $course current course record
 * @param stdClass $module current conm instance record
 * @param cm_info $cm course module information
 */
function conm_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the conm settings
 *
 * This function is called when the context for the page is a conm module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $conmnode conm administration node
 */
function conm_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $conmnode=null) {
    // TODO Delete this function and its docblock, or implement it.
}
