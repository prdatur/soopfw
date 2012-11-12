<?php

/**
 * Provides a class to handle class relevant things.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Tools
 */
class ClassTools
{
	/**
	 * Define class instance types.
	 */
	const CLASS_INSTANCE_TYPE_INTERFACE = 'implements';
	const CLASS_INSTANCE_TYPE_EXTENDS = 'extends';

	/**
	 * Get a list of classes which are a $search_Type of the given $instance_of.
	 *
	 * For example to get all login handler we can call this:
	 * ClassTools::get_class_of_instance('LoginHandler', ClassTools::CLASS_INSTANCE_TYPE_INTERFACE);
	 * because the LoginHandler is an interface and all LoginHandler need to implement this interface.	 *
	 *
	 * @param string $instance_of
	 *   The classname
	 * @param string $search_type
	 *   The search type, can be one of ClassTools::CLASS_INSTANCE_TYPE_*
	 *   (optional, default = ClassTools::CLASS_INSTANCE_TYPE_INTERFACE)
	 *
	 * @return array An array with all classes which are an instance of the provided $instance_of class.
	 */
	public static function get_class_of_instance($instance_of, $search_type = ClassTools::CLASS_INSTANCE_TYPE_INTERFACE) {
		static $classes = null, $cache = array();

		// Check if we have it already within the cache.
		if (isset($cache[$instance_of . '|' . $search_type])) {
			return $cache[$instance_of . '|' . $search_type];
		}

		// Get the classlist only within the first call, then cache it.
		if ($classes === null) {
			$classes = Core::get_classlist();
		}

		// Loop through all classes.
		$classlist = array();
		foreach ($classes['classes'] AS $classname => &$class) {

			// Check if the given $search_type exist for the current class, if so add it to our return array.
			if (isset($class[$search_type]) && in_array($instance_of, $class[$search_type])) {
				$classlist[$classname] = $classname;
			}
		}

		// Cache the data.
		$cache[$instance_of . '|' . $search_type] = $classlist;

		// Return the list.
		return $classlist;

	}
}

