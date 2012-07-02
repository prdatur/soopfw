
if(dlg_id == undefined) {
	var dlg_id = "";
}

var first = true;
Soopfw.behaviors.content_change_content = function() {

	if(first) {
		$("#form_id_menu_chooser").css("width", "350px")
		.after($("<a href='javascript:void(0);' class='form_button'>"+Soopfw.t("clear")+"</a>").click(function() {
			$("#form_id_menu_chooser").val("");
			$("#form_id_create_content_form_menu_chooser_hidden").val("");
		}))
		.after($("<a href='javascript:void(0);' class='form_button'>"+Soopfw.t("select parent")+"</a>").click(function() {
			dlg_id = Soopfw.default_action_dialog(Soopfw.t("Select a menu entry"), 'content', 'content_menu_chooser');
		}));
	}

	first = false;
	$(".select_menu").unbind('click');
	$(".select_menu").click(function() {
		$("#form_id_menu_chooser").val($(this).attr("menu_id")+":"+$(this).attr("parent")+": "+$(this).html());
		$("#form_id_create_content_form_menu_chooser_hidden").val($(this).attr("menu_id")+":"+$(this).attr("parent")+": "+$(this).html());
		$("#"+dlg_id).dialog("destroy");
		$("#"+dlg_id).remove();
	});

	$(".tablednd").tableDnD({
		dragHandle: 'handle_cell > .tabledrag-handle',
		enableIndent: false,
		indentSerializeAsObject: true,
		onDrop:function(table) {
			var index = 0;
			$("> tbody > tr", table).each(function() {
				$("*[name]", this).each(function() {

					var tmp = /^([^\[]+)\[[0-9]+\](.+)$/.exec($(this).prop("name"));
					$(this).prop("name", RegExp.$1+"["+index+"]"+RegExp.$2);

					var tmp = /^([^\[]+)\[[0-9]+\](.+)$/.exec($(this).prop("id"));
					$(this).prop("id", RegExp.$1+"["+index+"]"+RegExp.$2);
				});

				index++;
			});
		}
	});

	$(".add_another_item").unbind("click");
	$(".add_another_item").click(function() {

		var id = $(this).attr("did");
		var index = $("#add_more_container_"+id+" > tbody tr").length;
		$.ajax({
			url: '/content/create_content_add_another_item.ajax',
			dataType: 'html',
			data: {id: id, index: index},
			type: 'POST',
			success: function(result) {
				$("#add_more_container_"+id+" > tbody").append(result);
			}
		});


	});
}