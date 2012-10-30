<?php

/**
 * Provides a class to handle content based stuff.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category ModulObject
 */
class ContentHelper extends Object
{

	/**
	 * Get view data.
	 *
	 * If you provide the $configuration object all configurated values will overwrite the configured view data.
	 * This is usefull if a widget will return for example less entries or the values should truncated earlier.
	 *
	 * @param string $view_name
	 *   The view name.
	 * @param string &$static_tpl
	 *   This variable will be filled with the template path which should the module use to display the view content.
	 *   This can not be empty.
	 *
	 * @param ContentViewConfiguration $configuration.
	 *   The configuration object. (optional, default = NS)
	 *
	 * @throws SoopfwWrongParameterException
	 * @throws SoopfwErrorException
	 *
	 * @return array The view data.
	 */
	public function get_view($view_name, &$static_tpl, ContentViewConfiguration $configuration = null) {

		// Load our view object.
		$view = new ContentTypeViewObj($view_name);

		// Check if view is available.
		if (!$view->load_success()) {
			throw new SoopfwWrongParameterException(t('No such view'));
		}

		// If we did not provide a configuration, create an empty one.
		if (empty($configuration)) {
			$configuration = new ContentViewConfiguration();
		}

		// Setup the view template.
		if ($configuration->is_set(ContentViewConfiguration::TEMPLATE)) {
			$orig_path = $configuration->get(ContentViewConfiguration::TEMPLATE);
		}
		else {
			$template_file = $this->smarty->get_tpl(true) . '/content/views/' . $view_name . '.tpl';
		}

		$template_file = $orig_path = FileTools::get_path($template_file);

		// Validate view template.
		if ($template_file === null) {
			throw new SoopfwErrorException(t('Your provided path: "<b>@path</b>" is invalid', array(
				'@path' => $template_file,
			)));
		}

		// Check if the file really exist.
		if (!file_exists($template_file)) {
			throw new SoopfwErrorException(t('Can not display view, view template was not found.<br>Please create a template file "<b>/content/views/@view_name.tpl</b>" within your current template.', array(
				'@view_name' => $view_name,
			)));
		}

		$js_css_path_template = preg_replace('/^' . preg_quote(SITEPATH, '/') . '/', '', $template_file);

		// Add possible css file.
		$this->core->add_css(preg_replace('/\.tpl$/', '.css', $js_css_path_template));

		// Add possible js file.
		$this->core->add_js(preg_replace('/\.tpl$/', '.js', $js_css_path_template));

		$static_tpl = $template_file;

		// Get all view configurations.
		$view_data = $view->get_values();
		$view_data[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS] = json_decode($view_data[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS], true);

		// Override our view configurated display fields with the one which were configurated through the provided configuration object.
		$override_display_fields = $configuration->get(ContentViewConfiguration::DISPLAY_FIELDS);
		if (!empty($override_display_fields) && is_array($override_display_fields)) {
			$view_data[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS] = array_intersect_key($view_data[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS], array_flip($override_display_fields));
		}

		$view_data[ContentTypeViewObj::FIELD_SORT_FIELDS] = json_decode($view_data[ContentTypeViewObj::FIELD_SORT_FIELDS], true);

		// Setup database filter.
		$filter = DatabaseFilter::create(PageObj::TABLE, 'p')
				->add_where('content_type', $view_data[ContentTypeViewObj::FIELD_CONTENT_TYPE]);
		$filter->add_where('deleted', PageObj::DELETED_NO);

		// Because it is not important how the result order is we get our "pager entry count" now without a order by
		// This will save us some performance.

		// Setup the pager limit.
		if ($configuration->is_set(ContentViewConfiguration::MAX_ENTRIES_PER_PAGE)) {
			$limit = (int) $configuration->get(ContentViewConfiguration::MAX_ENTRIES_PER_PAGE);
		}
		else {
			$limit = (int) $view_data[ContentTypeViewObj::FIELD_MEPP];
		}

		// If pager is enabled setup the pager.
		if ($configuration->is_enabled(ContentViewConfiguration::ENABLE_PAGER) || $view_data[ContentTypeViewObj::FIELD_USE_PAGER] === '1') {
			$pager = new Pager($limit);
			$pager->set_entry_count($filter->select_count());

			$filter->offset($pager->get_offset());
		}

		// We need to configurate the databasefilter limit after we initialize the pager, because for the "global" count we need all values without a limit)
		if ($limit > 0) {
			$filter->limit($limit);
		}

		// Setup the sorted fields.
		if (!empty($view_data['sort_fields'])) {
			$joins = array();
			foreach ($view_data['sort_fields'] AS $field => $direction) {

				// Get our joins if needed.
				switch ($field) {
					// Title fields is within revision table.
					case 'title':
						$joins[PageRevisionObj::TABLE] = true;
						$table = PageRevisionObj::TABLE;
						break;

					// This fields are directly within page object so no join is needed.
					case 'created_by':
					case 'created':
					case 'last_modified':
					case 'last_modified_by':
					case 'last_access':
						$table = NS;
						break;
					// All other are within the content type field group value table, so join it
					default:
						if (!isset($joins[ContentTypeFieldGroupFieldValueObj::TABLE])) {
							$joins[ContentTypeFieldGroupFieldValueObj::TABLE] = array();
						}
						$joins[ContentTypeFieldGroupFieldValueObj::TABLE][] = $field;
						$table = ContentTypeFieldGroupFieldValueObj::TABLE;
						$field = 'value';
						break;
				}

				// After getting the joins, set the order by.
				$filter->order_by($field, $direction, $table);
			}

			foreach ($joins AS $table => $value) {
				$ctfgv = '';
				if ($table == ContentTypeFieldGroupFieldValueObj::TABLE) {
					$database_or = new DatabaseWhereGroup(DatabaseWhereGroup::TYPE_OR);
					foreach ($value AS $field) {
						$database_or->add_where('content_type_field_group_id', $field, '=', $table);
					}
					$ctfgv = ' AND ' . $database_or->get_sql(false);
				}

				$filter->join($table, $table . '.page_id = p.page_id AND ' . $table . '.language = p.language AND ' . $table . '.revision = p.last_revision' . $ctfgv);
			}
		}

		// Within the filter we only get the primary keys of page revision object back.
		$filter->add_column('page_id');
		$filter->add_column('language');
		$filter->add_column('`last_revision` as revision');

		// Set truncate max chars.
		if ($configuration->is_set(ContentViewConfiguration::TRUNCATE_CHARS)) {
			$truncate_chars = (int) $configuration->get(ContentViewConfiguration::TRUNCATE_CHARS);
		}
		else {
			$truncate_chars = (int) $view_data[ContentTypeViewObj::FIELD_TRUNCATE_CHARS];
		}

		// Set truncate policy.
		if ($configuration->is_set(ContentViewConfiguration::TRUNCATE_POLICY)) {
			$truncate_policy = $configuration->get(ContentViewConfiguration::TRUNCATE_POLICY);
		}
		else {
			$truncate_policy = $view_data[ContentTypeViewObj::FIELD_TRUNCATE_POLICY];
		}

		$data = array();

		// Get all page revision objects
		foreach ($filter->select_all() AS $row) {

			$page_revision_obj = new PageRevisionObj($row['page_id'], $row['language'], $row['revision']);
			$page_obj = new PageObj($row['page_id'], $row['language']);

			$values = $page_revision_obj->get_values();

			// Get only the fields back which we want.
			$values = array_intersect_key(array_merge($page_obj->get_values(), $values, json_decode($values['serialized_data'], true)), $view_data[ContentTypeViewObj::FIELD_DISPLAYED_FIELDS]);

			// Truncate all data values to the configurated max chars and policy.
			array_walk_recursive($values, function(&$value) use ($truncate_chars, $truncate_policy){
				$value = StringTools::truncate($value, $truncate_chars, $truncate_policy);
			});

			if (isset($values['created_by'])) {
				$user_obj = new UserObj($values['created_by']);
				$values['created_by_user'] = $user_obj->get_values();
			}

			if (isset($values['last_modified_by'])) {
				$user_obj = new UserObj($values['last_modified_by']);
				$values['last_modified_by_user'] = $user_obj->get_values();
			}
			$values['link'] = $page_revision_obj->get_alias();
			if ($values['link'] === false) {
				$values['link'] = '/' . $values['language'] . '/content/view/' . $values['page_id'];
			}
			else {
				$values['link'] = '/' . $values['link'];
			}
			// Add the page values to our returning array.
			$data[] = $values;

		}

		return $data;
	}
}