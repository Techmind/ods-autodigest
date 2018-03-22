<?php

$config = include (__DIR__ . '/config.php');
include('vendor/autoload.php');
include('lib/el_funs.php');
include('lib/api_funs.php');
include('lib/load_funs.php');

if (file_exists('.mine.cookie.php'))
{
	$file = include('.mine.cookie.php');
	list($cookie, $token, $uid) = $file;
} else {
	if ($argc < 3)
	{
		die ("usage: php ". basename(__FILE__) . ' $COOKIE $CSRF_TOKEN $UID');
	}

	$cookie = $argv[0];
	$token = $argv[1];
	$uid = $argv[2];
}

$channels = $config['channels'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

$client = Elasticsearch\ClientBuilder::create()->build();

$responseMessages = createMessagesIndex($client);

$responseUsers = createUsersIndex($client);

$time = microtime(true);

// [TODO] load channels + save channels stats (num users ?)

echo date('Y-m-d H:i:s') ." loading new messages\n";

foreach ($channels as $channel_id => $channel_name)
{
	// get latest message from elastic, so i don`t need to load older messages
	list($latest_msg, $last_found) = getLastIndexed($client, 'messages', 'message', ['channel_id' => $channel_id]);

	if ($last_found)
	{
		$decoded = json_decode($last_found['body'], true);
		$last_ts = floatval($decoded['ts']);
	} else {
		$last_ts = 0;
	}

	$all_messages = loadSlackMessages($channel_id, $token, $cookie, $last_ts, "$uid-$time");

	echo date('Y-m-d H:i:s') ." Loaded: name - $channel_name, cnt - " . count($all_messages) . "\n";

	$real = indexMessages($client, $all_messages, $channel_id, $positive_reactions, $negative_reactions, $last_ts);

	echo date('Y-m-d H:i:s') . " $real messages from '$channel_name' saved to index\n";
}

echo date('Y-m-d H:i:s') ." re-loading users\n";

$count = loadAndIndexSlackUsers($token, $uid, $cookie, $client);

echo date('Y-m-d H:i:s') ." loaded $count users \n";
