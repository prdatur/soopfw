<%if !empty($user_id) && !empty($admin_menu)%>
<%function name=admin_sub_menu level=0 id=""%>
<%if !empty($data)%>
<%$topline=true%>
	<ul>
<%foreach $data as $entry%>
		<li<%if $topline == true%> class="topline"<%$topline=false%><%/if%>>
<%if is_array($entry)%>
			<a href="<%$entry['#link']%>"<%if !empty($entry['#childs'])%> class="sub"<%/if%>><%$entry['#title']%></a>
<%if !empty($entry['#childs'])%>
<%admin_sub_menu data=$entry['#childs'] level=$level+1%>
<%/if%>
<%else%>
			<a href="<%$entry@key%>"><%$entry%></a>
<%/if%>
		</li>
<%/foreach%>
	</ul>
<%/if%>
<%/function%>
<div id="admin_menu" class="jqueryslidemenu clearfix">
<ul>
<%$firstentry=true%>
<%foreach $admin_menu as $tmpentry%>
<%foreach $tmpentry as $entry%>
	<li><a href="<%$entry['#link']%>" class="menulink<%if $firstentry == true%> first_menu_entry<%$firstentry=false%><%/if%>"><%$entry['#title']%></a>
<%admin_sub_menu data=$entry['#childs'] id=$entry['#id']%>
	</li>
<%/foreach%>
<%/foreach%>
	<li style="float:right;" class="menulink last_menu_entry"><a href="<%$logout_url%>" style="border:0px;padding: 6px 10px;"><%t key='Logout'%><img src="/1x1_spacer.gif" style="margin-left: 4px;" class="ui-icon-soopfw ui-icon-soopfw-cancel" /></a></li>
	<li style="float:right;" class="menulink last_menu_entry"><a href="<%$profile_url%>" style="border:0px;"><%t key='My account'%></a></li>
</ul>
</div>
<div style="clear:both"></div>
<script type="text/javascript">
//build menu with ID="myslidemenu" on page:
jqueryslidemenu.buildmenu("admin_menu");
</script>
<%/if%>