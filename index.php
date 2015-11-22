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
//require_once($CFG->libdir.'/filelib.php');

// *** can this be put into a support function?
$id = required_param('id', PARAM_INT);           // Course Module ID

$cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST); 

$PAGE->set_url('/mod/book/tool/github/index.php');

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);
require_capability( 'booktool/github:export', $context );

#***** What about the capability to view hidden chapters???
#** Include a specific github capability
#require_capability('booktool/exportimscp:export', $context);

#************** Need to think about what events get added
#\booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();

#--- show the header and initial display 

#************* SHOULD DO MORE SET UP HERE???

list( $github_client, $github_user ) = booktool_github_get_client( $id );

if ( ! $github_client ) {
    // couldn't authenticate with github
    echo $OUTPUT->header();

    print '<h1> Cannot authenticate with github</h1>';

    echo $OUTPUT->footer();

    die;
} 

$repo_details = booktool_github_get_repo_details( $id, $github_user );

if ( ! $repo_details ) {
    //*** eventually display the form to set up the repo connection 
    echo $OUTPUT->header();

    print '<h1> Have not configure repo connection yet</h1>';

    echo $OUTPUT->footer();
} else {
    
    echo $OUTPUT->header();

    $commits = booktool_github_get_commits( $id, $github_client, $repo_details);

    if ( ! $commits ) {
        print "<h3>Error - no get commits </h3>" ;
    } else {

        echo booktool_github_view_repo_details( $repo_details, $github_user );

        echo '<h3>History</h3>';

        $string = booktool_github_view_commits( $commits );

        echo $string;
    }
    echo $OUTPUT->footer();
}
