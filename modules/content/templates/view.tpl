<%$data|unescape%>

<%if !empty($view_links)%>
</div>
<div class="contentWrapper" style="margin-top: 10px;">
	<%foreach $view_links AS $link%>
	<a href="<%$link.href%>" style="margin-right: 5px;" class="form_button"><%$link.title%></a>
	<%/foreach%>
<%/if%>