<ul>
<%foreach $list AS $content_type%>
<li style='margin-bottom:10px;'>
	<a href="/content/create/<%$content_type.content_type%>"><%$content_type.description%></a>
</li>
<%/foreach%>
</ul>