<?php

/**
 * Provides a class to handle xhprof profiles.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class XhprofProfiler extends Object
{
	/**
	 * Determines if profiling is currently enabled.
	 *
	 * @var boolean
	 */
	protected $profiler_enabled = false;

	/**
	 * Construct, will auto enable based up on is_shell constances.
	 *
	 * @param Core $core
	 *   The core object (optional, default = null)
	 */
	public function __construct(&$core = null) {
		parent::__construct($core);

		if (!defined('is_shell')) {
			$this->enable_from_web();
		}
		else {
			$this->enable_from_cli();
		}
	}

	/**
	 * Enables profiling from provided _GET param.
	 */
	public function enable_from_web() {
		if (!empty($_GET['PROFILER'])) {
			$this->enable();
		}
	}

	/**
	 * Enables profiling from provided commandline arguments.
	 */
	public function enable_from_cli() {
		global $argv;
		if (!empty($argv) && (in_array('profiler', $argv) || in_array('verbose', $argv))) {
			$this->enable();
		}
	}

	/**
	 * Enables profiling.
	 */
	public function enable() {
		$this->profiler_enabled = true;
		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
	}

	/**
	 * If profiling was enabled we will stop it now and provide information about the run.
	 */
	public function __destruct() {
		if ($this->profiler_enabled === true) {
			$xhprof_data = xhprof_disable();
			$xhprof_domain = $this->core->core_config("core", "xhprof_domain");
			$xhprof_root = $this->core->core_config("core", "xhprof_root");
			if (empty($xhprof_root)) {
				return;
			}
			include $xhprof_root . "/xhprof_lib/utils/xhprof_lib.php";
			include $xhprof_root . "/xhprof_lib/utils/xhprof_runs.php";

			if (!class_exists('XHProfRuns_Default')) {
				return;
			}
			$xhprof_runs = new XHProfRuns_Default();
			$mem_usage = memory_get_usage(true)/1024/1024;
			$wall_time = round($xhprof_data['main()']['wt']/1000/1000, 3);
			$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_soopfw");
			if (!defined('is_shell')) {
				echo "<br /><div style='text-align:right;'><a href='" . $xhprof_domain . "/index.php?run=" . $run_id . "&source=xhprof_soopfw' target='_blank'>" . $wall_time . "s " . $mem_usage . "MB Profile data</a></div>";
			}
			else {
				$run_id_info = $run_id;
				if (!empty($xhprof_domain)) {
					$run_id_info = $xhprof_domain . "/index.php?run=" . $run_id . "&source=xhprof_soopfw";
				}

				echo "\n" . $run_id_info . " - " . $wall_time . "s " . $mem_usage . "MB Profile data\n";
			}
		}
	}
}