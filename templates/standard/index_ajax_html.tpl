<%if !empty($content_title)%><title><%$content_title.title%></title><%/if%>
<%$ajaxhtml = true%>
<script type="text/javascript" language="javascript">

	<%foreach from=$additional_css_files item=cssfile%>
	if(Soopfw.already_loaded_files['<%$cssfile.hash%>'] != true) {
		$("head").append(
			create_element({input: 'link', attr:{rel: 'stylesheet', type: 'text/css', href: '<%$cssfile.file%>'}})
		);
		Soopfw.already_loaded_files['<%$cssfile.hash%>'] = true;
	}
	<%/foreach%>

	Soopfw.prio_behaviors['system_add_js_config_from_ajax_<%$module_tpl%>'] = function() {
		Soopfw.config = $.extend(Soopfw.config, <%$js_variable_config|unescape%>);
	};

	<%foreach from=$additional_js_files['system'] item=jsfile%>
	if(Soopfw.already_loaded_files['<%$jsfile%>'] != true) {
		$("head").append(
			create_element({input: 'script', attr:{type: 'text/javascript', src:'<%$jsfile%>'}})
		);
		Soopfw.already_loaded_files['<%$jsfile%>'] = true;
	}
	<%/foreach%>
	<%foreach from=$additional_js_files['user'] item=jsfile%>
	if(Soopfw.already_loaded_files['<%$jsfile%>'] != true) {
		$("head").append(
			create_element({input: 'script', attr:{type: 'text/javascript', src :'<%$jsfile%>'}})
		);
		Soopfw.already_loaded_files['<%$jsfile%>'] = true;
	}
	<%/foreach%>
</script>

<%if !empty($content_title)%><%include file="title.tpl" title="" description=$content_title.description%><%/if%>
<%foreach from=$main_messages key=type item=message_arrray%>
            <%include file="default_info_box.tpl" type=$type description=$message_arrray%>
        <%/foreach%>
<%if !empty($module_tpl)%><%include file="file:$module_tpl"%><%/if%>
<script type="text/javascript" language="javascript">
	Soopfw.reload_behaviors();
</script>