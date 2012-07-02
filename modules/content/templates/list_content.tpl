<%include file='form.tpl' form=$search_form%>
<br />
<%$pager|unescape%>
<table class="ui-widget ui-widget-content " style="margin-top: 10px;" cellspacing="0" cellpadding="0" border="0">
	<thead class="ui-widget-header">
		<tr>
			<td style="text-align: left;"><%t key='title'%></td>
		</tr>
	</thead>
	<tbody>
	<%foreach $pages AS $page%>
		<tr id="row_<%$page.page_id%>">
			<td style="text-align: left;">
				<a href="/content/edit/<%$page.page_id%>">
					<span><%$page.title%>
						<%if $page.deleted == 'yes'%><span style='font-style: italic'>(deleted)</span><%/if%>
						<%if empty($page.last_revision)%><span style='font-style: italic'>(unpublished)</span><%/if%>
					</span>
				</a>
			</td>
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