var firsttime = true;

Soopfw.behaviors.admin_user_groups = function() {
	if(firsttime == false) {
		return;
	}
	firsttime = false;
	var groups = Soopfw.config.admin_user_groups;
	$(document).ready(function()
	{
		$("#createGroup").click(function () {
			var html = create_element({input:'div',append:[
				create_element({input: 'label', attr:{"for": 'groupCreateInput', html: "&nbsp;"+Soopfw.t("Groupname")+" :&nbsp;&nbsp;"}}),
				create_element({input:'input',attr:{type:'text', id:'groupCreateInput'},css:{width:'270px'}})
			]});

			var buttons = {}
			buttons[Soopfw.t("add")] = function() {
				if(trim($("#groupCreateInput").prop("value")) == "") {
					alert(Soopfw.t("Missing group name"));
				}
				else {
					ajax_request("/user/group_add.ajax",{title: $("#groupCreateInput").prop("value")}, function(result){
						add_group({group_id: result.group_id, title: $("#groupCreateInput").prop("value")});
						$(html).remove();
						$("#tdGroupID"+"_"+result.group_id).click();
					});
				}
			};
			buttons[Soopfw.t("cancel")] = function() {
				$(html).dialog("destroy");
			};

			$(html).dialog({
				title: Soopfw.t("Add group"),
				buttons: buttons
			});
		});

		for(var i in groups) {
			if(groups.hasOwnProperty(i)) {
				add_group(groups[i]);
			}
		}
	});

};

function add_group(group)
{
	$("#group_tbody").append(
		create_element({input: 'tr', attr:{id: "delTrGroup"+"_"+group.group_id, "class": 'group_list'},append:[
			create_element({input: 'td', attr:{id:"tdGroupID"+"_"+group.group_id,"class":'ui-state-default linkedElement',html: group.title}, click: function()
			{
				var values = parseID(this);
				var tmpGroupID = values[1];

				$("tr.group_list > td").removeClass("ui-state-active");
				$("tr#delTrGroup"+"_"+tmpGroupID+" > td").addClass("ui-state-active");
				$.ajax({
					url: "/user/user_groups_right_change/"+tmpGroupID+".ajax_html",
					dataType: 'html',
					success: function(result) {
						$("#group_tabs").html(result);
					}
				});
			}}),
			create_element({input: 'td', attr: {"class": 'ui-state-default'}, css: {"text-align": 'right'}, append:[
				create_element({input: 'a', attr:{id: 'group_id'+'_'+group.group_id,href: 'javascript:void(0);'}, append:[
					create_element({input: 'img', attr:{src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-cancel'}})
				], click: function()
				{
					var values = parseID(this);
					delete_group(values[2]);
				}})
			]})
		]})
	);
}

function delete_group(group_id)
{
	confirm(Soopfw.t("Do you want to delete this group?"),Soopfw.t("delete?"),function(){
		ajax_success("/user/group_delete.ajax", {group_id: group_id}, Soopfw.t("Group deleted"), '', function() {
			$("#delTrGroup"+"_"+group_id).remove();
		});
	});
}