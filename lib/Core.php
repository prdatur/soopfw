<?php
if (!defined('SITEPATH')) {
	define('SITEPATH', dirname(dirname(__FILE__)));
}

define("TIME_NOW", time());
define("DB_DATE", "Y-m-d");
define("DB_DATETIME", "Y-m-d H:i:s");
define("DB_TIME", "H:i:s");

mb_internal_encoding('UTF-8');

/**
 * This is not a primitive datatype but it can be used as a real not set variable, so if we realy want to check if a
 * parameter was provided to a function/method we can default assign NS so if we pass "", null or something similar to empty
 * it is also a allowed "provided" value. The value behind NS is choosen with a string which should never be a value provided by a user
 */
define("NS", "-||notset||-");

global $memcached_obj, $translation_cache;
$translation_cache = array();

/**
 * Define our primitive datatypes, these are used in several ways.
 * Most use is within parameter type checks.
 */
$i = 1;
define("PDT_INT", $i++, true);
define("PDT_FLOAT", $i++, true);
define("PDT_STRING", $i++, true);
define("PDT_DECIMAL", $i++, true);
define("PDT_DATE", $i++, true);
define("PDT_OBJ", $i++, true);
define("PDT_ARR", $i++, true);
define("PDT_BOOL", $i++, true);
define("PDT_INET", $i++, true);
define("PDT_SQLSTRING", $i++, true);
define("PDT_JSON", $i++, true);
define("PDT_PASSWORD", $i++, true);
define("PDT_ENUM", $i++, true);
define("PDT_TEXT", $i++, true);
define("PDT_TINYINT", $i++, true);
define("PDT_MEDIUMINT", $i++, true);
define("PDT_BIGINT", $i++, true);
define("PDT_SMALLINT", $i++, true);
define("PDT_DATETIME", $i++, true);
define("PDT_TIME", $i++, true);
define("PDT_FILE", $i++, true);
define("PDT_LANGUAGE", $i++, true);
define("PDT_LANGUAGE_ENABLED", $i++, true);
define("PDT_SERIALIZED", $i++, true);

//Include important standalone functions
require (SITEPATH . '/lib/common.php');

/**
 * Main Core, It will initialize all needed classes (Smarty, Memcache, DB, ...)
 * Also it Provides several useful methods for quick accessing within classes
 * which extends Object
 *
 * In a default install/behavior we DO NOT need to initialize the Core.
 *
 * Soopfw will do this for us within the default_index.php, also shell commands (clifs) will initialize the core for us.
 *
 * So do this only if you have your own standalone file.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Core
 */
class Core
{
	/**
	 * Define global return codes
	 */
	const GLOBEL_RETURN_CODE_SUCCESS = 200;

	/**
	 * Define message types
	 */
	const MESSAGE_TYPE_SUCCESS = "success";
	const MESSAGE_TYPE_ERROR = "error";
	const MESSAGE_TYPE_NOTICE = "information";

	/**
	 * Define Javascript scopes
	 */
	const JS_SCOPE_SYSTEM = 'system';
	const JS_SCOPE_USER = 'user';

	/**
	 * Define initializing type
	 */
	const INIT_TYPE_HTML = 'html';
	const INIT_TYPE_AJAXHTML = 'ajaxhtml';
	const INIT_TYPE_AJAX = 'ajax';

	/**
	 * Define run modes.
	 */
	const RUN_MODE_DEVELOPEMENT = 'development';
	const RUN_MODE_PRODUCTION = 'production';

	/**
	 * @var array the core config
	 */
	public $config;

	/**
	 * @var boolean should we debug ?
	 */
	private $debug;

	/**
	 * @var boolean logged in true, else false
	 */
	public $logged_in = false;

	/**
	 * The database object
	 *
	 * @var Db
	 */
	public $db = null;

	/**
	 * The Language object
	 *
	 * @var Language
	 */
	public $lng = null;

	/**
	 * hold the current language key
	 * @var string
	 */
	public $current_language = "";

	/**
	 * hold the default language key
	 * @var string
	 */
	public $default_language = "";

	/**
	 * Smarty
	 * @var Smarty
	 */
	public $smarty = null;

	/**
	 * The template file which will be displayed after processing
	 * @var string
	 */
	public $template = "index.tpl";

	/**
	 * Holds the memcached obj if enabled, else it will be null
	 * @var Memcached
	 */
	public $memcache_obj = null;

	/**
	 * Hold cache vars (just within the current runtime)
	 *
	 * @var array
	 */
	public $cache = array();

	/**
	 * The meta object (meta title and so on)
	 * @var Meta
	 */
	public $meta = null;

	/**
	 * JS-Files which will be included within template
	 * @var array
	 */
	public $js_files = array();

	/**
	 * CSS-Files which will be included within template
	 * @var array
	 */
	private $css_files = array();

	/**
	 * This variables will be provided within javascript code
	 * @var array
	 */
	private $js_config = array();

	/**
	 * Holds the session object
	 * @var Session
	 */
	public $session = null;

	/**
	 * Holds the RightManager
	 * @var RightManager
	 */
	public $right_manager = null;

	/**
	 * the inizializing type use one of Core::INIT_TYPE_*
	 * @var string
	 */
	public $init_type = self::INIT_TYPE_HTML;

	/**
	 * Holds all registered widgets.
	 * @var array
	 */
	private $widgets = array();

	/**
	 * Holds all useable mime types
	 * This array will be empty for performance reasons unles we need it.
	 * method get_mime_types or load_mime_types will fill it up.
	 *
	 * @var array
	 */
	public $mime_types = array();

	/**
	 * Holds all modules.
	 *
	 * @var array
	 */
	public $modules = array();

	/**
	 * The run mode.
	 *
	 * @var string
	 */
	public $run_mode = self::RUN_MODE_DEVELOPEMENT;

	/**
	 * Holds all objects which should exists the hole lifetime of the core.
	 *
	 * @var array
	 */
	public $class_holder = array();

	/**
	 * Holds all classes for the classloader.
	 *
	 * @var array
	 */
	public static $classes = array();

	/**
	 * Constructor which reads configs, and setting up Database connection and
	 * if $install is set to true it will not initialize create objects,
	 * also no session will be started
	 *
	 *
	 * @param boolean $is_shell
	 *   Whether we're running from the shell (and not in a webserver environment) (optional, default = false)
	 * @param string $run_mode
	 *   Force default run mode.
	 *   If database is enabled and run mode was saved within system configuration page
	 *   the database value has priority, normally the force value is used within standalone apps
	 *   which will be not configured normally with the default administration web interface.
	 *
	 *   Use one of Core::RUN_MODE_*
	 *   (optional, default = Core::RUN_MODE_DEVELOPEMENT)
	 *
	 *
	 */
	public function __construct($is_shell = false, $run_mode = self::RUN_MODE_DEVELOPEMENT) {
		//If we run as a shell command define the global constance is_shell
		if ($is_shell == true && !defined('is_shell')) {
			define('is_shell', $is_shell);
		}

		//Include core config
		require SITEPATH . "/config/core.php";

		if (!isset($this->config['db']['use'])) {
			$this->config['db']['use'] = false;
		}

		$this->config['db']['use'] = !empty($this->config['db']['use']);

		//Include Object because XhprofProfiler needs it.
		require_once SITEPATH . "/lib/Object.class.php";

		//Include xhprof profiling class.
		require_once SITEPATH . "/lib/XhprofProfiler.class.php";

		// Enable profiler.
		$this->class_holder[] = new XhprofProfiler($this);

		// Init the database if wanted.
		$this->init_database();

		// Init always memcache.
		$this->init_memcached();

		// Init the classloader.
		spl_autoload_register(array('Core', 'class_loader'));

		if ($this->config['db']['use'] === true) {
			$this->run_mode = $this->get_dbconfig("system", system::CONFIG_RUN_MODE, $run_mode);

		}
		else {
			$this->run_mode = $run_mode;
		}

		if ($this->run_mode === self::RUN_MODE_PRODUCTION) {
			error_reporting(E_ERROR);
			ini_set('display_errors', 'off');
			ini_set('html_errors', 'off');
		}
		else {
			error_reporting(E_ALL);
			ini_set('display_errors', 'on');
			ini_set('html_errors', 'on');
		}

		if (class_exists('SoopfwErrorHandler')) {
			set_error_handler(array('SoopfwErrorHandler', 'cc_error_handler'), error_reporting());
		}
	}

	/**
	 * Creates a singleton Core instance.
	 *
	 * @staticvar Core $core
	 *   The Core instance.
	 *
	 * @param boolean $is_shell
	 *   Whether we're running from the shell (and not in a webserver environment) (optional, default = false)
	 * @param string $run_mode
	 *   Force default run mode.
	 *   If database is enabled and run mode was saved within system configuration page
	 *   the database value has priority, normally the force value is used within standalone apps
	 *   which will be not configured normally with the default administration web interface.
	 *
	 *   Use one of Core::RUN_MODE_*
	 *   (optional, default = Core::RUN_MODE_DEVELOPEMENT)
	 * @param mixed $force_new_instance
	 *   If set to true it will force generating a new Core.
	 *   Please be aware that any previous changes like static cache
	 *   are resetted, a hole new clean core will be created.
	 *
	 *   Special note on this param.
	 *   If you provide NULL it will be really negated and interprated as
	 *   'force no Core creation eather Object is null'
	 *
	 *   (optional, default = false)
	 *
	 * @return Core the Core object instance.
	 */
	public static function get_instance($is_shell = false, $run_mode = self::RUN_MODE_DEVELOPEMENT, $force_new_instance = false) {
		static $core = null;
		if ($force_new_instance !== null && ($force_new_instance === true || $core === null)) {
			$core = new Core($is_shell, $run_mode);
		}
		return $core;
	}

	/**
	 * Initialize the database.
	 */
	public function init_database() {
		require_once SITEPATH . "/lib/database/Db.class.php";
		//If we want to use a database connection initialize the database object
		if ($this->config['db']['use'] == true) {
			$this->db = new Db($this->config['db']['host'], $this->config['db']['user'], $this->config['db']['pass'], $this->config['db']['database']);

			// Set the table prefix if configured.
			if (!empty($this->config['db']['table_prefix'])) {
				$this->db->table_prefix($this->config['db']['table_prefix']);
			}

		}
		unset($this->config['db']['user']);
		unset($this->config['db']['pass']);
		unset($this->config['db']['database']);
	}

	/**
	 * Load or reload the classlist
	 *
	 * @param boolean $force
	 *   if set to true it will force reload the classes.
	 * @param boolean $force_file
	 *   Whether we want to force to load the classes.php file or not
	 *   if not it can be retrieved through memcached (optiona, default = false)
	 *
	 * @return array the current classlist
	 */
	public static function load_classlist($force = false, $force_file = false) {
		global $memcached_obj;
		if ($force === true) {
			Core::$classes = array();
		}
		if (empty(Core::$classes)) {
			if ($force_file === false) {
				Core::$classes = $memcached_obj->get('classloader_classes');
			}

			if (empty(Core::$classes)) {
				include SITEPATH . '/config/classes.php';
				Core::$classes = &$classes;
				$memcached_obj->set('classloader_classes', Core::$classes);
			}
		}

		return Core::$classes;
	}

	/**
	 * Returns the current loaded classlist.
	 *
	 * @return array the classlist
	 */
	public static function get_classlist() {
		return Core::$classes;
	}

	/**
	 * Class autoload loader.
	 *
	 * @param string
	 *   class name
	 * @return boolean whether the class has been loaded successfully
	 */
	public static function class_loader($classname) {
		global $memcached_obj;
		$classes = self::load_classlist();
		if (array_key_exists($classname, $classes["classes"])) {
			require SITEPATH . $classes["classes"][$classname]['path'];
		}
		elseif (array_key_exists($classname, $classes["interfaces"])) {
			require SITEPATH . $classes["interfaces"][$classname]['path'];
		}
		elseif (file_exists($classname . ".php")) {
			require $classname . ".php";
		}
		else {
			// If we tried to load an invalid class maybe memcached is corrupted, reload next time with a fresh one from classes.php.
			if (!empty($memcached_obj)) {
				$memcached_obj->set('classloader_classes', array());
			}
			return false;
		}
		return true;

	}

	/**
	 * Regenerates the CSRF-token and return it.
	 *
	 * @return string the token
	 */
	public function regenerate_csrf_token() {
		return $_SESSION['CSRFtoken'] = md5(uniqid(microtime()));
	}

	/**
	 * Boot up the core, this is need because with creating the core object
	 * no $GLOBALS['core'] exist and therefore the Object class can not get the
	 * core from GLOBALS
	 * This will be called near after creating the core class
	 *
	 * if $is_shell is defined and set to true smarty will not be initialized,
	 *
	 * @param string $language
	 *   The language to be used, if not provided it will try to auto get it (optional, default = '')
	 * @param bool $install
	 *   Whether we're want to install (optional, default = false)
	 */
	public function boot($language = '', $install = false) {

		// Setup the default language.
		if (empty($this->config['core']['default_language'])) {
			$this->config['core']['default_language'] = 'en';
		}

		// Try to get a maybe overwritten default language from database.
		if ($this->config['db']['use'] === true) {
			$this->default_language = $this->get_dbconfig("system", system::CONFIG_DEFAULT_LANGUAGE, $this->config['core']['default_language']);
		}

		// Initialize our session.
		$this->session = new Session($this);

		// Check if current connection comes from WebUnitTest.
		$test_session_file = SITEPATH . '/uploads/session_is_test_' . $this->session->get_session_id();
		if (!empty($this->db) && file_exists($test_session_file)) {
			if ($this->mcache(file_get_contents($test_session_file))) {
				// Use test envoirment.
				$this->db->table_prefix('test_' . $this->db->table_prefix());

				// Reinit default language.
				$this->default_language = $this->get_dbconfig("system", system::CONFIG_DEFAULT_LANGUAGE, $this->config['core']['default_language']);

				$this->mcache_set_prefix('test_' . $this->db->table_prefix());
				// We need to reinit the session object.
				$this->session = new Session($this);
			}
		}

		//Set the provided $language if it is not empty
		if (!empty($language)) {
			$_SESSION['language'] = $language;
		}

		// If we have not setup a language set the current language to the default language.
		if (empty($_SESSION['language'])) {
			$_SESSION['language'] = $this->default_language;
		}

		$_SESSION['language'] = strtolower($_SESSION['language']);

		// Set our current language
		$this->current_language = $_SESSION['language'];



		// Check if we should redirect.
		if (!defined('is_shell') && !empty($this->session)) {
			$reload_page = $this->session->get("next_load_redirect", "");
			if (!empty($reload_page)) {
				$this->session->set("next_load_redirect", "");
				$this->location($reload_page);
			}
		}

		// Only do a normal startup if we do not install the core system.
		if ($install == false) {

			//Init smarty and meta information if we are not in shell mode.
			if (!defined('is_shell')) {
				$this->meta = new Meta();
			}

			// Read all available modules.
			// We need to include the dir class because it can happend that we are in a fresh install.
			require_once 'Dir.class.php';
			$dir = new Dir("modules", false);
			$dir->just_dirs();
			foreach ($dir AS $directory) {
				if (file_exists(SITEPATH . "/modules/" . $directory->filename . "/" . $directory->filename . ".php")) {
					$this->modules[$directory->filename] = $directory->filename;
				}
			}

			//If CSRF Token is empty create a unique one
			if (empty($_SESSION['CSRFtoken'])) {
				$_SESSION['CSRFtoken'] = md5(uniqid(microtime()));
			}

			// If translation module is activated load the language class.
			if ($this->module_enabled("translation")) {
				$this->lng = new Language($this->current_language, $this);

				register_shutdown_function(function () {
					global $translation_cache;

					if (!empty(Core::get_instance()->db) && Core::get_instance()->module_enabled("translation")) {
						$query = " INSERT IGNORE INTO `" . TranslationKeysObj::TABLE . "` (`id`,`key`) VALUES ";
						$query_arr = array();
						foreach ($translation_cache AS $id => $trans) {
							$query_arr[] = "('" . Db::safe($id) . "', '" . Db::safe($trans) . "')";
						}
						$query .= implode(",", $query_arr);
						Core::get_instance()->db->query_master($query);
					}
				});
			}

			// Initialize the right manager object.
			$this->right_manager = new RightManager($this);
		}

		if (!empty($this->db)) {
			$this->db->init_mysql_table();
		}

		if (!defined('is_shell')) {
			$this->init_smarty();
		}
	}

	public function mcache_set_prefix($prefix) {
		if (!empty($this->memcache_obj)) {
			$this->memcache_obj->set_option(CacheProvider::OPT_PREFIX_KEY, $this->core_config('core', 'domain') . ':' . $prefix . ':');
		}
	}
	/**
	 * Provide a method to run a shell (cli) command.
	 */
	public function run_cli() {
		$cmds = array();
		$classes = Core::get_classlist();

		// Fallback if class not found load it.
		if (!class_exists('CLICommand')) {
			require_once SITEPATH . '/lib/CLICommand.class.php';
		}

		// Search cli commands and setup long options array.
		$c = 1;
		foreach ($classes['classes'] AS $class => $v) {
			if (preg_match("/^cli_(.*)$/", $class, $matches)) {
				$cmds[$c++] = $matches[1];
			}
		}
		// Provide help options.
		$options_array = getopt('h', array("help") + $cmds);

		// Display help information if h, help or no options provided.
		if (empty($options_array) || isset($options_array['h']) || isset($options_array['help'])) {

			if (empty($options_array)) {
				echo "Missing parameters.\n";
			}
			echo "\n\nOptions:\n";
			echo " -h, --help\t\tDisplay this Help\n";
			foreach ($cmds AS $cmd) {
				$class = "cli_" . $cmd;
				$obj = new $class();
				echo " --" . $cmd . "\t\t" . $obj->get_description() . "\n";
			}
			echo "Example:\n";
			echo "php -f clifs.php -- --help\n";
			echo "./clifs.php -h\n";
		}
		// Run all provided commands.
		else {
			foreach ($options_array AS $command => $isset) {
				$class = "cli_" . $command;
				$obj = new $class();
				$obj->start();
			}
		}
	}

	/**
	 * Returns if ssl is currently active.
	 *
	 * @return boolean true if ssl is activated, else false
	 */
	public function is_ssl() {
		return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on");
	}

	/**
	 * Function will redirect to https page if we are currently on http mode.
	 */
	public function need_ssl() {

		if (!$this->is_ssl() && $this->get_dbconfig("system", system::CONFIG_SSL_AVAILABLE, 'no') === 'yes') {
			header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			exit();
		}
	}

	/**
	 * function will redirect to http page if we are currently on https mode
	 */
	public function need_no_ssl() {

		if ($this->is_ssl()) {
			header("Location: http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			exit();
		}
	}

	/**
	 * Returns the secured url. if ssl is not available it will get the unsecure back.
	 *
	 * @param boolean $strict
	 *   if set to true it will not get back an unsecure base url if ssl is unavailable,
	 *   instead it will return null if it can not return a valid secure url (optional, default = false)
	 *
	 * @return string the secure base url
	 */
	public function get_secure_url($strict = false) {
		if ($this->get_dbconfig("system", system::CONFIG_SSL_AVAILABLE, 'no') === 'yes') {
			return 'https://' . $this->get_dbconfig("system", system::CONFIG_SECURE_DOMAIN, $this->core_config('core', 'domain'));
		}

		if ($strict === true) {
			return null;
		}

		return 'http://' . $this->core_config('core', 'domain');
	}

	/**
	 * Initialize the memcached object or an equivalent wrapper
	 */
	public function init_memcached() {
		global $memcached_obj;

		// Only create the memcached object if we do not have it already.
		if (is_null($this->memcache_obj)) {

			require_once SITEPATH . '/lib/cache/CacheProviderInterface.class.php';
			require_once SITEPATH . '/lib/cache/CacheProvider.class.php';

			// Init original memcached object.
			if (class_exists("memcached")) {
				require_once SITEPATH . '/lib/cache/default_provider/MemcachedEngine.class.php';
				$memcached_obj = new MemcachedEngine($this);
			}
			// Memcached not exist, try to get memcache with the wrapper for memcached.
			else if (class_exists("memcache")) {
				require_once SITEPATH . '/lib/cache/default_provider/MemcachedWrapper.class.php';
				$memcached_obj = new MemcachedWrapper($this);
			}
			// Memcache also not exist, try to use database memcached wrapper.
			else if (!empty($this->db)) {
				require_once SITEPATH . '/lib/cache/default_provider/DBMemcached.class.php';
				$memcached_obj = new DBMemcached($this);
			}
			// We use no database connection so we have no realy cache, we use a static memcached wrapper.
			else {
				require_once SITEPATH . '/lib/cache/default_provider/StaticMemcached.class.php';
				$memcached_obj = new StaticMemcached($this);
			}

			$prefix = "";
			if ($this->config['db']['use'] === true) {
				$prefix = $this->db->table_prefix();
			}

			// Setup memcached prefix.
			$memcached_obj->set_option(CacheProvider::OPT_PREFIX_KEY, $this->core_config('core', 'domain') . ':' . $prefix . ':');

			// Add the current server to memcached object / wrapper.
			$memcached_obj->add_server($this->core_config("memcache", "host"), $this->core_config("memcache", "port"));
			$this->memcache_obj = &$memcached_obj;
		}
	}

	/**
	 * Get or set a configuration for a given module
	 * Be aware if you set some values manually it will be not permantent stored, only during current page request.
	 * The only permanent configuration within this method will be the values within config/core.php
	 *
	 *
	 * If $value is not provided or NS it will try to return the current value for that module and key
	 * Special note on $key as array
	 * for example we have a core config array like
	 * 	$this->config['module']['container1']['subcontainer']['field1']
	 * and want to get exactly the value for that field1 we must provide and array as key with the full path as array values
	 * example:
	 * 	$this->core_config('module', array('container1','subcontainer','field1'));
	 *
	 *
	 * @param string $modul
	 *   the module name
	 * @param mixed $key
	 *   the config key for that module or an array with the path of the config value (just in get mode)
	 * @param string $value
	 *   the value if we want to set the config key (optional, default = NS)
	 *
	 * @return mixed in set mode we return true if we could set the value else false , in get mode we return the value or null if not exist the returning value can be an array or single value
	 *
	 */
	public function core_config($modul, $key, $value = NS) {
		if ($value === NS) {
			// Get mode.
			$return = null;

			// Transform the provided key into an array if it is not already one.
			if (!is_array($key)) {
				$key = array($key);
			}
			// Get the complete module config.
			$return = $this->config[$modul];

			// Loop through our $key "path" and try to get the value for that path.
			foreach ($key AS $k) {
				if (isset($return[$k])) {
					$return = $return[$k];
				}
				else {
					return null;
				}
			}

			return $return;
		}
		// Set mode.
		// return false if $key is an array couse this can just be within get mode.
		if (is_array($key)) {
			return false;
		}

		// Pre init the config array for that module if it is ot set.
		if (!isset($this->config[$modul])) {
			$this->config[$modul] = array();
		}

		// Set the value.
		$this->config[$modul][$key] = $value;
		return true;
	}

	/**
	 * Set or get a memcached stored value
	 *
	 * @param string $key
	 *   the key
	 * @param mixed $value
	 *   The value , if param not provided it will try to return the value for the given key (optional, default = NS)
	 * @param int $expire
	 *   The expire time for the memcache key (optional, default = 0)
	 *
	 * @return mixed within set mode return true, else return the value or null if value is not present
	 */
	public function mcache($key, $value = NS, $expire = 0) {
		if ($value === NS) {
			return $this->memcache_obj->get($key);
		}
		$this->memcache_obj->set($key, $value, $expire);
		return true;
	}

	/**
	 * Set or get a static cache value
	 *
	 * @param string $modul
	 *   the module name
	 * @param string $key
	 *   the config key
	 * @param mixed $value
	 *   the value to be stored (optional, default = NS)
	 *
	 * @return mixed return null or the value within get mode, or true on set mode
	 */
	public function cache($modul, $key, $value = NS) {
		if ($value === NS) {
			$return = null;
			if (isset($this->cache[$modul][$key])) {
				$return = $this->cache[$modul][$key];
			}
			return $return;
		}

		if (!isset($this->cache[$modul])) {
			$this->cache[$modul] = array();
		}
		$this->cache[$modul][$key] = $value;
		return true;
	}

	/**
	 * Getmodule configuration within database
	 * If $key and $value is provided with NS the hole module configuration will be returnd
	 *
	 * if a module / key is not found it will return the default value
	 *
	 * @param string $modul
	 *   the module name
	 * @param mixed $key
	 *   the key as a string or if we want multiple keys provide an array with the database fields as the array values (optional, default = NS)
	 * @param mixed $default_value
	 *   the default value if the module/key not found (optional, default = NS)
	 * @param boolean $use_cache
	 *   if we want to use a static cache (for multi read outs within one request) (optional, default = false)
	 * @param boolean $strict_array
	 *   set to true if you want always an array as returning value, else if just one value is found it will return just the value (optional, default = false)
	 * @param boolean $serialize
	 *   set to true if you want to json_encode / json_decode the values (optional, default = false)
	 *
	 * @return mixed
	 * 		- return a single string value if $strict_array is set to false and just one value found
	 * 		- returns an array if $strict_array is set to true or it has more than one value
	 */
	public function get_dbconfig($modul, $key = NS, $default_value = NS, $use_cache = false, $strict_array = false, $serialize = false) {
		$value = $this->dbconfig($modul, $key, NS, $use_cache, $strict_array, $serialize);
		if ($value == null) {
			return $default_value;
		}

		// If we provided an array as default value we always want an array back for the value.
		if (is_array($default_value) && !is_array($value)) {
			// Transform the value to an array if it is not one.
			$value = array($value);
		}

		return $value;
	}

	/**
	 * Get or set module configuration within database
	 * If $key and $value is provided with NS the hole module configuration will be returnd
	 * $key NS and value NOT NS (set mode) is not provided
	 *
	 *
	 * @param string $modul
	 *   the module name
	 * @param mixed $key
	 *   the key as a string or if we want multiple keys provide an array with the database fields as the array values (optional, default = NS)
	 * @param mixed $value
	 *   the value (optional, default = NS)
	 * @param boolean $use_cache
	 *   if we want to use a static cache (for multi read outs within one request) (optional, default = false)
	 * @param boolean $strict_array
	 *   set to true if you want always an array as returning value, else if just one value is found it will return just the value (optional, default = false)
	 * @param boolean $serialize
	 *   set to true if you want to json_encode / json_decode the values (optional, default = false)
	 *
	 * @return mixed
	 * 	Get mode
	 * 		- return a single string value if $strict_array is set to false and just one value found
	 * 		- returns an array if $strict_array is set to true or it has more than one value
	 * 		- returns NULL if nothing is found.
	 *  Set mode
	 * 		- returns boolean true or false if the insert / update process within the database succeed or not
	 */
	public function dbconfig($modul, $key = NS, $value = NS, $use_cache = false, $strict_array = false, $serialize = false) {
		if ($this->config['db']['use'] === false) {
			return null;
		}
		if ($value === NS) {
			//Get mode
			if ($key === NS) {
				//Hole module config will be returned
				$where = " WHERE `modul` = '" . Db::safe($modul) . "'";

				$return = array();
				foreach ($this->db->query_slave_all("SELECT `value`, `key` FROM `" . CoreModulConfigObj::TABLE . "`" . $where) AS $v) {

					$json_test = json_decode($v['value'], true);
					if ($serialize === true || $json_test !== null) {
						$v['value'] = $json_test;
					}
					$return[$v['key']] = $v['value'];
				}
				return $return;
			}

			if (!is_array($key)) {
				$key = array($key);
			}

			//Return cached value if we want cache mode and the variable was already cached
			if ($use_cache === true && !is_null(($return = $this->cache($modul, implode("::", $key))))) {
				return $return;
			}

			//Build up all wanted options (sql where statement)
			$filter = DatabaseFilter::create(CoreModulConfigObj::TABLE, '', $this->db)
					->add_column('value')
					->add_column('key')
					->add_where('modul', $modul);

			$keys = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR, $this->db);
			foreach ($key AS $key_value) {
				$keys->add_where('key', $key_value);
			}

			$filter->add_where($keys);

			//Get the data from database
			$return = array();
			$rows = $filter->select_all();

			if (!empty($rows)) {
				foreach ($rows AS $row) {

					$json_test = json_decode($row['value'], true);
					if ($serialize === true || $json_test !== null) {
						$row['value'] = $json_test;
					}
					$return[$row['key']] = $row['value'];
				}

				//If we do not want a strict array and have just one value left, return just the value
				if ($strict_array === false && count($return) <= 1) {
					if ($use_cache === true) {
						$this->cache($modul, implode("::", $key), current($return));
					}
					return current($return);
				}
			}
			else {
				$return = null;
			}

			if ($use_cache === true) {
				$this->cache($modul, implode("::", $key), $return);
			}
			return $return;
		}

		if ($serialize === true || !is_scalar($value)) {
			$value = json_encode($value);
		}

		//Insert mode
		$return = $this->db->query_master("INSERT INTO `" . CoreModulConfigObj::TABLE . "` (`modul`, `key`, `value`) VALUES (@modul,@key,@value)
				ON DUPLICATE KEY UPDATE `value` = @value", array("@modul" => $modul, "@key" => $key, "@value" => $value));
		if ($return && $use_cache === true) {
			$this->cache($modul, $key, $value);
		}
		return $return;
	}

	/**
	 * Returns the mime type list.
	 * If the list is empty it will try to fill it up by loading the config/mime_types.php
	 *
	 * @return array the mime type list
	 */
	public function get_mime_types() {
		if (empty($this->mime_types)) {
			$this->load_mime_types();
		}
		return $this->mime_types;
	}

	public function load_mime_types() {
		if (empty($this->mime_types) && is_file(SITEPATH . '/config/mime_types.php')) {
			include SITEPATH . '/config/mime_types.php';
		}
	}

	/**
	 * Init Smarty and set the default template directory
	 */
	public function init_smarty() {
		$this->smarty = new Smarty();
		$this->smarty->enableSecurity(); //Can not be transformed into underscore couse this comes from original smarty class
		$this->smarty->init();
	}

	/**
	 * Reindex the alias menu
	 */
	public function reindex_menu() {
		//First clean the url alias table
		$this->db->query_master("DELETE FROM`" . UrlAliasObj::TABLE . "` WHERE type = 'module_menu'");

		//Loop through all modules
		foreach ($this->modules AS $module) {

			//Build up filepath to main module file
			$dir = SITEPATH . '/modules/' . $module . '/' . $module . '.php';

			//Check if the file exists
			if (file_exists($dir)) {
				//Initialize the module object
				include_once $dir;
				$obj = new $module();

				//Check if the menu method exist
				if (method_exists($obj, "menu")) {

					//Get the menu
					$menu_array = $obj->menu();
					if (!empty($menu_array) && is_array($menu_array)) {
						//Loop through the menu entries
						foreach ($menu_array AS $alias => $data) {

							//the array does not have a menu key, continue
							if (empty($data['menu'])) {
								continue;
							}

							//Transform a non array menu into an array
							if (!is_array($data['menu'])) {
								$data['menu'] = array($data['menu']);
							}

							//Setup the alias object with the menu values
							$alias_obj = new UrlAliasObj();
							$alias_obj->alias = $alias;
							$alias_obj->type = 'module_menu';
							if (isset($data['#perm'])) {
								$alias_obj->perm = $data['#perm'];
							}
							if (count($data['menu']) > 1) {
								$alias_obj->module = $data['menu'][0];
								$alias_obj->action = $data['menu'][1];
								unset($data['menu'][0]);
								unset($data['menu'][1]);
								if (!empty($data['menu'])) {
									$alias_obj->params = json_encode($data['menu']);
								}
							}
							else {
								$alias_obj->module = $module;
								$alias_obj->action = $data['menu'][0];
							}

							$alias_obj->insert();
						}
					}
				}
			}
		}
	}

	/**
	 * Assign the admin menu and if present the main_menu (id=main_menu) to smarty
	 */
	public function assign_menus() {

		$menu = array();
		if ($this->right_manager->has_perm("admin.user.show_admin_menu", false)) {
			$modules = $this->modules;
			$modules[] = "system";

			//loop through all modules
			foreach ($this->modules AS $module) {

				//If module is not enabled continue (system module is always be enabled)
				if ($module != "system" && !$this->module_enabled($module)) {
					continue;
				}

				//Load the module and check if it has the get_admin_menu method
				$module_obj = new $module();
				if (!method_exists($module_obj, "get_admin_menu")) {
					continue;
				}

				//Get the menu (key = order of this menu, value = menu_entries)
				$tmpmenu = $module_obj->get_admin_menu();
				if (empty($tmpmenu)) {
					continue;
				}
				$index = key($tmpmenu);
				//Get the menu entries for the menu
				$currentmenu = current($tmpmenu);


				//Check permissions if #perm key exists
				if (isset($currentmenu['#perm'])) {
					if (!$this->get_session()->is_logged_in() || !$this->get_right_manager()->has_perm($currentmenu['#perm'])) {
						continue;
					}
				}

				//Check permissions on subentries
				if (isset($currentmenu['#childs'])) {
					$this->unset_menu_entries_with_no_permission($currentmenu);
				}

				//Init the menu with the current order index
				if (!isset($menu[$index])) {
					$menu[$index] = array();
				}
				//Add the menu
				$menu[$index][] = $currentmenu;
			}

			ksort($menu);
			$this->smarty->assign_by_ref("admin_menu", $menu);
		}


		$current_module = $this->cache("core", "current_module");

		if (!empty($current_module)) {
			$menu_obj = new MenuObj($current_module);
			if (!$menu_obj->load_success()) {
				$menu_obj = new MenuObj("main_menu");
			}
		}
		else {
			$menu_obj = new MenuObj("main_menu");
		}

		$alter_menus = array();

		/**
		 * Provides hook: alter_menu
		 *
		 * Allow other modules to alter the menu entries for the menu id
		 *
		 * The returning array will just merged, so we can not remove other
		 * menu entries.
		 *
		 * @param string $menu_id
		 *   The menu id
		 *
		 * @return array
		 *   An array with a flat list of additional menu entries.
		 *   the tree will be parsed from parent_id which are based up on entry_id
		 */
		foreach ($this->hook('alter_menu', array('main_menu')) AS $alter_menu) {
			$alter_menus = array_merge_recursive($alter_menus, $alter_menu);
		}

		$this->smarty->assign_by_ref("main_menu", $menu_obj->get_menu_tree(true, $alter_menus));
	}

	/**
	 * Set the given $page as the redirect url on next page reload (useful for login system)
	 *
	 * @param string $page
	 *   the url
	 */
	public function request_next_time_refresh($page) {
		$this->session->set("next_load_redirect", $page);
	}

	/**
	 * Assign default variables to smarty
	 */
	public function smarty_assign_default_vars() {
		/**
		 * Provides hook: core_assign_default_vars
		 *
		 * Allow other modules to do things before all default vars will be assigned
		 * Usefull for adding javascript files for assign smarty values
		 */
		$this->hook('core_assign_default_vars');

		//Assign current template path
		$this->smarty->assign("SITEPATH", SITEPATH);
		$this->smarty->assign("request_uri", NetTools::get_request_uri());
		$this->smarty->assign("domain", $this->core_config('core', 'domain'));
		$this->js_config("domain", $this->core_config('core', 'domain'));
		$this->smarty->assign("TEMPLATE_PATH", $this->smarty->get_tpl());
		$this->js_config('template_path', $this->smarty->get_tpl());
		//Assign the user id if an user is logged in
		$current_user = $this->session->current_user();
		if (!empty($current_user)) {
			$this->smarty->assign("user_id", $current_user->user_id);
			$this->smarty->assign_by_ref("current_user", $this->session->current_user());
		}

		//If we have a translation object, assign language variables for javascript
		if ($this->lng) {
			$this->smarty->assign_json_no_reference("JS_LANG", $this->lng->get_all_js());
		}
		//Assign the current language
		$this->smarty->assign("current_language", $this->current_language);

		$this->js_config("current_language", $this->current_language);

		//get the configured profile, login and logout url for the current login handler
		$login_url = $this->session->get_login_url();
		$logout_url = $this->session->get_logout_url();
		$profile_url = $this->session->get_profile_url();

		$this->js_config("login_url", $login_url);
		$this->js_config("logout_url", $logout_url);

		//Assign meta object
		if (!empty($this->meta)) {
			$this->smarty->assign_by_ref("meta", $this->meta);
		}

		//Read out all previous setup messages and reset the session message array
		$messages = array();

		if (!empty($_SESSION['message'])) {
			$messages = array();
			if (isset($_SESSION['message']['error'])) {
				$messages['error'] = $_SESSION['message']['error'];
			}
			if (isset($_SESSION['message']['information'])) {
				$messages['information'] = $_SESSION['message']['information'];
			}
			if (isset($_SESSION['message']['success'])) {
				$messages['success'] = $_SESSION['message']['success'];
			}
			$_SESSION['message'] = array();
		}
		//Assign the messages
		$this->smarty->assign("main_messages", $messages);

		$this->cache_js_css();

		//Add needed css/js files
		$this->smarty->assign("additional_css_files", $this->css_files);
		$this->smarty->assign("additional_js_files", $this->js_files);

		//Add configured javascript configuration keys
		$this->smarty->assign_json("js_variable_config", $this->js_config);

		//If a user is currently logged in replace @userid and @username with the user information
		if ($this->get_session()->is_logged_in()) {
			$profile_url = str_replace("@userid", $this->get_session()->current_user()->user_id, $profile_url);
			$profile_url = str_replace("@username", $this->get_session()->current_user()->username, $profile_url);
		}

		//Assign the logout and profile url
		$this->smarty->assign_by_ref("logout_url", $logout_url);
		$this->smarty->assign_by_ref("profile_url", $profile_url);
		$this->smarty->assign_by_ref("login_url", $login_url);

		if (!empty($this->lng)) {
			$this->lng->load_language_list('', array(), true);
			$current_module = $this->cache("core", "current_module");
			if (empty($current_module)) {
				list($url) = explode('?', $_SERVER['REQUEST_URI'], 2);
				$base_link = '/' . preg_replace('/^\/[a-z]{2}(\/|$)/is', '', $url);
			}
			else {
				$base_link = '';
			}

			$languages = $this->lng->languages;
			foreach ($languages AS $key => &$language) {
				if (empty($current_module)) {
					$link = '/' . strtolower($key) . $base_link;
				}
				else {
					$mod_obj = new $current_module();
					$link = $mod_obj->get_translation_link($key);
				}
				$language = array(
					'language' => $language,
					'link' => preg_replace('/\/$/', '', preg_replace('/\/+/', '/', $link))
				);
			}
		}
		if ($this->session->is_logged_in()) {
			$this->smarty->assign("user_fullname", $this->session->current_user()->username);
		}

		if (!empty($this->lng)) {
			$this->smarty->assign_by_ref("enabled_languages", $languages);
			$this->lng->load_language_list();
		}

		// Assign registered widgets
		$this->smarty->assign_by_ref('core_widgets', $this->widgets);
	}

	/**
	 * Add a message
	 * The message will be shown if the next time a html output occures, so if a pure ajax request
	 * will be started it will not clear the message queue couse it would not be visible to the user.
	 *
	 * We can provide the $ajax parameter if we want to display an alert box with the error message
	 * if the ajax get an error
	 *
	 * With $ajax set to true be aware it will print out ALL messages which are previous queued, the returning message type
	 * will be the most worst one, so if 10 times a message was a success and want time an notice will be added the information
	 * state is more bad than the success so it will get this state at the end.
	 *
	 * Order: success -> notice -> error
	 *
	 * @param string $msg
	 *   the message
	 * @param string $type
	 *   use one of Core::MESSAGE_TYPE_* (optional, default = Core::MESSAGE_TYPE_SUCCES)
	 * @param boolean $ajax
	 *   if this is set to true it will be handled as an AjaxReturn (optional, default = false)
	 * @param mixed $ajax_data
	 *   if $ajax is set to true we can provide additional data send back to the browser within the current ajax request (optional, default = null)
	 */
	public function message($msg, $type = self::MESSAGE_TYPE_SUCCESS, $ajax = false, $ajax_data = null) {

		//Check if we are in ajax mode
		if ($ajax) {

			//Initialize the most bad message type variable
			$most_bad_type = "";

			//Check if we have error's
			if (!empty($_SESSION['message']['error'])) {
				//Get all previous errors and append it to the message
				$msg = implode("\n", $_SESSION['message']['error']) . "\n" . $msg;

				//set the most bad message type
				$most_bad_type = AjaxModul::ERROR_DEFAULT;

				//Clear all current error messages
				unset($_SESSION['message']['error']);
			}

			//Check if we have notices's
			if (!empty($_SESSION['message']['notice'])) {
				//Get all previous notices and append it to the message
				$msg = implode("\n", $_SESSION['message']['notice']) . "\n" . $msg;

				//set the most bad message type only if the previous higher type was not set
				if (empty($most_bad_type)) {
					$most_bad_type = self::MESSAGE_TYPE_SUCCESS;
				}

				//Clear all current notice messages
				unset($_SESSION['message']['notice']);
			}

			//Check if we have success's
			if (!empty($_SESSION['message']['success'])) {
				//Get all previous success's and append it to the message
				$msg = implode("\n", $_SESSION['message']['success']) . "\n" . $msg;

				//set the most bad message type only if the previous higher type was not set
				if (empty($most_bad_type)) {
					$most_bad_type = Core::MESSAGE_TYPE_SUCCESS;
				}

				//Clear all current success messages
				unset($_SESSION['message']['success']);
			}

			//Set the current message type only if we have not set it previous so the given message type is the most bad one
			if (empty($most_bad_type)) {
				$most_bad_type = $type;
			}

			//Switch the message type, based upon the message type we set the returning ajax type
			switch ($type) {
				case self::MESSAGE_TYPE_SUCCESS:
					$most_bad_type = Core::GLOBEL_RETURN_CODE_SUCCESS;
					break;
				default:
					$most_bad_type = AjaxModul::ERROR_DEFAULT;
					break;
			}

			//Return the ajax message, this function dies at this moment.
			AjaxModul::return_code($most_bad_type, $ajax_data, true, $msg);
		}

		//Check if we have an message array, if not create it
		if (!isset($_SESSION['message'])) {
			$_SESSION['message'] = array();
		}

		//Check if the given type is already within the message type array, if not create it with an empty array
		if (!isset($_SESSION['message'][$type])) {
			$_SESSION['message'][$type] = array();
		}

		//Add the message if we are in html mode, on shell mode we call console_log
		if (!defined('is_shell')) {
			$_SESSION['message'][$type][] = $msg;
		}
		else {
			CliHelper::console_log($msg, $type);
		}
	}

	/**
	 * Checks if a the given $module is enabled or not.
	 *
	 * @param string $module
	 *   The module to be checked
	 * @param boolean $set_value
	 *   if value is provided it will set the cache, else it will return the
	 *   module status, the $set_value will be used within ModulConfigObj::save
	 *   to have the current state of the module if we changed it somewhere.
	 * 	 (optional, default = null)
	 *
	 * @return mixed
	 *   - in GET-Mode, boolean true if enabled, else boolean false
	 *   - in SET-Mode returns NULL
	 */
	public function module_enabled($module, $set_value = null) {
		static $cache = array();

		if ($module === 'system') {
			return true;
		}
		// Set the cached value if we provide a $set_value
		if ($set_value !== null) {
			$cache[$module] = $set_value;
			return null;
		}

		//Check db/memcached only once.
		if (!isset($cache[$module])) {
			//If module is unknown, return false
			if (!isset($this->modules[$module])) {
				$cache[$module] = false;
				return false;
			}

			if (empty($this->db)) {
				$cache[$module] = false;
				return false;
			}
			//Load the module config of the given $module
			$modconfig = new ModulConfigObj($module);

			//If module is not installed return false
			if (!$modconfig->load_success()) {
				$cache[$module] = false;
				return false;
			}

			//Set cahe if it is enabled or not
			$cache[$module] = ($modconfig->enabled == 1);
		}

		//Return if it is enabled or not
		return $cache[$module];
	}

	/**
	 * Enable or disable debug mode
	 *
	 * @param boolean $val
	 *   true for enable, else false
	 */
	public function set_debug($val) {
		$this->debug = $val;
		$this->db->set_debug($val);
		$this->smarty->debugging = true;
	}

	/**
	 * Returns the current debug mode
	 *
	 * @return boolean true if debug is enabled, else false
	 */
	public function get_debug() {
		return $this->debug;
	}

	/**
	 * Returns the current session object by reference
	 *
	 * @return Session the session
	 */
	public function &get_session() {
		return $this->session;
	}

	/**
	 * Returns the right manager object by reference
	 *
	 * @return RightManager the right manager
	 */
	public function &get_right_manager() {
		return $this->right_manager;
	}

	/**
	 * Redirects direct to the given Location through header information
	 * If more than one slashes in series appears it will be replaced with just one (url cleanup)
	 *
	 * @param string $string
	 *   The location to be redirected
	 */
	public function location($string) {
		$scheme = "";
		if (preg_match("/^(https?:\/\/)(.*)/is", $string, $matches)) {
			$scheme = $matches[1];
			$string = $matches[2];
		}
		header("Location: " . $scheme . preg_replace("/\/+/is", "/", $string));
		exit();
	}

	/**
	 * Request a redirect through javascript with the given timeout
	 * It will provide smarty the variable $header_redirect which is array with configuration
	 * if this redirect request.
	 * The template ("standard/header.tpl") will check if this variable exist and creates
	 * a javascript timeout with the redirection.
	 *
	 * @param string $location
	 *   the location to be redirected
	 * @param int $seconds
	 *   the number of seconds to wait before redirecting (optional, default = 5)
	 *
	 * @return boolean true if request succeed, else false
	 */
	public function request_redirect($location, $seconds = 5) {
		if (empty($this->smarty)) {
			return false;
		}
		$this->smarty->assign("header_redirect", array("location" => $location, "timeout" => ($seconds * 1000)));
		return true;
	}

	/**
	 * Provide javascript the $value parameter within the Javascript variable Soopfw.config.{$var}
	 *
	 * If $as_array is set to true the config key given in $var will not be just set, it will first check
	 * if an entry already exists which is not an array if so it will be transformed to array($value)
	 * Then it will add all future calls just to the config key array
	 * For example:
	 * js_config('foo','bar', true);
	 * js_config('foo','bar2', true);
	 *
	 * Javascript will have
	 * Soopfw.config.foo[0] = 'bar';
	 * Soopfw.config.foo[1] = 'bar2';
	 *
	 * example of transforming previous added non-array value
	 *
	 * js_config('foo','bar'); => Soopfw.config.foo = 'bar';
	 * js_config('foo','bar2', true);
	 *
	 * will transform it to:
	 * Soopfw.config.foo[0] = 'bar';
	 * Soopfw.config.foo[1] = 'bar2';
	 *
	 * using array_key
	 *
	 * js_config('foo','bar2', true, 'id1');
	 * js_config('foo','bar2', true, 'id2');
	 *
	 * will produce:
	 * Soopfw.config.foo['id1'] = 'bar2';
	 * Soopfw.config.foo['id2'] = 'bar2';
	 *
	 * @param string $var
	 *   The variable name for the config key.
	 * @param mixed $value
	 *   Any variable which should be provided within javascript Soopfw.config.*
	 * @param boolean $as_array
	 *   If the value should be added as a config array (optional, default = false)
	 * @param string $array_key
	 *   if $as_array is set to true this value will be used for the array key.
	 *   if left empty a numeric array push will be used (optional, default = null)
	 */
	public function js_config($var, $value, $as_array = false, $array_key = null) {
		//Check if we want to just set the value to the config key or if we want
		//to add it to an config array
		if ($as_array == false) {  //Set
			$this->js_config[$var] = $value;
		}
		else { //We want to add an value to the specified config array
			//If key not exists create it
			if (!isset($this->js_config[$var])) {
				$this->js_config[$var] = array();
			}
			//If exists but is not an array, transform it to an array
			else if (!is_array($this->js_config[$var])) {
				$this->js_config[$var] = array($this->js_config[$var]);
			}

			//Add the value to the config array
			if (is_null($array_key)) {
				$this->js_config[$var][] = $value;
			}
			else {
				$this->js_config[$var][$array_key] = $value;
			}
		}
	}

	/**
	 * Returns the current javascript configuration keys
	 *
	 * @return array the javascript config array
	 */
	public function get_js_config() {
		return $this->js_config;
	}

	/**
	 * Load a css file (full path after docroot)
	 *
	 * @param string $file
	 *   The filepath to the css file which should be loaded
	 * @param boolean $is_full_path
	 *   if set to true it will not add the current template path to the file.
	 *   those files can not be overridden (optional, default = false)
	 */
	public function add_css($file, $is_full_path = false) {

		if ($is_full_path == true) {
			$this->css_files[] = $file;
			return;
		}

		$check_template_file = $this->smarty->get_tpl() . $file;

		$module = $this->cache("core", "current_module");
		if (!empty($module) && !empty($action) && file_exists(SITEPATH . '/' . $module . '/templates/' . $file) && is_readable(SITEPATH . '/' . $module . '/templates/' . $file)) {
			$file = '/' . $module . '/templates/' . $file;
		}
		elseif (file_exists(SITEPATH . $check_template_file) && is_readable(SITEPATH . $check_template_file)) {
			$file = $check_template_file;
		}

		if (file_exists(SITEPATH . $file)) {
			$this->css_files[] = $file;
		}
	}

	/**
	 * Load a javascript file (full path after docroot)
	 *
	 * @param string $file
	 *   The filepath to the javascript file which should be loaded
	 * @param string $type
	 *   The javascript scope, use one of Core::JS_SCOPE_* (optional, default = Core::JS_SCOPE_USER)
	 * @param boolean $is_full_path
	 *   if set to true it will not add the current template path to the file.
	 *   those files can not be overridden (optional, default = false)
	 */
	public function add_js($file, $type = self::JS_SCOPE_USER, $is_full_path = false) {
		if (!isset($this->js_files[$type])) {
			$this->js_files[$type] = array();
		}

		if ($is_full_path == true) {
			$this->js_files[$type][$file] = $file;
			return;
		}

		$check_template_file = $this->smarty->get_tpl() . $file;

		$module = $this->cache("core", "current_module");
		if (!empty($module) && !empty($action) && file_exists(SITEPATH . '/' . $module . '/templates/' . $file) && is_readable(SITEPATH . '/' . $module . '/templates/' . $file)) {
			$file = '/' . $module . '/templates/' . $file;
		}
		elseif (file_exists(SITEPATH . $check_template_file) && is_readable(SITEPATH . $check_template_file)) {
			$file = $check_template_file;
		}
		if (file_exists(SITEPATH . $file)) {
			$this->js_files[$type][] = $file;
		}
	}

	/**
	 * Adds a additional template file.
	 * The module action which want this widget must call the
	 * Framework smarty function <%get_widget type='type'%> where type is optional
	 * if type is not provided it will get all widgets at this point which the action
	 * registered.
	 *
	 * @param string $name
	 *   the unique name for this widget
	 * @param string $file
	 *   The filepath to the widget template file
	 */
	public function register_widget($name, $file) {
		$this->widgets[$name] = $file;
	}

	/**
	 * This method will call all enabled modules for the specific hook.
	 *
	 * A hook is defined if a method exists hook_{hookname}
	 *
	 * @param string $name
	 * 	 The hookname
	 * @param array $args
	 * 	 an array with all arguments which will be passed to the hooks
	 *   (optional, default = array())
	 */
	public function hook($name, $args = array()) {
		static $cache = null;
		static $loop_detection = array();

		if (isset($loop_detection[$name])) {
			return array();
		}

		$loop_detection[$name] = true;

		if ($cache == null) {
			$cache = array();
			foreach ($this->modules AS $module) {
				if (class_exists($module)) {
					$class = new ReflectionClass($module);
					$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
					foreach ($methods AS $method) {
						if (preg_match("/^hook_(.*)$/", $method->name, $match)) {
							$hook_name = $match[1];
							if (!isset($cache[$hook_name])) {
								$cache[$hook_name] = array();
							}
							$cache[$hook_name][$module] = $module;
						}
					}
				}
			}
		}

		if (!isset($cache[$name])) {
			unset($loop_detection[$name]);
			return array();
		}

		$hook_method = "hook_" . $name;
		$return_results = array();
		foreach ($cache[$name] AS $module) {
			$return_results[$module] = call_user_func_array(array(new $module(), $hook_method), $args);
		}

		unset($loop_detection[$name]);
		return $return_results;
	}

	/**
	 * Unset all menu entries where the user should not see based up on the #perm permission check.
	 * Function is recrusive.
	 *
	 * @param array &$menu
	 *   the menu array
	 */
	private function unset_menu_entries_with_no_permission(&$menu) {
		//Check if we have childs
		if (isset($menu['#childs']) && is_array($menu['#childs'])) {

			//Found childs, loop through it
			foreach ($menu['#childs'] AS $k => &$subchild) {
				//If a #perm entry is configured, check if the user is logged in and have the right, else unset the entry
				if (isset($subchild['#perm'])) {
					if (!$this->get_session()->is_logged_in() || !$this->get_right_manager()->has_perm($subchild['#perm'])) {
						unset($menu['#childs'][$k]);
						continue;
					}
				}

				//recrusive call with subchilds
				$this->unset_menu_entries_with_no_permission($subchild);
			}
		}
	}

	/**
	 * Checks if the direct or an child entry matches to the $checklink, if so return true
	 *
	 * @param string $checklink
	 *   the link which we want to check
	 * @param array $menu
	 *   the menu array
	 *
	 * @return boolean true if selected, else false
	 */
	private function check_if_menu_subentry_is_selected($checklink, $menu) {
		if (isset($menu['#link']) && $menu['#link'] == $checklink) {
			return true;
		}
		if (isset($menu['childs']) && is_array($menu['childs'])) {
			if ($this->check_if_menu_subentry_is_selected($checklink, $menu['childs'])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Cache javascript files if wanted and removes all cached js file loads and replace it with the cached one
	 */
	private function cache_js_css() {

		//If caching disabled by system configuration, do not cache, default caching is disabled
		$should_cache_js = $this->dbconfig("system", system::CONFIG_CACHE_JS);
		$should_cache_css = $this->dbconfig("system", system::CONFIG_CACHE_CSS);

		if (!empty($should_cache_css) && $should_cache_css == 'yes' && !empty($this->css_files)) {
			//This variable holds the last modified file within the current scope
			$scope_latest_modified_file = 0;

			//This will have a md5 hash of all files which we want to load so the same loaded files will get the same md5 unique hash
			$md5_check = "";

			//This will be the end array which files will be loaded
			$files_to_add = array();

			//Init yui compressor
			$yui = new YuiCompressor();

			$original_css_files = $this->css_files;
			foreach ($this->css_files AS $file) {

				//Get just the filename without get params
				$file = preg_replace("/\?.+$/", "", $file);

				//Get the current absolute filepath to the javascript file
				$file_path = SITEPATH . $file;

				// Skip if file does not exists.
				if (!file_exists($file_path)) {
					continue;
				}

				//Append the filepath to the md5 sum
				$md5_check .= $file_path;

				//Skip already minified files, but add them to the load list
				if (preg_match("/\.min\./", $file)) {
					$files_to_add[] = $file;
					continue;
				}

				/**
				 * Get the last modified filetime for this file and check if it is newer
				 * than the old newest modified file time, so at the end we have the newest
				 * modified file time
				 */
				$modified_time = filemtime($file_path);
				if ($modified_time > $scope_latest_modified_file) {
					$scope_latest_modified_file = $modified_time;
				}
			}

			//Get the md5 sum of the file loads
			$md5_check = md5($md5_check);

			//get the cache file path
			$cache_file_path = "/uploads/css_cache/" . $md5_check . ".css";

			$cache_css_file = true;
			//Check if already the cache file exists
			if (file_exists(SITEPATH . $cache_file_path)) {

				//Get the last modified time of the cache file
				$cache_last_modified = filemtime(SITEPATH . $cache_file_path);

				//If the modified file times equals the cached file is up to date, so we can skip this scope
				if ($scope_latest_modified_file == $cache_last_modified) {
					$this->css_files = array($cache_file_path . '?' . $cache_last_modified);
					$cache_css_file = false;
				}
			}

			if ($cache_css_file == true) {
				//At this point we need to generate the cache file

				foreach ($original_css_files AS $file) {
					$search_replace = array();

					if (!file_exists(SITEPATH . $file)) {
						continue;
					}

					$src = file_get_contents(SITEPATH . $file);
					preg_match_all('~\bbackground(-image)?\s*:(.*?)url\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', $src, $matches);

					if (!empty($matches['image'])) {
						$path = dirname($file);
						foreach ($matches['image'] AS $image_url) {
							$search_replace[$image_url] = str_replace('//', '/', $path . '/' . $image_url);
						}
						ksort($search_replace);
						foreach ($search_replace as $s => $r) {
							$src = preg_replace("/\((\"|')?" . preg_quote($s, "/") . "(\"|')?\)/", "(\$1" . $r . "\$2)", $src);
						}
					}

					$yui->add_string($src);
				}

				//Create needed directories for storing cache files
				if (!file_exists(SITEPATH . '/uploads/css_cache')) {
					mkdir(SITEPATH . '/uploads/css_cache', 0777);
				}

				$append_content = array();
				foreach ($files_to_add AS $append_files) {
					$append_content[] = file_get_contents(SITEPATH . $append_files);
				}

				//compress the wanted files and write the compressed output to the cache file
				file_put_contents(SITEPATH . $cache_file_path, implode("\n", $append_content) . "\n" . $yui->compress('css'));

				//Set the last modified time of the cache file to our newest modified file from our scope
				touch(SITEPATH . $cache_file_path, $scope_latest_modified_file);

				//set the cache file to be loaded instead of the original not compressed one.
				$this->css_files = array($cache_file_path . '?' . $scope_latest_modified_file);
			}
		}

		if (!empty($should_cache_js) && $should_cache_js == 'yes' && !empty($this->js_files)) {
			//Loop through every file scope
			foreach ($this->js_files AS $scope => $file_array) {

				//This variable holds the last modified file within the current scope
				$scope_latest_modified_file = 0;

				//This will have a md5 hash of all files which we want to load so the same loaded files will get the same md5 unique hash
				$md5_check = "";

				//This will be the end array which files will be loaded
				$files_to_add = array();

				//Init yui compressor
				$yui = new YuiCompressor();

				//Loop through all js files which we want to load within the current scope
				foreach ($file_array AS $original_javascript_file) {

					//Get just the filename without get params
					$original_javascript_file = preg_replace("/\?.+$/", "", $original_javascript_file);

					//Get the current absolute filepath to the javascript file
					$file_path = SITEPATH . $original_javascript_file;

					//Append the filepath to the md5 sum
					$md5_check .= $file_path;

					//Skip already minified files, but add them to the load list to append it
					if (preg_match("/\.min\./", $original_javascript_file)) {
						$files_to_add[] = $original_javascript_file;
						continue;
					}

					/**
					 * Get the last modified filetime for this file and check if it is newer
					 * than the old newest modified file time, so at the end we have the newest
					 * modified file time
					 */
					$modified_time = filemtime($file_path);
					if ($modified_time > $scope_latest_modified_file) {
						$scope_latest_modified_file = $modified_time;
					}
					//Add the js file to the yui to compress it if needed
					$yui->add_file($file_path);
				}
				//Get the md5 sum of the file loads
				$md5_check = md5($md5_check);

				//get the cache file path
				$cache_file_path = "/uploads/js_cache/" . $md5_check . ".js";

				//Check if already the cache file exists
				if (file_exists(SITEPATH . $cache_file_path)) {

					//Get the last modified time of the cache file
					$cache_last_modified = filemtime(SITEPATH . $cache_file_path);

					//If the modified file times equals the cached file is up to date, so we can skip this scope
					if ($scope_latest_modified_file == $cache_last_modified) {
						$this->js_files[$scope] = array($cache_file_path . '?' . $scope_latest_modified_file);
						continue;
					}
				}

				//At this point we need to generate the cache file
				//
				//Create needed directories for storing cache files
				if (!file_exists(SITEPATH . '/uploads/js_cache')) {
					mkdir(SITEPATH . '/uploads/js_cache', 0777);
				}

				// Generate the append content to the cache file.
				$append_content = array();
				foreach ($files_to_add AS $append_files) {
					$append_content[] = file_get_contents(SITEPATH . $append_files);
				}

				//compress the wanted files and write the compressed output and the appended content to the cache file
				file_put_contents(SITEPATH . $cache_file_path, implode("\n", $append_content) . "\n" . $yui->compress());

				//Set the last modified time of the cache file to our newest modified file from our scope
				touch(SITEPATH . $cache_file_path, $scope_latest_modified_file);

				//set the cache file to be loaded instead of the original not compressed one.
				$files_to_add[] = $cache_file_path;
				$this->js_files[$scope] = array($cache_file_path . '?' . $scope_latest_modified_file);
			}
		}
	}

}


