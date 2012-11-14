/**
 * Provides all needed functions to handle content creation.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */

// Create a variable to store the current dialog id.
if(dlg_id == undefined) {
	var dlg_id = "";
}

// Will determine that we run the behaviour for the first time.
var first = true;
Soopfw.behaviors.content_change_content = function() {

	// Add additional html for the menu chooser within the first call of this behaviour.
	if(first) {
		// Add the chooser and a clear button.
		$("#form_id_create_content_form_menu_chooser").css("width", "350px")
		.after($("<a href='javascript:void(0);' class='form_button'>"+Soopfw.t("clear")+"</a>").click(function() {
			$("#form_id_create_content_form_menu_chooser").val("");
			$("#form_id_create_content_form_menu_chooser_hidden").val("");
		}))
		.after($("<a href='javascript:void(0);' class='form_button'>"+Soopfw.t("select parent")+"</a>").click(function() {
			dlg_id = Soopfw.default_action_dialog(Soopfw.t("Select a menu entry"), 'content', 'content_menu_chooser');
		}));
	}

	first = false;

	// Enable the menu chooser.
	$(".select_menu").off('click').on('click', function() {
		$("#form_id_create_content_form_menu_chooser").val($(this).attr("menu_id")+":"+$(this).attr("parent")+": "+$(this).html());
		$("#form_id_create_content_form_menu_chooser_hidden").val($(this).attr("menu_id")+":"+$(this).attr("parent")+": "+$(this).html());
		$("#"+dlg_id).dialog("destroy");
		$("#"+dlg_id).remove();
	});

	// Enable table sorter.
	enable_tablednd();

	// If we click on the add more button, we will get another field group of this group, so we also need to reload the
	// table sorter to directly handle the newly added group of inputs.
	$(".add_another_item").off('click').on('click', function() {

		var id = $(this).attr("did");
		var index = $("#add_more_container_"+id+" > tbody tr").length;
		$.ajax({
			url: '/content/create_content_add_another_item.ajax',
			dataType: 'html',
			data: {id: id, index: index},
			type: 'POST',
			success: function(result) {
				$("#add_more_container_"+id+" > tbody").append(result);
				enable_tablednd();
			}
		});


	});
};

/**
 * Enables the table sort.
 */
function enable_tablednd() {
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
}