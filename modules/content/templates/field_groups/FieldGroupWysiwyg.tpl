<%foreach $data.elements AS $link_data%>
<p><%$link_data.text|bbcode|nl2br|unescape%></p>
<%/foreach%>