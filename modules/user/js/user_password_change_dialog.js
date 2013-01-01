if (user_change_pw_dlg_id === undefined) {
	var user_change_pw_dlg_id = "";
}
function change_user_password_dialog(customer_id) {
	user_change_pw_dlg_id = Soopfw.default_action_dialog(Soopfw.t('Change password'), 'user', 'change_password', [customer_id]);
}

function user_change_password_success() {
	if (!empty(user_change_pw_dlg_id)) {
		$("#" + user_change_pw_dlg_id).dialog('destroy').remove();
	}
}