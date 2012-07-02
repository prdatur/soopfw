function change_language(language, field, value, return_function) {
	var form_data = {
		lang: language,
		value: value
	};
	ajax_request("/translation/language_change.ajax",form_data,return_function);
}

function change_active(language, value) {
	var color = 'red';
	if(value == 1) {
		color = 'green';
	}
	change_language(language, "enabled",value,function() {
		$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-red");
		$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-green");
		$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-yellow");
		$("#activeImg_"+language).addClass("ui-icon-soopfw-status-"+color);
	});
}

Soopfw.behaviors.languages_language_config = function() {

	$("#multi_action").prop("value", "");
	$("#multi_action").unbind("change");
	$("#multi_action").change(function() {
		var value = $(this).prop("value");
		if(value == "") {
			return false;
		}

		if(value == "deactivate") {
			confirm(Soopfw.t("Really want to deactivate this language?"), Soopfw.t("deactivate?"), function() {
				$(".dmySelect").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var language = $(obj).prop("value");
						change_language(language, "enabled", 0, function() {
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-red");
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-green");
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeImg_"+language).addClass("ui-icon-soopfw-status-red");
						});
					}
				});
				$(".dmySelect").prop("checked", false);
				$("#dmySelectAll").prop("checked", false);
			});
		}
		if(value == "activate") {
			confirm(Soopfw.t("Really want to activate this language?"), Soopfw.t("activate?"), function() {
				$(".dmySelect").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var language = $(obj).prop("value");
						change_language(language, "enabled", 1, function() {
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-red");
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-green");
							$("#activeImg_"+language).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeImg_"+language).addClass("ui-icon-soopfw-status-green");
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
		var values = $(obj).attr("language");

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