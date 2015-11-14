<?php

require_once( __DIR__ . '/client/client/GitHubClient.php' );


// Starting this from 
//  http://requests-oauthlib.readthedocs.org/en/latest/examples/github.html

print "<h1> Hello </h1>";

$owner = 'djplaner';
$repo = 'edc3100';
$path = 'A_2nd_new_file.html';
$username = 'djplaner';
$password = 'n3tmask3r';

$CLIENT_ID = 'b8340758e05e8280f5ef';
// ???? do I have a secret
// ANS: Yes - this is something that each site using the tool has to create
//      Including setting up a call back URL
$CLIENT_SECRET = '805f9a89dd7c19171fd4d86ae1bd8eec6ebef19a';
$STATE= hash('sha256', microtime(TRUE).rand().$address);

//$client = new GitHubClient();
//$client->setDebug( true );
// ?????????? Should this be the client Id and secret?

//$client->setCredentials( $username, $password );

//print "<h1> get sha </h1>";
//$sha = getSha( $client, $owner, $repo, $path );
	
//$client->setAuthType( $client::GITHUB_AUTH_TYPE_OAUTH_BASIC );
// - This only works for basic authentication (not oauth)
//$client->setCredentials( $clientId, $clientSecret );

//$OAuthAccess = $client->oauth->webApplicationFlow();

//print "<h1> get an authorisation  </h1>";

//$scopes = array( "public_repo" );
//$note = "Simple testing";

// create fingerprint
//$address = 1534;
//print "<h3> $fingerPrint </h3>";

//    'fingerprint' => unique eventually ;
// 1st try -- get Require authentication message - with scope == user
// 2nd try -- remove scope - same message - so missing something else
// using - URL https://api.github.com/authorizations
//    - this is because "basic" can be 
//$OauthAccess = $client->oauth->createNewAuthorization( 
     //$scopes, $note );
     //null, $note );
 //    $scopes, $note, null, $clientId, $clientSecret, $fingerPrint );

//---------------
// - This won't work without doing an authorization of some type
//$auth = $client->oauth->listYourAuthorizations();
//print "<h1> done $auth </h1>";
//---------------

// - createNewAuthorization( $scopes, $note, $note_url, $client_id, $client_secret )






//$sha = getSha( $client, $owner, $repo, $path );
	
//print "SHAR is <xmp>" . $sha . "\n</xmp>";


/*




--- authorize / client_id redirect_uri scope  state (string)
//$client->setOauthKey( ?? );

$response = $client->oauth->listYourAuthorizations();
/* $params = array(
    'client_id' => $clientId;
    'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'scope' => 'user',
    'state' => $_SESSION['state']
  ); */

/*
print_r( $response );




die;
$sha = getSha( $client, $owner, $repo, $path );
	
print "SHAR is " . $sha . "\n";

$statuses = $client->repos->statuses->listStatusesForSpecificRef( $owner, $repo, $sha );
	
echo "Num statuses is " . count( $statuses ) . "\n";
foreach ( $statuses as $status ) {
    echo get_class( $status ) . "\n";
    print_r( $status );
}

#print "content is " . base64_decode( $response->getContent() );

#print "name is " . $response->getName();

*/

function getSha( $client, $owner, $repo, $path) {

    $data = array();
    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'GET', $data, 200, 'GitHubReadmeContent'   );

    return $response->getSha();
}

#-- display the content of a file
function getContent() {
    $owner = 'djplaner';
    $repo = 'edc3100';
    $path = 'Who_are_you.html';

    $client = new GitHubClient();

    $data = array();
    $response = $client->request( "/repos/$owner/$repo/contents/$path", 'GET', $data, 200, 'GitHubReadmeContent'   );

    print "content is " . base64_decode( $response->getContent() );

    print "name is " . $response->getName();
}

# get a list of commits
function listCommits() {

    $owner = 'djplaner';
    $repo = 'edc3100';
    $path = 'A_2nd_new_file.html';

    $client = new GitHubClient();
    $client->setDebug( true );

    $data = array();
    $data['path'] = $path;

    $before = memory_get_usage();
    $commits = $client->repos->commits->listCommitsOnRepository( 
                                $owner, $repo, null, $path );
    $after = memory_get_usage();

print_r($commits);
    echo "Count: " . count($commits) . "\n";
    foreach($commits as $commit)
    {
        /* @var $commit GitHubCommit */
      echo get_class($commit) . " - Sha: " . $commit->getSha() . "\n";
      $theCommit = $commit->getAuthor();
      print_r( $theCommit);
    }

    echo "size is " . convert( $after - $before ) . "\n";
}

function convert($size)
{
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function createFile() {
	$owner = 'djplaner';
	$repo = 'edc3100';
	$path = 'A_2nd_new_file.html';
	$username = 'djplaner';
	$password = 'n3tmask3r';
	
	$content = "This will be the content in the second file. The 1st time";
	
	$client = new GitHubClient();
	$client->setDebug( true );
	$client->setCredentials( $username, $password );
	
	$data = array();
	$data['message'] = 'First time creating a file';
	$data['content'] = base64_encode( $content );
	
	
	$response = $client->request( "/repos/$owner/$repo/contents/$path", 'PUT', $data, 201, 'GitHubReadmeContent'   );
	
    print_r( $response );
}

function updateFile() {
	$content = "This will be the content in the second file. The 4th time";
	
	$client = new GitHubClient();
	#$client->setDebug( true );
	$client->setCredentials( $username, $password );
	
	$sha = getSha( $client, $owner, $repo, $path );
	
	print "shar is $sha\n\n";
	$data = array();
	$data['message'] = 'First time creating a file - Update 2';
	$data['content'] = base64_encode( $content );
	$data['sha'] = $sha;
	$data['committer'] = array( 'name' => 'David Jones', 
	                            'email' => 'davidthomjones@gmail.com' );
	
	$response = $client->request( "/repos/$owner/$repo/contents/$path", 'PUT', $data, 200, 'GitHubReadmeContent'   );
	
	print_r( $response );
}
