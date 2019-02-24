<?php

$config_auth = include(__DIR__ . '/config/config_auth.php');
$config = include(__DIR__ . '/config/config.php');
$token = $config_auth['token_post'];

include(__DIR__ . '/vendor/autoload.php');
include(__DIR__ . '/lib/incl.php');

$slack = new wrapi\slack\slack($token);

$db = Elasticsearch\ClientBuilder::create()->build();

$time = time() - 24 * 60 * 60 * 7;

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
                'gte' => '5',
              ),
            ),
          ),
          2 => 
          array (
            'range' => 
            array (
              'negative_reaction_cnt' => 
              array (
                'lte' => '10',
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

$text =  "";

foreach ($rows as $row)
{
    $channel_id = $row['channel_id'];
    $body = json_decode($row['body'], true);
    $text .= " " . $config['slack_url'] . "/archives/$channel_id/p" . str_replace('.', '', $body['ts']) . "\n";
}

$result = $slack->chat->postMessage(array(
    "channel" => $config['digest_channel'],
    "text" => $text
  )
);
