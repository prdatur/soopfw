/**
 * Enables table sorting for the sortable configuration elements like the field sort order.
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
Soopfw.behaviors.content_create_view = function() {
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
};