
if(dlg_id == undefined) {
	var dlg_id = "";
}

Soopfw.behaviors.menu_overview = function() {
	enable_clicks();
};

function open_change_dialog(id) {
	var params = null;
	if(id != undefined) {
		params = [id];
	}
	dlg_id = Soopfw.default_action_dialog(Soopfw.t("add/change menu"), 'menu', 'change_menu', params);
}
function enable_clicks() {

	$("#add_menu").unbind("click");
	$("#add_menu").click(function() {
		open_change_dialog();
	});

	$(".dmyChange").unbind("click");
	$(".dmyChange").click(function() {
		open_change_dialog($(this).attr("id"));
	});

	$(".dmyDelete").unbind("click");
	$(".dmyDelete").click(function() {
		var id = $(this).attr("id");
		confirm(Soopfw.t("Do you really want to delete this menu, ALL submenus will be deleted too"), Soopfw.t("delete?"), function() {
			ajax_success("/admin/menu/delete_menu.ajax", {menu_id: id}, Soopfw.t("menu deleted"), Soopfw.t("Success"), function() {
				$('tr[did="'+id+'"]').remove();
				if($("#menu_tbody > tr").length <= 1) {
					$("tbody > tr:not(tr[did])").show();
				}
			});
		})
	});
}

function replace_new_menu_row(result){
	$('tr[did="'+result.menu_id+'"]').replaceWith(get_menu_row(result));
	enable_clicks();
	close_menu_dialog();
}

function add_new_menu_row(result) {
	$("tbody > tr:not(tr[did])").hide();
	$("#menu_tbody").append(get_menu_row(result));
	enable_clicks();
	close_menu_dialog();
}

function get_menu_row(result) {

	var tr = $('<tr did="'+result.menu_id+'">');
	tr.append(create_element({input: 'td', append:[
		create_element({input: 'a', attr:{href:'/admin/menu/entries/'+result.menu_id, html: result.title}})
	]}));

	var options = create_element({input: 'td', css: {"text-align": 'right'},append:[
		$('<a href="javascript:void(0);" class="dmyChange linkedElement option_links" id="'+result.menu_id+'">').append(create_element({input: 'img', attr:{title: Soopfw.t("Edit"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-edit'}})),
		$('<a href="javascript:void(0);" class="dmyDelete linkedElement option_links" id="'+result.menu_id+'">').append(create_element({input: 'img', attr:{title: Soopfw.t("delete?"),src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-cancel'}})),
	]});
	tr.append(options);
	return tr;
}

function close_menu_dialog() {
	$("#"+dlg_id).dialog("destroy");
}