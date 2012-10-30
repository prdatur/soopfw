<?php
/**
 * Provides an interface for a widget.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Widget
 */
interface Widget {

	/**
	 *
	 * Initialize the widget, will also perform form handlings for the widget if needed.
	 * This method must perform all actions what the widget should can do.
	 *
	 * Use only the returned uuid to access the widget because non "word" character will be replaced
	 * to _ (underline)
	 *
	 * @param string $name
	 *   the widget name
	 * @param string $unique_id
	 *   the unique id for this widget
	 * @param Configuration $widget_config
	 *   the widget configuration object (optional, default = null)
	 *
	 * @return mixed the cleaned uuid or null if the widget name is not supported.
	 */
	public function get_widget($name, $unique_id, Configuration $widget_config = null);
}

