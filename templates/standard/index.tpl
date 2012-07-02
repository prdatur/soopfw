<%include file="header.tpl"%>
		<div id="content_wrapper">
		<%if !empty($content_title)%><%include file="title.tpl" title=$content_title.title description=$content_title.description%><%/if%>
        <%foreach from=$main_messages key=type item=message_arrray%>
            <%include file="default_info_box.tpl" type=$type description=$message_arrray%>
        <%/foreach%>
			<%if !empty($module_tpl)%><%include file="file:$module_tpl"%><%/if%>
        </div>
<%include file="footer.tpl"%>