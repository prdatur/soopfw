	<div class="login_container" style="height:150px;">
		<div class="login_box">
			<div class="heading"><%t key='login'%></div>
			<div class="login_form">
				<form method="post" action="<%$smarty.server.http_host%>">
					<div class="inputfield">
						<div class="inputfield_desc"><%t key='username'%></div><div class="inputform"><input type="text" id="autofocus" name="user" value="<%$smarty.post.user%>"></div>
					</div>
					<div class="inputfield">
					<div class="inputfield_desc"><%t key='password'%></div><div class="inputform"><input type="password" name="pass" value=""></div>
					</div>
					<div class="inputfield">
						<div class="loginBtn"><input type="Submit" name="soopfw_login" value="<%t key='login'%>"></div>
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