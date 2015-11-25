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
 * Book github export lib
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');
require_once(__DIR__ . '/../../locallib.php');
require_once( __DIR__ . '/local/client/client/GitHubClient.php' );

const GITHUB_TOKEN_NAME = "github_token";

/***************************************************
 * github client specific calls
 */

/**
  * ( $github_client, $github_user ) = booktool_github_get_client( $id )
  * - get OAuth connection with github
  * - create a github client object to communicate with github
  * - get details of github user
  */

function booktool_github_get_client( $id ) {

    $attempts = 0;
    GET_TOKEN: 
    $oauth_token = booktool_github_get_oauth_token( $id);
    $attempts++;

    if ( $oauth_token  ) {
        $client = new GitHubClient();
        $client->setAuthType( 'Oauth' );
        $client->setOauthToken( $oauth_token );

        // replace this with get user details
        try{
            $user = $client->users->getTheAuthenticatedUser();
        } catch ( Exception $e ) {
            // oops problem, probably a 401, try and fix
            $msg = $e->getMessage();
            preg_match( '/.*actual status \[([^ ]*)\].*/', $msg, $matches );

            if ( $attempts > 2 ) {
                print "<h1> looks like I'm in a loop ";
                //** need to handle this, is this a failure?
                return Array( false, false );
            } elseif ( $matches[1] == 401 ) {
                // SHOULD DELETE token from session
                unset( $_SESSION['github_token'] );
                goto GET_TOKEN; 
            }
        }
        return Array( $client, $user );
    }
}

/**
 * does a given repo exist
 */

function booktool_github_repo_exists( $github_client, $repo_details ) {

    $data = Array();
    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'];
    try{
        $response = $github_client->request($request, 'GET', $data, 200, 
                                            'GitHubFullRepo');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * does a file exist within a repo
 **/

function booktool_github_path_exists( $github_client, $repo_details ) {

//$github_client->setDebug( true );

    $data = Array();
    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'] .
               "/contents/" . rawurlencode( $repo_details['path'] );
//print "<h3> does it exist? request is $request </h3>";

    try{
        $response = $github_client->request($request, 'GET', $data, 200, 
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}

/**
 * create an new empty file
 * - return true if worked
 */

function booktool_github_create_new_file( $github_client, $repo_details, $content='' ) {

//$github_client->setDebug( true );

    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'] .
               "/contents/" . rawurlencode( $repo_details['path'] );
    $data = Array();
    $data['message'] = 'Creating new file - Moodle book github tool';
    $data['content'] = base64_encode( $content );

    try{
        $response = $github_client->request($request, 'PUT', $data, 201, 
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    return true;
}




/** 
  * ( repo, path ) = get_repo_details( $book_id, $github_user );
  * - return an array of basic information about the connection
  *   that is required by the github_client
  *   - repo is the name of the github repo
  *   - path is the full path for the file from the report connected with book
  * - return false if can't get the information
  **/

function booktool_github_get_repo_details( $book_id ) {
    global $DB;

    // repo and path are contained in database table github_connections
    $result = $DB->get_record( 'booktool_github_connections', 
                                Array( 'bookid'=> $book_id) );

    // if no data, then just return false
    if ( ! $result ) {
        return false;
    }

    return Array( 'id' => $result->id,
                  'bookid' => $result->bookid,
                  'repo' => $result->repository,
                  'path' => $result->path );
}

/**
  * bool = put_repo_details( $repo_details )
  * - either insert or update repo details in the database
  * - dependent on whether repo_details has an id
  */

function booktool_github_put_repo_details( $repo_details ) {
    global $DB;

    $record = new StdClass();
    $record->bookid       = $repo_details['bookid'];
    $record->repository   = $repo_details['repo'];
    $record->path         = $repo_details['path'];

print "<h3> repo details is </h3> <xmp>" ;
var_dump( $repo_details ); print "</xmp>";
    if ( array_key_exists( 'id', $repo_details ) ) {
print "<h3>Update existing entry </h3>";
        // update an existing entry
        $record->id = $repo_details['id'];

        return $DB->update_record( 'booktool_github_connections', $record );

    } 
    // insert a new entry
print "<h3>record is </h3> <xmp>"; var_dump( $record ) ; print "</xmp>";

    return $DB->insert_record( 'booktool_github_connections', $record );
}


/**
 * $commits = getCommits( );
 * - return an array of GitHubCommit objects
 */

function booktool_github_get_commits( $id, $github_client, $repo_details) {

   try{
       $commits = $github_client->repos->commits->listCommitsOnRepository(
                            $repo_details['owner'], $repo_details['repo'], null, 
                            $repo_details['path'] );
    } catch ( Exception $e ) {
        $msg = $e->getMessage();
        echo '<xmp>Caught exception ' . $msg .  "</xmp>";
        return false;
    }
    return $commits;
}

/**
 * bool = booktool_github_change_in_form( $repo_details, $form )
 * - return TRUE/FALSE depending on whether there have been changes
 *   made in the form
 **/

function booktool_github_change_in_form( $repo_details, $form ) {

    // is there anything in the database?
//    if ( array_key_exists( 'repo', $repo_details ) &&
//         array_key_exists( 'path', $repo_details ) ) {
print "<h1> the repo exists </h1>";
        // has the form changed from defaults?
        $repoDefault = get_string( 'repo_form_default', 'booktool_github');
        $pathDefault = get_string( 'repo_path_default', 'booktool_github');

        if ( strcmp( $form->repo, $repoDefault ) !== 0 &&
             strcmp( $form->path, $pathDefault ) !== 0 ) {

print "<h1> path is different </h1>";
            // has the form data changed from content of the database? 
            if ( strcmp( $form->repo, $repo_details['repo']) !== 0 &&
                 strcmp( $form->path, $repo_details['path']) !== 0 ) {
print "<h1> form data changed </h1>";
                return true;
            }
        }
 //   }
    return false;
}

/***************************************************
 * Views
 ***************************************************/

/****************************
 * Display summary of what we know about details of repo
 * STATUS 
 * - Basic prototype version. Almost hard coded.
 * - Not really querying data from github
 */

function booktool_github_view_repo_details( $repo_details, $github_user){

    $repo_url = "https://github.com/" . $repo_details['owner'] . "/" .
                $repo_details['repo'];
    $path_url = $repo_url . "/blob/master/" . $repo_details['path'];
    //*** should I remove double / in path_url (but not from http://)
    $owner_url = "https://github.com/" . $repo_details['owner'];

    $string = '';

    $table = new html_table();
    $table->head = array( '', '' );
    
    $table->data[] = array( 'Repository', '<a href="' . $repo_url . '">' . 
                                $repo_details['repo'] . '</a>' );
    $table->data[] = array( 'File', '<a href="' . $path_url . '">' . 
                                $repo_details['path'] . '</a>' );

    // show information about current user
    $avatar_url = $github_user->getAvatarUrl();
    $name = $github_user->getName();
    $user_name = $github_user->getLogin();
    $user_url = $github_user->getHtmlUrl();

    $user_html = html_writer::start_div( "githubuser" );
    //$image = html_writer::empty_tag( 'img', array(
    $user_html .= html_writer::link( $user_url, 
                                     $name . '<br /> (' . $user_name . 
                                     ') &nbsp;&nbsp;' );
    $user_html .= html_writer::empty_tag( 'img', array(
                        'src' => $avatar_url,
                        'alt' => 'Avatar for ' . $name,
                        'height' => 20, 'width'=> 20 ) );
    $user_html .= html_writer::end_div();

    $table->data[] = array( 'User', $user_html );

    $string .= html_writer::table( $table ); 
    //$table->data[] = array( 'User', '<a href="' . $owner_url . '">' . 
     //                           $repo_details['owner'] . '</a>' );

    return $string;
}



/***************************************************
 * Given book id, github client and repo details
 * display a range of status and historical information about the
 * book and its connection to github
 */

function booktool_github_view_status( $id, $github_client, $repo_details ) {

    // if there are repo details show the commit information
    if ( array_key_exists( 'repo', $repo_details ) ) {
        $commits = booktool_github_get_commits( $id, $github_client, $repo_details);

        if ( ! $commits ) {
            print "<h3>Error - no get commits </h3>" ;
        } else {
            echo '<h3>History</h3>';
            $string = booktool_github_view_commits( $commits );
            echo $string;
        }
    } 
        
}



/***************************************************
 * - Given a a GitHubCommit object display info about each commit
 * - Currently a table where each row matches a commit and shows
 *   - Date changed - not showing it yet
 *   - Message for the commit & link to more information
 *   - Who made the commit 
 *
 * TO DO: 
 *   - what happens if DateTime errors?
 *   - clean up the mish-mash of html-write and html
 *     a renderer or template would be better
 */

function booktool_github_view_commits( $commits ) {

    $string = '';

    $table = new html_table();
    $table->head = array( 'Date changed', 'Details', 'Committer' );

    // message for link to commit details
    $details_link = get_string( 'commit_details', 'booktool_github' );
    $details_link = '<span style="font-size:small">[ ' . $details_link .
                    ' ] </span>';

    // each row is based on a single commit to the file
    foreach( $commits as $commit ) {
        // return GitHubCommitCommit object
        // date of commit
        $commit_details = $commit->getCommit();
        $message_text = $commit_details->getMessage();
        $html_url = $commit->getHtmlUrl();

        $message = html_writer::link( $html_url, $details_link );
        $message = '<div class="commit_message"> ' . $message_text . '</div>' .
                    $message;

        // author has the full name and date
        $author_details = $commit_details->getAuthor();
        $author_name = $author_details->getName();
        $date_commit = $author_details->getDate();
        $date = new DateTime( $date_commit );
        $date_display = $date->format( 'D, d M Y H:i:s' ); 

        // return GitHubUser object
        // - get the avatar, username and html url for user
        $committer_details = $commit->getCommitter();
        $user_name = $committer_details->getLogin();
        $avatar_url = $committer_details->getAvatarUrl();
        $user_url = $committer_details->getHtmlUrl();

        $committer = html_writer::start_div( "committer" );
        $image = html_writer::empty_tag( 'img', array(
                        'src' => $avatar_url,
                        'alt' => 'Avatar for ' . $user_name,
                        'height' => 20, 'width'=> 20 ) );
        $committer .= html_writer::link( $user_url, 
                                            $author_name . '&nbsp;&nbsp;' .
                                            $image . '<br /> (' . 
                                            $user_name . ')' );
        $committer .= html_writer::end_div();

        $row = array( $date_display, $message, $committer );
        $table->data[] = $row; 
    }

    $string .= html_writer::table( $table ); 

// debug stuff
/*    $string .= "<xmp>";
    $string .= print_r($commits, true);
    $string .= "</xmp>"; */
    return $string;

}

/**
 * $token = booktool_github_get_oauth_token( $URL )
 * - return the oauth access token from github
 * - if there isn't one, get one and then redirect back to $URL
 * - if one can't be gotten, show why
 */

function booktool_github_get_oauth_token( $id, $URL='/mod/book/tool/github/index.php' ) {

    if ( array_key_exists( "github_token", $_SESSION ) ) {
        return $_SESSION{"github_token"};
    } else {

        // redirect to get_oauth.php include passsing CURRENT_URL
        $url = new moodle_url( '/mod/book/tool/github/get_oauth.php',
                                array( 'id' => $id, 'url' => $URL ));
        redirect( $url );
        return false;
    } 
}

/*
 * clientid = booktool_github_get_client_details()
 * - return the base64 client id from github for this tool
 * TO DO: replace this with a call to the database or other location
 */

function booktool_github_get_client_details() {
    global $DB;

    $result = $DB->get_record( 'booktool_github', Array( 'id'=>1) );

    if ( ! $result ) {
        return Array( 'clientid' => '',
                  'clientsecret' => '' );
    } else {
        return Array( 'clientid' => $result->clientid,
                      'clientsecret' => $result->clientsecret );
    }
}


/*****************************************************************
 * "views" - functions that generate HTML to display
 ****************************************************************/

/*
 * Shows basic instructions about the github tool
 */

function booktool_github_show_instructions( $id ) {


    $git_url = new moodle_url( '/mod/book/tool/github/index.php',
                            array( 'id' => $id , 'instructions'=>1 ) );
    $book_url = new moodle_url( '/mod/book/view.php', array('id'=>$id ));

    $urls = Array( 'git_url' => $git_url->out(),
                   'book_url' => $book_url->out() );

    $content = Array( 'instructions_what_header', 'instructions_what_body',
                      'instructions_why_header', 'instructions_why_body',
                      'instructions_requirements_header', 
                      'instructions_requirements_body',
                      'instructions_whatnext_header', 
                      'instructions_whatnext_body' );

    foreach ( $content as $display ) {
        print get_string( $display, 'booktool_github', $urls );
    }

}

/*****************************************************************
 * Support utils
 ****************************************************************/

// encode/decode params
// - used to pass multiple paths via oauth as the STATE variable
// - enables github_oauth.php to know the id for the book and the
//   the URL to return to

// accept a hash array and convert it to url encoded string
function booktool_github_url_encode_params( $params ) {
    $json = json_encode( $params );
    return strtr(base64_encode($json), '+/=', '-_,');
}

// accept a url encoded string and return a hash array
function booktool_github_url_decode_params($state) {
    $json = base64_decode(strtr($state, '-_,', '+/='));
    return json_decode( $json );
}


