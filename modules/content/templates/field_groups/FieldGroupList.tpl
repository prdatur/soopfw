<%foreach $data AS $link_data%>
<p><%$link_data.text|markdown%></p>
<%/foreach%>