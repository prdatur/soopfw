var timeout_user_ping = null;

Soopfw.behaviors.user_ping = function() {
	request_ping();
};

function request_ping() {
	if (!empty(timeout_user_ping)) {
		clearTimeout(timeout_user_ping);
		timeout_user_ping = null;
	}
	timeout_user_ping = window.setTimeout(function() {
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/user/ping.ajax',
			async: true,
			success: function() {
				request_ping();
			}
		});
	}, Soopfw.config.user_ping_time*60000);
}