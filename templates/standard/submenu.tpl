<%function name=sub_menu level=0 id=""%>
<%if !empty($data)%>
	<ul>
<%foreach $data as $entry%>
		<li class="<%if !empty($entry['#active_direct'])%> active<%/if%>">
			<a href="<%$entry['#link']%>" class="<%if !empty($entry['#childs'])%> sub<%/if%>"><span style="<%if !empty($entry['#inactive'])%>font-style: italic;<%/if%>"><%$entry['#title']%></span></a>
<%if !empty($entry['#childs'])%>
<%sub_menu data=$entry['#childs'] level=$level+1%>
<%/if%>
		</li>
<%/foreach%>
	</ul>
<%/if%>
<%/function%>

<%if !empty($title) || !empty($menu)%>
<div class="submenu clearfix">
<%if !empty($title)%>
	<div class="title"><%$title%></div>

	<ul id="submenu" class="submenu_no_top_margin">
<%else%>
	<ul id="submenu">
<%/if%>
	<%foreach $menu AS $submenu%>
	<li <%if !empty($submenu['#active_direct'])%> class="active"<%/if%>><a href="<%$submenu['#link']%>"><span style="<%if !empty($submenu['#inactive'])%>font-style: italic;<%/if%>"><%$submenu['#title']%></span></a>
	<%sub_menu data=$submenu['#childs']%>
	</li>
	<%/foreach%>
</ul>
</div>
<%/if%>