function change_user_password_dialog(customer_id) {
		var html = create_element({input:'div',append:[
			create_element({input:'div', css:{"text-align": 'left',padding: '5px'}, append: [
				create_element({input: 'label',attr:{"for":'new_password', html:Soopfw.t("New password:")}}),
				create_element({input: 'input',attr:{type:'text', id:'new_password'}}),
				create_element({input: 'a', attr:{href:'javascript: void(0);', html: Soopfw.t("Generate password")}, click: function(){
					generate_password(8, $(this).prev());
				}})
			]}),
			/**
			 * DISABLED UNTIL EMAIL TEMPLATE CONFIGURATION IS IMPLEMENTED 
			create_element({input:'div', css:{"text-align": 'left',padding: '5px'},append: [
				create_element({input: 'input', attr:{type:'checkbox',id:'inform_user'}}),
				create_element({input: 'label', attr:{"for":'inform_user', html: Soopfw.t("Inform?")}})
			]})
			 */
		]});

		var buttons = {}
		buttons[Soopfw.t("Save")] = function() {
			var new_pw = $("#new_password").prop("value");
			if(empty(new_pw)) {
				alert(Soopfw.t("The provided password was empty"), Soopfw.t("Error"));
				return false;
			}
			var form_data = {
				user_id: customer_id,
				password: new_pw
				/**
				 * DISABLED UNTIL EMAIL TEMPLATE CONFIGURATION IS IMPLEMENTED 
				  ,inform: $("#inform_user").prop("checked")
				 */
			};
			ajax_request("/user/user_change_password.ajax",form_data,function() {
				alert(Soopfw.t("Successfully changed user password"), Soopfw.t("Success"), function() {
					$("#changePwDiv_"+customer_id).remove();
				});
			});
		};
		buttons[Soopfw.t("cancel")] = function() {
			$(html).dialog("destroy").remove();
		};

		$(html).dialog({
			title: Soopfw.t("Change password"),
			buttons: buttons
		});
}