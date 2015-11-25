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
 * Book GitHub export plugin
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');
//require_once($CFG->libdir.'/filelib.php');

require_once( __DIR__ . '/connection_form.php' );

// *** can this be put into a support function?
$id = required_param('id', PARAM_INT);           // Course Module ID
$instructions = optional_param( 'instructions', 0, PARAM_INT);

// kludge to fix form submission where bookid and id (connection id) get mixed
$tmp_bookid = optional_param( 'bookid', -1, PARAM_INT );

if ($tmp_bookid > -1 ) {
    $id = $tmp_bookid;
}

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

#************** Need to think about what events get added
#\booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();

#--- show the header and initial display 

//*****
// - has this book been configured to use github?

$repo_details = booktool_github_get_repo_details( $id );

// test the session variable "seen_git_instructions"
//unset( $_SESSION['github_seen_instructions'] );

if ( $instructions > 0 ) {
    $_SESSION['github_seen_instructions'] = 1;
}

echo $OUTPUT->header();

// if the instructions haven't been seen, display some basic info
$seen_instructions = array_key_exists( "github_seen_instructions", $_SESSION );
if ( ! $repo_details && ! $seen_instructions ) {
    booktool_github_show_instructions( $id );
    echo $OUTPUT->footer();
    die;
}
    
// Ready to use github connection

// get github client and github user details via oauth
list( $github_client, $github_user ) = booktool_github_get_client( $id );

// couldn't authenticate with github, probably never happen
// **** TIDY UP
if ( ! $github_client ) {
    print '<h1> Cannot authenticate with github</h1>';

    echo $OUTPUT->footer();

    die;
} 

// add the "owner" of this connection as the username from oAuth
$repo_details['owner'] = $github_user->getLogin();

// if no repo yet configured, repo_details[ repo|path ] will not exist

$commits = false;

//*************************************
// Start showing the form

$form = new connection_form( null, array( 'bookid' => $id ) );

// assume it's valid
$validConnection = true;

if ( $fromForm = $form->get_data() ) {
    // check to see if the repo/path have actually changed
    //  repo_details should have the existing data

    $repoDefault = get_string( 'repo_form_default', 'booktool_github');
    $pathDefault = get_string( 'repo_path_default', 'booktool_github');

    if ( strcmp( $fromForm->repo, $repoDefault ) !== 0 &&
         strcmp( $fromForm->path, $pathDefault ) !== 0 ) {

        if ( strcmp( $fromForm->repo, $repo_details['repo']) !== 0 || 
             strcmp( $fromForm->path, $repo_details['path']) !== 0 ) {

            $change = true;

            print "<h1>Going to change the database</h1>";
            print "<xmp> " ;
            var_dump( $fromForm );
            print "</xmp>";

            $repo_details['repo'] = trim( $fromForm->repo );
            $repo_details['path'] = trim( $fromForm->path );
            $repo_details['bookid'] = trim( $fromForm->bookid );
            $repo_details['id'] = trim( $fromForm->id );

            if ( ! booktool_github_repo_exists($github_client, $repo_details) ) {
                print get_string( 'form_repo_not_exist_error', 'booktool_github',
                                   $repo_details );
                $change = false;
                $validConnection = false;
                // - does the repo existing on github ?? create it??
                // *** maybe add this later - would require more work
                // create repo https://developer.github.com/v3/repos/#create
                // POST /user/repos 

            } else if ( ! booktool_github_path_exists($github_client, $repo_details)) {
  print "<h3> path does not exist </h3>";
                // file no exists, so create an empty one
                if ( ! booktool_github_create_new_file( $github_client, $repo_details) ) {
                    print get_string( 'form_no_create_file', 'booktool_github',
                                      $repo_details );
                    $change = false;
                    $validConnection = false;
                }  else {
    print "<h3>Able to create file </h3>";
                }
            }
            //****** where we save the data
            if ( $change ) {
                if ( ! booktool_github_put_repo_details( $repo_details ) ) {
                    print "<h1> updateing databse stuff</h1>";
                    print get_string( 'form_no_database_write', 'booktool_github' );
                } 
            }

            // if all ok, save it to the database
        } else { // didn't change the existing data
            print "<h1>didn't change the data</h1>";
            print "<xmp> " ;
            var_dump( $fromForm );
            print "</xmp>";
        }
    } else {  // didn't change the default form data
        print get_string( 'form_no_change_default_error', 'booktool_github' );
    } 


        // **** may just continue on at this stage
} 

// now show the rest of the form

    if ( ! array_key_exists( 'repo', $repo_details ) ) {
        print get_string( 'form_empty', 'booktool_github' );
    } else if ( $validConnection ) {
        print get_string( 'form_complete', 'booktool_github',
                          'http://github.com/' . $repo_details['owner'] . 
                          '/' . $repo_details['repo'] . '/blob/master/' .
                          $repo_details['path'] );
    } else {
        print get_string( 'form_connection_broken', 'booktool_github' );
    }

    // *** how does this handle the no change stuff?
print "<h1>report details is </h1> <xmp>"; var_dump( $repo_details ) ; print "</xmp>";
    //$form = new connection_form( null, $repo_details );
    $form->set_data( $repo_details );
    $form->display();

    if ( $validConnection ) {
        booktool_github_view_status( $id, $github_client, $repo_details );
    }

echo $OUTPUT->footer();



