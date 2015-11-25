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
 * github lib
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__.'/locallib.php');

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function booktool_github_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (has_capability('booktool/github:export', $PAGE->cm->context)) {
        $id = $PAGE->cm->id;
        $url = new moodle_url('/mod/book/tool/github/index.php', 
                              array('id'=>$id));
        $icon = new pix_icon('github', '', 'booktool_github', 
                              array('class'=>'icon'));
        $string = get_string( 'github', 'booktool_github' );

        $node->add( $string, $url, navigation_node::TYPE_SETTING, null, null, 
                    $icon);
    }
}

