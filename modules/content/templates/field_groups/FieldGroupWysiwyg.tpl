<%foreach $data.elements AS $link_data%>
<div><%$link_data.text|bbcode|unescape%></div>
<%/foreach%>