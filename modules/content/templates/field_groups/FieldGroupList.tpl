<ul>
	<%foreach $data AS $link_data%>
	<%if !empty($link_data.list)%>
	<li><%$link_data.list%></li>
	<%/if%>
	<%/foreach%>
</ul>