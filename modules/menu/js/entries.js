Soopfw.behaviors.menu_overview = function() {
	enable_clicks();
}

function open_change_dialog(id) {

	var params = [Soopfw.config.menu_entries_menu_id];
	if(id != undefined) {
		params.push(id);
	}
	Soopfw.default_action_dialog(Soopfw.t("add/change menu entry"), 'menu', 'change_menu_entry', params);
}


function check_if_unchanged(id) {



	if($("#draggable_warning").is(":visible")) {
		confirm(Soopfw.t("You have unsaved changes, if you save your values within the now created dialog, you will loose the unchanged states. Please check the information box how to save your work"), Soopfw.t("Drop unsaved changes?"), function() {
			open_change_dialog(id);
		});
	}
	else {
		open_change_dialog(id);
	}
}
function enable_clicks() {

	$(".tablednd").tableDnD({
		dragHandle: 'handle_cell > .tabledrag-handle',
		enableIndent: true,
		indentSerializeAsObject: true,
		onDrop:function(table) {
			$("tr.tableDnD_indentgroup > td:first-child", table).find(".sortorder_changed").remove();
			$("tr.tableDnD_indentgroup > td:first-child", table).append($("<span class='sortorder_changed'>*</span>"));
			$('#draggable_warning').show();
		}
	});

	$("#add_entry").unbind("click");
	$("#add_entry").click(function() {
		check_if_unchanged();
	});
	$("#save_new_order").unbind("click");
	$("#save_new_order").click(function() {
		var params = $(".tablednd").tableDnDSerialize();

		if(params[0] != undefined) {
			params = params[0];
			params['menu_id'] = Soopfw.config.menu_entries_menu_id;
			ajax_success("/menu/save_menu_entry_order.ajax", params, Soopfw.t("New menu order saved successfully"), Soopfw.t("success"), function() {
				$("#new_order").find(".sortorder_changed").remove();
				$('#draggable_warning').hide();
			});
		}
	});

	$(".dmyChange").unbind("click");
	$(".dmyChange").click(function() {
		check_if_unchanged($(this).attr("id"));
	});

	$(".dmyAdd").unbind("click");
	$(".dmyAdd").click(function() {
		check_if_unchanged($(this).attr("id"));
	});

	$(".dmyDelete").unbind("click");
	$(".dmyDelete").click(function() {
		var id = $(this).attr("id");
		confirm(Soopfw.t("Do you really want to delete this menu entry"), Soopfw.t("delete?"), function() {
			ajax_success("/menu/delete_menu_entry_translation.ajax", {entry_id: id}, Soopfw.t("menu entry deleted"), Soopfw.t("Success"), function() {
				entry_changed();
			});
		})
	});
}

function entry_changed() {
	location.reload(true);
}