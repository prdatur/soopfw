<div class="userinfos">
<div class="form_button ui-icon-soopfw-key" id="change_password"><%t key='Change user password'%></div>

	<div class="ui-widget" style="margin-top: 20px; width:400px;">
		<div class="ui-widget-header "><%t key='User information'%></div>
		<div class="ui-widget-content">
			<div><%t key='Last login'%>: <%$user->last_login|date_format:'%d.%m.%Y %H:%M':'-'%></div>
		</div>
	</div>

</div>