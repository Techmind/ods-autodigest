<?php
include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

$config = include (__DIR__ . '/config//config.php');

$type = isset($_GET['$type']) ? $_GET['$type'] : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
ob_start();
$options = [
	'messages' => 'messages',
	'messages_per_day' => 'messages_per_day',
	'reactions' => 'reactions',
	'reactions_per_day' => 'reactions_per_day',
	'max_reactions_for_post' => 'max_reactions_for_post',
	'channels_joined' => 'channels_joined',
	'channels_contributed' => 'channels_contributed',
];
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
	Limit: <input name='limit' value='<?=$limit?>'> <br />
	<br />
	Get top:
	<input type="submit" name="type" value="positive">
	<input type="submit" name="type" value="negative">
	<input type="submit" name="type" value="total">
</form>

<?php
$header = ob_get_clean();

if (isset($type))
{

	$db = Elasticsearch\ClientBuilder::create()->build();
	// [TODO]
} else {
	$content = '';
}

include(__DIR__ . '/template/template.php');
