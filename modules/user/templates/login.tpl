	<div class="login_container" style="height:150px;">
		<div class="login_box">
			<div class="heading">Login</div>
			<div class="login_form">
				<form method="post" action="<%$smarty.server.http_host%>">
					<div class="inputfield">
						<div class="inputfield_desc">Benutzername</div><div class="inputform"><input type="text" id="autofocus" name="user" value="<%$smarty.post.user%>"></div>
					</div>
					<div class="inputfield">
					<div class="inputfield_desc">Passwort</div><div class="inputform"><input type="password" name="pass" value=""></div>
					</div>
					<div class="inputfield">
						<div class="loginBtn"><input type="Submit" name="soopfw_login" value="Anmelden"></div>
					</div>
				</form>
			</div>
		</div>
	</div>
<script type="text/javascript">
    $().ready(function() {
        $("#autofocus").focus();
    });
</script>
