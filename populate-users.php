<?php

include('vendor/autoload.php');

$config = include ( __DIR__ . '/config.php');

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

$client = Elasticsearch\ClientBuilder::create()->build();

$params = [
	'index' => 'users',
	'body' => [
		'settings' => [
			'number_of_shards' => 1,
			'number_of_replicas' => 0
		],
		'mappings' => [
			'user' => [
				'_source' => [
					'enabled' => true
				],
				'properties' => [
					'name' => [
						'type' => 'keyword',
					],
					'real_name' => [
						'type' => 'text',
					],
					'real_name_normalized' => [
						'type' => 'text',
					],
					'image_72' => [
						'type' => 'text',
						"index" => false
					]
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

$cursor = null;

$all_messages = [];

$url = "/api/users.list";

$count = 0;

do
{

	$ch = curl_init();

	$post = [
		"token" => $token,
		'limit' => 100
	];

	if ($cursor)
	{
		$post['cursor'] = $cursor;
	}
	$time = microtime(true);

	$header = <<<EOD
:authority:opendatascience.slack.com
:method:POST
:path:$url?_x_id=$uid-$time
:scheme:https
accept:*/*
accept-encoding:gzip, deflate, br
accept-language:ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7
cookie:$cookie
origin:https://opendatascience.slack.com
user-agent:Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/64.0.3282.119 Chrome/64.0.3282.119 Safari/537.36
x-slack-version-ts:1521343560
EOD;

	curl_setopt_array($ch, [
		CURLOPT_URL => "https://opendatascience.slack.com$url",
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HEADER => $header,
		CURLOPT_POSTFIELDS => $post
	]);

// $output contains the output string
	$output = curl_exec($ch);

// close curl resource to free up system resources
	curl_close($ch);

	$response = json_decode($output, true);

	foreach ($response['members'] as $member)
	{
		$params = [
			'index' => 'users',
			'type' => 'user',
			'id' => $member['id'],
			'body' => [
				'name' => $member['name'],
				'real_name' => $member['profile']['real_name'],
				'real_name_normalized' => $member['profile']['real_name_normalized'],
				'image_72' => $member['profile']['image_72'],
			]
		];

		$resp = $client->index($params);

		$count++;
	}

	$cursor = $response['response_metadata']['next_cursor'];

	var_dump([$count, $cursor]);
} while (count($response['members']) > 1 && $cursor);
