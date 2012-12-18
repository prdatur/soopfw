function change_user_password_dialog(customer_id) {

		var elements = [];
		elements.push(create_element({input:'div', css:{"text-align": 'left', padding: '5px'}, append: [
			create_element({input: 'label', attr:{"for":'new_password', html:Soopfw.t("New password:")}}),
			create_element({input: 'input', css:{width: '100%'}, attr:{type:'text', id:'new_password'}}),
			create_element({input: 'a', css:{display: 'block'}, attr:{href:'javascript: void(0);', html: Soopfw.t("Generate password")}, click: function(){
				generate_password(12, $(this).prev());
			}})
		]}));

		if (Soopfw.config.current_user_id != customer_id) {
			elements.push(create_element({input:'div', css:{"text-align": 'left',padding: '5px'},append: [
				create_element({input: 'input', attr:{type:'checkbox',id:'inform_user'}}),
				create_element({input: 'label', attr:{"for":'inform_user', html: Soopfw.t("Inform?")}})
			]}));
		}
		var html = create_element({input:'div',append:elements});

		var buttons = {}
		buttons[Soopfw.t("Save")] = function() {
			var new_pw = $("#new_password").prop("value");
			if(empty(new_pw)) {
				alert(Soopfw.t("The provided password was empty"), Soopfw.t("Error"));
				return false;
			}
			var form_data = {
				user_id: customer_id,
				password: new_pw,
				inform: $("#inform_user").prop("checked")
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