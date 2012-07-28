if(dlg_id == undefined) {
	var dlg_id = "";
}

if (current_selected_email_template_creator == undefined) {
	var current_selected_email_template_creator = "";
}

Soopfw.behaviors.system_email_template_creator = function() {

	$('.system_create_email_template').off('click').on('click', function() {
		current_selected_email_template_creator = $(this).attr('did');
		open_change_dialog();
	});

	$('.system_change_email_template').off('click').on('click', function() {
		current_selected_email_template_creator = $(this).attr('did');
		open_change_dialog($('#'+current_selected_email_template_creator).val());
	});

};

function open_change_dialog(id) {
	var params = [];

	var name = $('#'+current_selected_email_template_creator).attr('name');

	if(id != undefined) {
		params.push(id);
	}
	else {
		params.push("");
	}

	if (Soopfw.config.system_email_template_available_variables[name] != undefined) {
		params.push(Soopfw.config.system_email_template_available_variables[name]);
	}

	dlg_id = Soopfw.default_action_dialog(Soopfw.t("add/change email template"), 'system', 'change_email_template', params, {
		width: 800
	});
}

function save_email_template_success(template_id) {
	//Close the dialog
	$("#"+dlg_id).dialog("destroy");
	$("#"+dlg_id).remove();
	$('.email_template_selector').append($('<option value="' + template_id + '">' + template_id + '</option>'));
	$('#'+current_selected_email_template_creator).val(template_id);
}