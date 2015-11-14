<?php

print "<h1> hello </h1>";

require('PHP-OAuth2/Client.php');
require('PHP-OAuth2/GrantType/IGrantType.php');
require('PHP-OAuth2/GrantType/AuthorizationCode.php');

const CLIENT_ID     = 'b8340758e05e8280f5ef';
const CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';

const REDIRECT_URI           = 'http://localhost:8080/moodle/mod/Book/tool/github/local/oauth_self.php';
//const AUTHORIZATION_ENDPOINT = 'https://graph.facebook.com/oauth/authorize';
const AUTHORIZATION_ENDPOINT = 'https://github.com/login/oauth/authorize';
const TOKEN_ENDPOINT         = 'https://github.com/login/oauth/access_token';
//const TOKEN_ENDPOINT         = 'https://graph.facebook.com/oauth/access_token';

$STATE= "HELLO DOLLY";


#$token = '?code=eb5f11d06fe2c6878753'

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);

//$client->setCurlOption( "CURLOPT_USERAGENT", "David's Moodle book github tool testing" );


if (!isset($_GET['code']))
{
    /*****
     * SEnd user to github oauth login
    ****/
print "<h1>STARTING STEP 1</h1>";
    $EXTRAS = Array( 'state' => $STATE, 'scope' => "user" );
    $auth_url = $client->getAuthenticationUrl(AUTHORIZATION_ENDPOINT, REDIRECT_URI, $EXTRAS);
    header('Location: ' . $auth_url);
    die('Redirect');
}
else
{
    /****
     * Need to exchange temp code with a proper one
     ***/

print "<h1>STARTING STEP 2</h1>";
    $params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
    $EXTRAS = Array( 'state' => $STATE );
print "<strong>local params</strong> <pre>" . print_r( $params ) . "</pre>";
    $response = $client->getAccessToken(TOKEN_ENDPOINT, 'authorization_code', $params, $EXTRAS);


    if ( $response['code'] != 200 ) {

print "<h3> Response was " . $response['code'] . "</h3>";

    parse_str($response['result'], $info);
//print "<h1>Got access token " . $info['access_token'] . "</h1>";
    $client->setAccessToken($info['access_token']);

print "<h3>Dump client and have a look</h3>";
print "<pre>" . var_dump( $client ) . "</pre>";

print "<h1>GOING TO DIE NOW didn't work </h1>";
        die;
    }

print "<h1>STARTING STEP 3</h1>";
print_r( $response );

    parse_str($response['result'], $info);

print "<h1>Got access token " . $info['access_token'] . "</h1>";

    $client->setAccessToken($info['access_token']);
    $response = $client->fetch('https://api.github.com/user/emails');
print "<xmp>";
    var_dump($response, $response['result']);
print "</xmp>";
}



