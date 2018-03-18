<?php
include('vendor/autoload.php');

$config = include (__DIR__ . '/config.php');

$channel_id = isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
?>
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
	<input type="submit" value="Get top">
</form>

<?php
if (isset($channel_id))
{

	$db = Elasticsearch\ClientBuilder::create()->build();
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
				['positive_reaction_cnt' => 'desc']
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


	include(__DIR__ . '/template.php');
}
