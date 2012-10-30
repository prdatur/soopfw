<?php

/**
 * RSS-Feed generator
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class RSSGenerator extends Object {

	/**
	 * Holds all elements for the feed.
	 *
	 * @var array
	 */
	private $entries = array();

	/**
	 * Provides all main information for the rss feed.
	 * @var array
	 */
	private $info = array();

	/**
	 * Construct.
	 *
	 * @param string $title
	 *   the title
	 * @param string $description
	 *   the description text
	 * @param string $website
	 *   the link to the main website
	 *   if not provided it will use the non-ssl configured domain within core config
	 *   (optional, default = "")
	 * @param string $language
	 *   the language in form of a locale string
	 *   for example: de-DE
	 *   if not provided it will use the current language for the locale (optional, default = "")
	 * @param string $date
	 *   the date within form: D, d M Y H:i:s
	 *   if not provided it will use current date (optional, default = "")
	 */
	public function __construct($title, $description, $website = "", $language = "", $date = "") {
		parent::__construct();

		if (empty($date)) {
			$date = gmdate("D, d M Y H:i:s");
		}

		if (empty($language)) {
			$language = $this->lng->get_locale();
		}

		if (empty($website)) {
			$website = 'http://' . $this->core->core_config('core', 'domain');
		}

		$this->info = array(
			'title' => $title,
			'description' => $description,
			'link' => $website,
			'language' => $language,
			'pubDate' => $date,
		);
	}
	/**
	 * Add an entry.
	 *
	 * @param string $title
	 *   the entry title
	 * @param string $description
	 *   the description
	 * @param string $url
	 *   the url to the entry
	 *   if it is not an absolute url including http(s)://
	 *   it will prepend the default configured core domain with non-ssl
	 * @param string $date
	 *   the publish date from the entry form: D, d M Y H:i:s
	 */
	public function add($title, $description, $url, $date) {
		if (!preg_match("/^https?:\/\//", $url)) {
			$url = 'http://' . $this->core->core_config('core', 'domain') . $url;
		}
		$this->entries[] = array(
			'title' => $title,
			'description' => $description,
			'link' => $url,
			'pubDate' => $date,
		);
	}

	/**
	 * Returns the rss feed string.
	 *
	 * @return string the generated RSS-String
	 */
	public function generate() {

		$lines = array();
		$lines[] = '<?xml version="1.0" encoding="utf-8"?>';
		$lines[] = '<rss version="2.0">';
		$lines[] = '	<channel>';
		foreach ($this->info AS $key => $value) {
			$lines[] = '		<' . $key . '>' . $value . '</' . $key . '>';
		}
		foreach ($this->entries AS $entry) {
			$lines[] = '		<item>';
			foreach ($entry AS $key => $value) {
				$lines[] = '			<' . $key . '>' . $value . '</' . $key . '>';
			}
			$lines[] = '		</item>';
		}

		$lines[] = '	</channel>';
		$lines[] = '</rss>';

		return implode("\n", $lines);
	}

	/**
	 * Display the rss feed
	 */
	public function display() {
		$data = $this->generate();
		header('Content-type: application/xml');
		header("Expires: " . gmdate("D, d M Y H:i:s", time() + (60 * 60 * 24)) . " GMT+1");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT+1");
		header("Content-Length: " . strlen($data));
		echo $data;
		exit();
	}
}