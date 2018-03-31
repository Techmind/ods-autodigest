<?php
ob_start();
$emojis = $config['emojis'];
$positive_reactions = $config['positive_reactions'];
$negative_reactions = $config['negative_reactions'];

foreach ($rows as $row)
{
	$body = json_decode($row['body'], true);
	$reactions_html = '';
	if (isset($body['reactions']))
	{
		foreach ($body['reactions'] as $react)
		{
			$style = 'display: inline-block;';
			$name = $react['name'];
			$cnt = $react['count'];
			if (in_array($name, $positive_reactions))
			{
				$style .= "background-color: green; padding: 1px;";
			}
			elseif (in_array($name, $negative_reactions))
			{
				$style .= "background-color: red; padding: 1px;";
			}
			$name_html = isset($emojis[$name])
				? "<img title=':$name:' height='24px' src='".$emojis[$name]."'>" :
				":$name:";
			$reactions_html .= " <div style='$style'>$name_html ($cnt)</div>";
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

	//replace user names

	preg_match_all('~@U[0-9A-Z]+~', $text, $matches);
	foreach ($matches[0] as $match) {
		$uid = substr($match, 2);

		$user_comment = isset($users[$body['user']]) ? $users[$body['user']] : null;

		$text = str_replace($match, $user['name'], $text);
	}

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

return ob_get_clean();