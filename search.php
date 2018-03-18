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

	include(__DIR__ . '/template.php');
}
