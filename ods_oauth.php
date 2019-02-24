<?php

include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

session_start();

// https://slack.com/oauth/authorize?client_id=559166861203.558719063889&scope=chat:write:bot,incoming-webhook,channels:history,links:write
 
$provider = new \AdamPaterson\OAuth2\Client\Provider\Slack([
    'clientId'          => '559166861203.558719063889',
    'clientSecret'      => 'regenerated',
    'redirectUri'       => 'http://37.147.114.9/ods_oauth.php',
]);
 
if (!isset($_GET['code'])) {
 
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => 'chat:write:bot incoming-webhook channels:history links:write users:read'
    ]);
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;
  
// Check given state against previously stored one to mitigate CSRF attack
} elseif ((empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) && false) {
 
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
 
} else {
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
 
    // Optional: Now you have a token you can look up a users profile data
    try {
 
        // We got an access token, let's now get the user's details
        $team = $provider->getResourceOwner($token);
 
	var_dump($team);
        // Use these details to create a new profile
        printf('Hello %s!', $team->getName());
 
    } catch (Exception $e) {
 
        // Failed to get user details
        exit('Oh dear...');
    }
 
    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
