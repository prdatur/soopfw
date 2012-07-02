<div class="defaultBox defaultBox_<%$type%>">
	<%if isset($title)%>
	<div id="title">
		<div>
			<%if $type == "success"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-check" alt="<%t key='Success'%>">
			<%elseif $type == "error"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" alt="<%t key='Error'%>" />
			<%elseif $type == "information"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-warning" alt="<%t key='Information'%>" />
			<%/if%>

		</div>
		<div class="title-cell">
            <%if !isset($title)%>
                <%if $type == "success"%>
					<%t key='Success'%>
                <%elseif $type == "error"%>
                    <%t key='Error'%>
                <%elseif $type == "information"%>
                    <%t key='Information'%>
                <%/if%>
            <%else%>
            <%$title%>
			<%/if%>
        </div>
	</div>
	<%/if%>
	<div id="description">
		<%if is_array($description)%>
		<ul>
			<%foreach from=$description item=el%>
			<li><%$el|unescape|nl2br%></li>
			<%/foreach%>
		</ul>
		<%else%>
		<%$description|nl2br%>
		<%/if%>
	</div>
</div>