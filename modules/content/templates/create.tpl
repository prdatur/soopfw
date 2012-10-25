<ul>
<%foreach $list AS $content_type%>
<li style='margin-bottom:10px;'>
	<a href="/admin/content/create/<%$content_type.content_type%>"><%$content_type.display_name%></a>
</li>
<%/foreach%>
</ul>