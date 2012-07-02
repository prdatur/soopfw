<table cellpadding="5" cellspacing="0">
	<tfoot>
	<tr>
		<td valign="top">
			<table cellpadding="4" cellspacing="0" class="ui-widget ui-widget-content">
				<thead class="ui-widget-header">
					<tr>
						<td><%t key='Owned groups'%></td>
						<td style="width:20px;" align="center"><div class="ui-icon ui-icon-circle-triangle-s"></div></td>
					</tr>
				</thead>
				<tbody id="rights_groups"></tbody>
			</table>
		</td>
		<td valign="top">
			<table cellpadding="4" cellspacing="0" class="ui-widget ui-widget-content " style="margin:10px;">
				<thead class="ui-widget-header">
					<tr>
						<td><%t key='User rights'%></td>
						<td style="width:30px;" align="center"><div class="ui-icon ui-icon-circle-minus" title="<%t key='Not owned'%>"></div></td>
						<td style="width:30px;" align="center"><div class="ui-icon ui-icon-person" title="<%t key='Managed by group'%>"></div></td>
						<td style="width:30px;" align="center"><div class="ui-icon ui-icon-circle-check" title="<%t key='Allowed'%>"></div></td>
						<td style="width:30px;" align="center"><div class="ui-icon ui-icon-circle-close" title="<%t key='Revoked'%>"></div></td>
					</tr>
				</thead>
				<tbody id="rights"></tbody>
			</table>
		</td>
	</tr>
	</tfoot>
</table>
