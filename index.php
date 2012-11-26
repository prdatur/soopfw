<?php
	require('lib/Core.php');
	$core = Core::get_instance(false);
	$web_action = new WebAction();
	$core->web_action = &$web_action;
	$web_action->process_action();