<%function name=get_widget type='' widget_id=""%>
<%if !empty($core_widgets[$type])%>
<%include file=$core_widgets[$type] widget_id=$widget_id%>
<%/if%>
<%/function%>