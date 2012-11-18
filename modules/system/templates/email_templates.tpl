<div class="form_button" id="add_email_template"><%t key='Add email template'%></div>
<%include file='form.tpl'%>
<br />
<%$pager|unescape%>
<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="width: 25px;text-align: center;"><input type="checkbox" id="dmySelectAll" class="input_checkbox"/></td>
			<td style="text-align: left;"><%t key='title'%></td>
			<td style="width: 25px;text-align: center;"></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $templates AS $entry%>
		<tr id="row_<%$entry.id%>">
			<td style="text-align: center;"><input type="checkbox" name="selected[]" value="<%$entry.id%>" id="dmySelect_<%$entry.id%>" class="dmySelect input_checkbox"/></td>
			<td style="text-align: left;">
				<a href="javascript:void(0);" class="dmyChange" did="<%$entry.id%>"><%$entry.id%></a>
			</td>
			<td style="text-align: center;" class="linkedElement_grey dmyDelete" did="<%$entry.id%>"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" title="<%t key='delete?'%>" alt="<%t key='delete?'%>"></td>
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
		<option value="delete"><%t key='delete?'%></option>
	</select>
</div>