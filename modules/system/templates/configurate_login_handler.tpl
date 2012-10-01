<table class='tablednd ui-widget-content' style="width: 100%">
	<thead class="ui-widget-header">
		<td colspan="2"><%t key="Login handler"%></td>
		<td colspan="2"><%t key="Enable"%></td>
	</thead>
	<tbody class="priority_change_data">
		<%$order = 0%>
		<%foreach $login_handlers AS $handler => $values%>
		<tr>
			<td class="handle_cell" style="width:20px;vertical-align:top;padding-top:5px;padding-left:5px;"><a class="tabledrag-handle" href="javascript:void(0);" title="<%t key="drag and drop to move"%>"><div class="handle">&nbsp;</div></a></td>
			<td><%$values.val%></td>
			<td><input type="checkbox" value="<%$values.val%>" name="enabled[<%$order%>]" <%if $values.enabled%>checked="checked"<%/if%>></td>
		</tr>
		<%$order = $order+1%>
		<%/foreach%>
	</tbody>
</table>
<div class="form_button save_priority_changes"><%t key="Save"%></div>