<?php
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
	$db = new SQLite3('dumps/db.sqlite');
	$sql = "SELECT * FROM messages WHERE channel_id = '" . $channel_id . "'";
	$sql .= " ORDER BY positive_reaction_cnt DESC, ts DESC, ts_float DESC LIMIT $limit";

	$res = $db->query($sql);

	$rows = [];

	while($row = $res->fetchArray(SQLITE3_ASSOC)){
		$rows[] = $row;
	}

	include(__DIR__ . '/template.php');
}
