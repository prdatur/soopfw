<div class="userinfos">
<div class="form_button ui-icon-soopfw-key" id="change_password"><%t key='Change user password'%></div>

	<div class="ui-widget" style="margin-top: 20px; width:400px;">
		<div class="ui-widget-header "><%t key='User information'%></div>
		<div class="ui-widget-content">
			<%foreach $account_info AS $identifier => $values%>
			<div><%$values.label%>: <%$values.value%></div>
			<%/foreach%>
		</div>
	</div>

</div>