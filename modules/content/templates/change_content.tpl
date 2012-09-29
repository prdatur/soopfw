<%if !empty($form)%>
	<%if !$form->is_ajax() && empty($noform)%>
	<form action="<%$form->action()%>" method="<%$form->method()%>" enctype="<%$form->enctype()%>" id="<%$form->formname%>">
	<%else%>
	<div<%if $form->is_ajax() || !empty($noform)%> ajax_form="1" action="<%$form->action()%>" ajax_return_type="<%$form->ajax_return_type()%>" method="<%$form->method()%>" enctype="<%$form->enctype()%>" id="<%$form->formname%>"<%/if%>>
	<%/if%>
	<div class="<%if empty($class)%>ui-widget<%else%><%$class%><%/if%> form" cellpadding="0" cellspacing="0">
		<%$header_title=$form->get_title()%>
		<%if (empty($no_header) || $no_header==false) && !empty($header_title)%>
		<div class="ui-widget-header "><%$header_title%> <%$header%></div>
		<%/if%>
		<div class="ui-widget-content">
			<%$form_content|unescape%>
			<div class="form_button_container">
				<%$form->get_type("button")%>
				<%foreach from=$form key=k item=element%>
					<%$element->fetch()%>
				<%/foreach%>
			</div>
		</div>
	</div>
	<%$form->get_type("hidden")%>
	<%foreach from=$form key=label item=element%>
		<%if $form->type != "object" || (!$form->get_object()->get_dbstruct()->is_reference_key($element->config('name')) || $form->get_object()->load_success())%><%$element->fetch()%><%/if%>
	<%/foreach%>
	<%if !$form->is_ajax() && (empty($noform))%>
	</form>
	<%else%>
	</div><!-- AJAX CLOSE -->
	<%/if%>
<%/if%>