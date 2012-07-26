<div class="form_button" id="add_content_type"><%t key='add content type'%></div>
<table class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
			<td><%t key='content type'%></td>
			<td style="width:100px;text-align: center;"><%t key='options'%></td>

		</tr>
	</thead>
	<tbody id="content_type_tbody">
		<%foreach $values AS $value%>
		<tr did="<%$value.content_type%>">
			<td><a href="/admin/content/manage_content_type_fields/<%$value.content_type%>"><%$value.description%></a></td>
			<td style="text-align: right;">
			<span class="linkedElement dmyChange option_links" id="<%$value.content_type%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-edit" title="<%t key='edit?'%>" alt="<%t key='edit?'%>" /></span>
			<span class="linkedElement dmyDelete option_links" id="<%$value.content_type%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>" /></span>
			</td>
		</tr>
		<%foreachelse%>
		<tr>
			<td colSpan="3" style="font-style: italic; text-align:center;">
				<%t key='Nothing found'%>
			</td>
		</tr>
		<%/foreach%>
	</tbody>
</table>
