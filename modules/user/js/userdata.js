Soopfw.add_tab_behaviors(function() {
	$("#change_password").off('click').on('click', function(ele) {
		change_user_password_dialog(Soopfw.config.admin_userdata_user_id);
	});
});