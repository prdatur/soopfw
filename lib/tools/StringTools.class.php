<?php

/**
 * Provides a class to handle strings.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class StringTools
{


	/**
	 * Char save truncate policy, will break right after specified chars.
	 *
	 * @var string
	 */
	const TRUNCATE_POLICY_CHAR_SAVE = 'char_save';

	/**
	 * Word save truncate policy, will not break words.
	 *
	 * @var string
	 */
	const TRUNCATE_POLICY_WORD_SAVE = 'word_save';

	/**
	 * Paragraph save truncate policy, will not break paragraphs (Checks HTML <p> tags or stops on new line).
	 *
	 * @var string
	 */
	const TRUNCATE_POLICY_PARAGRAPH_SAVE = 'paragraph_save';

	/**
	 * Sentence save truncate policy, will not break sentences. (Checks . (dots))
	 *
	 * @var string
	 */
	const TRUNCATE_POLICY_SENTENCE_SAVE = 'sentence_save';


	/**
	 * Truncate a string to a certain length if necessary,
	 * optionally splitting in the middle of a word, and
	 * appending the $etc string or inserting $etc into the middle.
	 *
	 * @param string $string
	 *   input string
	 * @param int $length
	 *   lenght of truncated text
	 * @param string $truncate_policy
	 *   truncate at word boundary
	 * @param string $etc
	 *   end string
	 *
	 *  @return string truncated string
	 */
	public static function truncate($string, $length = 80, $truncate_policy = StringTools::TRUNCATE_POLICY_WORD_SAVE, $etc = '...') {

		// Return empty string if we provide a length of 0 couse we do not calculate anything...
		if ($length == 0) {
			return '';
		}

		// Check for multibyte function.
		if (is_callable('mb_strlen')) {

			// Check if our string is longer than truncate length, if true truncate, else return the string.
			if (mb_strlen($string) > $length) {

				// Get the real length
				$length -= min($length, mb_strlen($etc));

				// Check again if length is 0
				if ($length == 0) {
					return '';
				}

				// Remove windows \r
				$string = preg_replace('/\r/is', '', $string);

				if ($truncate_policy === StringTools::TRUNCATE_POLICY_WORD_SAVE) {
					return preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1)) . $etc;
				}
				else if ($truncate_policy === StringTools::TRUNCATE_POLICY_SENTENCE_SAVE) {
					return preg_replace('/\.?([^\.]+)?$/u', '', mb_substr($string, 0, $length + 1)) . $etc;
				}
				else if ($truncate_policy === StringTools::TRUNCATE_POLICY_PARAGRAPH_SAVE) {
					$substr = mb_substr($string, 0, $length);
					$rev_string = strrev($substr);
					$strpos_nl = mb_stripos($rev_string, "\n");
					$strpos_p = mb_stripos($rev_string, ">p/<");

					if ($strpos_p === false || ($strpos_nl !== false && $strpos_nl <= $strpos_p)) {
						return preg_replace('/\n+[^\n]+$/is', '', $substr) . $etc;
					}
					else {
						return strrev(mb_substr($rev_string, $strpos_p)) . $etc;
					}
				}
				else {
					return mb_substr($string, 0, $length) . $etc;
				}
			}
			else {
				return $string;
			}

		}
		else {

			// Check if our string is longer than truncate length, if true truncate, else return the string.
			if (strlen($string) > $length) {

				$length -= min($length, strlen($etc));

				// Check again if length is 0
				if ($length == 0) {
					return '';
				}

				// Remove windows \r
				$string = preg_replace('/\r/is', '', $string);

				if ($truncate_policy === StringTools::TRUNCATE_POLICY_WORD_SAVE) {
					return preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1)) . $etc;
				}
				else if ($truncate_policy === StringTools::TRUNCATE_POLICY_SENTENCE_SAVE) {
					return preg_replace('/\.?([^\.]+)?$/u', '', substr($string, 0, $length + 1)) . $etc;
				}
				else if ($truncate_policy === StringTools::TRUNCATE_POLICY_PARAGRAPH_SAVE) {
					$substr = substr($string, 0, $length);
					$rev_string = strrev($substr);
					$strpos_nl = stripos($rev_string, "\n");
					$strpos_p = stripos($rev_string, ">p/<");
					if ($strpos_p === false || ($strpos_nl !== false && $strpos_nl <= $strpos_p)) {
						return preg_replace('/\n+[^\n]+$/is', '', $substr) . $etc;
					}
					else {
						return strrev(substr($rev_string, $strpos_p)) . $etc;
					}
				}
				else {
					return substr($string, 0, $length) . $etc;
				}
			}
			else {
				return $string;
			}

		}
	}

	/**
	 * Computes the differences of the two provided strings and output a diff
	 * in standard format as diff(1) would produce.
	 *
	 * @param string $old
	 *   the old string.
	 * @param string $new
	 *   the new string.
	 *
	 * @return string the diff output
	 */
	function php_diff($old, $new) {
		# split the source text into arrays of lines
		$t1 = explode("\n", $old);
		$x = array_pop($t1);
		if ($x > '')
			$t1[] = "$x\n\\ No newline at end of file";
		$t2 = explode("\n", $new);
		$x = array_pop($t2);
		if ($x > '')
			$t2[] = "$x\n\\ No newline at end of file";

		# build a reverse-index array using the line as key and line number as value
		# don't store blank lines, so they won't be targets of the shortest distance
		# search
		foreach ($t1 as $i => $x) {
			if ($x > '') {
				$r1[$x][] = $i;
			}
		}
		foreach ($t2 as $i => $x) {
			if ($x > '') {
				$r2[$x][] = $i;
			}
		}

		$a1 = 0;
		$a2 = 0;   # start at beginning of each list
		$actions = array();

		# walk this loop until we reach the end of one of the lists
		while ($a1 < count($t1) && $a2 < count($t2)) {
			# if we have a common element, save it and go to the next
			if ($t1[$a1] == $t2[$a2]) {
				$actions[] = 4;
				$a1++;
				$a2++;
				continue;
			}

			# otherwise, find the shortest move (Manhattan-distance) from the
			# current location
			$best1 = count($t1);
			$best2 = count($t2);
			$s1 = $a1;
			$s2 = $a2;
			while (($s1 + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
				$d = -1;
				foreach ((array) @$r1[$t2[$s2]] as $n) {
					if ($n >= $s1) {
						$d = $n;
						break;
					}
				}
				if ($d >= $s1 && ($d + $s2 - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
					$best1 = $d;
					$best2 = $s2;
				}
				$d = -1;
				foreach ((array) @$r2[$t1[$s1]] as $n)
					if ($n >= $s2) {
						$d = $n;
						break;
					}
				if ($d >= $s2 && ($s1 + $d - $a1 - $a2) < ($best1 + $best2 - $a1 - $a2)) {
					$best1 = $s1;
					$best2 = $d;
				}
				$s1++;
				$s2++;
			}
			while ($a1 < $best1) {
				$actions[] = 1;
				$a1++;
			}  # deleted elements
			while ($a2 < $best2) {
				$actions[] = 2;
				$a2++;
			}  # added elements
		}

		# we've reached the end of one list, now walk to the end of the other
		while ($a1 < count($t1)) {
			$actions[] = 1;
			$a1++;
		}  # deleted elements
		while ($a2 < count($t2)) {
			$actions[] = 2;
			$a2++;
		}  # added elements
		# and this marks our ending point
		$actions[] = 8;

		# now, let's follow the path we just took and report the added/deleted
		# elements into $out.
		$op = 0;
		$x0 = $x1 = 0;
		$y0 = $y1 = 0;
		$out = array();
		foreach ($actions as $act) {
			if ($act == 1) {
				$op|=$act;
				$x1++;
				continue;
			}
			if ($act == 2) {
				$op|=$act;
				$y1++;
				continue;
			}
			if ($op > 0) {
				$xstr = ($x1 == ($x0 + 1)) ? $x1 : ($x0 + 1) . ",$x1";
				$ystr = ($y1 == ($y0 + 1)) ? $y1 : ($y0 + 1) . ",$y1";
				if ($op == 1)
					$out[] = "{$xstr}d{$y1}";
				elseif ($op == 3) {
					$out[] = "{$xstr}c{$ystr}";
				}
				while ($x0 < $x1) {
					$out[] = '< ' . $t1[$x0];
					$x0++;
				}   # deleted elems
				if ($op == 2) {
					$out[] = "{$x1}a{$ystr}";
				}
				elseif ($op == 3) {
					$out[] = '---';
				}
				while ($y0 < $y1) {
					$out[] = '> ' . $t2[$y0];
					$y0++;
				}   # added elems
			}
			$x1++;
			$x0 = $x1;
			$y1++;
			$y0 = $y1;
			$op = 0;
		}
		$out[] = '';
		return join("\n", $out);
	}

}

