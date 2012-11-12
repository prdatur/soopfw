<%foreach $data.elements AS $link_data%>
<a href="<%$link_data.link%>"><%$link_data.text%></a>
<%/foreach%>