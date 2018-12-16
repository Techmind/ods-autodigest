<?php
ob_start();

if ($date == 'total')
{
	$date = '';
}

foreach ($users as $user_raw)
{
	$uid = $user_raw['_id'];
	$user = $user_raw['_source'];
	$reactions_html = '';
	
	if ($user)
	{
		$user_html = "<img height='24px' src='".$user['image_72']."'/> " . $user['name'];
	} else {
		$user_html = "@" . $body['user'];
	}

	$search = "(search: <b> from:@".$user['name']. " ";
	if ($date)
	{
		$date_frmt = substr($date, 0, 4) . "-" . substr($date, 4);
		$search .= "after:$date_frmt-00 before:$date_frmt-31";
	}
	$search .= ")</b> UID: $uid";

	echo $user_html .  " " . $user['real_name'] . " <b>" . $user[$type] . "</b>
	
	$search
	<br /><br /`>";
	
}

return ob_get_clean();
