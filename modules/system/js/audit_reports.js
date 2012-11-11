Soopfw.behaviors.system_audit_reports = function() {
	$('.audit_tooltip').qtip({
		position: {my: 'bottom center', at: 'top center', target: this},
		style: {
			classes: 'ui-tooltip-shadow ui-tooltip-youtube audit_report_tooltip'
		}
	});
};