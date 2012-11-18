<?php

/**
 * Translation action module.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class Translation extends ActionModul
{
	/**
	 * Default method
	 *
	 * @var string
	 */
	protected $default_methode = "search";

	/**
	 * Implements hook: admin_menu
	 *
	 * Returns an array which includes all links and childs for the admin menu.
	 * There are some special categories in which the module can be injected.
	 * The following categories are current supported:
	 *   style, security, content, structure, authentication, system, other
	 *
	 * @return array The menu
	 */
	public function hook_admin_menu() {
		return array(
			AdminMenu::CATEGORY_CONTENT => array(
				'#id' => 'soopfw_translation', // A unique id which will be needed to generate the submenu
				'#title' => t("Translation"), // The main title
				'#link' => '/admin/translation/search',
				'#perm' => 'admin.translate', // Perm needed
				'#childs' => array(
					array(
						'#title' => t("Translate"), // The main title
						'#link' => "/admin/translation/search", // The main link
					),
					array(
						'#title' => t("Import"), // The main title
						'#link' => "/admin/translation/import", // The main link
					),
					array(
						'#title' => t("Export"), // The main title
						'#link' => "/admin/translation/export", // The main link
					),
					array(
						'#title' => t("Manage"), // The main title
						'#link' => "/admin/translation/manage", // The main link
					),
				)
			)
		);
	}

	/**
	 * Action: manage
	 *
	 * Enable or disable languages.
	 */
	public function manage() {
		// Check perms.
		if (!$this->right_manager->has_perm("admin.translate", true)) {
			throw new SoopfwNoPermissionException();
		}

		// Set title and description.
		$this->title(t("Manage Translation"), t('Enable or disable languages'));

		$languages = array();
		$this->lng->load_language_list();
		asort($this->lng->languages);
		foreach ($this->lng->languages AS $key => $language) {
			$language_obj = new LanguagesObj($key);
			if (!$language_obj->load_success()) {
				$language_obj->lang = $key;
				$language_obj->enabled = false;
			}
			$languages[] = $language_obj;
		}

		$this->smarty->assign_by_ref("languages", $languages);
		$this->smarty->assign_by_ref("language_translation", $this->lng->languages);
	}

	/**
	 * Action: import
	 *
	 * Import a translation PO-File.
	 */
	public function import() {
		// Check perms.
		if (!$this->right_manager->has_perm("admin.translate", true)) {
			throw new SoopfwNoPermissionException();
		}

		// Set title and description.
		$this->title(t("Translation Import"), t("Import translation from a PO-File.
			First select a PO-File which you want to import.
			Then choose a language, the current language in which you are is pre-selected, the import will write the entries to this language.
			After that choose whether you want to [b]override[/b] current translation within the selected language."));

		// Add form.
		$form = new Form("import_translations", t("Import translations"));
		$file = new Filefield("translation_file", "", t("Choose a file"), t("Please choose the wanted PO-File"));
		$file->add_validator(new RequiredValidator(t("The translation file is needed.")));

		$form->add($file);
		$form->add(new Fieldset('select_language', t("Select Languages")));

		// Add all available languages and add them to the form as radiobuttons.
		$values = array();
		foreach ($this->lng->get_enabled_languages() AS $key => $language) {
			$values[$key] = $language;
		}
		$form->add(new Radiobuttons("language", $values, $this->core->current_language));

		$form->add(new Fieldset('select_options', t("Select Options")));

		$form->add(new Radiobuttons("override", array(
					"1" => t("Override already translated strings"),
					"0" => t("Import only new translations"),
						), "0"));

		$form->add(new Submitbutton("import", "import", t("Import")));
		$form->assign_smarty();

		$form->check_form();
		if ($form->is_submitted() && $form->is_valid()) {

			// Get current form values.
			$values = $form->get_values();

			// Store inserted fileid.
			$fid = $values['translation_file'];

			// Load the uploaded file.
			$file_obj = new MainFileObj($fid);

			// Check if file is loaded successfully.
			if (!$file_obj->load_success()) {
				$this->core->message(t("Could not load uploaded file."), Core::MESSAGE_TYPE_ERROR);
			}

			// Get the file contents.
			$contents = $file_obj->get_contents();

			$translations = array();
			$updated = $inserted = $errors = 0;
			// Search for strings msgid = "" msgstr="" the split of msgid into id at the beginning and msg at the end.
			// Let us fetch all msgid / msgstr blocks with modifier U to get the best results.
			if (preg_match_all("/id\s*\"(.*)\"\s*msgstr\s*\"(.*)\"\s*(msg|$)/iUs", $contents, $matches)) {
				foreach ($matches[1] AS $i => $key) {
					// Parse our key and value pairs to their original strings.
					$key = strtolower($this->get_imported_string($key));
					$matches[2][$i] = $this->get_imported_string($matches[2][$i]);

					// If one of them are empty we do not need it. Empty translation we do not want.
					if (empty($key) || empty($matches[2][$i])) {
						continue;
					}

					// Build up translation strings.
					$translations[$this->get_imported_string($key)] = $this->get_imported_string($matches[2][$i]);

					// Try to load the translation / language pair.
					$trans_obj = new TranslationObj(md5($key), $values['language']);

					// If we do not want to override previours translation but the above load was successfully we must now continue to not update it.
					if ((empty($values['override']) || $values['override'] != "1") && $trans_obj->load_success()) {
						continue;
					}
					$loadsuccess = $trans_obj->load_success();

					// Set translation object.
					$trans_obj->key = $key;
					$trans_obj->translation = $matches[2][$i];
					$trans_obj->language = $values['language'];
					$trans_obj->id = md5($key);

					// Save or insert it and count up statistics.
					if ($trans_obj->save_or_insert()) {
						if ($loadsuccess) {
							$updated++;
						}
						else {
							$inserted++;
						}
					}
					else {
						$errors++;
					}
				}
			}

			// Delete uploaded po-file file. we do not need it anymore.
			$file_obj->delete();

			// Set up success message with statistic information.
			$this->core->message(t("Import successfull.
				Imported new: [b]@inserted[/b],
				Updated: [b]@updated[/b],
				Errors: [b]@errors[/b].", array("@inserted" => $inserted, "@updated" => $updated, "@errors" => $errors)));
		}

		// We need only a static form template.
		$this->static_tpl = "form.tpl";
	}

	/**
	 * Action: export
	 *
	 * Exporting translations to po-files.
	 */
	public function export() {
		// Check perms.
		if (!$this->right_manager->has_perm("admin.translate", true)) {
			throw new SoopfwNoPermissionException();
		}

		$this->title(t("Translation Export"), t("Export translation to a PO-File.
			First select a module which you want to export, choose [b]all[/b] for all modules.
			Then choose a language, the current language in which you are is pre-selected.
			After that you can choose whether you want to [b]include[/b] or [b]exclude[/b] current translation within the selected language."));

		// Add form.
		$form = new Form("select_translation_export", t("Select Export options"));

		$form->add(new Fieldset('select_module', t("Select Modules")));
		$modules = array(
			'all' => t('All')
		);

		// Add all modules which can we select.
		foreach ($this->core->modules AS $module) {
			$modules[$module] = $module;
		}
		$form->add(new Radiobuttons("module", $modules, "all"));

		$form->add(new Fieldset('select_languages', t("Select Languages")));

		$languages = array();
		// Add all enabled languages which can we select.
		foreach ($this->lng->get_enabled_languages() AS $key => $language) {
			$languages[$key] = $language;
		}
		$form->add(new Radiobuttons("language", $languages, $this->core->current_language));

		$form->add(new Fieldset('select_options', t("Select Options")));

		$include_translations = array(
			'1' => t("Include translated strings"),
			'0' => t("Exclude translated strings"),
		);
		$form->add(new Radiobuttons("include_translations", $include_translations, "1"));


		$form->add(new Submitbutton("export", "export", t("Export")));
		$form->assign_smarty();

		$form->check_form();


		if ($form->is_submitted() && $form->is_valid()) {
			$values = $form->get_values();
			$result = array();
			$orig_module = $values['module'];

			// If module == 'all' clear the module to prevent build_language behaviour.
			$values['module'] = ($values['module'] == "all") ? '' : $values['module'];

			// Build the wanted languages from source and put it into syntax for PO-Files.
			foreach ($this->lng->build_language($values['module'], $values['language'], true, ($values['include_translations'] == "1")) AS $key => $translation) {
				$result[] = "msgid \"" . strtolower(str_replace("\n", "\\n\"\n\"", $key)) . "\"";
				$result[] = "msgstr \"" . str_replace("\n", "\\n\"\n\"", $translation) . "\"";
				$result[] = "";
			}
			// Implode all new lines.
			$content = implode("\n", $result);

			// Set headers for download and exit after.
			header("Expires: Mon, 26 Jul 1997 05:00:00 GMT+1");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT+1");
			header("Content-Length: " . strlen($content));

			header("Content-type: application/txt\n"); // or y
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename=\"" . $orig_module . "-" . $values['language'] . ".po\";\n\n");
			echo $content;
			exit();
		}

		$this->static_tpl = "form.tpl";
	}

	/**
	 * Action: search
	 *
	 * Search for translations.
	 */
	public function search() {
		// Check perms
		if (!$this->right_manager->has_perm("admin.translate", true)) {
			throw new SoopfwNoPermissionException();
		}

		$this->title(t("Translation"), t("Search for a translation key.
			You can use wildcard [b]*[/b], default if you search for [b]test[/b] it will search for [b]*test*[/b]"));

		// Setup search form
		$form = new Form("search_translations", t("Search"));
		$form->add(new Textfield("key"));
		$form->add(new Submitbutton("searchTranslation", t("Search")));
		$form->assign_smarty("search_form");

		// Check form and add errors if form is not valid.
		$form->check_form();

		if ($form->is_submitted()) { // Search was submited.
			// Set session key for user search values so a reload of a page will use the session values.
			$this->session->set("search_translation_search", $form->get_values());
		}
		else {
			// Form was not submited so try to load session values.
			$form->set_values($this->session->get("search_translation_search", array()));
		}

		$where = array();
		$val = $form->get_value("key");
		if (!empty($val)) {
			$where[] = "tk.`key` LIKE '" . $this->db->get_sql_string_search($val, "*.*") . "' OR t.`translation` LIKE '" . $this->db->get_sql_string_search($val, "*.*") . "'";
		}

		// If where array is not empty add the where.
		if (!empty($where)) {
			$where = " WHERE (" . implode(") AND (", $where) . ")";
		}
		else {
			$where = "";
		}

		// Build query string for pager.
		$query_string = "SELECT 1 FROM `" . TranslationKeysObj::TABLE . "` tk
			LEFT JOIN `" . TranslationObj::TABLE . "` t ON (t.id = tk.id)" . $where . " GROUP BY tk.`id`";

		// Init pager.
		$num_founds = $this->db->query_slave_count($query_string);

		$pager = new Pager(50, $num_founds);
		$pager->assign_smarty("pager");

		// Build query string.
		$query_string = "SELECT tk.id, tk.key, t.translation FROM `" . TranslationKeysObj::TABLE . "` tk
			LEFT JOIN `" . TranslationObj::TABLE . "` t ON (t.id = tk.id)" . $where . " GROUP BY tk.`id` ORDER BY tk.`key`,`translation`";

		// Search in DB.
		$translations = $this->db->query_slave_all($query_string, array(), $pager->max_entries_per_page(), $pager->get_offset());
		foreach ($translations AS &$translation) {
			// Add only if translation is not empty and matches our search string.
			if (!empty($translation['translation']) && preg_match("/" . preg_quote($val) . "/iUs", $translation['translation'])) {
				$translation['key'] = $translation['translation'];
			}
		}
		// Assign found users.
		$this->smarty->assign_by_ref("results", $translations);
	}

	/**
	 * Action: translate
	 *
	 * Translate the given translation id
	 *
	 * @param string $id
	 *   The translation key.
	 */
	public function translate($id) {
		// Check perms.
		if (!$this->right_manager->has_perm("admin.translate", true)) {
			throw new SoopfwNoPermissionException();
		}

		if (empty($id)) {
			throw new SoopfwWrongParameterException();
		}

		// Check if we have such a translation key.
		$translation = new TranslationKeysObj($id);
		if (!$translation->load_success()) {
			throw new SoopfwWrongParameterException(t("Translation string not found"));
		}
		$translation_objects = array();

		// Build up our translation form.
		$form = new Form("translate", t("Translate: [b]@string[/b]", array("@string" => $translation->key)));
		foreach ($this->lng->get_enabled_languages() AS $row => $language_string) {
			$translation_objects[$row] = $translation_tmp = new TranslationObj($id, $row);
			$form->add(new Textarea($row, $translation_tmp->translation, $language_string, t("Translation for language: @lang ", array("@lang" => $language_string))));
		}
		$form->add(new Submitbutton("save", t("Save")));
		$form->assign_smarty();

		$form->check_form();
		if ($form->is_submitted() && $form->is_valid()) {

			$values = $form->get_values();
			foreach ($translation_objects AS $lang => &$obj) {
				$obj->set_fields_bulk($translation->get_values(true));
				$obj->translation = $values[$lang];
				$obj->language = $lang;
				$obj->save_or_insert();
			}
			$this->core->message(t("Translation saved"), Core::MESSAGE_TYPE_SUCCESS);
		}

		$this->static_tpl = "form.tpl";
	}

	/**
	 * Called up on installation.
	 *
	 * @return boolean returns true on sucess.
	 */
	public function install() {
		if (!parent::install()) {
			return;
		}

		$language = new LanguagesObj();
		$language->lang = 'en';
		$language->enabled = true;
		$language->insert();

		$language = new LanguagesObj();
		$language->lang = 'de';
		$language->enabled = true;
		$language->insert();

		$language = new LanguagesObj();
		$language->lang = 'fr';
		$language->enabled = true;
		$language->insert();

		$language = new LanguagesObj();
		$language->lang = 'it';
		$language->enabled = true;
		$language->insert();

		return true;
	}

	/**
	 * Parse a given string from PO-File maybe multiline to a single string with no multiple \n"-chars for each line.
	 *
	 * @param string $string
	 *   The string to parse.
	 *
	 * @return string The parsed string.
	 */
	private function get_imported_string($string) {
		// Replace all \\n"\n" with \n.
		$string = preg_replace("/\\\\n\"\s*\n\s*\"/iUs", "\n", $string);
		// Replace starting "\n" to empty.
		$string = preg_replace("/^\"\s*\"/iUs", "", $string);
		return $string;
	}

}