<div class="form_button" id="add_address">add address</div>
<table cellpadding="2" cellspacing="0" class="ui-widget ui-widget-content " style="width:100%;">
	<thead>
	<tr class="ui-widget-header">
		<td><%t key='email'%></td>
		<td><%t key='address'%></td>
		<td style="width:90px;"><%t key='address group'%></td>
		<td align="center"><%t key='options'%></td>
	</tr>
	</thead>
	<tbody id="address_tbody">
		<%foreach from=$addresses item=address%>
		<tr id="addressTr_<%$address.id%>">
			<td><%$address.email|default:'&nbsp;'%></td>
			<td>
				<%$address.company|default:'&nbsp;'%> - <%$address.firstname|default:'&nbsp;'%> <%$address.lastname|default:'&nbsp;'%>, <%$address.nation|default:'&nbsp;'%>-<%$address.zip|default:'&nbsp;'%>, <%$address.city|default:'&nbsp;'%>
			</td>
			<td><%t key=$address.group default='&nbsp;'%></td>
			<td style="text-align: right;">
				<a href="javascript:void(0);" class="editAddress option_links" id="editAddress_<%$address.id%>"><img title="<%t key='Edit'%>" src="/1x1_spacer.gif" class="linkedElement ui-icon-soopfw ui-icon-soopfw-edit"></a>
				<a href="javascript:void(0);" class="deleteAddress option_links" id="deleteAddress_<%$address.id%>"><img title="<%t key='Delete'%>" src="/1x1_spacer.gif" class="linkedElement ui-icon-soopfw ui-icon-soopfw-cancel"></a>
			</td>
		</tr>
		<%/foreach%>
	</tbody>
</table>