<?php
$emojis = $config['emojis'];
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
			$name_html = isset($emojis[$name]) ? "<img title=':$name:' height='24px' src='".$emojis[$name]."'>" : ":$name:";
			$reactions_html .= " $name_html ($cnt)";
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

	$user = isset($users[$body['user']]) ? $users[$body['user']] : null;

	if ($user)
	{
		$user_html = "<img height='24px' src='".$user['image_72']."'/> " . $user['name'];
	} else {
		$user_html = "@" . $body['user'];
	}

	echo "<p><a href='$message_url'>GOTO</a> <br /> 
	$date 
	<span style='color: green'>".$row['positive_reaction_cnt']."</span>/<span style='color: black'>".$row['total_reaction_cnt']."</span>/<span style='color: red'>".$row['negative_reaction_cnt']."</span> 
	<br /> $user_html: $text <br /> $reactions_html $debug</p>";
}
