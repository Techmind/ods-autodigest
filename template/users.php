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

	echo $user_html . var_export($user, true) . "<br /><br /`>";
	
}

return ob_get_clean();
