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

// define the form used to define the github repository and path for
// the github connection

require_once("$CFG->libdir/formslib.php");
 
class connection_form extends moodleform {
    //Add elements to form
    public function definition() {
        global $CFG;
 
        $mform = $this->_form; 

        $mform->addElement( 'hidden', 'id', $this->_customdata['id'] );
        $mform->setType( 'id', PARAM_INT );
 
        $mform->addElement('text', 'repo', 
                            get_string('repo_form_element', 'booktool_github')); 
        $mform->setType('repo', PARAM_NOTAGS); 
        $mform->setDefault('repo', get_string( 'repo_form_default', 'booktool_github'));

        $mform->addElement('text', 'path', 
                            get_string('path_form_element', 'booktool_github')); 
        $mform->setType('path', PARAM_NOTAGS); 
        $mform->setDefault('path', get_string( 'repo_path_default', 'booktool_github')); 

        $this->add_action_buttons();
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
