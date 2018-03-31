<?php

include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

$config = include (__DIR__ . '/config.php');

$ts = isset($_GET['ts']) ? $_GET['ts'] : (time() - 3600 * 24 * 7 * 3);
$channel_id = isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$pos = isset($_GET['pos']) ? $_GET['pos'] : 5;
$neg = isset($_GET['neg']) ? $_GET['neg'] : 10;

$date_formatted = date('Y-m-d H:i:s', $ts);
ob_start();
?>
	<form action="./search.php">
		Channel:
		<select name="channel_id">
			<?php foreach ($config['channels'] as $channel_id_opt => $cnannel_name) :?>
				<option value="<?=$channel_id_opt?>" <?=(($channel_id==$channel_id_opt)?'selected="selected"':'')?>
				><?=$cnannel_name?>
				</option>
			<?php endforeach; ?>
		</select>
		<br />
		Timestamp > :<input name="ts" value="<?=$ts?>"> (<?=$date_formatted?>) <br />
		Positive reactions > (0 - ignore):<input name="pos" value="<?=$pos?>"> <br />
		Negative reactions < (0 - ignore):<input name="neg" value="<?=$neg?>"> <br />

		<input type="submit" value="Search">
	</form>

<?php
$header = ob_get_clean();

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

	try
	{
		$resp = $db->search($params);

		$rows = [];

		foreach ($resp['hits']['hits'] as $hit)
		{
			$row = $hit['_source'];
			$rows[] = $row;
		}
	} catch (\Exception $e)
	{
		var_dump($e);die;
	}

	$users = getUsers($rows, $db);

	$content = include(__DIR__ . '/template/messages.php');
} else {
	$content = '';
}

include(__DIR__ . '/template/template.php');
