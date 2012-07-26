<div class="form_button" id="add_user"><%t key='Add user'%></div>
<%include file='form.tpl' form=$search_form%>
<br />
<%$pager|unescape%>
<table class="ui-widget ui-widget-content" style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="width: 25px;text-align: center;"><input type="checkbox" id="dmySelectAllUser" class="input_checkbox"/></td>
			<td style="width: 25px;text-align: center;"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-lock" title="<%t key='active'%>" alt="<%t key='active'%>"></td>
			<td style="width: 20px;">&nbsp;</td>
			<td style="text-align: left;"><%t key='username'%></td>
			<td style="width: 120px;text-align: center;"><%t key='Last login'%></td>
			<td style="width: 25px;text-align: center;"></td>
			<td style="width: 25px;text-align: center;"></td>
		</tr>
	</thead>
	<tbody>
	<%foreach from=$users item=user%>
		<tr id="user_row_<%$user.user_id%>">
			<td style="text-align: center;"><input type="checkbox" name="selected_user[]" value="<%$user.user_id%>" id="dmySelectUser_<%$user.user_id%>" class="dmySelectUser input_checkbox"/></td>
			<td style="text-align: center" class="linkedElement dmyActiveUser" id="activeUser_<%$user.user_id%>"><img src="/1x1_spacer.gif" id="activeUserImg_<%$user.user_id%>" class="ui-icon-soopfw ui-icon-soopfw-status-<%$user.status_color%>" /></td>
			<td style="text-align: center;"><img src="/1x1_spacer.gif" title="<%$user.language%>" alt="<%$user.language%>" class="ui-icon-soopfw-country ui-icon-soopfw-country-<%$user.language|lower%>"></td>
			<td style="text-align: left;" id="adressTd_<%$user.user_id%>" class="adressTd"><a href="/admin/user/edit/<%$user.user_id%>"><span class="showUserAddress" id="showUserAddress_<%$user.user_id%>"><%$user.username%></span></a>
				<div id="userDefaultAdress_<%$user.user_id%>" style="display:none;"><span style="font-weight:bold; text-decoration: underline;"><%t key='Default address'%></span><br />
				<%$user.default_address.company%><br />
				<%$user.default_address.firstname%> <%$user.default_address.lastname%><br />
				<%$user.default_address.address%><br />
				<%$user.default_address.nation|upper%>-<%$user.default_address.zip%> <%$user.default_address.city%><br />
				<br />
				<%t key='phone'%>: <%$user.default_address.phone%><br />
				<%t key='mobile'%>: <%$user.default_address.mobile%><br />
				<%t key='fax'%>: <%$user.default_address.fax%><br />
				</div>
			</td>
			<td style="text-align: center;"><%$user.last_login|date_format:''%></td>
			<td style="text-align: center;" class="linkedElement dmyChangePassword" id="changePassword_<%$user.user_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-key" title="<%t key='Change password'%>"></td>
			<td style="text-align: center;" class="linkedElement dmyDeleteUser" id="deleteUser_<%$user.user_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>"></td>
		</tr>
	<%foreachelse%>
	<tr>
		<td colSpan="10" style="font-style: italic; text-align:center;">
			<%t key='Nothing found'%>
		</td>
	</tr>
	</tbody>
	<%/foreach%>
</table>
<div class="multi_action">
	&nbsp;&nbsp;&nbsp;<img src="<%$TEMPLATE_PATH%>/images/multi_choose_arrow.png">
	<select id="multi_action">
		<option value=""><%t key='selected:'%></option>
		<option value="deactivate"><%t key='deactivate?'%></option>
		<option value="activate"><%t key='activate?'%></option>
		<option value="delete"><%t key='delete?'%></option>
	</select>
</div>