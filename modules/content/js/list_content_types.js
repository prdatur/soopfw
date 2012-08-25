
if(dlg_id == undefined) {
	var dlg_id = "";
}

Soopfw.behaviors.content_list_content_types = function() {
	enable_clicks();
};

function open_change_dialog(id) {
	var params = null;
	if(id != undefined) {
		params = [id];
	}
	dlg_id = Soopfw.default_action_dialog(Soopfw.t("add/change content type"), 'content', 'manage_content_type', params);
}
function enable_clicks() {

	$("#add_content_type").unbind("click");
	$("#add_content_type").click(function() {
		open_change_dialog();
	});

	$(".dmyChange").unbind("click");
	$(".dmyChange").click(function() {
		open_change_dialog($(this).attr("id"));
	});

	$(".dmyDelete").unbind("click");
	$(".dmyDelete").click(function() {
		var id = $(this).attr("id");
		confirm(Soopfw.t("Do you really want to delete this content type, ALL content for this content type will be deleted too"), Soopfw.t("delete?"), function() {
			ajax_success("/content/delete_content_type.ajax", {content_type: id}, Soopfw.t("content type deleted"), Soopfw.t("Success"), function() {
				$('tr[did="'+id+'"]').remove();
				if($("#content_type_tbody > tr").length <= 1) {
					$("tbody > tr:not(tr[did])").show();
				}
			});
		})
	});
}

function replace_new_content_type_row(result){
	$('tr[did="'+result.content_type+'"]').replaceWith(get_content_type_row(result));
	enable_clicks();
	close_dialog();
}

function add_new_content_type_row(result) {
	$("tbody > tr:not(tr[did])").hide();
	$("#content_type_tbody").append(get_content_type_row(result));

	enable_clicks();
	close_dialog();
}

function get_content_type_row(result) {

	var tr = $('<tr did="'+result.content_type+'">');
	tr.append(create_element({input: 'td', append:[
		create_element({input: 'a', attr:{href:'/admin/content/manage_content_type_fields/'+result.content_type, html: result.description}})
	]}));

	var options = create_element({input: 'td', css: {"text-align": 'right'},append:[
		$('<a href="javascript:void(0);" class="dmyChange linkedElement option_links" id="'+result.content_type+'">').append(create_element({input: 'img', attr:{title: Soopfw.t("Edit"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-edit'}})),
		$('<a href="javascript:void(0);" class="dmyDelete linkedElement option_links" id="'+result.content_type+'">').append(create_element({input: 'img', attr:{title: Soopfw.t("delete?"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-cancel'}})),
	]});
	tr.append(options);
	return tr;
}

function close_dialog() {
	$("#"+dlg_id).dialog("destroy");
}