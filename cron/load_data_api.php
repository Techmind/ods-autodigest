<?php
$config = include(__DIR__ . '/../config/config.php');

$config_auth = include(__DIR__ . '/../config/config_auth.php');

$token = $config_auth['token_read'];

include(__DIR__ . '/../vendor/autoload.php');
include(__DIR__ . '/../lib/incl.php');

$slack = new wrapi\slack\slack($token);

$channels = $config['channels'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

$client = Elasticsearch\ClientBuilder::create()->build();

$responseMessages = createMessagesIndex($client);

$responseUsers = createUsersIndex($client);

//echo date('Y-m-d H:i:s') ." re-loading users\n";
// loading users 1st so we can update them, during message load
//$count = loadAndIndexSlackUsers($token, $uid, $cookie, $client);
//
//echo date('Y-m-d H:i:s') ." loaded $count users \n";

$time = microtime(true);

// [TODO] load all channels to EL + save channels stats (num users ?)

echo date('Y-m-d H:i:s') ." loading new messages\n";

//$channels = ['CGF76S5M2' => 'ods-api-test'];

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

	echo date('Y-m-d H:i:s') ." Loading #$channel_name\n";

	$all_messages = loadSlackMessagesApi($channel_id, $slack, $last_ts);

	echo date('Y-m-d H:i:s') ." Loaded: name - $channel_name, cnt - " . count($all_messages) . "\n";

	list($count, $missing_users) = indexMessages($client, $all_messages, $channel_id, $positive_reactions, $negative_reactions, $last_ts);

	echo date('Y-m-d H:i:s') . " $count messages from '$channel_name' saved to index\n";

	// calculate average stats and print for channels

	$resp = $client->search([
		'index' => 'messages',
		'type' => 'message',
		'size' => 0,
		'body' => '{
			"query" : {
				"constant_score" : {
					"filter" : {
						"match" : { "channel_id" : "'.$channel_id.'" }
					}
				}
			},
			"aggs" : {
				"positive_reaction_cnt_sum" : {
					"sum" : {
						"script" : {
						   "source": "doc.positive_reaction_cnt.value"
						}
					}
				}
			}}']);

	$sum = $resp['aggregations']['positive_reaction_cnt_sum']['value'];
	$hits = $resp['hits']['total'];

	$avg = ceil($sum / $hits);
	echo "Avg pos reactions: $avg hits: $hits\n";
}