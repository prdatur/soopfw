<%include file='form.tpl'%>
<%$pager|unescape%>
<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="text-align: center;width: 125px;"><%t key='Date'%></td>
			<td style="text-align: left;width: 75px;"><%t key='Severity'%></td>
			<td style="text-align: left;width: 100px;"><%t key='Category'%></td>
			<td style="text-align: left;width: 100px;"><%t key='User'%></td>
			<td style="text-align: left;"><%t key='Message'%></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $entries as $entry%>
		<tr class="log_level log_level_<%$entry.log_level%>">
			<td style="text-align: center;"><%$entry.date|date_format:'d.m.Y H:i:s'%></td>
			<td style="text-align: left;" >
				<%if $entry.log_level == -1%>
					<%t key='Debug'%>
				<%elseif $entry.log_level == 1%>
					<%t key='Notice'%>
				<%elseif $entry.log_level == 2%>
					<%t key='Normal'%>
				<%elseif $entry.log_level == 3%>
					<%t key='Warning'%>
				<%elseif $entry.log_level == 4%>
					<%t key='Alert'%>
				<%elseif $entry.log_level == 5%>
					<%t key='Critical'%>
				<%elseif $entry.log_level == 6%>
					<%t key='Emergency'%>
				<%/if%>
			</td>
			<td style="text-align: left;"><%$entry.type%></td>
			<td style="text-align: left;"><%$entry.username|unescape%></td>
			<td style="text-align: left;"><%$entry.message|truncate_soopfw:100%></td>
		</tr>
	<%foreachelse%>
	<tr>
		<td colSpan="10" style="font-style: italic; text-align:center;">
			<%t key='Nothing found'%>
		</td>
	</tr>
	<%/foreach%>
	</tbody>
</table>
<%$pager|unescape%>