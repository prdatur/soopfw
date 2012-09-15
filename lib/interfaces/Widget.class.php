<?php
/**
 * Provides an interface for a widget.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Widget
 */
interface Widget {

	/**
	 *
	 * Initialize the widget, will also perform form handlings for the widget if needed.
	 * This method must perform all actions what the widget should can do.
	 *
	 * @param string $name
	 *   the widget name
	 *
	 * @param string $unique_id
	 *   the unique id for this widget
	 */
	public function get_widget($name, $unique_id);
}

