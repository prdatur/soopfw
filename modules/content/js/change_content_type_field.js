Soopfw.behaviors.content_change_content_type_field = function () {
	$('.field_group_selector').off('change').on('change', function() {
		$.ajax({
			type: 'POST',
			dataType: 'html',
			url: '/admin/content/content_type_field_get_config.ajax',
			async: true,
			data: {content_field: $(this).val()},
			success: function(result) {
				$('.field_group_selector_replace').html(result);
				Soopfw.reload_behaviors();
			}
		});
	});
};