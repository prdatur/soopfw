
if(dlg_id == undefined) {
	var dlg_id = "";
}

Soopfw.behaviors.system_email_templates = function() {
	system_email_templates_enable_clicks();
}

function open_change_dialog(id) {
	var params = null;
	if(id != undefined) {
		params = [id];
	}
	dlg_id = Soopfw.default_action_dialog(Soopfw.t("add/change email template"), 'system', 'change_email_template', params, {
		width: 800
	});
}
function system_email_templates_enable_clicks() {

	$("#add_email_template").unbind("click");
	$("#add_email_template").click(function() {
		open_change_dialog();
	});

	$(".dmyChange").unbind("click");
	$(".dmyChange").click(function() {
		open_change_dialog($(this).attr("did"));
	});

	$("#multi_action").prop("value", "");
	$("#multi_action").unbind("change");
	$("#multi_action").change(function() {
		var value = $(this).prop("value");
		if(value == "") {
			return false;
		}

		if(value == "delete") {
			confirm(Soopfw.t("Really want to delete this email template?"), Soopfw.t("delete?"), function() {
				$(".dmySelect").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						ajax_request("/system/delete_email_template.ajax",{
							id: $(obj).prop("value")
						},function() {
							$("#row_"+$(obj).prop("value")).remove();
						});
					}
				});
				$(".dmySelect").prop("checked", false);
				$("#dmySelectAll").prop("checked", false);
			});
		}
		$("#multi_action").prop("value", "");

	});

	$("#dmySelectAll").unbind("click");
	$("#dmySelectAll").click(function() {
		$(".dmySelect").prop("checked", $("#dmySelectAll").prop("checked"));
	});


	$(".dmyDelete").unbind("click");
	$(".dmyDelete").eachClick(function(obj) {
		var values = $(obj).attr('did');
		confirm(Soopfw.t("Really want to delete this email template?"), Soopfw.t("delete?"), function() {
			ajax_success("/system/delete_email_template.ajax",{
				id: values
				},Soopfw.t("Server deleted"), Soopfw.t("delete?"),function() {
				$("#row_"+values[1]).remove();
			});
		});
	});
}

/**
 * if the server save succeed, it will call this method
 */
function save_email_template_success() {
	//Close the server dialog
	$("#"+dlg_id).dialog("destroy");
	$("#"+dlg_id).remove();
	wait_dialog();
	location.reload();
}