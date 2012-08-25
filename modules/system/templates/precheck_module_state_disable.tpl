<%t key='Do you want to disable this module?'%>
<div class='module_info' style="margin-top: 5px;border: 1px solid #E9E9E9;padding: 5px;">
	<span><%$moduleinfo.name%></span>
	<div class='module_description'><%$moduleinfo.description%></div>
</div>
<%if !empty($dependencies)%>
	<div id="depends_on">
		<%t key='The following modules depends on the current module you want to disable, they will be also disabled.'%>
		<ul>
		<%foreach $dependencies AS $dependency => $value%>
			<li><%$value%></li>
		<%/foreach%>
		</ul>
	</div>
<%/if%>
<br />
<span class="form_button" id='btn_disable_module'><%t key='Disable'%></span> &nbsp;&nbsp;
<span class="form_button" id='btn_cancel_module'><%t key='Cancel'%></span>