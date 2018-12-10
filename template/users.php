<?php
ob_start();

foreach ($users as $user)
{
	$user = $user['_source'];
	$reactions_html = '';
	
	if ($user)
	{
		$user_html = "<img height='24px' src='".$user['image_72']."'/> " . $user['name'];
	} else {
		$user_html = "@" . $body['user'];
	}

	echo $user_html .  " " . $user['real_name'] . " <b>" . $user[$type] . "</b><br /><br /`>";
	
}

return ob_get_clean();
