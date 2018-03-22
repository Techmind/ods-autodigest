<?php

/**
 * @param $url
 * @param $cookie
 * @param $data
 * @return mixed
 */
function makeApiReq($url, $cookie, $data)
{
	$ch = curl_init();

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
		CURLOPT_POSTFIELDS => $data
	]);

// $output contains the output string
	$output = curl_exec($ch);

// close curl resource to free up system resources
	curl_close($ch);
	return $output;
}