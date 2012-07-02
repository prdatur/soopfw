<div class="form_button" id="add_entry"><%t key='add menu entry'%></div>
<br />

<div class="defaultBox defaultBox_information" id="draggable_warning" style="display: hidden;">
	<div id="description">
		<span class="warning tabledrag-changed">*</span> <%t key='Changed orders are not saved, you must click on "save new order" to save it.'%>
	</div>
</div>
<%function name=menu_table_dnd level=0%>
	<%if $level == 0%>
	<table class="tablednd ui-widget ui-widget-content" id="new_order">
		<thead>
			<tr class="ui-widget-header nodrag nodrop">
				<td><%t key='menu entries'%></td>
				<td style="width:100px;text-align: center;"><%t key='options'%></td>
			</tr>
		</thead>
		<%/if%>
	<%foreach $data AS $id => $entries%>
		<tr id="<%$entries['entry_id']%>" did="<%$entries['entry_id']%>" rowid="<%$entries['entry_id']%>">
			<td class="handle_cell">
				<span class="intent_container"><%if $level > 0%><%for $i=1 to $level%><span class="menu_tablednd_indent">&nbsp;</span><%/for%><%/if%></span>
				<a class="tabledrag-handle" href="javascript:void(0);" title="<%t k='drag and drop to move'%>"><div class="handle">&nbsp;</div></a>
				<span><a href="<%$entries.destination%>" target="_blank"><%$entries['title']%></a></span>
			</td>
			<td style="text-align: right;">
				<%if $entries['language']|upper == $current_language|upper%>
				<span class="linkedElement dmyChange option_links" id="<%$entries.entry_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-edit" title="<%t key='edit?'%>" alt="<%t key='edit?'%>" /></span>
				<span class="linkedElement dmyDelete option_links" id="<%$entries.entry_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>" /></span>
				<%else%>
				<span class="linkedElement dmyAdd option_links" id="<%$entries.entry_id%>"><img src="/1x1_spacer.gif" class="ui-icon ui-icon-plus" style="display: inline;" title="<%t key='add?'%>" alt="<%t key='add?'%>" /></span>
				<%/if%>
			</td>
		</tr>

		<%if !empty($entries['#childs'])%>
		<%menu_table_dnd data=$entries['#childs'] level=$level+1%>
		<%/if%>
	<%/foreach%>

	<%if $level == 0%></table><%/if%>
<%/function%>
<%menu_table_dnd data=$menus level=0%>
<br />
<div class="form_button" id="save_new_order"><%t key='save new order'%></div>
