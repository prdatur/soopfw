Soopfw.behaviors.admin_user_rights = function() {
	var all_rights = Soopfw.config.user_rights.all_rights;
	var user_rights_user = Soopfw.config.user_rights.user_rights_user;
	var user_rights_group = Soopfw.config.user_rights.user_rights_group;


	var right_groups = Soopfw.config.user_rights.right_groups;
	var group_assignments = Soopfw.config.user_rights.group_assignments;

	var tmp_right_group_check = {};
	for(var i in all_rights)
	{
		if(all_rights.hasOwnProperty(i))
		{
			var right = all_rights[i];
			var lang_key = right.replace(/\./ig,"_");

			var selected_not_yet_owned = true;
			var selected_group = false;
			var selected_force_yes = false;
			var selected_force_no = false;

			if(user_rights_user["-"+right] != undefined)
			{
				selected_force_no = true;
				//selected_not_yet_owned = false;
			}
			else if(user_rights_user[right] != undefined)
			{
				selected_force_yes = true;
				//selected_not_yet_owned = false;
			}
			else if(user_rights_group[right] != undefined)
			{
				selected_group = true;
				selected_not_yet_owned = false;
			}

			if(user_rights_group[right] != undefined)
			{
				selected_not_yet_owned = false;
			}

			var checkbox_notowned = create_element({input: 'input', css:{width:"20px"}, attr:{type: 'radio', name: right, id: lang_key.toLowerCase()+"_notowned",value: 'notowned', checked: selected_not_yet_owned}, click: function(){
				prozess_permission_handler(this);
			}});
			var checkbox_group = create_element({input: 'input', css:{width:"20px"}, attr:{type: 'radio', name: right, id: lang_key.toLowerCase()+"_g",value: 'g'}, click: function(){
				prozess_permission_handler(this);
			}});
			var checkbox_yes = create_element({input: 'input', css:{width:"20px"}, attr:{type: 'radio', name: right, id: lang_key.toLowerCase()+"_y",value: 'y'}, click: function(){
				prozess_permission_handler(this);
			}});
			var checkbox_no = create_element({input: 'input', css:{width:"20px"}, attr:{type: 'radio', name: right, id: lang_key.toLowerCase()+"_n",value: 'n'}, click: function(){
				prozess_permission_handler(this);
			}});

			if(selected_not_yet_owned == false)
			{
				$(checkbox_notowned).prop("disabled", true);
			}

			if(selected_not_yet_owned == true)
			{
				$(checkbox_group).prop("disabled", true);
			}

			$("#rights").append(
				create_element({input: 'tr', append:[
					create_element({input: 'td', attr:{html: Soopfw.t(right)}}),
					create_element({input: 'td', attr:{id: lang_key.toLowerCase()+"_td_notowned"}, append:[checkbox_notowned,create_element({input: 'img', attr:{src:'/1x1_spacer.gif'}})]}),
					create_element({input: 'td', attr:{id: lang_key.toLowerCase()+"_td_g"}, append:[checkbox_group,create_element({input: 'img', attr:{src:'/1x1_spacer.gif'}})]}),
					create_element({input: 'td', attr:{id: lang_key.toLowerCase()+"_td_y"}, append:[checkbox_yes,create_element({input: 'img', attr:{src:'/1x1_spacer.gif'}})]}),
					create_element({input: 'td', attr:{id: lang_key.toLowerCase()+"_td_n"}, append:[checkbox_no,create_element({input: 'img', attr:{src:'/1x1_spacer.gif'}})]})
				]})
			);

			if(selected_not_yet_owned == true)
			{
				$(checkbox_notowned).addClass("linkedElement");
				$("#"+lang_key.toLowerCase()+"_td_notowned").addClass("linkedElement");
				$(checkbox_group).css("display", "none");
			}
			else
			{
				$(checkbox_notowned).css("display", "none");
				$(checkbox_group).addClass("linkedElement");
				$("#"+lang_key.toLowerCase()+"_td_g").addClass("linkedElement");
			}
			$("#"+lang_key.toLowerCase()+"_td_y").addClass("linkedElement");
			$("#"+lang_key.toLowerCase()+"_td_n").addClass("linkedElement");

			$(checkbox_yes).addClass("linkedElement");
			$(checkbox_no).addClass("linkedElement");

			$(checkbox_notowned).prop("checked", selected_not_yet_owned);
			$(checkbox_group).prop("checked", selected_group);
			$(checkbox_yes).prop("checked", selected_force_yes);
			$(checkbox_no).prop("checked", selected_force_no);
		}
	}

	for(var i in right_groups)
	{
		if(right_groups.hasOwnProperty(i))
		{
			var group = right_groups[i];
			var isMember = false;

			if(group_assignments[group.group_id] != undefined)
			{
				isMember = true;
				for(var right in group['rights'])
				{
					if(group['rights'].hasOwnProperty(right))
					{
						if(tmp_right_group_check[right] == undefined)
						{
							tmp_right_group_check[right] = [];
						}
						tmp_right_group_check[right].push(group.group_id);
					}
				}
			}

			var group_checkbox = create_element({input: 'input', attr:{type: 'checkbox', id:group.group_id,value:'isMember'}, click: function(){
				prozess_group_member_handler(this);
			}});
			$("#rights_groups").append(
				create_element({input: 'tr', append:[
					create_element({input: 'td', attr:{html: group.title}}),
					create_element({input: 'td', append:[group_checkbox]})
				]})
			);

			$(group_checkbox).prop("checked", isMember);
		}
	}


	function prozess_permission_handler(elm)
	{
		var id = $(elm).prop("id");
		data = {'right': $(elm).prop("name"), 'user_id': Soopfw.config.user_rights.user_id,'value': $(elm).prop("value")};
		ajax_request("/user/user_permission_change.ajax", data, function() {});
	}

	function prozess_group_member_handler(elm)
	{
		var id = $(elm).prop("id");
		var value = "remove";
		if($(elm).prop("checked") == true)
		{
			value = "add";
		}
		var data = {'group_id': id, 'user_id': Soopfw.config.user_rights.user_id,'value': value};
		ajax_request("/user/user_permission_group_change.ajax", data, function()
		{
			var group = right_groups[id];
			for(var right in group['rights'])
			{
				if(group['rights'].hasOwnProperty(right))
				{
					var check_key = right.replace(/\./ig,"_");
					if(tmp_right_group_check[right] == undefined)
					{
						tmp_right_group_check[right] = [];
					}
					if($(elm).prop("checked") == true)
					{
						if($("#"+check_key+"_notowned").prop("checked") == true)
						{

							$("#"+check_key+"_notowned").prop("checked", false);
							$("#"+check_key+"_g").prop("checked", true);
						}
						$("#"+check_key+"_notowned").prop("disabled", true);
						$("#"+check_key+"_g").prop("disabled", false);

						$("#"+check_key+"_notowned").css("display", "none");
						$("#"+check_key+"_notowned").removeClass("linkedElement");
						$("#"+check_key+"_td_notowned").removeClass("linkedElement");
						$("#"+check_key+"_g").css("display", "inline");
						$("#"+check_key+"_g").addClass("linkedElement");
						$("#"+check_key+"_td_g").addClass("linkedElement");

						tmp_right_group_check[right].push(group.group_id);
					}
					else
					{
						tmp_right_group_check[right] = $.grep(tmp_right_group_check[right], function(val) { return val != group.group_id });
						if(tmp_right_group_check[right].length <= 0)
						{
							if($("#"+check_key+"_g").prop("checked") == true)
							{
								$("#"+check_key+"_notowned").prop("checked", true);
								$("#"+check_key+"_g").prop("checked", false);
							}
							$("#"+check_key+"_notowned").prop("disabled", false);
							$("#"+check_key+"_g").prop("disabled", true);
							$("#"+check_key+"_notowned").addClass("linkedElement");
							$("#"+check_key+"_notowned").css("display", "inline");
							$("#"+check_key+"_td_notowned").addClass("linkedElement");
							$("#"+check_key+"_g").removeClass("linkedElement");
							$("#"+check_key+"_g").css("display", "none");
							$("#"+check_key+"_td_g").removeClass("linkedElement");
						}
					}
				}
			}
		});
	}
}