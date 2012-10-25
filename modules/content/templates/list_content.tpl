<%include file='form.tpl' form=$search_form%>
<br />
<%$pager|unescape%>
<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="text-align: left;"><%t key='Title'%></td>
			<td style="text-align: left;"><%t key='Type'%></td>
			<td style="text-align: left;"><%t key='Languages'%></td>
			<td style="text-align: center;width:125px;"><%t key='Last modified'%></td>
			<td style="text-align: left;"><%t key='Last modified by'%></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $pages AS $page%>
		<tr id="row_<%$page.page_id%>">
			<td style="text-align: left;">
				<a href="/admin/content/edit/<%$page.page_id%>">
					<span><%$page.title%>
						<%if $page.deleted == 'yes'%><span style='font-style: italic'>(<%t key='deleted'%>)</span><%/if%>
						<%if empty($page.last_revision)%><span style='font-style: italic'>(<%t key='unpublished'%>)</span><%/if%>
					</span>
				</a>
			</td>
			<td><%$page.display_name%></td>
			<td>
				<%foreach $available_languages AS $key => $val%>
				<img src='/1x1_spacer.gif' class="ui-icon-soopfw-country ui-icon-soopfw-country-<%$key|lower%><%if !isset($page.translated[$key])%> ui-icon-soopfw-disabled<%/if%>"/>
				<%/foreach%>
			</td>
			<td style="text-align: center"><%$page.last_modified|format_date:'d.m.Y H:i:s'%></td>
			<td><%$page.last_modified_by_username%></td>
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