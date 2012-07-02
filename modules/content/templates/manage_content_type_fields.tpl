<div class="form_button" id="add_field"><%t key='add new field'%></div>
<table class="tablednd ui-widget ui-widget-content" id="new_order">
	<thead>
		<tr class="ui-widget-header">
			<td><%t key='name'%></td>
			<td><%t key='id'%></td>
			<td><%t key='field type'%></td>
			<td style="width:100px;text-align: center;"><%t key='options'%></td>

		</tr>
	</thead>
	<tbody id="content_type_tbody">
		<%foreach $field_groups AS $field_group%>
		<tr did="<%$field_group.id%>" id="<%$field_group.id%>" rowid="<%$field_group.id%>">
			<td class="handle_cell">
				<a class="tabledrag-handle" href="javascript:void(0);" title="<%t k='drag and drop to move'%>"><div class="handle">&nbsp;</div></a>
				<span><%$field_group.name%></span>
			</td>
			<td><%$field_group.id%></td>
			<td><%$field_group.field_group%></td>
			<td style="text-align: right;">
			<span class="linkedElement dmyChange option_links" id="<%$field_group.id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-edit" title="<%t key='edit?'%>" alt="<%t key='edit?'%>" /></span>
			<span class="linkedElement dmyDelete option_links" id="<%$field_group.id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>" /></span>
			</td>
		</tr>
		<%foreachelse%>
		<tr>
			<td colSpan="4" style="font-style: italic; text-align:center;">
				<%t key='Nothing found'%>
			</td>
		</tr>
		<%/foreach%>
	</tbody>
</table>
<div class="form_button" id="save_new_order"><%t key='save new order'%></div>