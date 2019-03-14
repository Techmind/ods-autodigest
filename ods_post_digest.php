<?php

$config_auth = include(__DIR__ . '/config/config_auth.php');
$config = include(__DIR__ . '/config/config.php');
$token = $config_auth['token_post'];

include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

$slack = new wrapi\slack\slack($token);

$db = Elasticsearch\ClientBuilder::create()->build();

// (https://ods-api-test.slack.com/), ods-test@quick-mail.online : ods-test_1

$days = 7;
$time = time() - 24 * 60 * 60 * $days;

$pos_reactions_gte = 5;
$negative_reaction_lte = 10;
$params = array (
  'index' => 'messages',
  'type' => 'message',
  'body' => 
  array (
    'size' => 1000,
    'query' => 
    array (
      'bool' => 
      array (
        'must' => 
        array (
          0 => 
          array (
            'range' => 
            array (
              'ts' => 
              array (
                'gte' => $time,
              ),
            ),
          ),
          1 => 
          array (
            'range' => 
            array (
              'positive_reaction_cnt' => 
              array (
                'gte' => $pos_reactions_gte,
              ),
            ),
          ),
          2 => 
          array (
            'range' => 
            array (
              'negative_reaction_cnt' => 
              array (
                'lte' => $negative_reaction_lte,
              ),
            ),
          ),
        ),
/*        'filter' => 
        array (
          'term' => 
          array (
            'channel_id' => 'C5VQ222UX',
          ),
        ),
*/
      ),
    ),

    'sort' => 
    array (
      0 => 
      array (
        'ts' => 'desc',
      ),
    ),
  ),
);

$resp = $db->search($params);

$rows = [];

foreach ($resp['hits']['hits'] as $hit)
{
    $row = $hit['_source'];
    $rows[] = $row;
}

// sort by channel_id
usort($rows, function ($a, $b)
{
	$channel_id_a = $a['channel_id'];
	$channel_id_b = $b['channel_id'];

	return strcmp($channel_id_a, $channel_id_b);
});

$text = 'now()>msg_date>' . date('Y-m-d H:i:s', $time) . "\n";

$max_real_length = 4150;
$max_text_chars = 3800;
$link_text_length = 74;
$needed_charts_to_fit = floor(($max_text_chars - (count($rows) * $link_text_length)) / count($rows));
$text_from_message = min(80, $needed_charts_to_fit);

$channels = $config['channels'];

$last_channel_id = '';

foreach ($rows as $row)
{
    $channel_id = $row['channel_id'];
    if ($channel_id != $last_channel_id)
	{
		$channel_name = $channels[$channel_id];
		$text .= "#". $channel_name . " :\n";
	}
    $body = json_decode($row['body'], true);
	$cnt = $row['positive_reaction_cnt'];
	$msg_text = mb_substr($body['text'], 0, $text_from_message, "UTF-8") . (mb_strlen($body['text']) > $text_from_message ? '...' : '');
    $text .= " " . $config['slack_url'] . "archives/$channel_id/p" . str_replace('.', '', $body['ts'])
		. '  ' . $msg_text . " ($cnt)\n";
    $last_channel_id = $channel_id;
}

$real_length = mb_strlen($text);
echo 'Msg len:' . $real_length;

if ($real_length >= $max_real_length)
{
	$text = mb_substr($text, 0, $max_real_length);
}

$result = $slack->chat->postMessage(array(
    "channel" => '#ods-api-test',//$config['digest_channel'],
    "text" => $text
  )
);