<%function name=sub_menu level=0 menu_id=""%>
<%if !empty($data)%>
	<ul>
<%foreach $data as $entry%>
		<li>
			<a href="javascript:void(0);" menu_id="<%$menu_id%>" parent="<%$entry['entry_id']%>" class="select_menu"><%$entry['#title']%></a>
<%if !empty($entry['#childs'])%>
<%sub_menu data=$entry['#childs'] level=$level+1 menu_id=$menu_id%>
<%/if%>
		</li>
<%/foreach%>
	</ul>
<%/if%>
<%/function%>

<ul id="menu_tree">
	<%foreach $data AS $submenu%>
	<li><a href="javascript:void(0);" menu_id="<%$submenu.menu_id%>" parent="0" class="select_menu"><%$submenu['#title']%></a>
	<%sub_menu data=$submenu['#childs'] menu_id=$submenu.menu_id%>
	</li>
	<%/foreach%>
</ul>