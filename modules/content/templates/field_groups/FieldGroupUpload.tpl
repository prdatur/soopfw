<%foreach $data AS $link_data%>
<a href="<%$link_data.file_obj->get_path(true)%>"><%$link_data.file_obj->filename%></a>
<%/foreach%>