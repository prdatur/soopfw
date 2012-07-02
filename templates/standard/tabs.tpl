<div id="<%$tabs->id%>" class="ui_tabs">
        <ul class="tabs">
			<%foreach from=$tabs->tabs item=tab%>
            <li><a href="<%$tab.link%>" title="<%$tab.name%>"><%$tab.title%></a></li>
			<%/foreach%>
        </ul>
		<%foreach from=$tabs->tabs item=tab%>
		<div id="<%$tab.name|replace:' ':'_'%>"></div>
		<%/foreach%>
</div>