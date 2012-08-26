var dlg_id_confirm = null;
var current_module = null;

Soopfw.behaviors.system_modules_config = function() {
	$(".dmyActive").off('click').on('click', function(obj) {
		current_module = $(this).attr('module');
		dlg_id_confirm = Soopfw.default_action_dialog(Soopfw.t('Enable/Disable Module'), 'system', 'precheck_module_state', [current_module]);
	});

	$('#btn_cancel_module').off('click').on('click', function() {
		current_module = null;
		Soopfw.config.system_enable_dependencies = null;
		destroy_confirm_dialog();
	});

	$('#btn_enable_module').off('click').on('click', function() {
		if (current_module != null) {
			enable_module(current_module);
		}
	});

	$('#btn_disable_module').off('click').on('click', function() {
		if (current_module != null) {
			disable_module(current_module);
		}
	});

	$('.module_update').off('click').on('click', function() {
		var object = $(this).attr('did');
		confirm(Soopfw.t('Do you want to update this module?'), Soopfw.t('Module update'), function() {
			wait_dialog();
			ajax_request('/system/install_module/' + object + '/js', null, function() {
				document.location.reload();
			});
		});
	});
};

function enable_module(module) {
	if(Soopfw.config.system_enable_dependencies == undefined || Soopfw.config.system_enable_dependencies == null) {
			return;
	}

	wait_dialog(Soopfw.t('Please wait...'), '');
	Soopfw.config.system_enable_dependencies.push(module);

	var max_updates = Soopfw.config.system_enable_dependencies.length;
	var current = 1;
	Soopfw.ajax_queue_init("system_enable_module");

	Soopfw.progress("system_enable_module", max_updates, Soopfw.t("Enable module 1 from icomplete", { 'icomplete': max_updates}), "#popup_message");

	for(var i = 0; i < Soopfw.config.system_enable_dependencies.length; i++) {

		var enable_module = Soopfw.config.system_enable_dependencies[i];
		Soopfw.ajax_queue("system_enable_module",{

			url: "/system/update_module/" + enable_module + "/js",
			success: function(){
				var module_updated = Soopfw.config.system_enable_dependencies[current];
				if(current >= max_updates) {
					Soopfw.progress_update("system_enable_module", Soopfw.t("All modules enabled", {'icurrent': current, 'icomplete': max_updates}));
					location.href = "/admin/system/modules";
					return;
				}
				current++;
				Soopfw.progress_update("system_enable_module", Soopfw.t("Enable module: @module (icurrent from icomplete)", {'@module': module_updated, 'icurrent': current, 'icomplete': max_updates}));

			}
		});
	}
	Soopfw.ajax_queue_start("system_enable_module");
}

function disable_module(module) {
	if(Soopfw.config.system_disable_dependencies == undefined || Soopfw.config.system_disable_dependencies == null) {
			return;
	}

	wait_dialog(Soopfw.t('Please wait...'), '');
	Soopfw.config.system_disable_dependencies.push(module);

	var max_updates = Soopfw.config.system_disable_dependencies.length;
	var current = 1;
	Soopfw.ajax_queue_init("system_disable_module");

	Soopfw.progress("system_disable_module", max_updates, Soopfw.t("Disable module 1 from icomplete", { 'icomplete': max_updates}), "#popup_message");

	for(var i = 0; i < Soopfw.config.system_disable_dependencies.length; i++) {

		var enable_module = Soopfw.config.system_disable_dependencies[i];
		Soopfw.ajax_queue("system_disable_module",{

			url: "/system/disable_module.ajax",
			type: 'POST',
			data: {
				'module': enable_module
			},
			success: function(){
				var module_updated = Soopfw.config.system_disable_dependencies[current];
				if(current >= max_updates) {
					Soopfw.progress_update("system_disable_module", Soopfw.t("All modules disabled", {'icurrent': current, 'icomplete': max_updates}));
					location.href = "/admin/system/modules";
					return;
				}
				current++;
				Soopfw.progress_update("system_disable_module", Soopfw.t("Disable module: @module (icurrent from icomplete)", {'@module': module_updated, 'icurrent': current, 'icomplete': max_updates}));

			}
		});
	}
	Soopfw.ajax_queue_start("system_disable_module");
}

function destroy_confirm_dialog() {
	$("#"+dlg_id_confirm).dialog("destroy").remove();
}