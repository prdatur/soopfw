Soopfw.behaviors.user_admin_userdata = function() {
	$("#change_password").unbind("click");
	$("#change_password").click(function(ele) {
		change_user_password_dialog(Soopfw.config.admin_userdata_user_id);
	});
};