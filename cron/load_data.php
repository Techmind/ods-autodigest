<?php
include(__DIR__ . '/../vendor/autoload.php');
include(__DIR__ . '/../lib/incl.php');

$config = include(__DIR__ . '/../config/config.php');

if (file_exists(__DIR__ . '/../config/token')) {
	$token = trim(file_get_contents(__DIR__ . '/../config/token'));
} else {
	if ($argc < 3)
	{
		die ("usage: php ". basename(__FILE__) . ' $OLD_WEB_API_TOKEN');
	}

	$token = $argv[0];
}

$cookie = "";
$uid = "unused";

$channels = $config['channels'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

$client = Elasticsearch\ClientBuilder::create()->build();

$responseMessages = createMessagesIndex($client);

$responseUsers = createUsersIndex($client);

// loading users 1st so we can update them, during message load
$count = loadAndIndexSlackUsers($token, $uid, $cookie, $client);

echo date('Y-m-d H:i:s') ." loaded $count users \n";

$time = microtime(true);

// [TODO] load all channels to EL + save channels stats (num users ?)

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

	//var_dump($decoded);

	$all_messages = loadSlackMessages($channel_id, $token, $cookie, $last_ts, "$uid-$time");

	echo date('Y-m-d H:i:s') ." Loaded: name - $channel_name, cnt - " . count($all_messages) . "\n";

	$real = indexMessages($client, $all_messages, $channel_id, $positive_reactions, $negative_reactions, $last_ts);

	echo date('Y-m-d H:i:s') . " $real messages from '$channel_name' saved to index\n";
}

//die();

echo date('Y-m-d H:i:s') ." re-loading users\n";

