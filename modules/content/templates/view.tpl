<%if !empty($view_links)%>
<div style='margin-bottom: 10px;'>
	<%foreach $view_links AS $link%>
	<a href="<%$link.href%>" style="margin-right: 5px;" class="form_button"><%$link.title%></a>
	<%/foreach%>
</div>
<%/if%>
<%$data|unescape%>





