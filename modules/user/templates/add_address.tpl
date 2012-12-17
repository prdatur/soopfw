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
			<div style="float:left;width:40%;">
			<%$inner_fieldset_count = 0%>
			<%$button_mode = 0%>
			<%foreach from=$form item=element%>
				<%$label=$element->config('label')%>
				<%if $element|is_a:'Fieldset'%>
					<%$inner=$element->config('inner')%>
					<%if $inner_fieldset_count > 0 && !empty($inner) && $inner != 'yes'%>
						<%for $x=1 to $inner%>
							</fieldset>
						<%/for%>
						<%$inner_fieldset_count = $inner_fieldset_count - $inner%>
					<%elseif $inner_fieldset_count > 0 && empty($inner)%>
						<%for $x=1 to $inner_fieldset_count%>
							</fieldset>
						<%/for%>
						<%$inner_fieldset_count = 0%>
					<%/if%>
					<%$inner_fieldset_count = $inner_fieldset_count + 1%>
					<%$element->fetch()%>
					<%if !empty($label)%><legend><%$label%></legend><%/if%>
					<%$element->get_fieldset_description()%>
				<%elseif $element|is_a:'Submitbutton'%>
					<%if $button_mode == 0%>
						</div><div class="form_button_container" style="clear:both;">
						<%$button_mode = 1%>
					<%/if%>
					<%$element->fetch()%>
				<%else%>
					<%if $button_mode == 1%>
						</div>
						<%$button_mode = 0%>
					<%/if%>
				<div id="<%$element->config('id')%>_wrapper">
					<%$element->fetch()%>
				</div>
				<%/if%>

				<%if $element->config('name') == 'nation'%>
				</div><div style="float:left;margin-left: 20px;width:40%;">
				<%/if%>
			<%/foreach%>

			<%if $button_mode == 1%>

				<%$button_mode = 0%>
			<%/if%>

			<%if $inner_fieldset_count > 0%>
				<%for $i=1 to $inner_fieldset_count%>
					</fieldset>
				<%/for%>
			<%/if%>
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