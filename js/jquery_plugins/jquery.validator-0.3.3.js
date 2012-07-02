var validate;
(function($) {
	validate = function(elm, validClassName)
	{

		hideAlert();
		if(validClassName == undefined)
		{
			validClassName = 'valid_input';
		}
		else if(validClassName == false)
		{
			validClassName = '';
		}
		var obj = (typeof elm==="object")||false;
		if(obj)
		{

			var checkVal = check(elm);

			if (checkVal)$(elm).highlight();
			else $(elm).unhighlight();
		}
		else
		{
			var marker = true;
			$("*[validate=" + elm + "]").each(function(a,el){

				var checkVal = check(el);
				if (checkVal)
				{
					if(marker == true)$(el).focus();
					marker = false;
					$(el).highlight();
				}
				else
				{
					$(el).unhighlight();
				}
			});
			checkVal = marker;
		}
		return checkVal;
	}

	function revalidate()
	{
		if (!check(this))	$(this).unhighlight();
		else				$(this).highlight();
	}

	function check(elm)
	{

		var jelm = $(elm);

		var listsize = jelm.find("input:radio, input:checkbox").size();
		if (jelm.attr("disabled") || listsize > 0 && listsize == jelm.find("input:radio:disabled, input:checkbox:disabled").size())
		{
			return "";
		}

		//if empty value only perform required validation
		if(jelm.attr("checkOnlyIfSet") != "" && jelm.attr("checkOnlyIfSet") != undefined)
		{
			var jelm2 = $(jelm.attr("checkOnlyIfSet"));
			var value = false;
			if(jelm2.attr('type') == 'checkbox' || jelm2.attr('type') == 'radio')
			{
				if(jelm2.attr('checked') == true)
				{
					value = true;
				}
			}
			else if (!empty(jelm2.val()))
			{
				value = true;
			}
			if (value == false)
			{
				return "";
			}
		}

		if (jelm.val() == "" && jelm.find("input:radio:checked, input:checkbox:checked").size() == 0)
			return (jelm.attr("require")) ? "require":"";

		var validRegExp = jelm.attr("validExpress");
			if(validRegExp == '_FLOAT')			validRegExp = "^-?\\d+([,.]\\d{0,5})?$";
			if(validRegExp == '_STRING')		validRegExp = "^[a-z ]+$";
			if(validRegExp == '_PHONE')		validRegExp = "^\\+?[0-9\\-., ]+$";
			if(validRegExp == '_EPHONE')		validRegExp = "^((\\+|00)[1-9]+[ ])?[0-9\\-., ]+[ ][0-9\\-., ]+$";
			if(validRegExp == '_EMAIL')		validRegExp = "^[^\\W][a-zA-Z0-9\\_\\-\\.]+([a-zA-Z0-9\\_\\-\\.]+)*\\@[a-zA-Z0-9]([a-zA-Z0-9_\\-]+)?(\\.[a-zA-Z0-9_\\-]+)*\\.[a-zA-Z]{2,4}$";
			else if(validRegExp == '_INT')		validRegExp = "^-?\\d+$";

		if (jelm.attr("regular") && jelm.attr("validExpress") && !new RegExp(validRegExp, "img").test(jelm.val()))
			return "regular";

		var invalidRegExp = jelm.attr("invalidExpress");
		if(invalidRegExp == '_FLOAT')
			invalidRegExp = "^-?\\d+([,.]\\d{0,5})?$";

		if (jelm.attr("regular") && jelm.attr("invalidExpress") && new RegExp(invalidRegExp, "img").test(jelm.val()))
			return "regular";

		if (jelm.attr("compare") && $("#" + jelm.attr("compareTo")).val() != jelm.val())
			return "compare";

		if (jelm.attr("custom") && !new Function(jelm.attr("customFn")).call(elm))
			return "custom";

		if (jelm.attr("invalid") && jelm.val() == jelm.attr("invalidVal"))
			return "invalid";

		return "";
	}

	function showAlert() {
		var ctrl = $(this);
		//var top = -20;
		//var top = 0-ctrl.height()-32;
		var top = 0-ctrl.height()-72;
		//var left = ctrl.offset().left + Math.max(ctrl.width() - 260, 0);
		//var left = parseInt($(this).css("margin-left").split("px")[0])+30;
		var left = ctrl.width()+6+parseInt($(this).css("margin-left").split("px")[0]);
		var left = 0;
		ctrl.parents().each(function() {
			if ($(this).css("position") != "static" && ($(this).parent().css("position") != "static" && $(this).css("relative") != "absolute") && (!$.browser.mozilla || $(this).css("display") != "table")) {
				var offset = $(this).offset();
				top -= offset.top;
				left -= offset.left;

				return false;
			}
		});
		$(".alertbox_global").remove();
		$(ctrl).qtip({content:{text:ctrl.attr(check(this))},position:{my:'left center',at:'right center'},show:{solo:true,event:'click mouseenter'},style:{classes:'alertbox_global ui-tooltip-shadow ui-tooltip-red ui-tooltip-rounded'}});
	}

	function hideAlert() {
		$(".alertbox_global").remove();
	}

	$.fn.highlight = function() { this.removeClass("valid_input").addClass("invalid_input").focus(showAlert).change(revalidate); return this; }
	$.fn.unhighlight = function(cssclass) { this.removeClass("invalid_input").unbind("focus", showAlert).unbind("blur", hideAlert).parent().children(".alertbox_global").remove(); return this; } //  if(className != undefined){this.addClass(className)} return this; }
})(jQuery);