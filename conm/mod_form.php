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
 * The main conm configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_conm
 * @copyright  2015 Cecília Tivir
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');
/**
 * Module instance settings form
 *
 * @package    mod_conm
 * @copyright  2015 Cecília Tivir
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_conm_mod_form extends moodleform_mod {

    public function definition() {

        global $CFG, $DB;
        $mform =& $this->_form;

        $config = get_config('conm');

        $course_id = optional_param('course', 0, PARAM_INT); // course_module ID, or
        $course_module_id = optional_param('update', 0, PARAM_INT); // course_module ID, or
        if ($course_id) {
            $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
            $conm = null;
        } else if ($course_module_id) {
            $cm = get_coursemodule_from_id('conm', $course_module_id, 0, false, MUST_EXIST);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            $conm = $DB->get_record('conm', array('id' => $cm->instance), '*', MUST_EXIST);
        }
        
        //-----------------------------------------------------------------------
        // First Block General
        //-----------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('conmname', 'conm'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        //$mform->addHelpButton('name', 'conmname', 'conm');

        $mform->addElement('text', 'kword', get_string('conmkeywords', 'conm'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('kword', PARAM_TEXT);
        } else {
            $mform->setType('kword', PARAM_CLEAN);
        }
        $mform->addRule('kword', null, 'required', null, 'client');
        $mform->addRule('kword', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
       //$mform->addRule('kword', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        
        $this->standard_intro_elements();
     
        //Add file to upload ------------------------------------------------------------------------------
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'resource'));
        $mform->setExpanded('contentsection');
        
        $filemanager_options = array();
        $filemanager_options['accepted_types'] = '*';
        $filemanager_options['maxbytes'] = 0;
        $filemanager_options['maxfiles'] = -1;
        $filemanager_options['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('selectfiles'), null, $filemanager_options);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'resource'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'resource'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'resource'), $options);
        }
        $mform->addRule('files', null, 'required', null, 'client');
        
        //-------------------------------------------------------------------------------------------------------
        // Second Block: Add Repository Description Optional
        //-------------------------------------------------------------------------------------------------------
        
        $mform->addElement('header', 'general', get_string('repodescription', 'conm'));
        $mform->addElement('textarea', 'resume', get_string('reporesume','conm'), 'wrap="virtual" rows="5" cols="60"');
        
       
        $printlang = array( 0 => get_string('repolanguages', 'conm'), 
                            1 => get_string('repolanguageeng', 'conm'), 
                            2 => get_string('repolanguagefren', 'conm'),
                            3 => get_string('repolanguagegerm', 'conm'), 
                            4 => get_string('repolanguagejapn', 'conm'),
                            5 => get_string('repolanguagept', 'conm'),
                            6 => get_string('repolanguagespan', 'conm'),
                            7 => get_string('repolanguageo', 'conm')); 
        
        $mform->addElement('select', 'language', get_string('repolanguage', 'conm'), $printlang);

        $printexe = array( 0 => get_string('file_exe', 'conm'), 
                           1 => get_string('file_animation', 'conm'), 
                           2 => get_string('file_article', 'conm'),
                           3 => get_string('file_book', 'conm'), 
                           4 => get_string('file_book_chapter', 'conm'),
                           5 => get_string('file_image', 'conm'),
                           6 => get_string('file_lo', 'conm'),
                           7 => get_string('file_other', 'conm')); 
        $mform->addElement('select', 'type', get_string('repotype', 'conm'), $printexe);
                
        //------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        //------------------------------------------------------------------------------
        $this->add_action_buttons();
    }
}
