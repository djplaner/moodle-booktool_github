<?php

require_once( __DIR__ . '/client/client/GitHubClient.php' );


$owner = 'djplaner';
$repo = 'edc3100';
$path = 'A_2nd_new_file.html';
$username = 'djplaner';
$password = 'n3tmask3r';

$clientId = 'b8340758e05e8280f5ef';

$client = new GitHubClient();
$client->setDebug( true );
#$client->setCredentials( $username, $password );
	
$client->setAuthType( $client::GITHUB_AUTH_TYPE_OAUTH_BASIC );

// 1. create the session and make the query

$address = 1534;

$session = hash('sha256', microtime(TRUE).rand().$address);

echo $session . "\n";


--- authorize / client_id redirect_uri scope  state (string)
//$client->setOauthKey( ?? );

$response = $client->oauth->listYourAuthorizations();
/* $params = array(
    'client_id' => $clientId;
    'redirect_uri' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
    'scope' => 'user',
    'state' => $_SESSION['state']
  ); */

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
