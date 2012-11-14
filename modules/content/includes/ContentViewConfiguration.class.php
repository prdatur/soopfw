<?php
/**
 * Configuration for views.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @module Content
 * @category Configurations
 */
class ContentViewConfiguration extends Configuration {

	/**
	 * How many entries (per page) will be showed up.
	 *
	 * @use: set(), get()
	 * @return int
	 */
	const MAX_ENTRIES_PER_PAGE = 0;

	/**
	 * If the pager should enabled or not.
	 *
	 * @use: enable(), disable(), is_enabled(), is_disabled()
	 * @return boolean
	 */
	const ENABLE_PAGER = 1;

	/**
	 * The truncate policy
	 * Use one of ContentTypeViewObj::TRUNCATE_POLICY_*
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const TRUNCATE_POLICY = 2;

	/**
	 * The max chars after which we truncate.
	 *
	 * @use: set(), get()
	 * @return int
	 */
	const TRUNCATE_CHARS = 3;

	/**
	 * Displayed fields.
	 * You can NOT add unconfigured fields so what you can do is to remove some fields which you do not want.
	 * The value is an array holding all wanted views as the values.
	 *
	 * @use: set(), get()
	 * @return array
	 */
	const DISPLAY_FIELDS = 4;

	/**
	 * Set the template, this must be the full path WITHOUT SITEPATH
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const TEMPLATE = 5;

	/**
	 * The view to retrieve.
	 * This configuration is mandatory.
	 *
	 * @use: set(), get()
	 * @return string
	 */
	const VIEW_NAME = 6;
}

