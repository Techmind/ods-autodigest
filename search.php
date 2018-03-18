<?php
$config = include (__DIR__ . '/config.php');

$ts = isset($_GET['ts']) ? $_GET['ts'] : (time() - 3600 * 24 * 7);
$channel_id = isset($_GET['channel_id']) ? $_GET['channel_id'] : null;
$pos = isset($_GET['pos']) ? $_GET['pos'] : 5;
$neg = isset($_GET['neg']) ? $_GET['neg'] : 10;
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
	Timestamp > :<input name="ts" value="<?=$ts?>"> <br />
	Positive reactions > (0 - ignore):<input name="pos" value="<?=$pos?>"> <br />
	Negative reactions < (0 - ignore):<input name="neg" value="<?=$neg?>"> <br />

	<input type="submit" value="Search">
</form>

<?php
if (isset($channel_id))
{
	$db = new SQLite3('dumps/db.sqlite');
	$sql = "SELECT * FROM messages WHERE channel_id = '" . $channel_id . "'";
	if ($ts)
	{
		$sql .= " AND ts > $ts";
	}
	if ($pos)
	{
		$sql .= " AND positive_reaction_cnt > $pos";
	}
	if ($neg)
	{
		$sql .= " AND negative_reaction_cnt < $neg";
	}
	$sql .= " ORDER BY ts DESC, ts_float DESC";

	$res = $db->query($sql);

	$rows = [];

	while($row = $res->fetchArray(SQLITE3_ASSOC)){
		$rows[] = $row;
	}

	foreach ($rows as $row)
	{
		$body = json_decode($row['body'], true);
		$reactions_html = '';
		if (isset($body['reactions']))
		{
			foreach ($body['reactions'] as $react)
			{
				$name = $react['name'];
				$cnt = $react['count'];
				$reactions_html .= " :$name: ($cnt)";
			}
		}

//		var_dump($body);

		$message_url = "https://opendatascience.slack.com/archives/$channel_id/p" . str_replace('.', '', $body['ts']);

		$text = $body['text'];

		if (empty($text))
		{
			if (!empty($body['attachments'][0]))
			{
				$attach = $body['attachments'][0];
				if (!empty($attach['text']))
				{
					$text = $attach['text'];
				} elseif (!empty($attach['title_link']))
				{
					$text = $attach['title_link'];
				}
			}
		}
		$text = preg_replace('~<([^>]*)>~', "<a href='$1'>$1</a>", $text);

		$debug = '';
//		$debug = '<pre>' . $row['body'] . '</pre>';
		$date = date('Y-m-d H:i:s', $body['ts']);

		echo "<p><a href='$message_url'>GOTO</a> $date : <span style='color: green'>".$row['positive_reaction_cnt']."</span>/<span style='color: red'>".$row['negative_reaction_cnt']."</span> ".$text." <br /> $reactions_html $debug</p>";
	}
}
