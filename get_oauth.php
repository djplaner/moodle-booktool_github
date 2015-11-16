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

require_once(__DIR__ . 'local/PHP-OAuth2/Client.php');
require_once(__DIR__ . 'local/PHP-OAuth2/GrantType/IGrantType.php');
require_once(__DIR__ . 'local/PHP-OAuth2/GrantType/AuthorizationCode.php');

// **** NEED TO PUT THESE IN A DATABASE TABLE
const CLIENT_ID     = 'b8340758e05e8280f5ef';
const CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';
const REDIRECT_URI  = 'http://localhost:8080/moodle/mod/Book/tool/github/get_oauth.php';

// Hard-coded into the tool
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';
const GITHUB_TOKEN_NAME = "github_token";


global $SESSION;

$address=1530;
$STATE= hash('sha256', microtime(TRUE).rand().$address);

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

if (!isset($_GET['code'])) {
    // Send user to github oauth login

    $EXTRAS = Array( 'state' => $STATE, 'scope' => "user" );
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, 
                                        REDIRECT_URI, $EXTRAS);
    header('Location: ' . $auth_url);
    die('Redirect');
} else {
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
        print "<h1>Got access token " . $info['access_token'] . "</h1>";
        $oauth_token = $info['access_token'];

        $SESSION->{GITHUB_TOKEN_NAME} = $oauth_token;
    
        print "<H1> Okay got the tokane $oauth_token</h1>";
        print "<p>Should now redirect back to URL</p> ";
        // redirect to URL
    } else {
        print "<h1> FAILURE - no token </h1>";
        print_r( $info );
    }
}


