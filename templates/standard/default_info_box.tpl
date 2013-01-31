<div class="defaultBox defaultBox_<%$type%>">
	<%if isset($title)%>
	<div id="title">
		<div>
			<%if $type == "success"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-check" alt="<%t key='Success' db=1%>">
			<%elseif $type == "error"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel" alt="<%t key='Error' db=1%>" />
			<%elseif $type == "information"%>
			<img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-warning" alt="<%t key='Information' db=1%>" />
			<%/if%>

		</div>
		<div class="title-cell">
            <%if !isset($title)%>
                <%if $type == "success"%>
					<%t key='Success' db=1%>
                <%elseif $type == "error"%>
                    <%t key='Error' db=1%>
                <%elseif $type == "information"%>
                    <%t key='Information' db=1%>
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