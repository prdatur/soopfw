function change_module(modul, field, value, return_function) {
	var form_data = {
		modul: modul,
		field: field,
		value: value
	};
	ajax_request("/system/module_change.ajax",form_data,return_function);
}

function change_active(module, value) {
	var color = 'red';
	if(value == 1) {
		color = 'green';
	}
	change_module(module, "enabled",value,function() {
		$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-red");
		$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-green");
		$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-yellow");
		$("#activeImg_"+module).addClass("ui-icon-soopfw-status-"+color);
	});
}

Soopfw.behaviors.system_modules_config = function() {

	$("#multi_action").prop("value", "");
	$("#multi_action").unbind("change");
	$("#multi_action").change(function() {
		var value = $(this).prop("value");
		if(value == "") {
			return false;
		}

		if(value == "deactivate") {
			confirm(Soopfw.t("Really want to deactivate this module?"), Soopfw.t("deactivate?"), function() {
				$(".dmySelect").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var module = $(obj).prop("value");
						change_module(module, "enabled", 0, function() {
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-red");
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-green");
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeImg_"+module).addClass("ui-icon-soopfw-status-red");
						});
					}
				});
				$(".dmySelect").prop("checked", false);
				$("#dmySelectAll").prop("checked", false);
			});
		}
		if(value == "activate") {
			confirm(Soopfw.t("Really want to activate this module?"), Soopfw.t("activate?"), function() {
				$(".dmySelect").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var module = $(obj).prop("value");
						change_module(module, "enabled", 1, function() {
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-red");
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-green");
							$("#activeImg_"+module).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeImg_"+module).addClass("ui-icon-soopfw-status-green");
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

	$(".dmyActive").unbind("click");
	$(".dmyActive").eachClick(function(obj) {
		var values = $(obj).attr("module");

		var buttons = {};
		buttons[Soopfw.t("activate")] = function() {
			change_active(values , 1);
			$(this).trigger('close');
		};
		buttons[Soopfw.t("deactivate")] = function() {
			change_active(values , 0);
			$(this).trigger('close');
		};

		Soopfw.chooser_dialog(Soopfw.t("action"), buttons);
	});
};