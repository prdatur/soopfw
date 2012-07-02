<table class="ui-widget ui-widget-content" >
	<thead>
		<tr class="ui-widget-header">
			<td  style="text-align:left"><%t key='title'%></td>
			<td  style="text-align:right;width:140px;"><%t key='last modified'%></td>
			<td  style="text-align:left;width:120px;"><%t key='last modified by'%></td>
			<td  style="text-align:right"><%t key='options'%></td>
		</tr>
	</thead>
	<tbody id="content_type_tbody">
		<%foreach $revisions AS $revision%>
		<tr>
			<td style="text-align:left"><%$revision.title%></td>
			<td style="text-align:right"><%$revision.last_modified|format_date%></td>
			<td style="text-align:left">
				<%if !empty($revision.last_modified_by)%>
				<%$revision.last_modified_by.username%>
				<%else%><%t key='user not found'%><%/if%></td>
			<td style="text-align:right">
				<a href="/content/view/<%$revision.page_id%>/<%$revision.revision%>" target="_blank"><%t key='view'%></a> -
				<a href="/content/edit/<%$revision.page_id%>/<%$revision.revision%>" target="_blank"><%t key='edit'%></a>&nbsp;
			</td>
		</tr>
		<%/foreach%>
	</tbody>
</table>