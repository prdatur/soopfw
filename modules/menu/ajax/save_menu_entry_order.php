<?php
	/* @var $core Core */
	//Define needed params
	$params = new ParamStruct();
	$params->add_required_param("menu_id", PDT_STRING);
	$params->add_required_param("new_order", PDT_ARR);

	//Fill the params
	$params->fill();

	//Display error if params are not valid
	if(!$params->is_valid()) {
		AjaxModul::return_code(AjaxModul::ERROR_MISSING_PARAMETER, null, true);
	}

	//Check perms
	if(!$core->get_right_manager()->has_perm("admin.menu.manage")) {
		AjaxModul::return_code(AjaxModul::ERROR_NO_RIGHTS, null, true);
	}

	$core->db->transaction_begin();
	$menu_id = $params->menu_id;
	$new_order = $params->new_order;
	$order_counter = 0;
	if(recrusive_save_new_order($new_order, $menu_id, $core->db)) {
		$core->db->transaction_commit();
		AjaxModul::return_code(AjaxModul::SUCCESS, null, true);
	}

	$core->db->transaction_rollback();
	AjaxModul::return_code(AjaxModul::ERROR_DEFAULT, null, true);

	function recrusive_save_new_order(&$order, $menu_id, Db &$db, $parent = 0) {
		global $order_counter;
		foreach($order AS $k => $v) {
			if(empty($v)) {
				continue;
			}
			$order_counter++;
			$menu_obj = new MenuEntryObj($k);
			if(!$menu_obj->load_success()) {
				continue;
			}

			$menu_obj->order = $order_counter;
			$menu_obj->parent_id = $parent;
			if(!$menu_obj->save()) {
				return false;
			};

			if(is_array($v)) {
				if(!recrusive_save_new_order($v, $menu_id, $db, $k)) {
					return false;
				}
			}
		}
		return true;
	}

	function array_reverse_recursive($arr) {
		foreach ($arr as $key => $val) {
			if (is_array($val))
				$arr[$key] = array_reverse_recursive($val);
		}
		return array_reverse($arr);
	}

?>