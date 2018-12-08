<?php

/**
 * @param Elasticsearch\Client $client
 * @return array
 */
function createMessagesIndex($client)
{
	try {
		if ($client->indices()->exists(['index' => 'messages']))
		{
			return true;
		}
	} catch (Exception $e)
	{
		//var_dump('CATCH');die;
		//var_dump($e);
	}

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
	}
	catch (\Exception $e)
	{
		// ignore re-creation error
	}

	return $response;
}

/**
 * @param $all_messages
 * @param $channel_id
 * @param $positive_reactions
 * @param $negative_reactions
 * @param $client
 */
function indexMessages($client, $all_messages, $channel_id, $positive_reactions, $negative_reactions, $last_ts)
{
	$count = 0;
	$users_update = [];

	foreach ($all_messages as $message)
	{
		if (isset($message['subtype']) &&
			($message['subtype'] == 'file_comment' || $message['subtype'] == 'bot_message' || $message['subtype'] == 'tombstone'))
		{
			continue;
		}

		// ignore join message
		if (!empty($message['text']) && strpos($message['text'], 'has joined the channel') !== false)
		{
			continue;
		}

		if (!isset($message['user']))
		{
			// [TODO] throw exception
			var_dump($message);
			die;
		}
		$user_id = $message['user'];

		$message_ts = $message['ts'];

		if (floatval($message_ts) <= $last_ts)
		{
			break;
		}

		list($ts, $ts_float) = explode('.', $message_ts);
		$ts_float = floatval("0.$ts_float") * 1000000;

		$id = $channel_id . '-' . $message_ts . "-" . $message['user'] .

			$total = $neg = $pos = 0;

		if (isset($message['reactions']))
		{
			foreach ($message['reactions'] as $reaction)
			{
				
				$is_pos = in_array($reaction['name'], $positive_reactions);
				$is_neg = in_array($reaction['name'], $negative_reactions);
				if ($is_pos)
				{
					$pos += intval($reaction['count']);
				}
				else if ($is_neg)
				{
					$neg += intval($reaction['count']);
				}

				$total += intval($reaction['count']);
				
				
				foreach ($reaction['users'] as $from_uid)
				{
					if (!isset($users_update[$from_uid]['from_pos']))
					{
						$users_update[$from_uid]['from_pos'] = 0;
						$users_update[$from_uid]['from_neg'] = 0;
					}
					$users_update[$from_uid]['from_pos'] += $is_pos ? 1 : 0;
					$users_update[$from_uid]['from_neg'] += $is_neg ? 1 : 0;
				}

				
				if (!isset($users_update[$user_id]['to_pos']))
				{
					$users_update[$user_id]['to_pos'] = 0;
					$users_update[$user_id]['to_neg'] = 0;
				}
				$users_update[$user_id]['to_pos'] += $is_pos ? 1 : 0;
				$users_update[$user_id]['to_neg'] += $is_neg ? 1 : 0;
			}
		}

		$params = [
			'index' => 'messages',
			'type' => 'message',
			'id' => $id,
			'body' => [
				'body' => json_encode($message),
				'ts' => intval($ts),
				'positive_reaction_cnt' => $pos,
				'negative_reaction_cnt' => $neg,
				'total_reaction_cnt' => $total,
				'channel_id' => $channel_id
			]
		];


		$resp = $client->index($params);

		$count++;

		// [TODO] do not ignore errors
	}
	$ids = array_keys($users_update);

	if ($ids)
	{

		$users = getUsersByIds($ids, $client);
		$bulk = [];

		foreach ($users_update as $uid => $update_row)
		{
			 $bulk['body'][] = [
                                'index' => [
                                        '_index' => 'users',
                                        '_type' => 'user',
                                        '_id' => $uid
	                       ]
        	         ];

                	$row = $users[$uid];
		
			foreach ($update_row as $k => $v)
			{
				if (!isset($row[$k]))
				{
					$row[$k] = 0;
				}
				$row[$k] += $v;
			}

			$bulk['body'][] = $row;
		}

		$resp = $client->bulk($bulk);
	}

	return $count;
}

/**
 * @param $client
 * @param $channel_id
 * @return array
 */
function getLastIndexed($client, $index, $type, $condition, $ts_field = 'ts')
{
	$latest_msg = $client->search([
		'index' => $index,
		'type' => $type,
		'body' => [
			'size' => 1,
			'query' => [
				'bool' => [
					'must' => [
						'term' => $condition
					],
				]
			],
			'sort' => [
				[$ts_field => 'desc']
			]
		]
	]);
	if (!empty($latest_msg['hits']['hits'][0]))
	{
		$last_found = $latest_msg['hits']['hits'][0]['_source'];
	} else {
		$last_found = null;
	}

	return array($latest_msg, $last_found);
}

/**
 * @param $client
 * @return array
 */
function createUsersIndex($client)
{
	try 
	{
		if ($client->indices()->exists(['index' => 'users']))
		{	
			return true;
		}
	} catch (\Exception $e) {
		var_dump($e);
	}

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
						],
						'from_pos' => ['type' => 'integer'],
						'to_pos' => ['type' => 'integer'],
						'from_neg' => ['type' => 'integer'],
						'to_neg' => ['type' => 'integer']
					]
				],
			]
		]
	];


	try
	{
		$response = $client->indices()->create($params);
	}
	catch (\Exception $e)
	{
		// ignore re-creation error
	}
	return $response;
}


function getUsers($rows, $db)
{

	if (empty($rows))
	{
		return [];
	}
	$user_ids = [];

	foreach ($rows as $row)
	{
		$body = json_decode($row['body'], true);

		if (isset($body['text']))
		{
			preg_match_all('~@U[0-9A-Z]+~', $body['text'], $matches);
			foreach ($matches[0] as $match)
			{
				$uid = substr($match, 1);

				$user_ids[$uid] = true;
			}
		}

		$user_ids[$body['user']] = true;
	}

	return getUsersByIds(array_keys($user_ids), $db);
}


/**
 * @param $rows
 * @param $db
 */
function getUsersByIds($ids, $db)
{
	$users = [];
	// get users
	$mget_params = [
		'index' => 'users',
		'type' => 'user',
		'body' => [
			'ids' => $ids
		]
	];
	$mget_users = $db->mget($mget_params);

	foreach ($mget_users['docs'] as $user)
	{
		if ($user['found'])
		{
			$users[$user['_id']] = $user['_source'];
		}
	}

	return $users;
}
