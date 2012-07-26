Soopfw.behaviors.system_updater = function() {
		if(Soopfw.config.update_db_modules == undefined) {
			return;
		}
		var max_updates = Soopfw.config.update_db_modules.length;
		var current = 1;
		Soopfw.ajax_queue_init("system_updatedb");

		Soopfw.progress("system_updatedb", max_updates, Soopfw.t("Updating Module 1 from icomplete", { 'icomplete': max_updates}), "#updatedb_status");

		for(var i = 0; i < Soopfw.config.update_db_modules.length; i++) {

			var module = Soopfw.config.update_db_modules[i];
			Soopfw.ajax_queue("system_updatedb",{

				url: "/system/update/"+module+"/js",
				success: function(module){
					var module_updated = Soopfw.config.update_db_modules[current];
					if(current >= max_updates) {
						Soopfw.progress_update("system_updatedb", Soopfw.t("Updating Module completed", {'icurrent': current, 'icomplete': max_updates}));
						location.href = "/admin/system/updatedb";
						return;
					}
					current++;
					Soopfw.progress_update("system_updatedb", Soopfw.t("Updating Module: @module (icurrent from icomplete)", {'@module': module_updated, 'icurrent': current, 'icomplete': max_updates}));

				}
			});
		}
		Soopfw.ajax_queue_start("system_updatedb");

	};