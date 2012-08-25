<%t key='Do you want to enable this module?'%>
<div class='module_info' style="margin-top: 5px;border: 1px solid #E9E9E9;padding: 5px;">
	<span><%$moduleinfo.name%></span>
	<div class='module_description'><%$moduleinfo.description%></div>
</div>
<%if !empty($dependencies)%>
	<div id="depends_on">
		<%t key='The module depends on the following modules which will be also enabled'%>
		<ul>
		<%foreach $dependencies AS $dependency => $value%>
			<li><%$value.name%></li>
		<%/foreach%>
		</ul>
	</div>
<%/if%>
<br />
<span class="form_button" id='btn_enable_module'><%t key='Enable'%></span> &nbsp;&nbsp;
<span class="form_button" id='btn_cancel_module'><%t key='Cancel'%></span>