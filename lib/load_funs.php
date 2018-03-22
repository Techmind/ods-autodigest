<?php

/**
 * @param $channel_id
 * @param $token
 * @param $url
 * @param $cookie
 * @param $last_ts
 * @return array
 */
function loadSlackMessages($channel_id, $token, $cookie, $last_ts, $x_id)
{
	$url = "/api/conversations.history?_x_id=$x_id";

	$all_messages = [];

	$latest = null;

	do
	{

		$post = [
			"channel" => $channel_id,
			"limit" => 200,
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

		$output = makeApiReq($url, $cookie, $post);

		$response = json_decode($output, true);

		$all_messages = array_merge($all_messages, $response['messages']);

		$latest = $all_messages[count($all_messages) - 1]['ts'];

		if ($latest < $last_ts)
		{
			break;
		}

	} while (count($response['messages']) > 1);
	return $all_messages;
}

/**
 * @param $token
 * @param $uid
 * @param $cookie
 * @param $client
 */
function loadAndIndexSlackUsers($token, $uid, $cookie, $client)
{
	$cursor = null;

	$count = 0;

	do
	{
		$post = [
			"token" => $token,
			'limit' => 100
		];

		if ($cursor)
		{
			$post['cursor'] = $cursor;
		}
		$time = microtime(true);

		$url = "/api/users.list?_x_id=$uid-$time";

		$output = makeApiReq($url, $cookie, $post);

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

	} while (count($response['members']) > 1 && $cursor);

	return $count;
}