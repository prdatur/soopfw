<table class="ui-widget ui-widget-content" >
	<thead>
		<tr class="ui-widget-header">
			<td  style="text-align:left"><%t key='title'%></td>
			<td  style="text-align:right;width:140px;"><%t key='created'%></td>
			<td  style="text-align:right;width:120px;"><%t key='created by'%></td>
			<td  style="text-align:right"><%t key='options'%></td>
		</tr>
	</thead>
	<tbody id="content_type_tbody">
		<%foreach $revisions AS $revision%>
		<tr>
			<td style="text-align:left"><%$revision.title%></td>
			<td style="text-align:right"><%$revision.created|format_date%></td>
			<td style="text-align:right">
				<%if !empty($revision.created_by)%>
				<%$revision.created_by.username%>
				<%else%><%t key='user not found'%><%/if%></td>
			<td style="text-align:right">
				<a href="/admin/content/view/<%$revision.page_id%>/<%$revision.revision%>" target="_blank"><%t key='view'%></a> -
				<a href="/admin/content/edit/<%$revision.page_id%>/<%$revision.revision%>" target="_blank"><%t key='edit'%></a>&nbsp;
			</td>
		</tr>
		<%/foreach%>
	</tbody>
</table>