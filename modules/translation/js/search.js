Soopfw.behaviors.translation_search = function() {
	$("#rebuild_languages").unbind("click");
	$("#rebuild_languages").click(function() {
		wait_dialog(Soopfw.t("Rebuilding translations"));
		ajax_request("/translation/build_languages.ajax", {}, function() {
			success_alert(Soopfw.t("Translations rebuilded"));
		});
	});
};