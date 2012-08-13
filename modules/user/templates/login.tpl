<%include file="`$SITE_PATH`/form.tpl"%>
<%if $lost_password_type != 1%>
<a href="/user/lost_password"><%t key='Forgot password?'%></a>
<%/if%>