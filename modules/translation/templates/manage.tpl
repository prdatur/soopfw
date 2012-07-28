<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="width: 25px;text-align: center;"><input type="checkbox" id="dmySelectAll" class="input_checkbox"/></td>
			<td style="width: 25px;text-align: center;"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-lock" title="<%t key='active'%>" alt="<%t key='active'%>"></td>
			<td style="text-align: left;"><%t key='Language'%></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $languages as $language%>
		<%if $language->enabled == 0%>
		<%$status_icon = 'red'%>
		<%else%>
		<%$status_icon = 'green'%>
		<%/if%>
		<tr>
			<td style="text-align: center;"><input type="checkbox" name="selected_languages[]" value="<%$language->lang%>" class="dmySelect input_checkbox"/></td>
			<td style="text-align: center" class="linkedElement dmyActive" language="<%$language->lang%>"><img src="/1x1_spacer.gif" id="activeImg_<%$language->lang%>" class="ui-icon-soopfw ui-icon-soopfw-status-<%$status_icon%>"></td>
			<td style="text-align: left;"><%$language_translation[$language->lang|lower]%></td>
		</tr>
	<%foreachelse%>
	<tr>
		<td colSpan="10" style="font-style: italic; text-align:center;">
			<%t key='Nothing found'%>
		</td>
	</tr>
	</tbody>
	<%/foreach%>
</table>
<div class="multi_action">
	&nbsp;&nbsp;&nbsp;<img src="<%$TEMPLATE_PATH%>/images/multi_choose_arrow.png">
	<select id="multi_action">
		<option value=""><%t key='selected:'%></option>
		<option value="deactivate"><%t key='deactivate?'%></option>
		<option value="activate"><%t key='activate?'%></option>
	</select>
</div>