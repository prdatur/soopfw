<%include file="`$SITEPATH`/templates/internal.tpl"%>
<%if empty($smarty.get.ajax)%>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<TITLE><%$meta->title%></TITLE>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!-- Add CSS files -->
	<%foreach from=$additional_css_files item=cssfile%>
	<link rel="StyleSheet" href="<%$cssfile%>" type="text/css" />
	<%/foreach%>


	<!-- Add Javascript files -->
	<%foreach from=$additional_js_files['system'] item=jsfile%>
	<script language="javascript" type="text/javascript" src="<%$jsfile%>"></script>
	<%/foreach%>

	<script type="text/javascript" language="javascript">
		<%if !empty($JS_LANG)%>
		//Language array
		var LANG = <%$JS_LANG|unescape%>;
		<%else%>
		var LANG = {};
		<%/if%>

		//Javascript config variables
		Soopfw['config'] = {};
		Soopfw.behaviors.system_add_js_config = function() {
			Soopfw.config = $.extend(Soopfw.config, <%$js_variable_config|unescape%>);
		};
		<%foreach from=$additional_css_files item=cssfile%>
		Soopfw.already_loaded_files['<%$cssfile%>'] = true;
		<%/foreach%>
		<%foreach from=$additional_js_files['system'] item=jsfile%>
		Soopfw.already_loaded_files['<%$jsfile%>'] = true;
		<%/foreach%>
		<%foreach from=$additional_js_files['user'] item=jsfile%>
		Soopfw.already_loaded_files['<%$jsfile%>'] = true;
		<%/foreach%>
	</script>

	<!-- Add Javascript files -->
	<%foreach from=$additional_js_files['user'] item=jsfile%>
	<script language="javascript" type="text/javascript" src="<%$jsfile%>"></script>
	<%/foreach%>

	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="/favicon.gif" type="image/gif" />

	<script type="text/javascript" language="javascript">
		<%if !empty($header_redirect)%>
		window.setTimeout("document.location.href='<%$header_redirect.location|unescape%>';",<%$header_redirect.timeout%>)
		<%/if%>
	</script>
</head>
<body>

<%/if%>
<%include file="admin_menu.tpl"%>
<div id="wrapper" style="position: relative;">
	<div id="header-background"></div>
	<div id="header">
		<span id="header-title">SoopFw<span id="header-slogan">Simple object oriented PHP-Framework</span></span>
		
	</div>
	<div id="language-wrapper">
	<%if !empty($enabled_languages)%>
		<ul id="select-language" class="clearfix">
			<%foreach $enabled_languages AS $key => $language%>
			<li><a title="<%$language.language%>" href="<%$language.link%>"><%$language.language%></a></li>
			<%/foreach%>
		</ul>
	<%/if%>
	</div>
	<div id="content">
		<div class="widecolumn<%if !empty($smarty.get.ajax)%> dialogWidecolumn<%/if%>">