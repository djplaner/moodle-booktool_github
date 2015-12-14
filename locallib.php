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
  * ( repo, path ) = get_repo_details( $book_id );
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

    return Array( 'connection_id' => $result->id,
                  'bookid' => $result->bookid,
                  'repo' => $result->repository,
                  'path' => $result->path,
                  'pushedtime' => $result->pushedtime,
                  'pushedrevision' => $result->pushedrevision );
}

/**
  * bool = put_repo_details( $repo_details )
  * - either insert or update repo details in the database
  * - dependent on whether repo_details has an id
  */

function booktool_github_put_repo_details( $repo_details ) {
    global $DB;

    // make sure all the required values available
    $checks = Array( 'pushedrevision', 'pushedtime' );
    foreach ( $checks as $check ) {
        if ( ! array_key_exists( $check, $repo_details )) {
            $repo_details[$check] = '';
        }
    }

    $record = new StdClass();
    $record->bookid       = $repo_details['bookid'];
    $record->repository   = $repo_details['repo'];
    $record->path         = $repo_details['path'];
    $record->pushedrevision = $repo_details['pushedrevision'];
    $record->pushedtime = $repo_details['pushedtime'];

    if ( array_key_exists( 'connection_id', $repo_details ) && 
         $repo_details['connection_id'] > 0) {
        // update an existing entry if no id or id is 0
        // i.e. the form was empty
        $record->id = $repo_details['connection_id'];

        $DB->update_record( 'booktool_github_connections', $record );
        return true;
    } 
    // insert a new entry

    return $DB->insert_record( 'booktool_github_connections', $record ); 
}


/**
 * $commits = getCommits( );
 * - return an array of GitHubCommit objects
 */

function booktool_github_get_commits( $github_client, $repo_details) {

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
//print "<h1> the repo exists </h1>";
        // has the form changed from defaults?
        $repoDefault = get_string( 'repo_form_default', 'booktool_github');
        $pathDefault = get_string( 'repo_path_default', 'booktool_github');

        if ( strcmp( $form->repo, $repoDefault ) !== 0 &&
             strcmp( $form->path, $pathDefault ) !== 0 ) {

//print "<h1> path is different </h1>";
            // has the form data changed from content of the database? 
            if ( strcmp( $form->repo, $repo_details['repo']) !== 0 &&
                 strcmp( $form->path, $repo_details['path']) !== 0 ) {
//print "<h1> form data changed </h1>";
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

function booktool_github_view_status( $cmid, $github_client, $repo_details, $urls ) {

    // if there are repo details show the commit information
    if ( array_key_exists( 'repo', $repo_details ) ) {
        $commits = booktool_github_get_commits( $github_client, $repo_details);
        // ?? need to set default value
        $pushed_revision = $repo_details['pushedrevision'];
        $pushed_time = $repo_details['pushedtime'];

        $book_revision = booktool_github_get_book_revision( $repo_details );
        $lastgit_time = booktool_github_get_last_gittime( $commits );

/*print "<h3>repo</h3><xmp>"; var_dump($repo_details); print "</xmp>";
print "<h3> Status situation </h3> ";
print "<ul> <li> pushed_revision $pushed_revision versus book_revision $book_revision </li> <li> pushed_time $pushed_time versus git_time $lastgit_time</li> </ul>"; */

        // *** space to add push/pull etc
        booktool_github_show_push_pull( $cmid, $pushed_revision, $pushed_time, $book_revision, $lastgit_time, $urls ) ;
            
        if ( ! $commits ) {
            // ***** fix this up
            print get_string('form_no_commits','booktool_github',$urls);
        } else {
            print get_string('form_history','booktool_github',$urls);

            $string = booktool_github_view_commits( $commits );
            echo $string;
        }
    } 
        
}

/***************************************************
 * booktool_github_get_book_revision( $repo_details)
 * - return the revision number for the Moodle book
 */

function booktool_github_get_book_revision( $repo_details) {
    global $DB;

    $result = $DB->get_record( 'book', Array( 'id'=>$repo_details['bookid']) );

    if ( ! $result ) {
        return 0;
    } else {
        return $result->revision;
    }
}

/***************************************************
 * booktool_github_get_last_gittime( $commits )
 */ 

function booktool_github_get_last_gittime( $commits ) {

    $commit = $commits[0]->getCommit();
    $author_details = $commit->getAuthor();
    $date = $author_details->getDate();
//??? error checking

    return strtotime( $date );
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

/******************************************************************
 * booktool_github_show_push_pull( $pushed_revision, $pushed_time, $book_revision, $lastgit_time );
 * - figure out whether push pull up to date should be shown
 * - BOOK PUSH
 *   - if pushedrevision is behind current Book revision
 #   - if pushedtime ahead last big commit
 * - GIT PULL
 *   - if repo_details->timepushed is behind most recent commit
 */

function booktool_github_show_push_pull( $cmid, $pushed_revision, $pushed_time, 
                                         $book_revision, $lastgit_time, $urls ) {

    $status = '';
    $push = false;
    $pull = false;

    if ( $pushed_revision < $book_revision )  {
        $status .= get_string('book_revision','booktool_github');
        $push = true;
    }
    if ( $pushed_time > $lastgit_time ) {
        $status .= get_string('missing_push', 'booktool_github');
        $push = true;
    }
    if ( $pushed_time < $lastgit_time ) {
        $status .= get_string('behind_git', 'booktool_github');

        $pull = true;
    }

    if ( ! $push && ! $pull ) {
        $status .= get_string('consistent', 'booktool_github');
    }

    $urls['status'] = $status;
    print get_string('form_status','booktool_github', $urls);

    $push_url = new moodle_url('/mod/book/tool/github/push.php', array('id' => $cmid));
    $pull_url = new moodle_url('/mod/book/tool/github/pull.php', array('id' => $cmid));

    $arr = Array( 'push_url'=>$push_url->out(), 'pull_url'=>$pull_url->out());
    print get_string('form_operations', 'booktool_github', $arr);

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

        print get_string( 'github_redirect', 'booktool_github' );
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

/*
 * $content = booktool_github_get_file_content( $github_client, $repo_details )
 * - return the contents of the github file
 */

function booktool_github_get_file_content( $github_client, $repo_details ) {
    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'] .
               "/contents/" . rawurlencode( $repo_details['path'] );

    $data = array();
    $response = array();
    try{
        $response = $github_client->request( $request, 'GET', $data, 200, 'GitHubReadmeContent'   );
    } catch ( Exception $e ) {
        return 0;
    }

    return base64_decode( $response->getContent() );
}

/*****************************************************************
 * PUSH | PULL functions
 */

function booktool_github_push_book( $github_client, $repo_details, $message ) {
    global $DB;

    // get the book data
    $book = $DB->get_record( 'book', Array( 'id'=> $repo_details['bookid']) );
    $select = "bookid=" . $repo_details['bookid'] ." order by pagenum";
    $result = $DB->get_records_select( 'book_chapters', $select);

    // generate the content
    $book_content = booktool_github_prepare_book_html( $book, $result );

    // do the push??
    $data = array();
    $data['message'] = $message;
    $data['content'] = base64_encode( $book_content );
    $git_details = booktool_github_git_details( $github_client, $repo_details );
    if ( $git_details === 0 ) {
        print "<h3> Failure</h3>";
        return false;
    }
    $data['sha'] = $git_details->getSha();

    $data['committer'] = array( 'name' => 'David Jones',
                                'email' => 'davidthomjones@gmail.com' );

    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'] .
               "/contents/" . rawurlencode( $repo_details['path'] );

    $data['content'] = base64_encode( $book_content );

    try{
        $response = $github_client->request($request, 'PUT', $data, 200, 
                                            'GitHubReadmeContent');
    } catch ( Exception $e ) {
        return false;
    }

    // need to update the book table
    // - modify pushedrevision to current book revision
    // - set pushedtime to latest git time
    $commits = booktool_github_get_commits( $github_client, $repo_details) ;
    $lastgit_time = booktool_github_get_last_gittime( $commits );
//print "<h3>FROM repo_details</h3><xmp>";var_dump($repo_details);print "</xmp>";

    $repo_details['pushedtime'] = $lastgit_time;
    $repo_details['pushedrevision'] = $book->revision;

//print "<h3>TO repo_details</h3><xmp>";var_dump($repo_details);print "</xmp>";

    return booktool_github_put_repo_details( $repo_details );
}

/*
 * return a modified GitHubReadmeContent object with details about the
 * latest version of the file
 * - add "date" to hold the time the file was last committed 
 */

function booktool_github_git_details( $github_client, $repo_details ) {
    $request = "/repos/" . $repo_details['owner'] . "/" . $repo_details['repo'] .
               "/contents/" . rawurlencode( $repo_details['path'] );

    // get the basic detail about the file
    $data = array();
    $response = array();
    try{
        $response = $github_client->request( $request, 'GET', $data, 200, 'GitHubReadmeContent'   );
    } catch ( Exception $e ) {
//print "<h3>First failure</h3>";
        return 0;
    }

    return $response;
}


//*** transform the contents of the book into some sort of single HTML string
// * Important information for each chapter is
//   - pagenum, subchapter, title, content, contentformat, hidden
// * Improtant information for the book
//   - name, intro, introformat, customtitles?
function booktool_github_prepare_book_html( $book, $result ) {
    global $DB;

    $content = "<!DOCTYPE html>\n<html>\n    <head><title>" . $book->name. "</title></head>\n<body>\n";

    // the book forms an article
    // - title - name of book
    // - data attributes for other database values
    $content .= "\n" . '<article title="' . $book->name . 
                '" data-introformat="' . $book->introformat .
                '" data-customtitles="' . $book->customtitles . 
                '" data-numbering="' . $book->numbering .
                '" data-navstyle="' . $book->navstyle .  '">' . "\n" . 
       // head to contain title and intro
                '    <head><h1>' . $book->name . "</h1>\n" .
                '        <div>' . $book->intro . "</div>\n" .
                "    </head>\n";

    // create each chapter as a section within the article
    // complication: the next chapter may be a sub-chapter, if that's the
    // case, don't want to close the section tag for the chapter.
    $prevChapter = 'none';
    $lastChapter = false;
    $numItems = count( $result );
    $i = 0;

    foreach ( $result as $chapter ) {
//print "<h3> Chapter $i</h3>";
        // how to set last chapter
        $lastChapter = ( ++$i === $numItems );
//print "<p>LastChapter: $lastChapter </p>";
        // add the appropriate HTML and set the value for last chapter
        $content = generate_chapter_html( $content, $chapter, 
                                          $prevChapter, $lastChapter );

        $prevChapter = $chapter->subchapter;
    }

    $content .= "</body>\n</html>";

    return $content ;
}

/******************************************************************
 * generate_chapter_html( $content, $chapter, $prevChapter, $lastChapter )
 * - add HTML to $content depending on the data in $chapter and
 *   what the $lastChapter was
 * - return new value for $lastChapter
 */

function generate_chapter_html( $content, $chapter, $prevChapter, $lastChapter ) {

    // <section BODY is always needed
    $body = '    <section title="' . $chapter->title .
             '" data-subchapter="' .  $chapter->subchapter . 
             '" data-pagenum="' . $chapter->pagenum . 
             '" data-contentformat="' . $chapter->contentformat . 
             '" data-hidden="' . $chapter->hidden . '">' . "\n" .
             '        <head><h1>' . $chapter->title . '</h1></head>'. "\n" .
             '        <div>' . $chapter->content . '</div>' . "\n" ;

    // first chapter
    if ( $prevChapter === 'none' ) {
//print "<p>FIRST CHAPTER.... sub is " . $chapter->subchapter;
        if ( $chapter->subchapter == 0 ) {
            // <section BODY
            $content .= $body;
//print "<p>NOT sub chatper</p>\n";
        } else {
            // <section EMPTY <section BODY
            $content .= '    <section></section>' . "\n    " . $body;
//print "<p>sub chatper</p>\n";
        }
    //  in the middle chapters
    } else if ( ! $lastChapter ) {
//print "<p>MIDDLE CHAPTER...." . $chapter->subchapter;
        if ( $prevChapter == 0 ) {
            if ( $chapter->subchapter == 0 ) {
                // </section <section BODY
                $content .= '    </section>' . "\n    " . $body;
//print "<p>NOT sub chatper</p>\n";
            } else {
                // <section BODY
                $content .= $body;
//print "<p>sub chatper</p>\n";
            }
        } else { 
            if ( $chapter->subchapter == 0 ) {
                // </section #subc </section #chap <section BODY 
                $content .= "        </section>\n    </section>\n    " . $body;
//print "<p>NOT sub chatper</p>\n";
            } else {
                // </section #subc <section BODY
                $content .= "        </section>\n    " . $body;
//print "<p>sub chatper</p>\n";
            }
        }
    //  last chapter
    } else {
//print "<p>LAST CHAPTER...." . $chapter->subchapter;
        if ( $prevChapter == 0) {
            if ( $chapter->subchapter == 0 ) {
                // </section <section BODY </section
                $content .= "    </section>\n    " . $body . 
                            "\n    </section>";
//print "<p>NOT sub chatper</p>\n";
            } else {
                // <section BODY </section #subc </section #chap
                $content .= $body . "\n        </section>\n    </section>";
//print "<p>sub chatper</p>\n";
            }
        } else { 
            if ( $chapter->subchapter == 0 ) {
                // </section #subc </section #chap <section BODY </section
                $content .= "        </section>\n    </section>\n" . $body . 
                            "\n    </section>";
//print "<p>NOT sub chatper</p>\n";
            } else {
                // </section #subc <section BODY </section #subc </section #chap
                $content .= "        </section>\n" . $body . 
                            "\n        </section>\n    </section>";
//print "<p>sub chatper</p>\n";
            }
        }
    }

    return $content;
}



/******************************************************************
 * book_tool_github_pull_book
 */

function booktool_github_pull_book( $github_client, $repo_details, $book ) {
    global $DB;

    // retrieve the content of the github file
    $content = booktool_github_get_file_content($github_client, $repo_details);
    if ( $content === 0 ) {
        return false;
    }

    // parse it
    $git_book = booktool_github_parse_file_content( $content );

    if ( $git_book === false ) {
        return false;
    }

    // update the book table entry
    booktool_github_update_book_table( $repo_details, $git_book );

   
    // remove old book chapters and add the new ones
    $result = $DB->delete_records('book_chapters',array('bookid'=>$repo_details['bookid']));

    booktool_github_insert_chapters_table( $repo_details, $git_book );

    // update the github_connections table
    // - pushedtime should equal lastcommit in git
    // - pushed revision = latest revision from book + 1
    $commits = booktool_github_get_commits( $github_client, $repo_details) ;
    $lastgit_time = booktool_github_get_last_gittime( $commits );
//print "<h3>FROM repo_details</h3><xmp>";var_dump($repo_details);print "</xmp>";

    $repo_details['pushedtime'] = $lastgit_time;
    $repo_details['pushedrevision'] = 1 + $book->revision;

//print "<h3>TO repo_details</h3><xmp>";var_dump($repo_details);print "</xmp>";

    return booktool_github_put_repo_details( $repo_details );
}

/*
 * Insert chapters from git_book into the chapters table
 */

function booktool_github_insert_chapters_table( $repo_details, $git_book ) {
    global $DB;

    $chapters = Array();
    foreach ( $git_book->chapters as $git_chapter ) {
        $chapter = (object)$git_chapter;
        $chapter->bookid = $repo_details['bookid'];
        $chapter->timecreated = time();
        $chapter->timemodified = 0;
        $chapter->importsrc = '';
        array_push( $chapters, $chapter );
    }

    return $DB->insert_records( 'book_chapters', $chapters, true ); 
}

/*
 * update the book table with data from git
 * - intro, name, introformat, customtitles, numbering, navstyle 
 *   changed to git value
 * - timemodified - gets updated to now
 * - revision - gets incremented
 */

function booktool_github_update_book_table( $repo_details, $git_book ) {
    global $DB;

    $update_fields = Array( 'intro', 'name', 'introformat', 'customtitles',
                            'numbering', 'navstyle' );

    // get the existing data for the book
    $book = $DB->get_records( "book", Array( 'id' => $repo_details['bookid'] ));

    if ( sizeof( $book ) == 0 ) {
        return false;
    }

    $book = $book[$repo_details['bookid']];

//print "<h3>Changing this</h3><xmp>"; var_dump($book); print "</xmp>";
//print "<h3>gitbook</h3><xmp>"; var_dump($git_book); print "</xmp>";
    // update the right bits from git
    foreach ( $update_fields as $change ) {
        $book->$change = $git_book->book->$change;
    }
    
    // update locally
    $book->revision++;// = 10 + $book->revision ;
    $book->timemodified = time();

//print "<h3>TO</h3><xmp>"; var_dump($book); print "</xmp>";
    return $DB->update_record( "book", $book );
}


/*
 * parse the HTML file 
 */
function booktool_github_parse_file_content( $content ) {

    // book_details
    // - BOOK -> name introformat customtitles
    // - CHAPTERS -> one for each chapter
    $book_details = new StdClass;
    $book_details->book = new StdClass;
    $book_details->chapters = Array();

    $dom = new DOMDocument;
    $dom->loadHTML( $content );

    $xpath = new DOMXPath($dom);

    //******* GET BOOK DATA
    // - attributes other than intro
    $book_info = $xpath->query( "//div[@class='mg-book']");
    if ( $book_info === false ) {
        return false;
    }

    foreach ( $book_info as $book ) {
        $attrNames = Array( 'name', 'introformat',
                            'customtitles', 'numbering', 'navstyle' );
        foreach ( $attrNames as $name ) {
            $attribute = $book->attributes->getNamedItem( 'data-' .$name );
            $book_details->book->$name = $attribute->nodeValue;
        }
    }

    // - book_intro
    $book_intro = $xpath->query( "//div[@class='mg-book_intro']");
    if ( $book_intro === false ) {
        return false;
    }

    foreach ( $book_intro as $intro ) {
        $book_details->book->intro = booktool_github_DOMinnerHTML( $intro );
    }

//print "<xmp>"; var_dump( $book_details ); print "</xmp>";

    //******* remove the headings for chapter titles from chapter content
    $headings = $xpath->query( "//h1[@class='mg-chapterTitle']");
    // apparently have to do the dumy array thing for it to work
    $remove = Array();
    foreach ( $headings as $heading ) {
        $remove[] = $heading;
    }

    foreach ( $remove as $heading ) {
        $heading->parentNode->removeChild($heading);
    }

    //********* get the chapter data
    $chapters = $xpath->query( "//div[@class='mg-book_chapter']");
    if ( $chapters === false ) {
        return false;
    }

    foreach ( $chapters as $chapter ) {
        $new_chapter = Array();
       $attrNames = Array( 'subchapter', 'pagenum', 'hidden',
                            'contentformat', 'title');
        foreach ( $attrNames as $name ) {
            $attribute = $chapter->attributes->getNamedItem('data-'. $name );
            $new_chapter[$name] = $attribute->nodeValue;
        }
        $new_chapter['content'] = booktool_github_DOMinnerHTML( $chapter );
//print "<h3>Content $new_chapter[$name]</h3><xmp>";var_dump($new_chapter['content']); print "</xmp>";
        array_push( $book_details->chapters, $new_chapter );
    }

//print "<xmp>"; var_dump( $book_details ); print "</xmp>";

    return $book_details;
}

function booktool_github_DOMinnerHTML(DOMNode $element)
{
    $innerHTML = "";
    $children  = $element->childNodes;

    foreach ($children as $child)
    {
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML;
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





