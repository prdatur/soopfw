<%if $form->is_submitted() == false%>
<%include file="form.tpl" form=$form%>
<%else%>
<div id="updatedb_status"></div>
<%/if%>