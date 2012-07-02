<table class="ui-widget ui-widget-content" >
	<thead>
		<tr class="ui-widget-header">
			<td><%t key='language'%></td>
			<td><%t key='title'%></td>
		</tr>
	</thead>
	<tbody id="content_type_tbody">
		<%foreach $translations AS $language => $translation%>
		<tr>
			<td><%$translation.language%></td>
			<td><a href="<%$translation.link%>"><%$translation.title%></a></td>
		</tr>
		<%/foreach%>
	</tbody>
</table>