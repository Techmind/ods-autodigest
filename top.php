<?php
include('vendor/autoload.php');

$config = include (__DIR__ . '/config.php');
include (__DIR__ . '/shared.php');

$channel_id = isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
?>
<a href="./search.php">Search</a> / <a href="./top.php">Top</a> <br />

<form action="./top.php">
	Channel:
	<select name="channel_id">
		<?php foreach ($config['channels'] as $channel_id_opt => $cnannel_name) :?>
			<option value="<?=$channel_id_opt?>" <?=(($channel_id==$channel_id_opt)?'selected="selected"':'')?>
				><?=$cnannel_name?>
			</option>
		<?php endforeach; ?>
	</select>
	Limit: <input name='limit' value='<?=$limit?>'> <br />
	<br />
	Get top:
	<input type="submit" name="type" value="good">
	<input type="submit" name="type" value="bad">
	<input type="submit" name="type" value="-">
</form>

<?php
if (isset($channel_id))
{

	$db = Elasticsearch\ClientBuilder::create()->build();
	$sort = 'positive_reaction_cnt';
	if ($_GET['type'] == 'bad')
	{
		$sort = 'negative_reaction_cnt';
	}
	elseif ($_GET['type'] == '-')
	{
		$sort = 'total_reaction_cnt';
	}

	$params = [
		'index' => 'messages',
		'type' => 'message',
		'body' => [
			'size' => $limit,
			'query' => [
				'bool' => [
					'must' => [
						'term' => [ 'channel_id' => $channel_id ]
					],
				]
			],
			'sort' => [
				[$sort => 'desc']
			]
		]
	];

	try
	{
		$resp = $db->search($params);

		$rows = [];

		foreach ($resp['hits']['hits'] as $hit)
		{
			$rows[] = $hit['_source'];
		}
	} catch (\Exception $e)
	{
		var_dump($e);die;
	}

	$users = get_users($rows, $db);

	include(__DIR__ . '/render_messages.php');
}
