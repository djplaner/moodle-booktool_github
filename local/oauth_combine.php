<?php

/*
 * Combine PHP-OAuth2 and GitHubClient to get details of authenticated user
 */

require('PHP-OAuth2/Client.php');
require('PHP-OAuth2/GrantType/IGrantType.php');
require('PHP-OAuth2/GrantType/AuthorizationCode.php');

require_once( __DIR__ . '/client/client/GitHubClient.php' );


// Created on github by owner of Moodle instance
// - will have to be a configuration option  
const CLIENT_ID     = 'b8340758e05e8280f5ef';
const CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';

// Will need to be created based on BASE_URL of Moodle install but the
// final stages can be calculated based on Moodle location of tool
const REDIRECT_URI           = 'http://localhost:8080/moodle/mod/Book/tool/github/local/oauth_combine.php';

// Hard-coded into the tool
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';

// Will probably need to store this locally
$address=1530;
$STATE= hash('sha256', microtime(TRUE).rand().$address);

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

// ** pass user-agent as parameter?
//$client->setCurlOption( "CURLOPT_USERAGENT", "David's Moodle book github tool testing" );

if (!isset($_GET['code'])) {
    // Send user to github oauth login

print "<h1>STARTING STEP 1</h1>";
    $EXTRAS = Array( 'state' => $STATE, 'scope' => "user" );
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, 
                                                REDIRECT_URI, $EXTRAS);
    header('Location: ' . $auth_url);
    die('Redirect');
} else {
    // Need to exchange temp code with a proper one

print "<h1>STARTING STEP 2</h1>";
    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
    $EXTRAS = Array( 'state' => $_GET['state'] );
print "<strong>local params</strong> <pre>" . print_r( $params ) . "</pre>";
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', 
                                        $params, $EXTRAS);

    // check for failure
    if ( $response['code'] != 200 ) {
        print "<h3> Response was " . $response['code'] . "</h3>";
        die;
    }

print "<h1>STARTING STEP 3</h1>";
print_r( $response );
print "<h3>end response</h3>";
    parse_str($response['result'], $info);

    if ( array_key_exists( 'access_token', $info ) ) {
        print "<h1>Got access token " . $info['access_token'] . "</h1>";
     
        print "<h1> handing over to GitHubClient </h1>";

        $oauth_token = $info['access_token'];
        $client = new GitHubClient();
        $client->setAuthType( 'Oauth' );
        $client->setOauthToken( $oauth_token );
//        $client->setDebug( true );

        $response = $client->users->getTheAuthenticatedUser();
print "<h1>RESPONSE IS from GitHub API</h1>";
        var_dump( $response );

        print "<h3>Show user details</h3>";
        print "<ul> <li> Name" . $response->getName() . "</li>" .
                 "<li> Email" . $response->getEmail() . "</li></ul> "; 
    } else {
         print "<h1> FAILURE - no token </h1>";
         print_r( $info );
    }
}



