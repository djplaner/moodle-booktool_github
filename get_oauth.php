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
 * Book IMSCP export plugin -- get_oauth.php 
 * - check and update Oauth token from github
 *
 * @package    booktool_github
 * @copyright  2015 David Jones {@link http://djone.es}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../../../config.php');
require_once($CFG->dirroot.'/mod/book/tool/github/locallib.php');

require_once(__DIR__ . '/local/PHP-OAuth2/Client.php');
require_once(__DIR__ . '/local/PHP-OAuth2/GrantType/IGrantType.php');
require_once(__DIR__ . '/local/PHP-OAuth2/GrantType/AuthorizationCode.php');

// **** NEED TO PUT THESE IN A DATABASE TABLE
const CLIENT_ID     = 'b8340758e05e8280f5ef';
const CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';
const REDIRECT_URI  = 'http://localhost:8080/moodle/mod/Book/tool/github/get_oauth.php';

// Hard-coded into the tool
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';


//-- do the Moodle checks
$id = optional_param('id', -1, PARAM_INT);           // Course Module ID
$code = optional_param( 'code', '', PARAM_BASE64);
$url = optional_param( 'url', '', PARAM_LOCALURL );

if ( $id == -1 ) {
    print "<h3> ERROR need to get id from STATE</h3>";
    //$state = optional_param( 'state', '', PARAM_BASE64 );
    $state = optional_param( 'state', '', PARAM_RAW );
//    print "<ul>  <li> code is " . $code . "</li>";
 //   print "<li> State is " . $state . "</li></ul> ";

    if ( $state == '' ) {
        print "<h1>failure getting state</h1>";
    } else { 
        $params = booktool_github_url_decode_params( $state );
        print "<h4>Params are </h4>";
        var_dump( $params );
        $id = $params->id;
        $state = $params->state;
        $url = $params->url;
    }
} else {
      print "<h1> ID in get auth is " . $id . "</h1>";
}

$cm = get_coursemodule_from_id('book', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$book = $DB->get_record('book', array('id'=>$cm->instance), '*', MUST_EXIST);

//$PAGE->set_url('/mod/book/tool/github/index.php', array('id'=>$id));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/book:read', $context);
require_capability('mod/book:edit', $context);
require_capability('mod/book:viewhiddenchapters', $context);

// ???? This is using straight PHP session stuff
// Need to replace with proper use
//session_start();

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

//if (!isset($_GET['code'])) {

if ( $code == '' ) {

    $address=1530;
    $STATE= hash('sha256', microtime(TRUE).rand().$address);
    
    $param_arr = array( 'id' => $id, 'url' => $url, 'state' => $STATE );
    $param_str = booktool_github_url_encode_params( $param_arr );

    // Send user to github oauth login

    $EXTRAS = Array( 'state' => $param_str, 'scope' => "user" );
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, 
                                        REDIRECT_URI, $EXTRAS);
    header('Location: ' . $auth_url);
    die('Redirect');
} else {
//print "<h1>exchange token</h1>";
    // Need to exchange temp code with a proper one
    $params = array('code' => $_GET['code'], 
                    'redirect_uri' => REDIRECT_URI);
    $EXTRAS = Array( 'state' => $_GET['state'] );
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 
                                'authorization_code', $params, $EXTRAS);

    // check for failure
    if ( $response['code'] != 200 ) {
        print "<h3> Response was " . $response['code'] . "</h3>";
        die;
    }

    parse_str($response['result'], $info);

    if ( array_key_exists( 'access_token', $info ) ) {
 //       print "<h1>Got access token " . $info['access_token'] . "</h1>";
        $oauth_token = $info['access_token'];

        $_SESSION["github_token"] = $oauth_token;
    
 //       print "<H1> Okay got the tokane $oauth_token</h1>";
//        print "<p>Should now redirect back to URL</p> ";
        // redirect to URL
        $url = new moodle_url( $url, array( 'id' => $id ));
        redirect ($url );
    } else {
        print "<h1> FAILURE - no token </h1>";
        print_r( $info );
    }
}


