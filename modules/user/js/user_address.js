Soopfw.behaviors.user_address = function() {
	$("#add_address").unbind("click");
	$("#add_address").click(function() {
		dlg_id = Soopfw.default_action_dialog(Soopfw.t("address"), 'user', 'add_address', [
			Soopfw.config.user_address.user_id
		]);
	});
	enable_icon_clicks();
};

function enable_icon_clicks() {
	$(".editAddress").unbind("click");
	$(".editAddress").click(function() {
		var addressID = parseID(this, 1);
		dlg_id = Soopfw.default_action_dialog(Soopfw.t("address"), 'user', 'add_address', [
			Soopfw.config.user_address.user_id,
			addressID
		]);
	});

	$(".deleteAddress").unbind("click");
	$(".deleteAddress").click(function() {
		var addressID = parseID(this, 1);
		confirm(Soopfw.t("Do you really want to delete this address"), Soopfw.t("delete?"), function() {
			ajax_success("/user/delete_address.ajax", {address_id: addressID}, Soopfw.t("address deleted"), Soopfw.t("Success"), function() {
				$("#addressTr_"+addressID).remove();
			});
		})
	});
}


function replace_new_address_row(result){
	$("#addressTr_"+result.id).replaceWith(get_address_row(result));
	enable_icon_clicks();
}

function get_address_row(result) {
	return create_element({input: 'tr', attr: {id: 'addressTr_'+result.id}, append: [
		create_element({input: 'td', attr: {html: result.email}}),
		create_element({input: 'td', attr: {html: get_address_info_cell(result)}}),
		create_element({input: 'td', attr: {html: Soopfw.t(result.group)}}),
		create_element({input: 'td', css: {'text-align': 'right'}, append:[
			create_element({input: 'a', attr: {"class": 'editAddress option_links', id: 'editAddress_'+result.id, href: 'javascript:void(0);'}, append:[
				create_element({input: 'img', attr:{title: Soopfw.t("Edit"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-edit'}})

			]}),
			create_element({input: 'a', attr: {"class": 'deleteAddress option_links', id: 'deleteAddress_'+result.id, href: 'javascript:void(0);'}, append:[
				create_element({input: 'img', attr:{title: Soopfw.t("delete?"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-cancel'}})

			]})
		]}),
	]});
}

function get_address_info_cell(result) {
	var output = [];
	var info_grous = [
		result.company,
		result.firstname + ' ' + result.lastname,
		result.nation + '-' + result.zip,
		result.city,
	];
	for(var i = 0; i < info_grous.length; i++) {
		var data = info_grous[i].trim();
		if(data != "" && data != "-") {
			output.push(data);
		}
	}

	return implode(", ", output)

}
function add_new_address_row(result) {
	$("#address_tbody").append(get_address_row(result));
	enable_icon_clicks();
}

function close_user_address_dialog() {
	$("#"+dlg_id).dialog("destroy");
}
