<%foreach $data.elements AS $link_data%>
<%$link_data.text|nl2br|markdown%>
<%/foreach%>