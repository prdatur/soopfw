/*
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.
 */

if(dlg_id == undefined) {
	var dlg_id = "";
}


Soopfw.behaviors.content_manage_field_groups = function() {
	enable_clicks();
};

function open_change_dialog(id) {
	var params = [Soopfw.config.content_type];
	if(!empty(id)) {
		params.push(id);
	}
	dlg_id = Soopfw.default_action_dialog(Soopfw.t("add/change content type field"), 'content', 'change_content_type_field', params);
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
		enableIndent: false,
		indentSerializeAsObject: true,
		onDrop:function(table) {
			$("tr.tableDnD_indentgroup > td:first-child", table).find(".sortorder_changed").remove();
			$("tr.tableDnD_indentgroup > td:first-child", table).append($("<span class='sortorder_changed'>*</span>"));
			$('#draggable_warning').show();
		}
	});

	$("#add_field").unbind("click");
	$("#add_field").click(function() {
		check_if_unchanged();
	});
	$("#save_new_order").unbind("click");
	$("#save_new_order").click(function() {
		var params = $(".tablednd").tableDnDSerialize();

		if(params[0] != undefined) {
			params = params[0];
			params['content_type'] = Soopfw.config.content_type;
			ajax_success("/content/save_field_group_order.ajax", params, Soopfw.t("New field order saved successfully"), Soopfw.t("success"), function() {
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
			ajax_success("/content/delete_field_group.ajax", {id: id}, Soopfw.t("field group deleted"), Soopfw.t("Success"), function() {
				entry_changed();
			});
		})
	});
}

function entry_changed() {
	location.reload(true);
}

function close_dialog() {
	$("#"+dlg_id).dialog("destroy");
}