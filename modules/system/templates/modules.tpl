<b><%t key='Status-icon legend'%>:</b>
<div id="status_icon_legend">
	<div><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-status-red" /><span><%t key='Module can not be installed/enabled'%></span></div>
	<div><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-status-green" /><span><%t key='Module is enabled (can be disabled)'%></span></div>
	<div><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-status-yellow" /><span><%t key='Module is disabled (can be enabled)'%></span></div>
</div>
<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="width: 25px;text-align: center;"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-lock" title="<%t key='active'%>" alt="<%t key='active'%>"></td>
			<td style="text-align: left;"><%t key='Module'%></td>
			<td style="text-align: left;width: 150px;"><%t key='Dependencies'%></td>
			<td style="text-align: center;width: 100px;"><%t key='Current version'%></td>
			<td style="text-align: center;width: 100px;"><%t key='Module version'%></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $modules as $module%>
		<%if !empty($module['not_installable'])%>
			<%$status_icon = 'red'%>
		<%elseif $module.obj->enabled == 0%>
			<%$status_icon = 'yellow'%>
		<%else%>
			<%$status_icon = 'green'%>
		<%/if%>
		<tr>
			<td style="text-align: center" class="linkedElement <%if empty($module['not_installable']) && $module.obj->modul !== 'system'%>dmyActive" module="<%$module.obj->modul%><%/if%>"><%if $module.obj->modul !== 'system'%><img src="/1x1_spacer.gif" <%if empty($module['not_installable'])%>id="activeImg_<%$module.obj->modul%>"<%/if%> class="ui-icon-soopfw ui-icon-soopfw-status-<%$status_icon%>" /><%/if%></td>
			<td style="text-align: left;">
				<div class="module_info">
					<%$module.name%><%if !empty($module['not_installable'])%> (<%t key='Module can not be enabled/installed because of dependency problems'%>)<%/if%>
					<div class="module_description"><%$module.description%></div>
				</div>
			</td>
			<td style="text-align: left;">
				<%if !empty($module.dependencies) && is_array($module.dependencies)%>
				<ul>
				<%foreach $module.dependencies AS $dependency%>
					<%if $dependency.state == SystemHelper::DEPENDENCY_UNAVAILABLE%>
						<%$depence_status = 'unavailable'%>
					<%elseif $dependency.state == SystemHelper::DEPENDENCY_ENABLED%>
						<%$depence_status = 'enabled'%>
					<%elseif $dependency.state == SystemHelper::DEPENDENCY_DISABLED%>
						<%$depence_status = 'disabled'%>
					<%/if%>
					<li class="dependency_<%$depence_status%>"><%$dependency.name%></li>
				<%/foreach%>
				</ul>
				<%/if%>
			</td>
			<td style="text-align: center;"><%$module.current_version%></td>
			<td style="text-align: center;"><%$module.version%></td>
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