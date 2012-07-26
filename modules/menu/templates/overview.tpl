<div class="form_button" id="add_menu"><%t key='add menu'%></div>
<table class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header">
			<td><%t key='menu'%></td>
			<td style="width:100px;text-align: center;"><%t key='options'%></td>

		</tr>
	</thead>
	<tbody id="menu_tbody">
		<%foreach $menus AS $menu%>
		<tr did="<%$menu.menu_id%>">
			<td><a href="/admin/menu/entries/<%$menu.menu_id%>"><%$menu.title%></a></td>
			<td style="text-align: right;">
			<span class="linkedElement dmyChange option_links" id="<%$menu.menu_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-edit" title="<%t key='edit?'%>" alt="<%t key='edit?'%>" /></span>
			<span class="linkedElement dmyDelete option_links" id="<%$menu.menu_id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>" /></span>
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
