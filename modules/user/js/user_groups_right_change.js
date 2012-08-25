var rights = {};
var group_id = 0;
var right_store = {};
Soopfw.behaviors.admin_user_groups_right_change = function() {
	rights = Soopfw.config.admin_user_groups_right_change_rights_array;
	group_id = Soopfw.config.admin_user_groups_right_change_group_id;
	$(document).ready(function()
	{
		$("#reset_rights").click(function(){
			read_rights();
		});

		$("#save_rights").click(function(){
			confirm(Soopfw.t("Do you want to save the new rights?"),Soopfw.t("Save"), function(){
				var data = {
					'group_id': group_id
				};
				var c = 0;
				for(var i in right_store) {

					if(right_store.hasOwnProperty(i)) {
						if(right_store[i] == "1") {
							data['rights['+c+']'] = i;
							c++;
						}
					}

				}
				ajax_request("/user/group_save_rights.ajax",data, function() {
					success_alert(Soopfw.t("Rights saved"));
				});
			});

		});
		read_rights($(this).prop("id"));

	});
};

function read_rights()
{
	Soopfw.ajax_loader('ajax_loader', 'load_group_rights');
	ajax_request("/user/group_read_rights.ajax",{ 'group_id': group_id }, function(result) {
		$("#rights_tbody").html("");
		for(var i in rights) {
			if(rights.hasOwnProperty(i)) {
				var right = i;

				var right_checkbox = create_element({input: 'input', attr:{id:'chk_'+right, "class": 'rightCheckbox', type: 'checkbox', value: right}});

				$(right_checkbox).click(function() {
					if($(this).prop("checked") == true) {
						right_store[$(this).prop("value")] = 1;
					}
					else {
						right_store[$(this).prop("value")] = 0;
					}
				});
				$("#rights_tbody").append(
					create_element({input: 'tr',append:[
						create_element({input: 'td',append:[
							create_element({input: 'label', attr:{"for": 'chk_'+right, html: Soopfw.t(right)}})
						]}),
						create_element({input: 'td',css: {"text-align": 'center'}, append:[right_checkbox]})
					]})
				);
				if(result.rights[right] != undefined && result.rights[right] == 1) {
					right_store[right] = 1;
					$(right_checkbox).prop("checked", true);
				}
				else {
					right_store[right] = 0;
					$(right_checkbox).prop("checked", false);
				}
			}
		}
		Soopfw.ajax_loader('ajax_loader', 'load_group_rights');
	});
}