<?php

include('vendor/autoload.php');

$config = include ( __DIR__ . '/config.php');

$channels = $config['channels'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

$client = Elasticsearch\ClientBuilder::create()->build();

$params = [
	'index' => 'messages',
	'body' => [
		'settings' => [
			'number_of_shards' => 1,
			'number_of_replicas' => 0
		],
		'mappings' => [
			'message' => [
				'_source' => [
					'enabled' => true
				],
				'properties' => [
					'body' => [
						'type' => 'text',
						'analyzer' => 'russian',
						'term_vector' => 'yes',
						'copy_to' => 'combined'
					],
					'ts' => [
						'type' => 'long',
					],
					'channel_id' => [
						'type' => 'keyword',
					],
					'positive_reaction_cnt' => [
						'type' => 'integer',
					],
					'negative_reaction_cnt' => [
						'type' => 'integer',
					],
					'total_reaction_cnt' => [
						'type' => 'integer',
					],
				]
			],
		]
	]
];


try
{
	$response = $client->indices()->create($params);
} catch (\Exception $e)
{
	// ignore re-creation error
}

$files = glob("dumps/*.js");

foreach ($files as $file)
{
	list($_, $channel_id_js) = explode('-', $file);
	list($channel_id) = explode('.', $channel_id_js);

	$channel_name = $channels[$channel_id];

	echo "Indexing: $channel_name\n";

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
		list($ts, $ts_float) = explode('.', $message['ts']);
		$ts_float = floatval("0.$ts_float") * 1000000;

		$id = $channel_id . '-' . $message['ts'] . "-" . $message['user'].

		$total = $neg = $pos = 0;

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

				$total += intval($reaction['count']);
			}
		}

		$params = [
			'index' => 'messages',
			'type' => 'message',
			'id' => $id,
			'body' => [
				'body' => $body,
				'ts' => intval($ts),
				'positive_reaction_cnt' => $pos,
				'negative_reaction_cnt' => $neg,
				'total_reaction_cnt' => $total,
				'channel_id' => $channel_id
			]
		];


		$resp = $client->index($params);
	}
}
