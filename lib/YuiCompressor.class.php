<?php

/**
 * Provides a php class wrapper to generate compressed files with yui compressor
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class YuiCompressor
{

	/**
	 * The yui compressor version
	 *
	 * @var string
	 */
	private $yui_version = '2.4.7';

	/**
	 * Option array
	 * @var array
	 */
	private $options = array(
		'linebreak' => false,
		'verbose' => false,
		'nomunge' => false,
		'semi' => false,
		'nooptimize' => false
	);

	/**
	 * Holds all files which we want to compress
	 *
	 * @var array
	 */
	private $files = array();

	/**
	 * Holds a custom string which will be compressed to
	 *
	 * @var string
	 */
	private $string = '';

	/**
	 * Construct
	 *
	 * @param array $options
	 *   The options. (optional, default = array())
	 */
 	public function __construct($options = array()) {
		foreach ($options as $option => $value) {
			$this->set_option($option, $value);
		}
	}

	/**
	 * Set one of the YUI compressor options.
	 *
	 * @param string $option
	 *   The option key.
	 * @param mixed $value
	 *   The value.
	 */
	public function set_option($option, $value) {
		$this->options[$option] = $value;
	}

	/**
	 * Add a file to be compressed.
	 *
	 * @param string $file
	 *   The absolute file path.
	 */
	public function add_file($file) {
		$this->files[] = $file;
	}

	/**
	 * Append a string to the custom string which will be also compressed
	 *
	 * @param string $string
	 *   The string
	 */
	public function add_string($string) {
		$this->string .= ' '.$string;
	}

	/**
	 * Returns the compressed string
	 *
	 * @return string the compressed string
	 */
	public function compress($type = 'js') {

		//Get all contents from added files
		foreach ($this->files as $file) {
			$this->string .= file_get_contents($file) or die("Cannot read from uploaded file");
			$this->string .= "\n";

		}

		// create single file from all input
		$input_hash = sha1($this->string);

		//Write the hole string to a temporary file
		$file = SITEPATH.'/uploads/'.$input_hash.'.txt';
		$fh = fopen($file, 'w') or die("Can't create new file");
		fwrite($fh, $this->string);

		// start with basic command
		$cmd = "java -Xmx128m -jar ".SITEPATH."/plugins/yuicompressor-".$this->yui_version.".jar ".$file." --charset UTF-8";

		// set the file type
		$cmd .= " --type ".(strtolower($type) == "css" ? "css" : "js");

		// and add options as needed
		if ($this->options['linebreak'] && (int)$this->options['linebreak'] > 0) {
			$cmd .= ' --line-break '.(int)$this->options['linebreak'];
		}

		if ($this->options['verbose']) {
			$cmd .= " -v";
		}

		if ($this->options['nomunge']) {
			$cmd .= ' --nomunge';
		}

		if ($this->options['semi']) {
			$cmd .= ' --preserve-semi';
		}

		if ($this->options['nooptimize']) {
			$cmd .= ' --disable-optimizations';
		}

		// execute the command
		exec($cmd.' 2>&1', $raw_output);

		// add line breaks to show errors in an intelligible manner
		$flattened_output = implode("\n", $raw_output);

		// clean up (remove temp file)
		if (file_exists($file)) {
			unlink($file);
		}

		// return compressed output
		return $flattened_output;
	}

}


