Soopfw.behaviors.content_admin_config = function() {
	$('#form_id_content_config_saveconfig').off('click').on('click', function() {
		wait_dialog(Soopfw.t('Please wait, while system is reindex your changes.'));
	});
};