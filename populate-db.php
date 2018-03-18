<?php

$config = include ( __DIR__ . '/config.php');

$channels = $config['channels'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

$db = new SQLite3('dumps/db.sqlite');

$res1 = $db->query("CREATE TABLE IF NOT EXISTS messages
(id string PRIMARY KEY NOT NULL, 
channel_id CHAR(8) NOT NULL,
positive_reaction_cnt INT NOT NULL,
negative_reaction_cnt INT NOT NULL,
body TEXT,
ts INTEGER,
ts_float INTEGER)");

$res2 = $db->query("CREATE INDEX search_ts ON messages ('channel_id', 'ts')");

$files = glob("dumps/*.js");

foreach ($files as $file)
{
	list($_, $channel_id_js) = explode('-', $file);
	list($channel_id) = explode('.', $channel_id_js);

	$channel_name = $channels[$channel_id];

	$messages = json_decode(file_get_contents($file), true);

	foreach ($messages as $message)
	{
		$body = json_encode($message);

		if (isset($message['subtype']) &&
			($message['subtype'] == 'file_comment' || $message['subtype'] == 'bot_message' || $message['subtype'] == 'tombstone'))
		{
			continue;
		}

		if (!isset($message['user']))
		{
			var_dump($message);die;
		}
		$id = $channel_id . "-" . $message['user'] . "-" . $message['ts'];
		list($ts, $ts_float) = explode('.', $message['ts']);
		$ts_float = floatval("0.$ts_float") * 1000000;

		$neg = $pos = 0;

		if (isset($message['reactions']))
		{
			foreach ($message['reactions'] as $reaction)
			{
				if (in_array($reaction['name'], $positive_reactions))
				{
					$pos += intval($reaction['count']);
				}
				else if (in_array($reaction['name'], $negative_reactions))
				{
					$neg += intval($reaction['count']);
				}
			}
		}

		$res3 = $db->query("
			INSERT INTO messages (id, channel_id, positive_reaction_cnt, negative_reaction_cnt, body, ts, ts_float)
			VALUES 
			('$id', '$channel_id', '$pos', '$neg', '".SQLite3::escapeString($body)."', '$ts', '$ts_float')");
	}
}
