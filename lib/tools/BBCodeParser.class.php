<?php

/**
 * Provides a class to transform bb code tags into html.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class BBCodeParser
{
	/**
	 * Define the highest values which can be used in img tags: [img=height, width]url[/img] and [img=width]url[/img]
	 */
	const IMG_MAX_WIDTH = 400;
	const IMG_MAX_HEIGHT = 400;

	/**
	 * Holds all allowed bbcodes.
	 *
	 * @var array
	 */
	private $allowed_tags = array(
		// Dummy but need to exist.
		'' => array('', ''),
		// Direct parser.
		'table' => array('<table class="default_table ui-widget ui-widget-content">', '</table>'),
		'th' => array('<th>', '</th>'),
		'tr' => array('<tr>', '</tr>'),
		'td' => array('<td>', '</td>'),
		'i' => array('<i>', '</i>'),
		'u' => array('<u>', '</u>'),
		's' => array('<s>', '</s>'),
		'b' => array('<b>', '</b>'),
		'hr' => array('<hr>', '</hr>'),
		'h1' => array('<h1>', '</h1>'),
		'h2' => array('<h2>', '</h2>'),
		'h3' => array('<h3>', '</h3>'),
		'h4' => array('<h4>', '</h4>'),
		'h5' => array('<h5>', '</h5>'),
		'h6' => array('<h6>', '</h6>'),
		'sup' => array('<sup>', '</sup>'),
		'sub' => array('<sub>', '</sub>'),
		'small' => array('<small>', '</small>'),
		'big' => array('<big>', '</big>'),
		'code' => array('<pre>', '</pre>'),
		'list' => array('<ul>', '</ul>'),
		'ul' => array('<ul>', '</ul>'),
		'li' => array('<li>', '</li>'),
		'ol' => array('<ol>', '</ol>'),
		'bold' => array('<b>', '</b>'),
		'quote' => array('<blockquote>', '</blockquote>'),
		'center' => array('<div style="text-align: center;">', '</div>'),
		// Method parser.
		'sourcecode' => 'parse_sourcecode',
		'url' => 'parse_url',
		'img' => 'parse_img',
		'size' => 'parse_size',
		'color' => 'parse_color',
		'mail' => 'parse_mail',
		'email' => 'parse_mail',
	);

	/**
	 * Holds a list of bb tags where the inner can not be escaped.
	 *
	 * @var array
	 */
	private $plain_text_tags = array(
		'code' => true,
		'sourcecode' => true,
	);

	/**
	 * This string is used to generate a unique id which is used for skipping tags.
	 *
	 * @var string
	 */
	private $unique_id_open = '';

	/**
	 * This string is used to generate a unique id which is used for skipping tags.
	 *
	 * @var string
	 */
	private $unique_id_close = '';

	/**
	 * Construct, will generate uuid's which can be safely used to escape disallowed bbcodes.
	 */
	public function __construct() {
		$uid = uniqid();
		$this->unique_id_open = '|-' . $uid . '|';
		$this->unique_id_close = '|' . $uid . '-|';
	}

	/**
	 * Transforms a BB-Code text into html.
	 *
	 * @param string $text
	 *   The bbcode encoded string.
	 *
	 * @return string The parsed html.
	 */
	public function parse($text) {
		// Parse our text, back replace our escaped "[" and "]" then return the html.
		$s = array($this->unique_id_open, $this->unique_id_close);
		$r = array('[', ']');
		return str_replace($s, $r, $this->parse_tag("", nl2br($text)));
	}

	/**
	 * Parses recrusive all tags.
	 *
	 * @param string|array $tag_opening
	 *   The full opening tag string or a already parsed tag data array.
	 * @param string $text
	 *   The inner text for the tag (till the end).
	 * @param array &$open_tags
	 *   DONT PROVIDE.
	 *   Will hold all current opened tags, internal usage only (optional, default = array())
	 * @param boolean $skip
	 *   DONT PROVIDE.
	 *   Will determine that we want to not parse any tags until the closing tag for the original opening tag was found,
	 *   internally usage only. (optional, default = false)	 *
	 *
	 * @return string The parsed string.
	 */
	private function parse_tag($tag_opening, $text, &$open_tags = array(), $skip = false) {

		// Get the tag data, if $tag_opening is already a parsed one use this, else parse the tag string.
		$main_tag = (is_array($tag_opening)) ? $tag_opening : $this->get_tag_data($tag_opening);

		// Setup needed variables.
		$tag_open = $main_tag['tag'];
		$parameters = $main_tag['params'];

		// If the current tag is not empty (first run) mark it as "open".
		if (!empty($tag_opening)) {
			if (!isset($open_tags[$tag_open])) {
				$open_tags[$tag_open] = array();
			}
			$open_tags[$tag_open][] = true;
		}

		// Override/Set $skip only if we currently are not skipping.
		if ($skip === false) {
			// If current tag is a plain text tag where we do not want to parse data within the inner html,
			// set to skip it unless we found the original closing tag.
			$skip = isset($this->plain_text_tags[$tag_open]);
		}

		$tag = array();

		// Process next tag.
		while (($pos = strpos($text, '[')) !== false) {

			// Get the end position for this tag.
			$end_pos = strpos($text, "]");

			// If no end position found within left text we must direct return that this text is not a bb tag.
			if ($end_pos === false) {
				// Escape the found [-char and return the text before and after [
				return substr($text, 0, $pos) . $this->unique_id_open . substr($text, $pos + 1);
			}

			// Pre-get the tag to check if we have a not a broken tag.
			$tag = substr($text, $pos + 1, $end_pos - $pos - 1);

			// Check that no starting [ are present within the tag, if it exist skip the current wrong tag.
			if (($wrong_start_bracket = strpos($tag, "[")) !== false) {
				// Escape the found [-char and return the text before and after [
				$text = substr($text, 0, $pos) . $this->unique_id_open . substr($text, $pos + 1);
				continue;
			}

			// Build up tag info array.
			$tag = array(
				1 => substr($text, 0, $pos),
				2 => $tag,
				3 => substr($text, $end_pos + 1),
			);
			
			// Found matching end-tag.
			if (isset($tag[2]{0}) && $tag[2]{0} == '/') {

				// Preinit return value because below we use .= notation.
				$return = "";

				// Check if current opened main tag is the tag we found for closing.
				if ($tag[2] != '/' . $tag_open) {
					// Tag did not equals, lets try to reconstruct our code.
					// First get the opening simple tag for the found closing one (just without the /).
					$opening_tag_for_closing = substr($tag[2], 1);

					// Verify that the tag is within our allow list.
					if ($skip === false && isset($this->allowed_tags[$opening_tag_for_closing])) {

						// It is allowed so we need to parse the original opening tag.
						// Check if we need to call callback function.
						if (!is_array($this->allowed_tags[$tag_open])) {
							$method = $this->allowed_tags[$tag_open];
							$return = $this->{$method}($tag[1], $parameters);
						}
						// We have no callback function, instead we have an array with array(open-html,closing-html).
						else {
							list($replace_open, $replace_close) = $this->allowed_tags[$tag_open];
							$return = $replace_open . $tag[1] . $replace_close;
						}

						// Check if we have already opened the current closing tag in a previous step.
						if (!isset($open_tags[$opening_tag_for_closing])) {
							// We did not opened the tag so we need to remove the found closing tag.
							$text = $return . $tag[3];
							continue;
						}

						// We have opened it,
						// we will mark it now as closed and return the parsed data and the closing tag.
						$this->close_open_tag($tag_open, $open_tags);

						return $return . $tag[2] . $tag[3];
					}
					else {
						// The current found tag is disallowed so we need to threat it like normal text, to do this
						// we have to escape it with our unique ids and DONT parse the front.
						$text = $tag[1] . $this->unique_id_open . $tag[2] . $this->unique_id_close . $tag[3];
					}
				}
				else {
					// We have found the original closing tag for the current opening tag.
					// Reset skiping non self closing tag.
					$skip = false;

					// Mark the tag as closed.
					$this->close_open_tag($tag_open, $open_tags);

					// Check if we need to call callback function.
					if (!is_array($this->allowed_tags[$tag_open])) {
						$method = $this->allowed_tags[$tag_open];
						$return = $this->{$method}($tag[1], $parameters);
					}
					// We have no callback function, instead we have an array with array(open-html,closing-html).
					else {
						list($replace_open, $replace_close) = $this->allowed_tags[$tag_open];
						$return = $replace_open . $tag[1] . $replace_close;
					}

					// Return the parsed data.
					return $return . $tag[3];
				}
			}

			// Found item is another opening tag, parse it recrusive.
			else {

				// Get the found tag.
				$tag_data = $this->get_tag_data($tag[2]);

				// Check if the found tag is allowed.
				if ($skip === false && !empty($tag_data['tag']) && isset($this->allowed_tags[$tag_data['tag']])) {
					// Tag is allowed so parse it.
					$text = $tag[1] . $this->parse_tag($tag_data, $tag[3], $open_tags, $skip);
				}
				// Tag is not allowed, replace the "[" and "]" with our unique id string.
				else {
					// Escape the bbcode with our unique ids.
					$text = $tag[1] . $this->unique_id_open . $tag_data['tag'] . $this->unique_id_close . $tag[3];
				}
			}
		}

		// If we are here we have a missing closing tag.
		// Try to close it.
		if (isset($this->allowed_tags[$tag_open])) {
			list($replace_open, $replace_close) = $this->allowed_tags[$tag_open];
			return $replace_open . $text . $replace_close;
		}
		// Nothing can be done, just return the text.
		return $text;
	}

	/**
	 * Parses a full tag into an array.
	 *
	 * @param string $tag
	 *   The full tag.
	 *
	 * @return array An array with the direct tag name as 'tag' and if found a parameter array within 'params' key
	 */
	private function get_tag_data($tag) {
		// If we do not have a equals char no parameter are present,
		// we can safely direct return the tag with empty parameter.
		if (strpos($tag, '=') === false && strpos($tag, '"') === false && strpos($tag, '&quot;') === false && strpos($tag, "'") === false) {
			return array(
				'tag' => $tag,
				'params' => array(),
			);
		}

		// Get rid of spaces within escaped quotes strings.
		while(($start_pos = strpos($tag, '&quot;')) !== false) {
			$substr = substr($tag, $start_pos + 6);
			if (($end_pos = strpos($substr, '&quot;')) === false) {
				$tag = str_replace('&quot;', '', $tag);
				break;
			}
			$sub_o = substr($substr, 0, $end_pos);
			$tag = str_replace('&quot;' . $sub_o . '&quot;', str_replace(" ", ":space:", $sub_o), $tag);
		}
		
		// Get rid of spaces within unescaped quotes strings.
		while(($start_pos = strpos($tag, '"')) !== false) {
			$substr = substr($tag, $start_pos + 6);
			if (($end_pos = strpos($substr, '"')) === false) {
				$tag = str_replace('"', '', $tag);
				break;
			}
			$sub_o = substr($substr, 0, $end_pos);
			$tag = str_replace('"' . $sub_o . '"', str_replace(" ", ":space:", $sub_o), $tag);
		}
		
		// Normalize important tag parts which is needed for parse_str to work correctly.
		$tag = preg_replace("/\s+=/", "=", preg_replace("/=\s+/", "=", $tag));
		
		// Little hack, because the parameters are like "url get parameter" which can be parsed by parse_str,
		// we only need to replace all spaces with ampersands
		$arr = array();
		parse_str(str_replace(":space:", " ", str_replace(" ", "&", $tag)), $arr);

		// Return the data.
		return array(
			'tag' => key($arr),
			'params' => $arr,
		);
	}

	/**
	 * Removes a tag from the open tag array, if the array is after removing empty remove also the tag container.
	 *
	 * @param string $tag
	 *   The tag to close.
	 * @param array $open_tags
	 *   The open tag array.
	 */
	private function close_open_tag($tag, &$open_tags) {
		if (isset($open_tags[$tag])) {
			array_pop($open_tags[$tag]);
			if (empty($open_tags[$tag])) {
				unset($open_tags[$tag]);
			}
		}
	}

	/**
	 * Parses img tags.
	 *
	 * @param string $text
	 *   The inner text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   possible parameters:
	 * 		- img: The size of the image (img=WxH or img=W).
	 * 		- align: The alignment (left or right).
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_img($text, Array $parameters = array()) {
		// Preinit variables.
		$attrs = "";
		
		// Check for img parameter (max size).
		if (isset($parameters['img'])) {
			// Get width and height.
			$w_h = explode(",", $parameters['img']);

			// Check if we have height also.
			if (isset($w_h[1])) {
				// Setup width AND height.
				$attrs .= ' width="' . min((int) $w_h[0], self::IMG_MAX_WIDTH) . '"';
				$attrs .= ' height="' . min((int) $w_h[1], self::IMG_MAX_HEIGHT) . '"';
			}
			else if ($w_h[0] != 0) {
				// Setup just width.
				$attrs .= ' width="' . min((int) $w_h[0], self::IMG_MAX_WIDTH) . '"';
			}
		}

		if (isset($parameters['alt'])) {
			$attrs .= ' alt="' . $parameters['alt'] . '"';
		}
		
		if (isset($parameters['title'])) {
			$attrs .= ' title="' . $parameters['title'] . '"';
		}
		
		// Check if we want to align the image.
		if (isset($parameters['align']) && ($parameters['align'] == 'left' || $parameters['align'] == 'right')) {
			$attrs .= ' align="' . $parameters['align'] . '"';
		}

		// Return the html.
		return '<img' . $attrs . ' src="' . htmlspecialchars($text) . '" />';
	}

	/**
	 * Parses mail tags.
	 *
	 * @param string $text
	 *   The inner text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   required parameters:
	 * 		- color: the color
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_color($text, Array $parameters = array()) {
		if (!isset($parameters['color'])) {
			return $text;
		}
		return '<span style="color:' . $parameters['color'] . '">' . $text . '</span>';
	}

	/**
	 * Parses mail tags.
	 *
	 * @param string $text
	 *   The inner text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   required parameters:
	 * 		- mail: the mail to link
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_mail($text, Array $parameters = array()) {
		if ((!isset($parameters['mail']) || empty($parameters['mail'])) && (!isset($parameters['email']) || empty($parameters['email']))) {
			return $text;
		}
		
		$mail = "";
		if (isset($parameters['mail']) && !empty($parameters['mail'])) {
			$mail = $parameters['mail'];
		}
		else {
			$mail = $parameters['email'];
		}
		return '<a href="mailto:' . htmlspecialchars($mail) . '">' . $text . '</a>';
	}

	/**
	 * Parses size tags.
	 *
	 * @param string $text
	 *   The inner text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   required parameters:
	 * 		- size: the size from 1 to 6
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_size($text, Array $parameters = array()) {
		if (!isset($parameters['size']) || empty($parameters['size'])) {
			return $text;
		}
		$px = intval($parameters['size']);
		return '<h' . $px . '>' . $text . '</h' . $px . '>';
	}

	/**
	 * Parses url.
	 *
	 * @param string $text
	 *   The url text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   possible parameters:
	 * 		- url: The url
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_url($text, Array $parameters = array()) {
		$url = $text;
		if (isset($parameters['url']) && !empty($parameters['url'])) {
			$url = $parameters['url'];
		}
		return '<a href="' . $url . '">' . $text . '</a>';
	}
	/**
	 * Parses sourcecode.
	 *
	 * @param string $text
	 *   The text.
	 * @param array $parameters
	 *   The parameters for this tag.
	 *   possible parameters:
	 * 		- sourcecode: the source code type for geshi
	 *   (optional, default = array())
	 *
	 * @return string The parsed html.
	 */
	protected function parse_sourcecode($text, Array $parameters = array()) {
		/*if (isset($parameters['sourcecode']) && !empty($parameters['sourcecode'])) {
			$url = $parameters['sourcecode'];
		}*/
		return '<pre class="source">' . htmlspecialchars(str_replace("<br />", "" , $text)) . '</pre>';
	}

}