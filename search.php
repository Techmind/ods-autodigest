<?php

include('vendor/autoload.php');

$config = include (__DIR__ . '/config.php');

$ts = isset($_GET['ts']) ? $_GET['ts'] : (time() - 3600 * 24 * 7);
$channel_id = isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$pos = isset($_GET['pos']) ? $_GET['pos'] : 5;
$neg = isset($_GET['neg']) ? $_GET['neg'] : 10;
?>
	<form action="./search-el.php">
		Channel:
		<select name="channel_id">
			<?php foreach ($config['channels'] as $channel_id_opt => $cnannel_name) :?>
				<option value="<?=$channel_id_opt?>" <?=(($channel_id==$channel_id_opt)?'selected="selected"':'')?>
				><?=$cnannel_name?>
				</option>
			<?php endforeach; ?>
		</select>
		<br />
		Timestamp > :<input name="ts" value="<?=$ts?>"> <br />
		Positive reactions > (0 - ignore):<input name="pos" value="<?=$pos?>"> <br />
		Negative reactions < (0 - ignore):<input name="neg" value="<?=$neg?>"> <br />

		<input type="submit" value="Search">
	</form>

<?php
if (isset($channel_id))
{
	$db = Elasticsearch\ClientBuilder::create()->build();
	$params = [
		'index' => 'messages',
		'type' => 'message',
		'body' => [
			'size' => 1000,
			'query' => [
				'bool' => [
					'must' => [
						[
							'range' => [
								"ts" => ['gte' => $ts]
							]
						]
					],
					'filter' => [
						'term' => [ 'channel_id' => $channel_id ]
					],
				]
			],
			'sort' => [
				['ts' => 'desc']
			]
		]
	];
	$sql = "SELECT * FROM messages WHERE channel_id = '" . $channel_id . "'";
	if ($pos)
	{
		$params['body']['query']['bool']['must'][] = ['range' => ['positive_reaction_cnt' => ['gte' => $pos]]];
	}
	if ($neg)
	{
		$params['body']['query']['bool']['must'][] = ['range' => ['negative_reaction_cnt' => ['lte' => $neg]]];
	}

	$user_ids = [];
	try
	{
		$resp = $db->search($params);

		$rows = [];

		foreach ($resp['hits']['hits'] as $hit)
		{
			$row = $hit['_source'];
			$body = json_decode($row['body'], true);
			$user_ids[$body['user']] = true;
			$rows[] = $row;
		}
	} catch (\Exception $e)
	{
		var_dump($e);die;
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

	include(__DIR__ . '/template.php');
}
