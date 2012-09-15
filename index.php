<?php
	error_reporting(E_ALL);
	ini_set('display_errors', 'on');
	ini_set('html_errors', 'on');

	require('lib/Core.php');
	//require('lib/web/WebAction.class.php');
	$core = Core::get_instance(false);
	$web_action = new WebAction();
	$web_action->process_action();

	display_xhprof_run();

	function display_xhprof_run() {
		global $prof_enable;
		if($prof_enable) {
			$xhprof_data = xhprof_disable();
			$xhrprof_domain = Core::get_instance()->core_config("core", "xhprof_domain");
			$xhrprof_root = Core::get_instance()->core_config("core", "xhprof_root");
			if (empty($xhrprof_domain) || empty($xhrprof_root)) {
				return;
			}
			include $xhrprof_root . "/xhprof_lib/utils/xhprof_lib.php";
			include $xhrprof_root . "/xhprof_lib/utils/xhprof_runs.php";

			if (!class_exists('XHProfRuns_Default')) {
				return;
			}
			$xhprof_runs = new XHProfRuns_Default();
			$mem_usage = memory_get_usage(true)/1024/1024;
			$wall_time = round($xhprof_data['main()']['wt']/1000/1000, 3);
			$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_soopfw");
			echo "<br /><div style='text-align:right;'><a href='" . $xhrprof_domain . "/index.php?run=" . $run_id . "&source=xhprof_soopfw' target='_blank'>" . $wall_time . "s " . $mem_usage . "MB Profile data</a></div>";
		}
	}