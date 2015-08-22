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
 * Book IMSCP export plugin
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');
#require_once($CFG->libdir.'/filelib.php');


$id = required_param('id', PARAM_INT);           // Course Module ID

$cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);

$PAGE->set_url('/mod/book/tool/github/index.php', array('id'=>$id));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);

#***** What about the capability to view hidden chapters???

#require_capability('booktool/exportimscp:export', $context);

#-- functionality from exportismcp - to be replaced
#************** Need to think about what events get added
#\booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();

#--- Dummy data to be replaced

#$repo = 'edc3100';
#$path = 'A_2nd_new_file.html';
#$username = 'djplaner';
#$password = 'n3tmask3r';

#--- show the header and initial display 

echo $OUTPUT->header();
#************* SHOULD DO MORE SET UP HERE???


echo '<h3>GitHub details</h3>';

echo booktool_github_view_repo_details( );

echo '<h3>History</h3>';

$commits = booktool_github_get_commits();
$string = booktool_github_view_commits( $commits );

echo $string;

#echo '<xmp>';
#print_r( $commits );
#echo '</xmp>';

echo $OUTPUT->footer();

