<%if !empty($view_links)%>
	<%foreach $view_links AS $link%>
	<a href="<%$link.href%>" style="margin-right: 5px;" class="form_button"><%$link.title%></a>
	<%/foreach%>
</div>
<div class="contentWrapper" style="margin-bottom: 10px;">
<%/if%>
<%$data|unescape%>




	
