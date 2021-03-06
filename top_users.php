<?php
include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

$config = include (__DIR__ . '/config/config.php');

$type = isset($_GET['$type']) ? $_GET['$type'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
ob_start();
$options = [
	'to_pos' => 'Positive reactions to user`s messages',
	'to_neg' => 'Negative reactions to user`s messages',
	'from_pos' => 'Positive reactions to messages created',
	'from_neg' => 'Negative reactions to messages created'
];
$dates = range(time() - 3600*24*365, time(), 3600*24*30);
$dates = array_map(function ($time) { return date('Ym', $time);}, $dates);
$dates = array_reverse($dates);
$dates['total'] = 'total';
?>

<form action="./top_users.php">
	Channel:
	<select name="$type">
		<?php foreach ($options as $opt_type => $opt_name) :?>
			<option value="<?=$opt_type?>" <?=(($type==$opt_type)?'selected="selected"':'')?>
				><?=$opt_name?>
			</option>
		<?php endforeach; ?>
	</select>
	<select name="date">
		<?php foreach ($dates as $opt_value) :?>
			<option value="<?=$opt_value?>" <?=(($date==$opt_value)?'selected="selected"':'')?>
			><?=$opt_value?>
			</option>
		<?php endforeach; ?>
	</select>
	Limit: <input name='limit' value='<?=$limit?>'> <br />
	<br />
	Get top:
	<input type="submit" name="type" value="get">
</form>

<?php
$header = ob_get_clean();

if (isset($type))
{

	$db = Elasticsearch\ClientBuilder::create()->build();

	if ($date != 'total')
	{
		$type = $type . '_' . $date;
	}

    $params = [
            'index' => 'users',
            'type' => 'user',
            'body' => [
                    'size' => $limit,
                    'query' => [
                            'bool' => [
                                    'must' => [
                                    ],
                            ]
                    ],
		'sort' => [
                            [$type => 'desc']
                    ]
            ]
    ];

	$pos = 1;
        if ($pos)
        {
                $params['body']['query']['bool']['must'][] = ['range' => [$type => ['gte' => $pos]]];
        }

        $resp = $db->search($params);

	$users = $resp['hits']['hits'];

	$content =  include(__DIR__ . '/template/users.php');
} else {
	$content = '';
}

include(__DIR__ . '/template/template.php');
