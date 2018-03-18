<?php

$config = include (__DIR__ . '/config.php');

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



$time = microtime(true);

$url = "/api/conversations.history?_x_id=$uid-$time";

foreach ($channels as $channel_id => $channel_name)
{

	$latest = null;

	$all_messages = [];

	do
	{

		$ch = curl_init();

		$post = [
			"channel" => $channel_id,
			"limit" => 1000,
			"ignore_replies" => "true",
			"include_pin_count" => "true",
			"inclusive" => "true",
			"no_user_profile" => "true",
			"_x_reason" => "message-pane/requestHistory",
			"token" => $token,
			"visible" => 1
		];

		if ($latest)
		{
			$post['latest'] = $latest;
		}

		$header = <<<EOD
:authority:opendatascience.slack.com
:method:POST
:path:$url
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

		$all_messages = array_merge($all_messages, $response['messages']);

		$latest = $all_messages[count($all_messages) - 1]['ts'];
	} while (count($response['messages']) > 1);

	echo "Loaded: name - $channel_name, cnt - " . count($all_messages) . "\n";
	file_put_contents(__DIR__ . '/dumps/messages-'.$channel_id.'.js', json_encode($all_messages));
}
