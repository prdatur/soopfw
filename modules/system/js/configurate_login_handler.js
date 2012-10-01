if(priority_change_dlg == undefined) {
	var priority_change_dlg = "";
}
Soopfw.behaviors.system_configurate_login_handler = function() {
	$(".tablednd").tableDnD({
		dragHandle: 'handle_cell > .tabledrag-handle',
		enableIndent: false,
		indentSerializeAsObject: true,
		onDrop:function(table) {
			var index = 0;
			$("> tbody > tr", table).each(function() {
				$("*[name]", this).each(function() {

					var tmp = /^([^\[]+)\[[0-9]+\]$/.exec($(this).prop("name"));
					$(this).prop("name", RegExp.$1+"["+index+"]");

					var tmp = /^([^\[]+)\[[0-9]+\]$/.exec($(this).prop("id"));
					$(this).prop("id", RegExp.$1+"["+index+"]");
				});

				index++;
			});
		}
	});

	$(".save_priority_changes").off('click').on('click', function () {
		var values = {};
		$("*[name]", $('.priority_change_data')).each(function() {
			if ($(this).prop('checked')) {
				values[$(this).attr('name')] = $(this).val();
			}

		});
		ajax_success('/system/save_login_handler_priority.ajax', values, Soopfw.t('Login handler priority changed successfully.'), null, function() {
			$("#"+priority_change_dlg).dialog("destroy");
			$("#"+priority_change_dlg).remove();
		});
	});

};