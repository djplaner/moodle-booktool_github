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

require_once( __DIR__ . '/push_form.php' );

// *** can this be put into a support function?
$cmid = required_param('id', PARAM_INT);           // Course Module ID

$cm = get_coursemodule_from_id('book', $cmid, 0, false, MUST_EXIST);

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST); 

$tool_url = new moodle_url( '/mod/book/tool/github/index.php', array( 'id' => $cmid));
$book_url = new moodle_url( '/mod/book/view.php', array('id'=>$cmid));

$PAGE->set_url('/mod/book/tool/github/push.php');

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);
require_capability( 'booktool/github:export', $context );

$PAGE->navbar->add( 'GitHub tool', $tool_url );
$PAGE->navbar->add( get_string('push_form_crumb','booktool_github'), 
                    new moodle_url( '/mod/book/tool/github/push.php',
                                    array('id'=>$cmid) ));

#************** Need to think about what events get added
#\booktool_exportimscp\event\book_exported::create_from_book($book, $context)->trigger();

#--- show the header and initial display 

//*****
// - has this book been configured to use github?

$repo_details = booktool_github_get_repo_details( $book->id );

echo $OUTPUT->header();

// get github client and github user details via oauth
list( $github_client, $github_user ) = booktool_github_get_client( $cmid );

// couldn't authenticate with github, probably never happen
// **** TIDY UP
if ( ! $github_client ) {
    print '<h1> Cannot authenticate with github</h1>';

    echo $OUTPUT->footer();

    die;
} 

// add the "owner" of this connection as the username from oAuth
$repo_details['owner'] = $github_user->getLogin();

//*************************************
// Start showing the form

$form = new push_form( null, array( 'id' => $cmid ));

// Build params for messages
$git_url = 'http://github.com/' . $repo_details['owner'] . '/' .
            $repo_details['repo'] . '/blob/master/' . $repo_details['path'];
$repo_url = 'http://github.com/' . $repo_details['owner'] . '/' .
            $repo_details['repo'] . '//' ;
$git_user_url = 'http://github.com/' . $repo_details['owner'];

$urls = Array( 'book_url' => $book_url->out(), 'tool_url'=>$tool_url->out(),
                'git_url' => $git_url, 'repo_url' => $repo_url,
                'git_user_url' => $git_user_url );


if ( $fromForm = $form->get_data() ) {
    // user has submitted the form, they want to do the push

    // grab the book content and combine into a single file

    // commit the file
    if ( booktool_github_push_book( $github_client, $repo_details,
                                    $fromForm->message ) ) {
        print get_string('push_success','booktool_github',$urls);
    } else {
        print get_string('push_failure','booktool_github',$urls);
    }
} else {
    // just display the initial warning
    print get_string( 'push_warning', 'booktool_github', $urls );

    $form->display();
}

echo $OUTPUT->footer();



