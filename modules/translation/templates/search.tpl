<br /><div class="form_button" id="rebuild_languages"><%t key='Rebuild translations'%></div><br />
<%include file='form.tpl' form=$search_form%> <br />
<div class="ui-widget">
	<div class="ui-widget-header "style="padding: 5px;"><%t key='Search results'%>:</div>
	<div class=" ui-widget-content">
	<%foreach $results AS $result%>
	<div style="padding: 5px;"><a href="/admin/translation/translate/<%$result.id%>"><%$result.key|truncate:200%></a></div>
	<%foreachelse%>
	<div><%t key='No results'%></div>
	<%/foreach%>
	</div>
</div>
<%$pager|unescape%>