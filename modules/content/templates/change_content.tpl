<%if !$form->is_ajax() && (empty($noform))%>
<form action="<%$form->action()%>" method="<%$form->method()%>" enctype="<%$form->enctype()%>" id="<%$form->formname%>">
<%/if%>
<div class="<%if empty($class)%>ui-widget<%else%><%$class%><%/if%> form" cellpadding="0" cellspacing="0">
	<%if empty($no_header) || $no_header==false%>
	<div class="ui-widget-header "><%$form->get_title()%> <%$header%></div>
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
<%/if%>

<%if $form->is_ajax() && $form->handle_ajax_submit == true%>
<script type="text/javascript">

Soopfw.behaviors.SystemAjaxForm = function() {
		$(".ajax_submit_handler").unbind("click");
		$(".ajax_submit_handler").click(function() {
			var values = get_form_by_class("inputs_<%$form->formname%>","name", true);
			$.ajax({
				url: '<%$form->action()%>',
				type: '<%$form->method()%>',
				data: values,
				dataType: '<%$form->ajax_return_type()%>',
				success: function(result) {

					if('<%$form->ajax_return_type()%>' == 'html') {
						<%$form->ajax_return_type_handler()%>(result);
					}
					else {
						parse_ajax_result(result, function(result,code,desc) {
							success_alert('Data successfully saved\n'+desc, function() {
								if(Soopfw.config['js_function_callback'] != undefined) {
									for(var i in Soopfw.config['js_function_callback']) {
										if(!Soopfw.config['js_function_callback'].hasOwnProperty(i)) {
											continue;
										}
										var function_name = Soopfw.config['js_function_callback'][i];
										var search = /^[a-zA-Z0-9_.]+$/g;
										if(search.test(function_name)) {
											eval(function_name+"(result, code, desc);");
										}
									}
								}
							});
						});
					}
				}
			});
		});
	};

</script>
<%/if%>