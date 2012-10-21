<?php
/**
 * Provides a helper class for Widgets.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib.widget
 * @category Widget
 */
class WidgetHelper {

	/**
	 * Transforms the given widget id into a cleaned one.
	 *
	 * @param string $unique_id
	 *   THe original widget id.
	 * @return string The cleaned unique widget id.
	 */
	public static function clean_widget_id($unique_id) {
		return preg_replace("/[\W]/", "_", $unique_id);
	}
}

