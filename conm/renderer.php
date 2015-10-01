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
 * conm module renderering methods are defined here.
 *
 * @package    mod_conm
 * @copyright  2015 Cecília Tivir
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/conm/locallib.php');

/**
 * conm module renderer class
 */
class mod_conm_renderer extends plugin_renderer_base {

    /**
     * Renders the conm page header.
     *
     * @param conm conm
     * @return string
     */
    public function video_header($conm) {
        global $CFG;

        $output = '';

        $name = format_string($conm->get_instance()->name,
                              true,
                              $conm->get_course());
        $title = $this->page->course->shortname . ': ' . $name;

        $coursemoduleid = $conm->get_course_module()->id;
        $context = context_module::instance($coursemoduleid);

        // Add videojs css and js files.
        $this->page->requires->css('/mod/conm/video-js-4.12.8/video-js.min.css');
        $this->page->requires->js('/mod/conm/video-js-4.12.8/video.js', true);

        // Set the videojs flash fallback url.
        $swfurl = new moodle_url('/mod/conm/video-js-4.12.8/video-js.swf');
        $this->page->requires->js_init_code(
            'videojs.options.flash.swf = "' . $swfurl . '";');

        // Yui module handles responsive mode video resizing.
        if ($conm->get_instance()->responsive) {
            $config = get_config('conm');

            $this->page->requires->yui_module(
                'moodle-mod_conm-videojs',
                'M.mod_conm.videojs.init',
                array($conm->get_instance()->id,
                      $swfurl,
                      $conm->get_instance()->width,
                      $conm->get_instance()->height,
                      (boolean) $config->limitdimensions));
        }

        // Header setup.
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);

        $output .= $this->output->header();
        $output .= $this->output->heading($name, 3);

        if (!empty($conm->get_instance()->intro)) {
            $output .= $this->output->box_start('generalbox boxaligncenter', 'intro');
            $output .= format_module_intro('conm',
                                           $conm->get_instance(),
                                           $coursemoduleid);
            $output .= $this->output->box_end();
        }

        return $output;
    }

    /**
     * Render the footer
     *
     * @return string
     */
    public function video_footer() {
        return $this->output->footer();
    }

    /**
     * Render the conm page
     *
     * @param conm conm
     * @return string The page output.
     */
    public function video_page($conm) {
        $output = '';
        $output .= $this->video_header($conm);
        $output .= $this->video($conm);
        $output .= $this->video_footer();

        return $output;
    }


    /**
     * Utility function for getting a file URL
     *
     * @param stored_file $file
     * @return string file url
     */
    private function util_get_file_url($file) {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false);
    }

    /**
     * Utility function for getting area files
     *
     * @param int $contextid
     * @param string $areaname file area name (e.g. "videos")
     * @return array of stored_file objects
     */
    private function util_get_area_files($contextid, $areaname) {
        $fs = get_file_storage();
        return $fs->get_area_files($contextid,
                                   'mod_conm',
                                   $areaname,
                                   false,
                                   'itemid, filepath, filename',
                                   false);
    }

    /**
     * Utility function for getting the video poster image
     *
     * @param int $contextid
     * @return url to the poster image (or the default image)
     */
    private function get_poster_image($contextid) {
        $posterurl = null;
        $posters = $this->util_get_area_files($contextid, 'posters');
        foreach ($posters as $file) {
            $posterurl = $this->util_get_file_url($file);
            break;  // Only one poster allowed.
        }
        if (!$posterurl) {
            $posterurl = $this->pix_url('moodle-logo', 'conm');
        }

        return $posterurl;
    }

    /**
     * Utility function for creating the video element HTML.
     *
     * @param object $conm
     * @param url to the video poster image
     * @return string the video element HTML
     */
    private function get_video_element_html($conm, $posterurl) {
        /* The width and height are set to auto if responsive flag is set
           but is not ignored. They are still used to calculate proportions
           in the javascript that handles video resizing. */
        $width = ($conm->get_instance()->responsive ?
                  'auto' : $conm->get_instance()->width);
        $height = ($conm->get_instance()->responsive ?
                   'auto' : $conm->get_instance()->height);

        // Renders the video element.
        return html_writer::start_tag(
            'video',
            array('id' => 'conm-' . $conm->get_instance()->id,
                  'class' => 'video-js vjs-default-skin',
                  'controls' => 'controls',
                  'preload' => 'auto',
                  'width' => $width,
                  'height' => $height,
                  'poster' => $posterurl,
                  'data-setup' => '{}')
        );
    }

    /**
     * Utility function for creating the video source elements HTML.
     *
     * @param int $contextid
     * @return string HTML
     */
    private function get_video_source_elements_html($contextid) {
        $output = '';
        $videos = $this->util_get_area_files($contextid, 'videos');
        foreach ($videos as $file) {
            if ($mimetype = $file->get_mimetype()) {
                $videourl = $this->util_get_file_url($file);

                $output .= html_writer::empty_tag(
                    'source',
                    array('src' => $videourl,
                          'type' => $mimetype)
                );
            }
        }

        return $output;
    }

    /**
     * Utility function for creating the video caption track elements
     * HTML.
     *
     * @param int $contextid
     * @return string HTML
     */
    private function get_video_caption_track_elements_html($contextid) {
        $output = '';
        $first = true;
        $captions = $this->util_get_area_files($contextid, 'captions');
        foreach ($captions as $file) {
            if ($mimetype = $file->get_mimetype()) {
                $captionurl = $this->util_get_file_url($file);

                // Get or construct caption label for video.js player.
                $filename = $file->get_filename();
                $dot = strrpos($filename, '.');
                if ($dot) {
                    $label = substr($filename, 0, $dot);
                } else {
                    $label = $filename;
                }

                // Perhaps filename is a three letter ISO 6392 language code (e.g. eng, swe)?
                if (preg_match('/^[a-z]{3}$/', $label)) {
                    $maybelabel = get_string($label, 'core_iso6392');

                    /* Strings not in language files come back as [[string]], don't
                       use those for labels. */
                    if (substr($maybelabel, 0, 2) !== '[[' ||
                            substr($maybelabel, -2, 2) === ']]') {
                        $label = $maybelabel;
                    }
                }

                $options = array('kind' => 'captions',
                                 'src' => $captionurl,
                                 'label' => $label);
                if ($first) {
                    $options['default'] = 'default';
                    $first = false;
                }

                // Track seems to need closing tag in IE9 (!).
                $output .= html_writer::tag('track', '', $options);
            }
        }

        return $output;
    }

    /**
     * Utility function for getting the HTML for the alternative video
     * links in case video isn't showing/playing properly.
     *
     * @param int $contextid
     * @return string HTML
     */
    private function get_alternative_video_links_html($contextid) {
        $output = '';
        $videooutput = '';

        $first = true;
        $videos = $this->util_get_area_files($contextid, 'videos');
        foreach ($videos as $file) {
            if ($mimetype = $file->get_mimetype()) {
                $videourl = $this->util_get_file_url($file);

                if ($first) {
                    $first = false;
                } else {
                    $videooutput .= ', ';
                }
                $extension = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                $videooutput .= html_writer::tag('a',
                                                 $extension,
                                                 array('href' => $videourl));
            }
        }

        $output = html_writer::tag('p',
                                   get_string('video_not_playing',
                                              'conm',
                                              $videooutput),
                                   array());
        return html_writer::tag('div',
                                $output,
                                array('class' => 'conm-not-playing-msg'));
    }

    /**
     * Renders conm video.
     *
     * @param conm $conm
     * @return string HTML
     */
    public function video(conm $conm) {
        $output  = '';
        $contextid = $conm->get_context()->id;

        // Open conm div.
        $vclass = ($conm->get_instance()->responsive ?
                   'conm conm-responsive' : 'conm');
        $output .= $this->output->container_start($vclass);

        // Open video tag.
        $posterurl = $this->get_poster_image($contextid);
        $output .= $this->get_video_element_html($conm, $posterurl);

        // Elements for video sources.
        $output .= $this->get_video_source_elements_html($contextid);

        // Elements for caption tracks.
        $output .= $this->get_video_caption_track_elements_html($contextid);

        // Close video tag.
        $output .= html_writer::end_tag('video');

        // Alternative video links in case video isn't showing/playing properly.
        $output .= $this->get_alternative_video_links_html($contextid);

        // Close conm div.
        $output .= $this->output->container_end();

        return $output;
    }
}
