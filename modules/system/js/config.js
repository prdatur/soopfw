Soopfw.behaviors.system_config = function() {
	$(".change_login_handler_priority").off('click').on('click', function() {
		priority_change_dlg = Soopfw.default_action_dialog(Soopfw.t('Change login handler priority'), 'system', 'configurate_login_handler');
	});
};