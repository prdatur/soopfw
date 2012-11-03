<?php
error_reporting(E_ALL);
			ini_set('display_errors', 'on');
			ini_set('html_errors', 'on');
	require('lib/Core.php');
	$core = Core::get_instance(false);
	$web_action = new WebAction();
	$web_action->process_action();