<?php

/**
 * Provides a class to handle git repositories.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class Git
{
	/**
	 * The repository path.
	 *
	 * @var string
	 */
	protected $repo_path = null;

	/**
	 * The path to git binary.
	 *
	 * @var string
	 */
	public $git_path = '/usr/bin/git';

	/**
	 * Create a new git repository
	 *
	 * @param string $repo_path
	 *   repository path
	 * @param string $source
	 *   directory to source (optional, default = null)
	 *
	 * @return Git the new Git instance.
	 */
	public static function &create_new($repo_path, $source = null) {
		if (is_dir($repo_path) && file_exists($repo_path . "/.git") && is_dir($repo_path . "/.git")) {
			throw new Exception('"$repo_path" is already a git repository');
		}
		else {
			$repo = new self($repo_path, true, false);
			if (is_string($source)) {
				$repo->clone_from($source);
			}
			else {
				$repo->run('init');
			}
			return $repo;
		}
	}

	/**
	 * Constructor
	 *
	 * @param string $repo_path
	 *   repository path (optional, default = null)
	 * @param boolean $create_new
	 *   create if not exists? (optional, default = false)
	 * @param boolean $_init
	 *   wether we want to init the git repo or not. (optional, default = true)
	 * @return void
	 */
	public function __construct($repo_path = null, $create_new = false, $_init = true) {
		if (is_string($repo_path)) {
			$this->set_repo_path($repo_path, $create_new, $_init);
		}
	}

	/**
	 * Set the repository's path
	 *
	 * @param string $repo_path
	 *   repository path
	 * @param boolean $create_new
	 *   create if not exists? (optional, default = false)
	 * @param boolean $_init
	 *   wether we want to init the git repo or not. (optional, default = true)
	 */
	public function set_repo_path($repo_path, $create_new = false, $_init = true) {
		if (is_string($repo_path)) {
			if ($new_path = realpath($repo_path)) {
				$repo_path = $new_path;
				if (is_dir($repo_path)) {
					if (file_exists($repo_path . "/.git") && is_dir($repo_path . "/.git")) {
						$this->repo_path = $repo_path;
					}
					else {
						if ($create_new) {
							$this->repo_path = $repo_path;
							if ($_init)
								$this->run('init');
						}
						else {
							throw new Exception('"$repo_path" is not a git repository');
						}
					}
				}
				else {
					throw new Exception('"$repo_path" is not a directory');
				}
			}
			else {
				if ($create_new) {
					if ($parent = realpath(dirname($repo_path))) {
						mkdir($repo_path);
						$this->repo_path = $repo_path;
						if ($_init)
							$this->run('init');
					}
					else {
						throw new Exception('cannot create repository in non-existent directory');
					}
				}
				else {
					throw new Exception('"$repo_path" does not exist');
				}
			}
		}
	}

	/**
	 * Run a command in the git repository
	 *
	 * @param string $command
	 *   command to run
	 *
	 * @return string the git returning string.
	 */
	protected function run_command($command) {
		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$pipes = array();
		$resource = proc_open($command, $descriptorspec, $pipes, $this->repo_path);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$status = trim(proc_close($resource));
		if ($status) {
			throw new Exception($stderr);
		}

		return $stdout;
	}

	/**
	 * Run a git command in the git repository
	 *
	 * @param string $command
	 *   command to run.
	 *
	 * @return string the git returning string.
	 */
	public function run($command) {
		return $this->run_command($this->git_path . " " . $command);
	}

	/**
	 * Add files
	 *
	 * @param mixed $files
	 *   files to add
	 *   can be a single file or an array with all files. (optional, default = '*')
	 *
	 * @return string the git returning string.
	 */
	public function add($files = "*") {
		if (is_array($files)) {
			$files = '"' . implode('" "', $files) . '"';
		}
		return $this->run("add " . $files . " -v");
	}

	/**
	 * Commit.
	 *
	 * @param string $message
	 *   commit message. (optional, default = '')
	 *
	 * @return string the git returning string.
	 */
	public function commit($message = "") {
		return $this->run("commit -av -m \"" . $message . "\"");
	}

	/**
	 * Clone a repository.
	 *
	 * @param string $source
	 *   source url.
	 *
	 * @return string the git returning string.
	 */
	public function clone_from($source) {
		return $this->run("clone --local " . $source . " " . $this->repo_path);
	}

	/**
	 * Clone a remote repository.
	 *
	 * @param string $source
	 *   source url.
	 *
	 * @return string the git returning string.
	 */
	public function clone_remote($source) {
		return $this->run("clone " . $source . " " . $this->repo_path);
	}

	/**
	 * Runs a `git clean` call
	 *
	 * Accepts a remove directories flag
	 *
	 * @access public
	 * @param boolean delete directories?
	 * @return string the git returning string.
	 */
	public function clean($dirs = false) {
		return $this->run("clean" . (($dirs) ? " -d" : ""));
	}

	/**
	 * Create a new branch.
	 *
	 * @param string $branch
	 *   branch name.
	 *
	 * @return string the git returning string.
	 */
	public function create_branch($branch) {
		return $this->run("branch " . $branch);
	}

	/**
	 * Delete a branch.
	 *
	 * @param string $branch
	 *   branch name
	 * @param boolean $force
	 *   wether to force or not (optional, default = false).
	 *
	 * @return string the git returning string.
	 */
	public function delete_branch($branch, $force = false) {
		return $this->run("branch " . (($force) ? '-D' : '-d') . " $branch");
	}

	/**
	 * Returns a list of local branches.
	 *
	 * @param boolean $keep_asterisk
	 *   keep asterisk mark on branch name (optional, default (false).
	 *
	 * @return array an array with all branches
	 */
	public function list_branches($keep_asterisk = false) {

		$branch_array = explode("\n", $this->run("branch"));

		foreach ($branch_array as $i => &$branch) {

			$branch = trim($branch);
			if (!$keep_asterisk) {
				$branch = str_replace("* ", "", $branch);
			}
			if ($branch == "") {
				unset($branch_array[$i]);
			}
		}

		return $branch_array;
	}

	/**
	 * Returns a list of tags.
	 *
	 * @return array the tag list
	 */
	public function list_tags() {

		$tag_array = explode("\n", $this->run("tag"));

		foreach ($tag_array as $i => &$tag) {
			$tag = trim($tag);
			if ($tag == "") {
				unset($tag_array[$i]);
			}
		}

		return $tag_array;
	}

	/**
	 * List all remote branches.
	 *
	 * @param boolean $keep_asterisk
	 *   keep asterisk mark on active branch. (optional, default (false)
	 *
	 * @return array an array with all remote branches
	 */
	public function list_remote_branches($keep_asterisk = false) {

		$branch_array = explode("\n", $this->run("branch -a"));

		foreach ($branch_array as $i => &$branch) {

			$branch = trim($branch);
			if (!$keep_asterisk) {
				$branch = str_replace("* ", "", $branch);
			}
			if ($branch == "" || !preg_match("/^remotes\/origin\/[^\s]+$/", $branch)) {
				unset($branch_array[$i]);
			}
		}

		return $branch_array;
	}

	/**
	 * Returns name of active branch
	 *
	 * @param boolean $keep_asterisk
	 *   keep asterisk mark on branch name (optional, default (false).
	 *
	 * @return string the active branch name
	 */
	public function active_branch($keep_asterisk = false) {

		// Get local branches.
		$branch_array = $this->list_branches(true);

		// Get the active branch
		$active_branch = preg_grep("/^\*/", $branch_array);
		reset($active_branch);

		// Remove asterisks if wanted.
		if ($keep_asterisk) {
			return current($active_branch);
		}
		else {
			return str_replace("* ", "", current($active_branch));
		}
	}

	/**
	 * Checkout a branch.
	 *
	 * @param string $branch
	 *   branch name
	 *
	 * @return string the returning message from git.
	 */
	public function checkout($branch) {

		// Checkout the branch.
		return $this->run("checkout " . $branch);
	}

}