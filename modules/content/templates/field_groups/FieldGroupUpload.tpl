<%foreach $data AS $link_data%>
<%if !empty($link_data.file_obj)%>
<a href="<%$link_data.file_obj->get_path(true)%>"><%$link_data.file_obj->filename%></a>
<%/if%>
<%/foreach%>