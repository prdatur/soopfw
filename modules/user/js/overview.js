function change_user(user_id, field, value, return_function) {
	var form_data = {
		user_id: user_id,
		field: field,
		value: value
	};
	ajax_request("/user/user_change.ajax",form_data,return_function);
}

function change_active(user_id, value) {
	var color = 'red';
	if(value == 'yes') {
		color = 'green';
	}
	change_user(user_id, "active",value,function() {
		$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-red");
		$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-green");
		$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-yellow");
		$("#activeUserImg_"+user_id).addClass("ui-icon-soopfw-status-"+color);
	});
}

function add_user_success(user_id) {
	location.href = "/user/edit/"+user_id;
}

var add_user_id = 0;
Soopfw.behaviors.user_admin_overview = function() {

	$("#add_user").unbind("click");
	$("#add_user").click(function() {
		add_user_id = Soopfw.default_action_dialog(Soopfw.t("Add user"), 'user', 'add_user');
		console.log(add_user_id);
	});

	$("#form_id_form_add_user_btn_cancel").unbind("click");
	$("#form_id_form_add_user_btn_cancel").click(function() {
		console.log(add_user_id);
		$("#"+add_user_id).dialog("destroy");
		$("#"+add_user_id).remove();
	});

	$(".dmyChangePassword").unbind("click");
	$(".dmyChangePassword").eachClick(function(ele) {
		change_user_password_dialog(parseID(ele, 1));
	});


	$("#multi_action").prop("value", "");
	$("#multi_action").unbind("change");
	$("#multi_action").change(function() {
		var value = $(this).prop("value");
		if(value == "") {
			return false;
		}

		if(value == "delete") {
			confirm(Soopfw.t("Really want to delete this user?"), Soopfw.t("delete?"), function() {
				$(".dmySelectUser").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						ajax_request("/user/user_delete.ajax",{user_id: $(obj).prop("value")},function() {
							$("#user_row_"+$(obj).prop("value")).remove();
						});
					}
				});
				$(".dmySelectUser").prop("checked", false);
				$("#dmySelectAllUser").prop("checked", false);
			});
		}
		if(value == "deactivate") {
			confirm(Soopfw.t("Really want to deactivate this user?"), Soopfw.t("deactivate?"), function() {
				$(".dmySelectUser").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var user_id = $(obj).prop("value");
						change_user(user_id, "active", 'no', function() {
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-red");
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-green");
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeUserImg_"+user_id).addClass("ui-icon-soopfw-status-red");
						});
					}
				});
				$(".dmySelectUser").prop("checked", false);
				$("#dmySelectAllUser").prop("checked", false);
			});
		}
		if(value == "activate") {
			confirm(Soopfw.t("Really want to activate this user?"), Soopfw.t("activate?"), function() {
				$(".dmySelectUser").each(function(a, obj) {
					if($(obj).prop("checked") == true) {
						var user_id = $(obj).prop("value");
						change_user(user_id, "active", 'yes', function() {
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-red");
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-green");
							$("#activeUserImg_"+user_id).removeClass("ui-icon-soopfw-status-yellow");
							$("#activeUserImg_"+user_id).addClass("ui-icon-soopfw-status-green");
						});
					}
				});
				$(".dmySelectUser").prop("checked", false);
				$("#dmySelectAllUser").prop("checked", false);
			});
		}
		$("#multi_action").prop("value", "");

	});

	$("#dmySelectAllUser").unbind("click");
	$("#dmySelectAllUser").click(function() {
		$(".dmySelectUser").prop("checked", $("#dmySelectAllUser").prop("checked"));
	});

	$(".dmyActiveUser").unbind("click");
	$(".dmyActiveUser").eachClick(function(obj) {
		var values = parseID(obj);

		var buttons = {};
		buttons[Soopfw.t("activate")] = function() {
			change_active(values[1] , 'yes');
			$(this).trigger('close');
		};
		buttons[Soopfw.t("deactivate")] = function() {
			change_active(values[1] , 'no');
			$(this).trigger('close');
		};

		Soopfw.chooser_dialog(Soopfw.t("action"), buttons);
	});

	$(".dmyDeleteUser").unbind("click");
	$(".dmyDeleteUser").eachClick(function(obj) {
		var values = parseID(obj);
		confirm(Soopfw.t("Really want to delete this user?"), Soopfw.t("delete?"), function() {
			ajax_success("/user/user_delete.ajax",{user_id: values[1]},Soopfw.t("User deleted"), Soopfw.t("delete?"),function() {
				$("#user_row_"+values[1]).remove();
			});
		});
	});


	$(".showUserAddress").each(function(k,v) {
		var values = parseID(v);
		$('#showUserAddress_'+values[1]).qtip({
			content: {text: $("#userDefaultAdress_"+values[1])},
			position: {my: 'top left', at: 'bottom right',offset: 10,target: 'mouse', adjust: {mouse: true, x: 22}},
			style: {classes: 'ui-tooltip-shadow ui-tooltip-light ui-tooltip-rounded'}
		});
	});
};