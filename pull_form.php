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
 * Book github plugin
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// define the form used to handle pulling book content to github

require_once("$CFG->libdir/formslib.php");
 
class pull_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; 

        $mform->addElement( 'hidden', 'id', $this->_customdata['id'] );
        $mform->setType( 'id', PARAM_INT );

        $button = array();
        $button[] = &$mform->createElement('submit','submitbutton', get_string('pull_button', 'booktool_github'));
        $mform->addGroup($button,'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
