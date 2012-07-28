var current_selected_textarea = null;
Soopfw.behaviors.system_change_email_template = function() {
	$('textarea').off('focus').on('focus',function() {
		current_selected_textarea = this;
	});
	$('input:text').off('focus').on('focus',function() {
		current_selected_textarea = this;
	});
};

function system_change_email_template_insert_variable(variable) {
	$(current_selected_textarea).replaceSelection('{' + variable + '}').focus();
}