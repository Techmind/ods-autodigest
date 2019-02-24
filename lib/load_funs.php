<?php


/**
 * @param $channel_id
 * @param $last_ts
 * @return array
 */
function loadSlackMessagesApi($channel_id, $api, $last_ts)
{
	$all_messages = [];

	$latest = null;

	do
	{

		$post = [
			"channel" => $channel_id,
			"count" => 1000,
			"ignore_replies" => "true",
			"include_pin_count" => "true",
			"inclusive" => "true",
			"oldest" => $last_ts,
		];

		if ($latest)
		{
			$post['latest'] = $latest;
		}

		$response = $api->channels->history($post);

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

	$cookie = "";

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

		list($output, $err) = makeApiReq($url, $cookie, $post);

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

		$success = false;
		while (!$success)
		{

			list($output, $err) = makeApiReq($url, $cookie, $post);

			$response = json_decode($output, true);

			$success = !empty($response['members']);

			if (!$success)
			{
				echo "User load failed... retrying in 5 sec!\n";
				sleep(5);
			}
		}

		$bulk = ['body' => []];

		foreach ($response['members'] as $member)
		{
			$bulk['body'][] = [
				'index' => [
					'_index' => 'users',
					'_type' => 'user',
					'_id' => $member['id']
				]
			];

			$bulk['body'][] = [
				'name' => $member['name'],
                                'real_name' => $member['profile']['real_name'],
                                'real_name_normalized' => $member['profile']['real_name_normalized'],
                                'image_72' => $member['profile']['image_72'],
				'to_pos' => 0,
				'from_pos' => 0,
				'to_neg' => 0,
				'from_neg' => 0
			];
/*
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
*/
//			$resp = $client->index($params);

			$count++;
		}

		$resp = $client->bulk($bulk);

		//var_dump($resp['items'][0]);
		// making requests too fast will block us on slack api
		//sleep(2);

		$cursor = $response['response_metadata']['next_cursor'];

		echo /*$output . */" err - " . $err;

		 echo date('Y-m-d H:i:s') ." Loading users $count\n";

	} while (count($response['members']) > 1 && $cursor);

	return $count;
}
