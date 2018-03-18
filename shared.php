<?php

/**
 * @param $rows
 * @param $db
 */
function get_users($rows, $db)
{
	$user_ids = [];

	foreach ($rows as $row)
	{
		$body = json_decode($row['body'], true);
		$user_ids[$body['user']] = true;
	}

	$users = [];
	// get users
	$mget_params = [
		'index' => 'users',
		'type' => 'user',
		'body' => [
			'ids' => array_keys($user_ids)
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