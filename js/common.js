var KEY_ENTER = 13;
var VK_ENTER = 13;

jQuery.fn.eachClick = function(callFunction) {
	this.each(function (key,value) {
		$(value).click(function (event) {
			callFunction(this, event);
		});
	});
}

String.prototype.br2nl =
  function() {
    return this.replace(/<br\s*\/?>/mg,"\n");
  };

function uuid() {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
		return v.toString(16);
	});
}

function foreach(arr, callback_func) {
	var i = "";
	for(i in arr) {
		if(arr.hasOwnProperty(i) == true) {
			if(callback_func(i, arr[i]) == true) {
                return;
            }
		}
	}
}

Array.prototype.remove = function (removeItem) {
	$.grep(this, function(val) { return val != removeItem; });
}

/**
 * Generate a password and return it, if elm is provided set the "value" attribute to the generated password
 * @param length (Int) The length of the password which will be generated (optional, default = 10)
 * @param elm (String) An jquery element selector (optional, default = undefined)
 * @param charSet (String) A charset which will be used to generate the random chars (optional, default = A-z0-9)
 * If you provide it, please do not use 0-9, you must provide every single char like 0123456789
 * @return String The generated password
 */
function generate_password(length, elm, charSet) {
	var rc = "";
	if(charSet == undefined) {
		charSet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
	}
	var charArr = charSet.shuffleString();
	if(length == undefined) {
		length = 10;
	}
	for (var i = 0; i < length; i++)
	{
		charArr.shuffleString();
		rc += charArr.charAt(getRandomNum(0, charArr.length));
	}
	if(elm != undefined) {
		$(elm).prop("value",rc);
	}
	return rc;
}

String.prototype.shuffleString = function ()
{
	var tmpArr = [];
	var string = this;
	var len = string.length;
	var returnString = "";
	for(var i = 0; i < len; i++)
	{
		tmpArr.push(string.charAt(i));
	}
	tmpArr.shuffle();
	for(var i = 0; i < tmpArr.length; i++ )
	{
		returnString += tmpArr[i];
	}
	return returnString;
}

Array.prototype.shuffle = function ()
{
	var tmp, rand;
	for(var i =0; i < this.length; i++)
	{
		rand = Math.floor(Math.random() * this.length);
		tmp = this[i];
		this[i] = this[rand];
		this[rand] =tmp;
	}
}

function getRandomNum(lbound, ubound) {
	return (Math.floor(Math.random() * (ubound - lbound)) + lbound);
}

function success_alert(msg, callback, title)
{
	if(msg == undefined)
	{
		msg = Soopfw.t("Operation successfully executed");
	}

	if(callback == undefined)
	{
		callback = function(){};
	}

	if(title == undefined)
	{
		title = Soopfw.t("Status");
	}
	$.alerts.iconClass = "success";
	$.alerts.timeout = 2500;
	return alert(msg, title, callback);
}

/**
 * Displays a question dialog and execute the callback_function if the user
 * click on "ok".
 * Questions are the same as confirmations but with a different icon
 *
 * @param string msg
 *   The message to display.
 * @param string title
 *   The title for the dialog.
 * @param mixed callback_function
 *   An anonymous function or a function name as a string
 */
function question(msg, html, title, callback_function)
{
	$.alerts.cancelButton = Soopfw.t("cancel");
	jQuestion({msg:msg, html:html}, title, function(a,b) {
		if( a == true) {
			callback_function(b);
		}
	});
}

/**
 * Displays a confirmation dialog and execute the callback_function if the user
 * click on "ok".
 *
 * @param string msg
 *   The message to display.
 * @param string title
 *   The title for the dialog.
 * @param mixed callback_function
 *   An anonymous function or a function name as a string
 * @param boolean parse_result
 *   If set to true we do not just execute the callback function if the user
 *   choose 'ok' instead we call EVERYTIME the callback function and provide
 *   as the first parameter the result if the use clicked 'ok' or 'cancel'
 */
function confirm(msg, title, callback_function, parse_result)
{
	if(callback_function == undefined) {
		callback_function = function(){};
	}
	$.alerts.cancelButton = Soopfw.t("cancel");
	return jConfirm(msg, title, function(r) {
		if(parse_result == true) {
			return callback_function(r);
		}
		if( r == true) {
			return callback_function();
		}
	});

}

/**
 * Displays a alert dialog and execute the callback_function if the user
 * click on "ok" or closes the dialog.
 *
 * @param string msg
 *   The message to display.
 * @param string title
 *   The title for the dialog.
 * @param mixed callback_function
 *   An anonymous function or a function name as a string
 */
function alert(msg, title, callback_function)
{
	if(callback_function == undefined) {
		callback_function = function(){};
	}
	$.alerts.cancelButton = Soopfw.t("cancel");
	return jAlert(msg, title, callback_function);
}

function wait_dialog(msg, title)
{
	if(title == undefined) {
		title = Soopfw.t("Please wait");
	}
	if(msg == undefined) {
		msg = Soopfw.t("Action pending, please be patience");
	}
	return jWaitDialog(title, msg);
}

function get_form_by_class(classname, selector, checkboxFalse)
{
	if(selector == undefined || selector == null) {
		selector = "name";
	}
	if(classname == undefined) {
		classname = ".default_form";
	}
	else {
		var firstchar = classname.substr(0, 1);
		if(firstchar != '.' && firstchar != '#') {
			classname = "."+classname;
		}
	}
	var formVariables = {};
	$(classname).each(function(k,v) {
		if($(v)[0].type == "checkbox") {
			if($(v).prop("checked")) {
				formVariables[$(v).prop(selector)] = $(v).prop("value");
			}
			else if (checkboxFalse == true) {
				formVariables[$(v).prop(selector)] = "0";
			}
		}
		else if($(v)[0].type == "radio") {
			if($(v).prop("selected") || $(v).prop("checked")) {
				formVariables[$(v).prop(selector)] = $(v).prop("value");
			}
		}
		else {
			formVariables[$(v).prop(selector)] = $(v).prop("value");
		}
	});
	return formVariables
}

function htmlspecialchars(str,typ) {
    if(typeof str=="undefined") str="";
    if(typeof typ!="number") typ=2;
    typ=Math.max(0,Math.min(3,parseInt(typ)));
    var from=new Array(/&/g,/</g,/>/g);
    var to=new Array("&amp;","&lt;","&gt;");
    if(typ==1 || typ==3) {from.push(/'/g); to.push("&#039;");}
    if(typ==2 || typ==3) {from.push(/"/g); to.push("&quot;");}
    for(var i in from) str=str.replace(from[i],to[i]);
    return str;
  }

function popup(URL, width, height, align, valign) {
	if (width <= 0) {
		width=800;
	}
	if (height <= 0) {
		height=600;
	}

	var left = 20;
	var top = 20;

	if(align == undefined)
	{
		align = "center";
	}

	if(valign == undefined)
	{
		valign = "center";
	}

	if(align == "left")
	{
		left = 0;
	}
	else if(align == "center")
	{
		left = ($(window).width()/2)-(width/2);
	}
	else if(align == "right")
	{
		left = $(window).width()-width;
	}

	if(valign == "top")
	{
		top = 0;
	}
	else if(valign == "center")
	{
		top = ($(window).height()/2)-(height/2);
	}
	else if(valign == "bottom")
	{
		top = $(window).height()-height;
	}
	day = new Date();
	id = day.getTime();
	window.open(URL, id, 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=' + width + ',height=' + height +' ,left = '+left+',top = '+top);
	return false;
}

function parse_ajax_result(result, return_function, additionalParams, parseCodes,error_function)
{
	if(parseCodes == undefined || parseCodes == null)
	{
		var parseCodes = [200];
	}
	if(additionalParams == undefined || additionalParams == null)
	{
		var additionalParams = new Object();
	}

	if(return_function == undefined) {
		return_function = function() {};
	}
	for(var i in parseCodes)
	{
		if(parseCodes[i] == result.code)
		{
			return return_function(result.data, result.code, result.desc, additionalParams);
		}
	}
	var code = parseInt(result.code);

	if(code >= 300 && code < 400)
	{
		return return_function(result.data, result.code, result.desc, additionalParams);
	}

	if(code == 550)
	{
		alert(Soopfw.t("You did to much actions in time, please validate that you are human"), Soopfw.t("Error"), function(){location.reload();});
		return false;
	}
	if(code >= 600 && code < 700)
	{
		if(error_function != undefined)
		{
			error_function();
		}
		alert(Soopfw.t("No permission")+"\n"+result.desc+" ("+result.code+")");
		return false;
	}

	if(code == 406)
	{
		if(error_function != undefined)
		{
			error_function();
		}
		alert(Soopfw.t("You did not filled out all required fields")+"\n"+result.desc+" ("+result.code+")");
		return false;
	}
	if(error_function != undefined)
	{
		error_function();
	}
	if(result.desc == undefined || result.desc == "") {
		alert(Soopfw.t("Ajax call failed")+"\n"+result.desc+" ("+result.code+")");
	}
	else {
		alert(result.desc);
	}
	return false;
}

function ajax_success(url, data, msg,title, return_function, error_function)
{
	if(title == undefined || title == '' || title == null)
	{
		title = Soopfw.t("Status");
	}
	if(msg == undefined || msg == '' || msg == null)
	{
		msg = Soopfw.t("Operation successfully executed");
	}
	ajax_request(url, data, function(result){
		success_alert(msg, function(){
			if(return_function == undefined)
			{
				return true;
			}
			return_function(result);
		}, title);
	}, error_function);
}
function ajax_request(url, dataArray, return_function, error_function, options)
{

	$.ajax($.extend({
        type: 'POST',
        dataType: 'json',
		url: url,
		async: true,
		data: dataArray,
		success: function(result) {
			if(error_function == undefined) {
				error_function = function(){}
			}
			if(return_function == undefined) {
				return_function = function(){}
			}
			parse_ajax_result(result, function(result, code, desc, additionalParams) {
				return_function(result, code, desc, additionalParams);
			},null,null, error_function);
		}
	}, options));
}


function toggle(caller, obj)
{
	$(obj).toggle();
	if($(obj).css("display") == "none")
	{
		$(caller).removeClass("ui-icon-circle-triangle-n");
		$(caller).addClass("ui-icon-circle-triangle-s");
	}
	else
	{
		$(caller).removeClass("ui-icon-circle-triangle-s");
		$(caller).addClass("ui-icon-circle-triangle-n");
	}
}

function create_element(options) {
	var tmpEle = document.createElement(options['input']);
	for(var key in options['attr']) {
		if(key == "html"){
			$(tmpEle).html(options['attr'][key]);
		} else if (
			key == 'checked' ||
			key == 'selected'
		) {
			$(tmpEle).prop(key, options['attr'][key]);
		} else {
			$(tmpEle).attr(key, options['attr'][key]);
		}

	}
	if(options['click'] != undefined){		$(tmpEle).click(options['click']);}
	if(options['change'] != undefined){		$(tmpEle).change(options['change']);}
	if(options['mouseover'] != undefined){	$(tmpEle).mouseover(options['mouseover']);}
	if(options['mouseout'] != undefined){	$(tmpEle).mouseout(options['mouseout']);}
	if(options['keypress'] != undefined){	$(tmpEle).keypress(options['keypress']);}
	if(options['keydown'] != undefined){	$(tmpEle).keypress(options['keydown']);}
	if(options['keyup'] != undefined){	$(tmpEle).keyup(options['keyup']);}
	if(options['mouseup'] != undefined){	$(tmpEle).mouseup(options['mouseup']);}
	if(options['append'] != undefined){
		jQuery.each(options['append'],function (key, value) {
			if(value != "" && value != undefined) {
				$(tmpEle).append(value);
			}
		});
	}
	if(options['css'] != undefined) {
		for(var key in options['css']) {
			$(tmpEle).css(key, options['css'][key]);
		}
	}

	if(options['input'] == "input" && options['attr']['type'] == "checkbox")
	{
		$(tmpEle).addClass("input_checkbox");
	}

	if(options['input'] == "input" && options['attr']['type'] == "radio")
	{
		$(tmpEle).addClass("input_radio");
	}
	return $(tmpEle);
}

function implode (glue, pieces) {
    // Joins array elements placing glue string between items and return one string
    //
    // version: 911.718
    // discuss at: http://phpjs.org/functions/implode    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Waldo Malqui Silva
    // +   improved by: Itsacon (http://www.itsacon.net/)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: implode(' ', ['Kevin', 'van', 'Zonneveld']);    // *     returns 1: 'Kevin van Zonneveld'
    // *     example 2: implode(' ', {first:'Kevin', last: 'van Zonneveld'});
    // *     returns 2: 'Kevin van Zonneveld'
    var i = '', retVal='', tGlue='';
    if (arguments.length === 1) {        pieces = glue;
        glue = '';
    }
    if (typeof(pieces) === 'object') {
        if (pieces instanceof Array) {            return pieces.join(glue);
        }
        else {
            for (i in pieces) {
                retVal += tGlue + pieces[i];                tGlue = glue;
            }
            return retVal;
        }
    }    else {
        return pieces;
    }
}

function explode (delimiter, string, limit) {
    // Splits a string on string separator and return array of components. If limit is positive only limit number of components is returned. If limit is negative all components except the last abs(limit) are returned.
    //
    // version: 909.322
    // discuss at: http://phpjs.org/functions/explode    // +     original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: kenneth
    // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: d3x
    // +     bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // *     example 1: explode(' ', 'Kevin van Zonneveld');
    // *     returns 1: {0: 'Kevin', 1: 'van', 2: 'Zonneveld'}
    // *     example 2: explode('=', 'a=bc=d', 2);
    // *     returns 2: ['a', 'bc=d']
	var emptyArray = { 0: '' };

    // third argument is not required
	if ( arguments.length < 2 || typeof arguments[0] == 'undefined' || typeof arguments[1] == 'undefined' )
	{
		return null;
	}

	if ( delimiter === '' || delimiter === false || delimiter === null )
	{
		return false;
	}

	if ( typeof delimiter == 'function' || typeof delimiter == 'object' || typeof string == 'function' || typeof string == 'object' )
	{
		return emptyArray;
	}
	if ( delimiter === true )
	{
		delimiter = '1';
	}

	if (!limit)
	{
		return string.toString().split(delimiter.toString());
	}
	else
	{
		// support for limit argument
		var splitted = string.toString().split(delimiter.toString());
		var partA = splitted.splice(0, limit - 1);
		var partB = splitted.join(delimiter.toString());
		partA.push(partB);
		return partA;
	}
}

function call_user_func (cb) {
    if (typeof cb === 'string') {
        func = (typeof this[cb] === 'function') ? this[cb] : func = (new Function(null, 'return ' + cb))();
    }    else if (Object.prototype.toString.call(cb) === '[object Array]') {
        func = (typeof cb[0] == 'string') ? eval(cb[0] + "['" + cb[1] + "']") : func = cb[0][cb[1]];
    }
    else if (typeof cb === 'function') {
        func = cb;    }

    if (typeof func != 'function') {
        throw new Error(func + ' is not a valid function');
    }
    var parameters = Array.prototype.slice.call(arguments, 1);
    return (typeof cb[0] === 'string') ? func.apply(eval(cb[0]), parameters) : (typeof cb[0] !== 'object') ? func.apply(null, parameters) : func.apply(cb[0], parameters);
}

function is_array (mixed_var) {
    var key = '';
	var getFuncName = function (fn) {
        var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
        if (!name) {
            return '(Anonymous)';
        }        return name[1];
    };

    if (!mixed_var) {
        return false;
	}



    if (typeof mixed_var === 'object') {
         if (mixed_var.hasOwnProperty) {
            for (key in mixed_var) {
                // Checks whether the object has the specified property
                // if not, we figure it's not an object in the sense of a php-associative-array.
                if (false === mixed_var.hasOwnProperty(key)) {                    return false;
                }
            }
        }
         // Read discussion at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_is_array/
        return true;
    }

    return false;
}

function count (mixed_var, mode) {
		var key, cnt = 0;

		if (mixed_var === null) {        return 0;

		}
		else if (!is_array(mixed_var)) {
			return 1;
		}
		 if (mode === 'COUNT_RECURSIVE') {
			mode = 1;
		}
		if (mode != 1) {
			mode = 0;    }

		for (key in mixed_var){
			cnt++;
			if ( mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){            cnt += this.count(mixed_var[key], 1);
			}
		}

		return cnt;
	}

	function array_keys (input, search_value, argStrict) {

		var tmp_arr = {}, strict = !!argStrict, include = true, cnt = 0;
		var key = '';

		for (key in input) {        include = true;
			if (search_value != undefined) {
				if (strict && input[key] !== search_value){
					include = false;
				} else if (input[key] != search_value){                include = false;
				}
			}

			if (include) {            tmp_arr[cnt] = key;
				cnt++;
			}
		}
			return tmp_arr;
	}

function trim (zeichenkette) {
  // Erst führende, dann Abschließende Whitespaces entfernen
  // und das Ergebnis dieser Operationen zurückliefern
  return zeichenkette.replace (/^\s+/, '').replace (/\s+$/, '');
}


function nl2br (str, is_xhtml) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Philip Peterson
    // +   improved by: Onno Marsman
    // +   improved by: Atli Þór
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Maximusya
    // *     example 1: nl2br('Kevin\nvan\nZonneveld');
    // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
    // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
    // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
    // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
    // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'

    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';

    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

//function str_replace (search, replace, subject, count) {
    // Replaces all occurrences of search in haystack with replace
    //
    // version: 909.322
    // discuss at: http://phpjs.org/functions/str_replace    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Gabriel Paderni
    // +   improved by: Philip Peterson
    // +   improved by: Simon Willison (http://simonwillison.net)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)    // +   bugfixed by: Anton Ongson
    // +      input by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    tweaked by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   input by: Oleg Eremeev
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Oleg Eremeev
    // %          note 1: The count parameter must be passed as a string in order    // %          note 1:  to find a global variable in which the result will be given
    // *     example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
    // *     returns 1: 'Kevin.van.Zonneveld'
    // *     example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
    // *     returns 2: 'hemmo, mars'    var i = 0, j = 0, temp = '', repl = '', sl = 0, fl = 0,
 /*           f = [].concat(search),
            r = [].concat(replace),
            s = subject,
            ra = r instanceof Array, sa = s instanceof Array;    s = [].concat(s);
    if (count) {
        this.window[count] = 0;
    }
     for (i=0, sl=s.length; i < sl; i++) {
        if (s[i] === '') {
            continue;
        }
        for (j=0, fl=f.length; j < fl; j++) {            temp = s[i]+'';
            repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0];
            s[i] = (temp).split(f[j]).join(repl);
            if (count && s[i] !== temp) {
                this.window[count] += (temp.length-s[i].length)/f[j].length;}        }
    }
    return sa ? s : s[0];
}
*/

function str_replace(search, replace, subject) {
	return subject.split(search).join(replace);
}

function date_format(date, format)
{
	return format_date(date, format);
}
function format_date(date, format)
{

	if(format == undefined)
	{
		format = "dd.mm.yyyy HH:MM:ss";
	}
	if(date == undefined || date == null)
	{
		date = new Date();
	}
	else
	{
		if(parseInt(date) != date && parseFloat(date) != date)
		{
			date = date+" GMT+0100";
			date = Date.parse(date);
		}
	}
	var tmpDate = new Date(date);
	return tmpDate.format(format);
}

function date_compare(todate,fromdate)
{
	//console.log(todate);
	if(parseInt(todate) != todate)
	{
		todate = todate+" GMT+0100";
		todate = Date.parse(todate);
	}
	else
	{
		todate = new Date(todate);
	}


	if(fromdate == undefined)
	{
		fromdate = new Date();
	}
	else
	{
		fromdate = new Date(Date.parse(fromdate));
	}


	//console.log(fromdate);
	//console.log(todate);
	return todate.compareTo(fromdate);
}

function empty (mixed_var) {
    // !No description available for empty. @php.js developers: Please update the function summary text file.
    //
    // version: 911.1619
    // discuss at: http://phpjs.org/functions/empty    // +   original by: Philippe Baumann
    // +      input by: Onno Marsman
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: LH
    // +   improved by: Onno Marsman    // +   improved by: Francesco
    // +   improved by: Marc Jansen
    // +   input by: Stoyan Kyosev (http://www.svest.org/)
    // *     example 1: empty(null);
    // *     returns 1: true    // *     example 2: empty(undefined);
    // *     returns 2: true
    // *     example 3: empty([]);
    // *     returns 3: true
    // *     example 4: empty({});    // *     returns 4: true
    // *     example 5: empty({'aFunc' : function () { alert('humpty'); } });
    // *     returns 5: false

    var key;
    if (mixed_var === "" ||
        mixed_var === 0 ||
        mixed_var === "0" ||
        mixed_var === null ||        mixed_var === false ||
        typeof mixed_var === 'undefined'
    ){
        return true;
    }
    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }        return true;
    }

    return false;
}

function parseID(value,index,splitchar) {
	if(splitchar == undefined)
	{
		splitchar = "_";
	}

	if(index == undefined)
	{
		return $(value).prop("id").split(splitchar);
	}
	else
	{
		return $(value).prop("id").split(splitchar)[index];
	}
}

function createDialog(options) {
	if(options.active_color != undefined && options.groupBy != undefined && $(options.target).hasClass("activeDialogColor_"+options.id) == false)
	{
		$(options.target).addClass("activeDialogColor_"+options.groupBy)
	}
	//options.inactive_color = $(options.target).parent().css("background-color");
	if(document.getElementById(options.id) != undefined) {
		$("#"+options.id).remove();
		if(options.onload != undefined)
		{
			options.onload($(tmpDiv));
		}
		if(options.active_color != undefined)
		{
			$(options.target).removeClass(options.active_color);
		}
	} else {
		if(options.active_color != undefined && options.target != undefined)
		{
			if(options.groupBy != undefined)
			{
				$(".activeDialogColor_"+options.groupBy).removeClass(options.active_color);
				//$(".activeDialogColor_"+options.groupBy).css("background-color", options.inactive_color);
			}
			$(options.target).addClass(options.active_color);
			//$(options.target).css("background-color",options.active_color);
		}

		if(options.groupBy != undefined) {
			$("."+options.groupBy).remove();
		}
		var width = options.width;
		var height = options.height;

		if(options.success_label == undefined)
		{
			options.success_label = Soopfw.t("Send");
		}

		if(options.cancel_label == undefined)
		{
			options.cancel_label = Soopfw.t("cancel");
		}

		if(options.buttons == true) {

			var buttons = create_element({input:'div', attr: {"class": 'dialogButtons'}, append:[
				create_element({input:'div', css: {width: '50%', "float":'left'},append:[
					create_element({input:'button',attr:{value:options.cancel_label, html:options.cancel_label},css:{display:'inline'},click:function(event) {
						if(options.active_color != undefined) {
							$(options.target).removeClass(options.active_color);
						}
						if(options.cancel == undefined) {
							$("#"+options.id).remove();
						}
						else {
							options.cancel(event);
						}
					}})
				]}),
				create_element({input:'div', css: {width: '50%', "float":'right'},append:[
					create_element({input:'button',attr:{value:options.success_label, html:options.success_label},css:{display:'inline'},click:function(event) {
						if(options.active_color != undefined) {
							$(options.target).removeClass(options.active_color);
						}
						if(options.success == undefined) {
							$("#"+options.id).remove();
						}
						else {
							if(options.success(event)) {
								$("#"+options.id).remove();
							}
						}
					}})
				]}),
				create_element({input:'div', css:{"clear": 'both'}})
			]});

		}

		var wrapper = create_element({input: 'div', attr: {"class": 'dialogBody'}, css: {height: '100%'}});
		jQuery.each(options.append,function (key, value) {
			if(value != "" && value != undefined) {
				$(wrapper).append(value);

			}
		});
		var appendContent = create_element({input:'div',css:{margin:'0px'},attr:{cellpadding:'0',cellspacing:'0',"class":'defaultDialog'}, append:[
			create_element({input:'div', attr:{"class": 'dialogTitle'},append:[
				create_element({input:'div', css:{padding:"2px", "text-align": 'left', 'float': 'left'},attr:{html:options.title}}),
				create_element({input: 'img',css:{'float': 'right'}, attr:{src: '/1x1_spacer.gif', "class": 'linkedElement ui-icon-soopfw ui-icon-soopfw-cancel'}, click: function(){
					if(options.active_color != undefined)
					{
						$(options.target).removeClass(options.active_color);
					}
					if(options.cancel == undefined) {
						$("#"+options.id).remove();
					}
					else {
						options.cancel(event);
					}
				}}),
				create_element({input:'div', css:{"clear": 'both'}})
			]}),
			wrapper,
			buttons
		]});

		var tmpDivTable = create_element({input:'div', attr:{"class":'jsdialog'},append:[appendContent]});
		var tmpDiv = create_element({input:'div', css:{position:'absolute'}, attr:{id:options.id},append:[tmpDivTable]});

		if(options.width != undefined) {
			$(tmpDiv).css("width",options.width+"px");
		} else {
			options.width = 0;
		}

		if(options.height != undefined)		{
			$(tmpDiv).css("height",options.height+"px");
		} else {
			options.height = 0;
		}

		if(options.groupBy != undefined) {
			$(tmpDiv).addClass(options.groupBy);
		}

		$("body").append(tmpDiv);
		if(options.target != undefined) {
			var offsetc = $(options.target).offset();
			if(options.left == undefined) {
				options.left = 0;
			}
			if(options.top == undefined) {
				options.top = 0;
			}

			$(tmpDiv).css("left",(offsetc.left+options.left)+$(options.target).width());
			$(tmpDiv).css("top",offsetc.top+options.top-15);
		}
		else
		{
			var top = ($(window).height() / 2) -  ($(tmpDiv).height()/2);
			var left = ($(window).width() / 2) -  ($(tmpDiv).width()/2);

			$(tmpDiv).css("left",left);
			$(tmpDiv).css("top",top);
		}

		//$(tmpDivTable).shadow({width:8, fit:true});
		if(options.onload != undefined)
		{
			options.onload($(tmpDiv));
		}
	}
}

/*
 * Date Format 1.2.3
 * (c) 2007-2009 Steven Levithan <stevenlevithan.com>
 * MIT license
 *
 * Includes enhancements by Scott Trenda <scott.trenda.net>
 * and Kris Kowal <cixar.com/~kris.kowal/>
 *
 * Accepts a date, a mask, or a date and a mask.
 * Returns a formatted version of the given date.
 * The date defaults to the current date/time.
 * The mask defaults to dateFormat.masks.default.
 */

var dateFormat = function () {
	var	token = /d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,
		timezone = /\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,
		timezoneClip = /[^-+\dA-Z]/g,
		pad = function (val, len) {
			val = String(val);
			len = len || 2;
			while (val.length < len) val = "0" + val;
			return val;
		};

	// Regexes and supporting functions are cached through closure
	return function (date, mask, utc) {
		var dF = dateFormat;

		// You can't provide utc if you skip other args (use the "UTC:" mask prefix)
		if (arguments.length == 1 && Object.prototype.toString.call(date) == "[object String]" && !/\d/.test(date)) {
			mask = date;
			date = undefined;
		}

		// Passing date through Date applies Date.parse, if necessary
		date = date ? new Date(date) : new Date;
		if (isNaN(date)) throw SyntaxError("invalid date");

		mask = String(dF.masks[mask] || mask || dF.masks["default"]);

		// Allow setting the utc argument via the mask
		if (mask.slice(0, 4) == "UTC:") {
			mask = mask.slice(4);
			utc = true;
		}

		var	_ = utc ? "getUTC" : "get",
			d = date[_ + "Date"](),
			D = date[_ + "Day"](),
			m = date[_ + "Month"](),
			y = date[_ + "FullYear"](),
			H = date[_ + "Hours"](),
			M = date[_ + "Minutes"](),
			s = date[_ + "Seconds"](),
			L = date[_ + "Milliseconds"](),
			o = utc ? 0 : date.getTimezoneOffset(),
			flags = {
				d:    d,
				dd:   pad(d),
				ddd:  dF.i18n.dayNames[D],
				dddd: dF.i18n.dayNames[D + 7],
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  dF.i18n.monthNames[m],
				mmmm: dF.i18n.monthNames[m + 12],
				yy:   String(y).slice(2),
				yyyy: y,
				h:    H % 12 || 12,
				hh:   pad(H % 12 || 12),
				H:    H,
				HH:   pad(H),
				M:    M,
				MM:   pad(M),
				s:    s,
				ss:   pad(s),
				l:    pad(L, 3),
				L:    pad(L > 99 ? Math.round(L / 10) : L),
				t:    H < 12 ? "a"  : "p",
				tt:   H < 12 ? "am" : "pm",
				T:    H < 12 ? "A"  : "P",
				TT:   H < 12 ? "AM" : "PM",
				Z:    utc ? "UTC" : (String(date).match(timezone) || [""]).pop().replace(timezoneClip, ""),
				o:    (o > 0 ? "-" : "+") + pad(Math.floor(Math.abs(o) / 60) * 100 + Math.abs(o) % 60, 4),
				S:    ["th", "st", "nd", "rd"][d % 10 > 3 ? 0 : (d % 100 - d % 10 != 10) * d % 10]
			};

		return mask.replace(token, function ($0) {
			return $0 in flags ? flags[$0] : $0.slice(1, $0.length - 1);
		});
	};
}();

// Some common format strings
dateFormat.masks = {
	"default":      "ddd mmm dd yyyy HH:MM:ss",
	shortDate:      "m/d/yy",
	mediumDate:     "mmm d, yyyy",
	longDate:       "mmmm d, yyyy",
	fullDate:       "dddd, mmmm d, yyyy",
	shortTime:      "h:MM TT",
	mediumTime:     "h:MM:ss TT",
	longTime:       "h:MM:ss TT Z",
	isoDate:        "yyyy-mm-dd",
	isoTime:        "HH:MM:ss",
	isoDateTime:    "yyyy-mm-dd'T'HH:MM:ss",
	isoUtcDateTime: "UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"
};

// Internationalization strings
dateFormat.i18n = {
	dayNames: [
		"Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat",
		"Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
	],
	monthNames: [
		"Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
		"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
	]
};

// For convenience...
Date.prototype.format = function (mask, utc) {
	return dateFormat(this, mask, utc);
};

// mredkj.com
function NumberFormat(num, inputDecimal)
{
this.VERSION = 'Number Format v1.5.4';
this.COMMA = ',';
this.PERIOD = '.';
this.DASH = '-';
this.LEFT_PAREN = '(';
this.RIGHT_PAREN = ')';
this.LEFT_OUTSIDE = 0;
this.LEFT_INSIDE = 1;
this.RIGHT_INSIDE = 2;
this.RIGHT_OUTSIDE = 3;
this.LEFT_DASH = 0;
this.RIGHT_DASH = 1;
this.PARENTHESIS = 2;
this.NO_ROUNDING = -1
this.num;
this.numOriginal;
this.hasSeparators = false;
this.separatorValue;
this.inputDecimalValue;
this.decimalValue;
this.negativeFormat;
this.negativeRed;
this.hasCurrency;
this.currencyPosition;
this.currencyValue;
this.places;
this.roundToPlaces;
this.truncate;
this.setNumber = setNumberNF;
this.toUnformatted = toUnformattedNF;
this.setInputDecimal = setInputDecimalNF;
this.setSeparators = setSeparatorsNF;
this.setCommas = setCommasNF;
this.setNegativeFormat = setNegativeFormatNF;
this.setNegativeRed = setNegativeRedNF;
this.setCurrency = setCurrencyNF;
this.setCurrencyPrefix = setCurrencyPrefixNF;
this.setCurrencyValue = setCurrencyValueNF;
this.setCurrencyPosition = setCurrencyPositionNF;
this.setPlaces = setPlacesNF;
this.toFormatted = toFormattedNF;
this.toPercentage = toPercentageNF;
this.getOriginal = getOriginalNF;
this.moveDecimalRight = moveDecimalRightNF;
this.moveDecimalLeft = moveDecimalLeftNF;
this.getRounded = getRoundedNF;
this.preserveZeros = preserveZerosNF;
this.justNumber = justNumberNF;
this.expandExponential = expandExponentialNF;
this.getZeros = getZerosNF;
this.moveDecimalAsString = moveDecimalAsStringNF;
this.moveDecimal = moveDecimalNF;
this.addSeparators = addSeparatorsNF;
if (inputDecimal == null) {
this.setNumber(num, this.PERIOD);
} else {
this.setNumber(num, inputDecimal);
}
this.setCommas(true);
this.setNegativeFormat(this.LEFT_DASH);
this.setNegativeRed(false);
this.setCurrency(false);
this.setCurrencyPrefix('$');
this.setPlaces(2);
}
function setInputDecimalNF(val)
{
this.inputDecimalValue = val;
}
function setNumberNF(num, inputDecimal)
{
if (inputDecimal != null) {
this.setInputDecimal(inputDecimal);
}
this.numOriginal = num;
this.num = this.justNumber(num);
}
function toUnformattedNF()
{
return (this.num);
}
function getOriginalNF()
{
return (this.numOriginal);
}
function setNegativeFormatNF(format)
{
this.negativeFormat = format;
}
function setNegativeRedNF(isRed)
{
this.negativeRed = isRed;
}
function setSeparatorsNF(isC, separator, decimal)
{
this.hasSeparators = isC;
if (separator == null) separator = this.COMMA;
if (decimal == null) decimal = this.PERIOD;
if (separator == decimal) {
this.decimalValue = (decimal == this.PERIOD) ? this.COMMA : this.PERIOD;
} else {
this.decimalValue = decimal;
}
this.separatorValue = separator;
}
function setCommasNF(isC)
{
this.setSeparators(isC, this.COMMA, this.PERIOD);
}
function setCurrencyNF(isC)
{
this.hasCurrency = isC;
}
function setCurrencyValueNF(val)
{
this.currencyValue = val;
}
function setCurrencyPrefixNF(cp)
{
this.setCurrencyValue(cp);
this.setCurrencyPosition(this.LEFT_OUTSIDE);
}
function setCurrencyPositionNF(cp)
{
this.currencyPosition = cp
}
function setPlacesNF(p, tr)
{
this.roundToPlaces = !(p == this.NO_ROUNDING);
this.truncate = (tr != null && tr);
this.places = (p < 0) ? 0 : p;
}
function addSeparatorsNF(nStr, inD, outD, sep)
{
nStr += '';
var dpos = nStr.indexOf(inD);
var nStrEnd = '';
if (dpos != -1) {
nStrEnd = outD + nStr.substring(dpos + 1, nStr.length);
nStr = nStr.substring(0, dpos);
}
var rgx = /(\d+)(\d{3})/;
while (rgx.test(nStr)) {
nStr = nStr.replace(rgx, '$1' + sep + '$2');
}
return nStr + nStrEnd;
}
function toFormattedNF()
{
var pos;
var nNum = this.num;
var nStr;
var splitString = new Array(2);
if (this.roundToPlaces) {
nNum = this.getRounded(nNum);
nStr = this.preserveZeros(Math.abs(nNum));
} else {
nStr = this.expandExponential(Math.abs(nNum));
}
if (this.hasSeparators) {
nStr = this.addSeparators(nStr, this.PERIOD, this.decimalValue, this.separatorValue);
} else {
nStr = nStr.replace(new RegExp('\\' + this.PERIOD), this.decimalValue);
}
var c0 = '';
var n0 = '';
var c1 = '';
var n1 = '';
var n2 = '';
var c2 = '';
var n3 = '';
var c3 = '';
var negSignL = (this.negativeFormat == this.PARENTHESIS) ? this.LEFT_PAREN : this.DASH;
var negSignR = (this.negativeFormat == this.PARENTHESIS) ? this.RIGHT_PAREN : this.DASH;
if (this.currencyPosition == this.LEFT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c0 = this.currencyValue+" ";
} else if (this.currencyPosition == this.LEFT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c1 = this.currencyValue+" ";
}
else if (this.currencyPosition == this.RIGHT_INSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n0 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n3 = negSignR;
}
if (this.hasCurrency) c2 = " "+this.currencyValue;
}
else if (this.currencyPosition == this.RIGHT_OUTSIDE) {
if (nNum < 0) {
if (this.negativeFormat == this.LEFT_DASH || this.negativeFormat == this.PARENTHESIS) n1 = negSignL;
if (this.negativeFormat == this.RIGHT_DASH || this.negativeFormat == this.PARENTHESIS) n2 = negSignR;
}
if (this.hasCurrency) c3 = " "+this.currencyValue;
}
nStr = c0 + n0 + c1 + n1 + nStr + n2 + c2 + n3 + c3;
if (this.negativeRed && nNum < 0) {
nStr = '<font color="red">' + nStr + '</font>';
}
return (nStr);
}
function toPercentageNF()
{
nNum = this.num * 100;
nNum = this.getRounded(nNum);
return nNum + '%';
}
function getZerosNF(places)
{
var extraZ = '';
var i;
for (i=0; i<places; i++) {
extraZ += '0';
}
return extraZ;
}
function expandExponentialNF(origVal)
{
if (isNaN(origVal)) return origVal;
var newVal = parseFloat(origVal) + '';
var eLoc = newVal.toLowerCase().indexOf('e');
if (eLoc != -1) {
var plusLoc = newVal.toLowerCase().indexOf('+');
var negLoc = newVal.toLowerCase().indexOf('-', eLoc);
var justNumber = newVal.substring(0, eLoc);
if (negLoc != -1) {
var places = newVal.substring(negLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, true, parseInt(places));
} else {
if (plusLoc == -1) plusLoc = eLoc;
var places = newVal.substring(plusLoc + 1, newVal.length);
justNumber = this.moveDecimalAsString(justNumber, false, parseInt(places));
}
newVal = justNumber;
}
return newVal;
}
function moveDecimalRightNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, false);
} else {
newVal = this.moveDecimal(val, false, places);
}
return newVal;
}
function moveDecimalLeftNF(val, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimal(val, true);
} else {
newVal = this.moveDecimal(val, true, places);
}
return newVal;
}
function moveDecimalAsStringNF(val, left, places)
{
var spaces = (arguments.length < 3) ? this.places : places;
if (spaces <= 0) return val;
var newVal = val + '';
var extraZ = this.getZeros(spaces);
var re1 = new RegExp('([0-9.]+)');
if (left) {
newVal = newVal.replace(re1, extraZ + '$1');
var re2 = new RegExp('(-?)([0-9]*)([0-9]{' + spaces + '})(\\.?)');
newVal = newVal.replace(re2, '$1$2.$3');
} else {
var reArray = re1.exec(newVal);
if (reArray != null) {
newVal = newVal.substring(0,reArray.index) + reArray[1] + extraZ + newVal.substring(reArray.index + reArray[0].length);
}
var re2 = new RegExp('(-?)([0-9]*)(\\.?)([0-9]{' + spaces + '})');
newVal = newVal.replace(re2, '$1$2$4.');
}
newVal = newVal.replace(/\.$/, '');
return newVal;
}
function moveDecimalNF(val, left, places)
{
var newVal = '';
if (places == null) {
newVal = this.moveDecimalAsString(val, left);
} else {
newVal = this.moveDecimalAsString(val, left, places);
}
return parseFloat(newVal);
}
function getRoundedNF(val)
{
val = this.moveDecimalRight(val);
if (this.truncate) {
val = val >= 0 ? Math.floor(val) : Math.ceil(val);
} else {
val = Math.round(val);
}
val = this.moveDecimalLeft(val);
return val;
}
function preserveZerosNF(val)
{
var i;
val = this.expandExponential(val);
if (this.places <= 0) return val;
var decimalPos = val.indexOf('.');
if (decimalPos == -1) {
val += '.';
for (i=0; i<this.places; i++) {
val += '0';
}
} else {
var actualDecimals = (val.length - 1) - decimalPos;
var difference = this.places - actualDecimals;
for (i=0; i<difference; i++) {
val += '0';
}
}
return val;
}
function justNumberNF(val)
{
newVal = val + '';
var isPercentage = false;
if (newVal.indexOf('%') != -1) {
newVal = newVal.replace(/\%/g, '');
isPercentage = true;
}
var re = new RegExp('[^\\' + this.inputDecimalValue + '\\d\\-\\+\\(\\)eE]', 'g');
newVal = newVal.replace(re, '');
var tempRe = new RegExp('[' + this.inputDecimalValue + ']', 'g');
var treArray = tempRe.exec(newVal);
if (treArray != null) {
var tempRight = newVal.substring(treArray.index + treArray[0].length);
newVal = newVal.substring(0,treArray.index) + this.PERIOD + tempRight.replace(tempRe, '');
}
if (newVal.charAt(newVal.length - 1) == this.DASH ) {
newVal = newVal.substring(0, newVal.length - 1);
newVal = '-' + newVal;
}
else if (newVal.charAt(0) == this.LEFT_PAREN
&& newVal.charAt(newVal.length - 1) == this.RIGHT_PAREN) {
newVal = newVal.substring(1, newVal.length - 1);
newVal = '-' + newVal;
}
newVal = parseFloat(newVal);
if (!isFinite(newVal)) {
newVal = 0;
}
if (isPercentage) {
newVal = this.moveDecimalLeft(newVal, 2);
}
return newVal;
}



function money_format(value, currency)
{
	var num = new NumberFormat();
	num.setInputDecimal('.');
	num.setNumber(value);
	num.setPlaces('2');
	num.setCurrencyValue(currency);
	num.setCurrency(true);
	num.setCurrencyPosition(num.RIGHT_OUTSIDE);
	num.setNegativeFormat(num.LEFT_DASH);
	num.setNegativeRed(true);
	num.setSeparators(true, '\'', ',');
	return num.toFormatted();
}


/*
 * jQuery UI Menu (not officially released)
 *
 * This widget isn't yet finished and the API is subject to change. We plan to finish
 * it for the next release. You're welcome to give it a try anyway and give us feedback,
 * as long as you're okay with migrating your code later on. We can help with that, too.
 *
 * Copyright (c) 2010 AUTHORS.txt (http://jqueryui.com/about)
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * http://docs.jquery.com/UI/Menu
 *
 * Depends:
 *	jquery.ui.core.js
 *  jquery.ui.widget.js
 */
(function($) {

$.widget("ui.menu", {
	_create: function() {
		var self = this;
		this.element
			.addClass("ui-menu ui-widget ui-widget-content ui-corner-all")
			.prop({
				role: "listbox",
				"aria-activedescendant": "ui-active-menuitem"
			})
			.click(function( event ) {
				if ( !$( event.target ).closest( ".ui-menu-item a" ).length ) {
					return;
				}
				// temporary
				event.preventDefault();
				self.select( event );
			});
		this.refresh();
	},

	refresh: function() {
		var self = this;

		// don't refresh list items that are already adapted
		var items = this.element.children("li:not(.ui-menu-item):has(a)")
			.addClass("ui-menu-item")
			.prop("role", "menuitem");

		items.children("a")
			.addClass("ui-corner-all")
			.prop("tabindex", -1)
			// mouseenter doesn't work with event delegation
			.mouseenter(function( event ) {
				self.activate( event, $(this).parent() );
			})
			.mouseleave(function() {
				self.deactivate();
			});
	},

	activate: function( event, item ) {
		this.deactivate();
		if (this.hasScroll()) {
			var offset = item.offset().top - this.element.offset().top,
				scroll = this.element.prop("scrollTop"),
				elementHeight = this.element.height();
			if (offset < 0) {
				this.element.prop("scrollTop", scroll + offset);
			} else if (offset > elementHeight) {
				this.element.prop("scrollTop", scroll + offset - elementHeight + item.height());
			}
		}
		this.active = item.eq(0)
			.children("a")
				.addClass("ui-state-hover")
				.prop("id", "ui-active-menuitem")
			.end();
		this._trigger("focus", event, { item: item });
	},

	deactivate: function() {
		if (!this.active) { return; }

		this.active.children("a")
			.removeClass("ui-state-hover")
			.removeAttr("id");
		this._trigger("blur");
		this.active = null;
	},

	next: function(event) {
		this.move("next", ".ui-menu-item:first", event);
	},

	previous: function(event) {
		this.move("prev", ".ui-menu-item:last", event);
	},

	first: function() {
		return this.active && !this.active.prev().length;
	},

	last: function() {
		return this.active && !this.active.next().length;
	},

	move: function(direction, edge, event) {
		if (!this.active) {
			this.activate(event, this.element.children(edge));
			return;
		}
		var next = this.active[direction + "All"](".ui-menu-item").eq(0);
		if (next.length) {
			this.activate(event, next);
		} else {
			this.activate(event, this.element.children(edge));
		}
	},

	// TODO merge with previousPage
	nextPage: function(event) {
		if (this.hasScroll()) {
			// TODO merge with no-scroll-else
			if (!this.active || this.last()) {
				this.activate(event, this.element.children(":first"));
				return;
			}
			var base = this.active.offset().top,
				height = this.element.height(),
				result = this.element.children("li").filter(function() {
					var close = $(this).offset().top - base - height + $(this).height();
					// TODO improve approximation
					return close < 10 && close > -10;
				});

			// TODO try to catch this earlier when scrollTop indicates the last page anyway
			if (!result.length) {
				result = this.element.children(":last");
			}
			this.activate(event, result);
		} else {
			this.activate(event, this.element.children(!this.active || this.last() ? ":first" : ":last"));
		}
	},

	// TODO merge with nextPage
	previousPage: function(event) {
		if (this.hasScroll()) {
			// TODO merge with no-scroll-else
			if (!this.active || this.first()) {
				this.activate(event, this.element.children(":last"));
				return;
			}

			var base = this.active.offset().top,
				height = this.element.height();
				result = this.element.children("li").filter(function() {
					var close = $(this).offset().top - base + height - $(this).height();
					// TODO improve approximation
					return close < 10 && close > -10;
				});

			// TODO try to catch this earlier when scrollTop indicates the last page anyway
			if (!result.length) {
				result = this.element.children(":first");
			}
			this.activate(event, result);
		} else {
			this.activate(event, this.element.children(!this.active || this.first() ? ":last" : ":first"));
		}
	},

	hasScroll: function() {
		return this.element.height() < this.element.prop("scrollHeight");
	},

	select: function( event ) {
		this._trigger("selected", event, { item: this.active });
	}
});

}(jQuery));

var comboboxArray = new Object();
(function($) {
		$.widget("ui.combobox", {
			options: {
				"class": '',
				id: ''
			},
			_create: function() {
				var self = this;

				var select = this.element.hide();

				var input = $("<input>")
					.insertAfter(select)
					.autocomplete({
						scroll: true,
						source: function(request, response) {
							var matcher = new RegExp(request.term, "i");
							response(select.children("option").map(function() {
								var text = $(this).text();
								if (this.value && (!request.term || matcher.test(text)))
									return {
										id: this.value,
										//label: text.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + $.ui.autocomplete.escapeRegex(request.term) + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>"),
										label: text,
										value: trim(text)
									};
							}));
						},
						delay: 0,
						change: function(event, ui) {
							if (!ui.item) {
								// remove invalid value, as it didn't match anything
								$(this).val("");
								return false;
							}
							select.val(ui.item.id);
							select.change();
							self._trigger("selected", event, {
								item: select.find("[value='" + ui.item.id + "']")
							});

						},
						minLength: 0
					})
					.addClass("ui-corner-left")
					.addClass("combobox_input")
					.click(function() {
						// close if already visible
						if ($(this).autocomplete("widget").is(":visible")) {
							$(this).autocomplete("close");
							return;
						}
						// pass empty string as value to search for, displaying all results
						$(this).autocomplete("search", "");
					});

				if(this.options["class"] != '')
				{
					var tmpArr = this.options["class"].split(" ");
					foreach(tmpArr, function(k,v){
						$(input).addClass(v);
					});

				}
				if(this.options["id"] != '')
				{
					$(input).prop("id", this.options["id"]);
				}
				if(select.find("[selected]")[0] != undefined) {
					$(input).prop("value", trim(select.find("[selected]")[0].text));
				}
				$("<div><img src='/1x1_spacer.gif' /></div>")
				.insertAfter(input)
				.removeClass("ui-corner-all")
				.addClass("ui-corner-right")
				.addClass("combobox_btn")
				.click(function() {
					// close if already visible
					if (input.autocomplete("widget").is(":visible")) {
						input.autocomplete("close");
						return;
					}

					// pass empty string as value to search for, displaying all results
					input.autocomplete("search", "");
					input.focus();
				});
				comboboxArray["cmb_"+select.prop("id")] = input;

			}
		});

	})(jQuery);

//
// // jQuery Alert Dialogs Plugin
//
// Version 1.1
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 14 May 2009
//
// Visit http://abeautifulsite.net/notebook/87 for more information
//
// Usage:
//		jAlert( message, [title, callback] )
//		jConfirm( message, [title, callback] )
//		jQuestion( html, [title, callback] )
//		jPrompt( message, [value, title, callback] )
//
// History:
//
//		1.00 - Released (29 December 2008)
//
//		1.01 - Fixed bug where unbinding would destroy all resize events
//
// License:
//
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC.
//
(function($) {

	$.alerts = {

		// These properties can be read/written by accessing $.alerts.propertyName from your scripts at any time

		timeout: -1,						// timeout, will hide alert after x milliseconds
		last_timeout: null,
		verticalOffset: -75,                // vertical offset of the dialog from center screen, in pixels
		horizontalOffset: 0,                // horizontal offset of the dialog from center screen, in pixels/
		repositionOnResize: true,           // re-centers the dialog on window resize
		overlayOpacity: .71,                // transparency level of overlay
		overlayColor: '#686868',               // base color of overlay
		draggable: true,                    // make the dialogs draggable (requires UI Draggables plugin)
		okButton: '&nbsp;OK&nbsp;',         // text for the OK button
		cancelButton: '&nbsp;Cancel&nbsp;', // text for the Cancel button
		dialogClass: null,                  // if specified, this class will be applied to all dialogs
		iconClass: "",


		// Public methods

		alert: function(message, title, callback) {
			if( title == null ) title = 'Alert';
			var alert = $.alerts._show(title, message, null, 'alert', function(result) {
				if( callback ) callback(result);
			});
			$.alerts.iconClass = "";
			return alert;
		},
		wait_dialog: function(title, message) {
			if( title == null ) title = Soopfw.t("Please wait");
			var alert = $.alerts._show(title, message, null, 'wait_dialog', function() {});
			$.alerts.iconClass = "";
			return alert;
		},

		question: function(html, title, callback) {
			if( title == null ) title = 'Choose';

			$.alerts._show(title, html, null, 'question', function(result,html) {
				if( callback ) callback(result,html);
			});
		},

		confirm: function(message, title, callback) {
			if( title == null ) title = 'Confirm';

			var alert = $.alerts._show(title, message, null, 'confirm', function(result) {
				if( callback ) return callback(result);
				return true
			});
			return alert;
		},

		prompt: function(message, value, title, callback) {
			if( title == null ) title = 'Prompt';
			$.alerts._show(title, message, value, 'prompt', function(result) {
				if( callback ) callback(result);
			});
		},

		// Private methods

		_show: function(title, msg, value, type, callback) {

			if($.alerts.last_timeout != null) {
				clearTimeout($.alerts.last_timeout);
				$.alerts.last_timeout = null;
			}
			$.alerts._hide();
			$.alerts._overlay('show');

			$("BODY").append(
			  '<div id="popup_container">' +
			    '<h1 id="popup_title"><div id="popup_titletxt"></div><div id="popup_cancel_corner"><img id="popup_corner_cancel" src="/1x1_spacer.gif" class="linkedElement ui-icon-soopfw ui-icon-soopfw-cancel"></div></h1>' +
			    '<div id="popup_content">' +
			      '<div id="popup_message"></div>' +
				'</div>' +
			  '</div>');

			if( $.alerts.dialogClass ) $("#popup_container").addClass($.alerts.dialogClass);

			// IE6 Fix
			var pos = ($.browser.msie) ? 'absolute' : 'fixed';

			$("#popup_container").css({
				position: pos,
				zIndex: 99999,
				padding: 0,
				margin: 0
			});


			$("#popup_titletxt").text(title);
			if($.alerts.iconClass != "")
			{
				$("#popup_content").addClass($.alerts.iconClass);
				$("#popup_message").css("padding-left", "0px");
				$("#popup_message").css("text-align", "center");
			}
			else
			{
				$("#popup_content").addClass(type);
			}
			if(type != 'question')
			{
				$("#popup_message").html(msg);
			}
			else
			{
				$("#popup_message").html(msg['msg']);
			}
			$("#popup_message").html( $("#popup_message").html().replace(/\n/g, '<br />') );

			/*$("#popup_container").css({
				minWidth: $("#popup_container").outerWidth(),
				maxWidth: $("#popup_container").outerWidth()
			});*/

			$.alerts._reposition();
			$.alerts._maintainPosition(true);

			//$("#popup_container").expose({closeSpeed: 0, loadSpeed: 'fast', opacity: 0.4});
			$("#popup_corner_cancel").click(function(){ if(type == "alert") { $("#popup_ok").click(); }else { $("#popup_cancel").click();} } );
			//$("#popup_corner_cancel").click(function(){$("#popup_container").expose({api: true}).close();$.alerts._hide();});
			switch( type ) {
				case 'alert':
					$("#popup_message").after('<div id="popup_panel"><input type="button" value="' + $.alerts.okButton + '" id="popup_ok" class="form_button" /></div>');
					$("#popup_ok").click( function() {
						//$("#popup_container").expose({api: true}).close();
						$.alerts._hide();
						if( callback ) callback(true);
					});
					$("#popup_ok").focus().keypress( function(e) {
						if( e.keyCode == 13 || e.keyCode == 27 ) $("#popup_ok").trigger('click');
					});
				break;
				case 'wait_dialog':
					$("#popup_message").after('<div id="popup_panel"><br /></div>');

				break;
				case 'question':

					//msg['html'] = msg['html'].html();
					$("#popup_message").after('<div id="popup_panel"><input type="button" value="' + $.alerts.okButton + '" id="popup_ok"  class="form_button"/> <input type="button" value="' + $.alerts.cancelButton + '" id="popup_cancel"  class="form_button"/></div>');
					msg['html'].css("margin-bottom","10px");

					$("#popup_panel").prepend(msg['html']);
					$("#popup_panel").css("margin-left", '35px');
					$("#popup_ok").click( function() {
						//$("#popup_container").expose({api: true}).close();
						formVariables = {};
						var selector = "name";
						$("#popup_panel").find("input,select,textarea").each(function(x,v)
						{
							if($(v)[0].type == "checkbox")
							{
								if($(v).prop("checked"))
								{
									formVariables[$(v).prop(selector)] = $(v).prop("value");
								}
							}
							else if($(v)[0].type == "radio")
							{
								if($(v).prop("selected") || $(v).prop("checked"))
								{
									formVariables[$(v).prop(selector)] = $(v).prop("value");
								}
							}
							else
							{
								formVariables[$(v).prop(selector)] = $(v).prop("value");
							}
						});
						$.alerts._hide();
						if( callback ) callback(true, formVariables);
					});
					$("#popup_cancel").click( function() {
						//$("#popup_container").expose({api: true}).close();
						formVariables = {};
						var selector = "name";
						$("#popup_panel").find("input,select,textarea").each(function(x,v)
						{
							if($(v)[0].type == "checkbox")
							{
								if($(v).prop("checked"))
								{
									formVariables[$(v).prop(selector)] = $(v).prop("value");
								}
							}
							else if($(v)[0].type == "radio")
							{
								if($(v).prop("selected") || $(v).prop("checked"))
								{
									formVariables[$(v).prop(selector)] = $(v).prop("value");
								}
							}
							else
							{
								formVariables[$(v).prop(selector)] = $(v).prop("value");
							}
						});
						$.alerts._hide();

						if( callback ) callback(false, formVariables);
					});
					$("#popup_ok").focus();
					$("#popup_ok, #popup_cancel").keypress( function(e) {
						//$("#popup_container").expose({api: true}).close();
						if( e.keyCode == 13 ) $("#popup_ok").trigger('click');
						if( e.keyCode == 27 ) $("#popup_cancel").trigger('click');
					});
				break;
				case 'confirm':
					$("#popup_message").after('<div id="popup_panel"><input type="button" value="' + $.alerts.okButton + '" id="popup_ok"  class="form_button"/> <input type="button" value="' + $.alerts.cancelButton + '" id="popup_cancel"  class="form_button"/></div>');
					$("#popup_ok").click( function() {
						//$("#popup_container").expose({api: true}).close();
						$.alerts._hide();

						if( callback ) callback(true);
					});
					$("#popup_cancel").click( function() {
						//$("#popup_container").expose({api: true}).close();
						$.alerts._hide();

						if( callback ) callback(false);
					});
					$("#popup_ok").focus();
					$("#popup_ok, #popup_cancel").keypress( function(e) {
						//$("#popup_container").expose({api: true}).close();
						if( e.keyCode == 13 ) $("#popup_ok").trigger('click');
						if( e.keyCode == 27 ) $("#popup_cancel").trigger('click');
					});
				break;
				case 'prompt':
					$("#popup_message").append('<br /><input type="text" size="30" id="popup_prompt" />').after('<div id="popup_panel"><input type="button" value="' + $.alerts.okButton + '" id="popup_ok" style="margin-right: 10px;" class="form_button"/> <input  style="margin-left: 10px;" type="button" value="' + $.alerts.cancelButton + '" id="popup_cancel" /></div>');
					$("#popup_prompt").width( $("#popup_message").width() );
					$("#popup_ok").click( function() {
						//$("#popup_container").expose({api: true}).close();
						var val = $("#popup_prompt").val();
						$.alerts._hide();
						if( callback ) callback( val );
					});
					$("#popup_cancel").click( function() {
						//$("#popup_container").expose({api: true}).close();
						$.alerts._hide();

						if( callback ) callback( null );
					});
					$("#popup_prompt, #popup_ok, #popup_cancel").keypress( function(e) {
						//$("#popup_container").expose({api: true}).close();
						if( e.keyCode == 13 ) $("#popup_ok").trigger('click');
						if( e.keyCode == 27 ) $("#popup_cancel").trigger('click');
					});
					if( value ) $("#popup_prompt").val(value);
					$("#popup_prompt").focus().select();
				break;
			}
			process_form_buttons();
			// Make draggable
			if( $.alerts.draggable && !$.browser.chrome ) {
				try {
					$("#popup_container").draggable({ handle: $("#popup_title") });
					$("#popup_title").css({ cursor: 'move' });
				} catch(e) { /* requires jQuery UI draggables */ }
			}

			if($.alerts.timeout != -1)
			{
				if($.alerts.last_timeout != null) {
					clearTimeout($.alerts.last_timeout);
					$.alerts.last_timeout = null;
				}
				$.alerts.last_timeout = setTimeout(function() {
					$("#popup_ok").trigger('click');
				}, $.alerts.timeout);

			}
			$.alerts.timeout = -1;
		//	$("#popup_container").expose({api: true}).load();
		},

		_hide: function() {
			$("#popup_container").remove();

			$.alerts._overlay('hide');
			$.alerts._maintainPosition(false);

		},

		_overlay: function(status) {
			switch( status ) {
				case 'show':
					$.alerts._overlay('hide');
					$("BODY").append('<div class="popup_overlay"></div>');
					$(".popup_overlay").css({
						position: 'absolute',
						zIndex: 99997,
						top: '0px',
						left: '0px',
						width: '100%',
						height: $(document).height(),
						"background-color": $.alerts.overlayColor,
						opacity: $.alerts.overlayOpacity
					});
				break;
				case 'hide':
					$(".popup_overlay").remove();
				break;
			}
		},

		_reposition: function() {
			var top = (($(window).height() / 2) - ($("#popup_container").outerHeight() / 2)) + $.alerts.verticalOffset;
			var left = (($(window).width() / 2) - ($("#popup_container").outerWidth() / 2)) + $.alerts.horizontalOffset;
			if( top < 0 ) top = 0;
			if( left < 0 ) left = 0;

			// IE6 fix
			if( $.browser.msie && parseInt($.browser.version) <= 6 ) top = top + $(window).scrollTop();

			$("#popup_container").css({
				top: top + 'px',
				left: left + 'px'
			});
			$(".popup_overlay").height( $(document).height() );
		},

		_maintainPosition: function(status) {
			if( $.alerts.repositionOnResize ) {
				switch(status) {
					case true:
						$(window).bind('resize', $.alerts._reposition);
					break;
					case false:
						$(window).unbind('resize', $.alerts._reposition);
					break;
				}
			}
		}

	}

	// Shortuct functions
	jAlert = function(message, title, callback, timeout) {
		return $.alerts.alert(message, title, callback, timeout);
	}

	// Shortuct functions
	jWaitDialog = function(title, message) {
		$.alerts.iconClass = "wait";
		return $.alerts.wait_dialog(title,message);
	}

	jConfirm = function(message, title, callback) {
		return $.alerts.confirm(message, title, callback);
	};

	jQuestion = function(message, title, callback) {
		$.alerts.question(message, title, callback);
	};

	jPrompt = function(message, value, title, callback) {
		$.alerts.prompt(message, value, title, callback);
	};

})(jQuery);

/*
 * Auto Expanding Text Area (1.2.2)
 * by Chrys Bader (www.chrysbader.com)
 * chrysb@gmail.com
 *
 * Version 1.2.3 update
 * by Richard Vallee
 * richardvallee@gmail.com
 *
 * Special thanks to:
 * Jake Chapa - jake@hybridstudio.com
 * John Resig - jeresig@gmail.com
 *
 * Copyright (c) 2008 Chrys Bader (www.chrysbader.com)
 * Licensed under the GPL (GPL-LICENSE.txt) license.
 *
 *
 * NOTE: This script requires jQuery to work.  Download jQuery at www.jquery.com
 *
 */

(function(jQuery) {

	var self = null;

	jQuery.fn.autogrow = function(o)
	{
		return this.each(function() {
			new jQuery.autogrow(this, o);
		});
	};


    /**
     * The autogrow object.
     *
     * @constructor
     * @name jQuery.autogrow
     * @param Object e The textarea to create the autogrow for.
     * @param Hash o A set of key/value pairs to set as configuration properties.
     * @cat Plugins/autogrow
     */

	jQuery.autogrow = function (e, o)
	{
		this.options		  	= o || {};
		this.dummy			  	= null;
		this.interval	 	  	= null;
		this.line_height	  	= this.options.lineHeight || parseInt(jQuery(e).css('line-height'));
		this.min_height		  	= this.options.minHeight || parseInt(jQuery(e).css('min-height'));
		this.max_height		  	= this.options.maxHeight || parseInt(jQuery(e).css('max-height'));;
		this.textarea		  	= jQuery(e);
		this.expand_tolerance	= (!isNaN(this.options.expandTolerance) && this.options.expandTolerance > 0) ? this.options.expandTolerance : 4;

		if(isNaN(this.line_height))
		  this.line_height = 0;

		// Only one textarea activated at a time, the one being used
		this.init();
	};

	jQuery.autogrow.fn = jQuery.autogrow.prototype = {
    	autogrow: '1.2.3'
  	};

 	jQuery.autogrow.fn.extend = jQuery.autogrow.extend = jQuery.extend;

	jQuery.autogrow.fn.extend({
		init: function() {
			var self = this;
			this.textarea.css({overflow: 'hidden', display: 'block'});
			this.textarea.bind('focus', function() { self.startExpand() } ).bind('blur', function() { self.stopExpand() });
			this.checkExpand();
		},

		startExpand: function() {
		  var self = this;
			this.interval = window.setInterval(function() {self.checkExpand()}, 400);
		},

		stopExpand: function() {
			clearInterval(this.interval);
		},

		checkExpand: function() {
			if (this.dummy == null) {
				this.dummy = jQuery('<div></div>');
				this.dummy.css({
					'font-size'  : this.textarea.css('font-size'),
					'font-family': this.textarea.css('font-family'),
					'width'      : this.textarea.css('width'),
					'padding'    : this.textarea.css('padding'),
					'line-height': this.line_height + 'px',
					'overflow-x' : 'hidden',
					'position'   : 'absolute',
					'top'        : 0,
					'left'		 : -9999
					}).appendTo('body');
			} else {
				// If the dummy was already created, show it as it is hidden after expansion
				this.dummy.show();
			}

			// Strip HTML tags
			var html = this.textarea.val().replace(/(<|>)/g, '');

			// IE is different, as per usual
			if (jQuery.browser.msie) {
				html = html.replace(/\n/g, '<BR/>new');
			}
			else {
				html = html.replace(/\n/g, '<br/>new');
			}

			if (this.dummy.html() != html) {
				this.dummy.html(html);
				if (this.max_height > 0 && (this.dummy.height() + (this.expand_tolerance*this.line_height) > this.max_height)) {
					this.textarea.css('overflow-y', 'auto');
					if (this.textarea.height() < this.max_height) {
						this.textarea.animate({height: (this.max_height + (this.expand_tolerance*this.line_height)) + 'px'}, 100);
					}
				}
				else {
					this.textarea.css('overflow-y', 'hidden');
					if (this.textarea.height() < this.dummy.height() + (this.expand_tolerance*this.line_height) || (this.dummy.height() < this.textarea.height())) {
						if (this.dummy.height() < this.min_height) {
							this.textarea.animate({height: (this.min_height + (this.expand_tolerance*this.line_height)) + 'px'}, 100);
						} else {
							this.textarea.animate({height: (this.dummy.height() + (this.expand_tolerance*this.line_height)) + 'px'}, 100);
						}
					}
				}
			}

			// Hide the dummy, as otherwise it overflows the body when the content is long
			this.dummy.hide();
		}

	 });
})(jQuery);

/*
 * jQuery delegate plug-in v1.0
 *
 * Copyright (c) 2007 Jörn Zaefferer
 *
 * $Id: jquery.delegate.js 4786 2008-02-19 20:02:34Z joern.zaefferer $
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

// provides cross-browser focusin and focusout events
// IE has native support, in other browsers, use event caputuring (neither bubbles)

// provides delegate(type: String, delegate: Selector, handler: Callback) plugin for easier event delegation
// handler is only called when $(event.target).is(delegate), in the scope of the jQuery-object for event.target

// provides triggerEvent(type: String, target: Element) to trigger delegated events
;(function($) {
	$.each({
		focus: 'focusin',
		blur: 'focusout'
	}, function( original, fix ){
		$.event.special[fix] = {
			setup:function() {
				if ( $.browser.msie ) return false;
				this.addEventListener( original, $.event.special[fix].handler, true );
			},
			teardown:function() {
				if ( $.browser.msie ) return false;
				this.removeEventListener( original,
				$.event.special[fix].handler, true );
			},
			handler: function(e) {
				arguments[0] = $.event.fix(e);
				arguments[0].type = fix;
				return $.event.handle.apply(this, arguments);
			}
		};
	});

	$.extend($.fn, {
		delegate: function(type, delegate, handler) {
			return this.bind(type, function(event) {
				var target = $(event.target);
				if (target.is(delegate)) {
					return handler.apply(target, arguments);
				}
			});
		},
		triggerEvent: function(type, target) {
			return this.triggerHandler(type, [jQuery.event.fix({ type: type, target: target })]);
		}
	})
})(jQuery);

/*
 * Jeditable - jQuery in place edit plugin
 *
 * Copyright (c) 2006-2009 Mika Tuupola, Dylan Verheul
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/jeditable
 *
 * Based on editable by Dylan Verheul <dylan_at_dyve.net>:
 *    http://www.dyve.net/jquery/?editable
 *
 */

/**
  * Version 1.7.1
  *
  * ** means there is basic unit tests for this parameter.
  *
  * @name  Jeditable
  * @type  jQuery
  * @param String  target             (POST) URL or function to send edited content to **
  * @param Hash    options            additional options
  * @param String  options[method]    method to use to send edited content (POST or PUT) **
  * @param Function options[callback] Function to run after submitting edited content **
  * @param String  options[name]      POST parameter name of edited content
  * @param String  options[id]        POST parameter name of edited div id
  * @param Hash    options[submitdata] Extra parameters to send when submitting edited content.
  * @param String  options[type]      text, textarea or select (or any 3rd party input type) **
  * @param Integer options[rows]      number of rows if using textarea **
  * @param Integer options[cols]      number of columns if using textarea **
  * @param Mixed   options[height]    'auto', 'none' or height in pixels **
  * @param Mixed   options[width]     'auto', 'none' or width in pixels **
  * @param String  options[loadurl]   URL to fetch input content before editing **
  * @param String  options[loadtype]  Request type for load url. Should be GET or POST.
  * @param String  options[loadtext]  Text to display while loading external content.
  * @param Mixed   options[loaddata]  Extra parameters to pass when fetching content before editing.
  * @param Mixed   options[data]      Or content given as paramameter. String or function.**
  * @param String  options[indicator] indicator html to show when saving
  * @param String  options[tooltip]   optional tooltip text via title attribute **
  * @param String  options[event]     jQuery event such as 'click' of 'dblclick' **
  * @param String  options[submit]    submit button value, empty means no button **
  * @param String  options[cancel]    cancel button value, empty means no button **
  * @param String  options[cssclass]  CSS class to apply to input form. 'inherit' to copy from parent. **
  * @param String  options[style]     Style to apply to input form 'inherit' to copy from parent. **
  * @param String  options[select]    true or false, when true text is highlighted ??
  * @param String  options[placeholder] Placeholder text or html to insert when element is empty. **
  * @param String  options[onblur]    'cancel', 'submit', 'ignore' or function ??
  *
  * @param Function options[onsubmit] function(settings, original) { ... } called before submit
  * @param Function options[onreset]  function(settings, original) { ... } called before reset
  * @param Function options[onerror]  function(settings, original, xhr) { ... } called on error
  *
  * @param Hash    options[ajaxoptions]  jQuery Ajax options. See docs.jquery.com.
  *
  */

(function($) {

    $.fn.editable = function(target, options) {

        if ('disable' == target) {
            $(this).data('disabled.editable', true);
            return;
        }
        if ('enable' == target) {
            $(this).data('disabled.editable', false);
            return;
        }
        if ('destroy' == target) {
            $(this)
                .unbind($(this).data('event.editable'))
                .removeData('disabled.editable')
                .removeData('event.editable');
            return;
        }

        var settings = $.extend({}, $.fn.editable.defaults, {target:target}, options);

        /* setup some functions */
        var plugin   = $.editable.types[settings.type].plugin || function() { };
        var submit   = $.editable.types[settings.type].submit || function() { };
        var buttons  = $.editable.types[settings.type].buttons
                    || $.editable.types['defaults'].buttons;
        var content  = $.editable.types[settings.type].content
                    || $.editable.types['defaults'].content;
        var element  = $.editable.types[settings.type].element
                    || $.editable.types['defaults'].element;
        var reset    = $.editable.types[settings.type].reset
                    || $.editable.types['defaults'].reset;
        var callback = settings.callback || function() { };
        var onedit   = settings.onedit   || function() { };
        var onsubmit = settings.onsubmit || function() { };
        var onreset  = settings.onreset  || function() { };
        var onerror  = settings.onerror  || reset;

        /* show tooltip */
        if (settings.tooltip) {
            $(this).prop('title', settings.tooltip);
        }

        settings.autowidth  = 'auto' == settings.width;
        settings.autoheight = 'auto' == settings.height;

        return this.each(function() {

            /* save this to self because this changes when scope changes */
            var self = this;

            /* inlined block elements lose their width and height after first edit */
            /* save them for later use as workaround */
            var savedwidth  = $(self).width();
            var savedheight = $(self).height();

            /* save so it can be later used by $.editable('destroy') */
            $(this).data('event.editable', settings.event);

            /* if element is empty add something clickable (if requested) */
            if (!$.trim($(this).html())) {
                $(this).html(settings.placeholder);
            }

            $(this).bind(settings.event, function(e) {

                /* abort if disabled for this element */
                if (true === $(this).data('disabled.editable')) {
                    return;
                }

                /* prevent throwing an exeption if edit field is clicked again */
                if (self.editing) {
                    return;
                }

                /* abort if onedit hook returns false */
                if (false === onedit.apply(this, [settings, self])) {
                   return;
                }

                /* prevent default action and bubbling */
                e.preventDefault();
                e.stopPropagation();

                /* remove tooltip */
                if (settings.tooltip) {
                    $(self).removeAttr('title');
                }

                /* figure out how wide and tall we are, saved width and height */
                /* are workaround for http://dev.jquery.com/ticket/2190 */
                if (0 == $(self).width()) {
                    //$(self).css('visibility', 'hidden');
                    settings.width  = savedwidth;
                    settings.height = savedheight;
                } else {
                    if (settings.width != 'none') {
                        settings.width =
                            settings.autowidth ? $(self).width()  : settings.width;
                    }
                    if (settings.height != 'none') {
                        settings.height =
                            settings.autoheight ? $(self).height() : settings.height;
                    }
                }
                //$(this).css('visibility', '');

                /* remove placeholder text, replace is here because of IE */
                if ($(this).html().toLowerCase().replace(/(;|")/g, '') ==
                    settings.placeholder.toLowerCase().replace(/(;|")/g, '')) {
                        $(this).html('');
                }

                self.editing    = true;
                self.revert     = $(self).html();
                $(self).html('');

                /* create the form object */
                var form = $('<form />');

                /* apply css or style or both */
                if (settings.cssclass) {
                    if ('inherit' == settings.cssclass) {
                        form.prop('class', $(self).prop('class'));
                    } else {
                        form.prop('class', settings.cssclass);
                    }
                }

                if (settings.style) {
                    if ('inherit' == settings.style) {
                        form.prop('style', $(self).prop('style'));
                        /* IE needs the second line or display wont be inherited */
                        form.css('display', $(self).css('display'));
                    } else {
                        form.prop('style', settings.style);
                    }
                }

                /* add main input element to form and store it in input */
                var input = element.apply(form, [settings, self]);

                /* set input content via POST, GET, given data or existing value */
                var input_content;

                if (settings.loadurl) {
                    var t = setTimeout(function() {
                        input.disabled = true;
                        content.apply(form, [settings.loadtext, settings, self]);
                    }, 100);

                    var loaddata = {};
                    loaddata[settings.id] = self.id;
                    if ($.isFunction(settings.loaddata)) {
                        $.extend(loaddata, settings.loaddata.apply(self, [self.revert, settings]));
                    } else {
                        $.extend(loaddata, settings.loaddata);
                    }
                    $.ajax({
                       type : settings.loadtype,
                       url  : settings.loadurl,
                       data : loaddata,
                       async : false,
                       success: function(result) {
                          window.clearTimeout(t);
                          input_content = result;
                          input.disabled = false;
                       }
                    });
                } else if (settings.data) {
                    input_content = settings.data;
                    if ($.isFunction(settings.data)) {
                        input_content = settings.data.apply(self, [self.revert, settings]);
                    }
                } else {
                    input_content = self.revert;
                }
                content.apply(form, [input_content, settings, self]);

                input.prop('name', settings.name);

                /* add buttons to the form */
                buttons.apply(form, [settings, self]);

                /* add created form to self */
                $(self).append(form);

                /* attach 3rd party plugin if requested */
                plugin.apply(form, [settings, self]);

                /* focus to first visible form element */
                $(':input:visible:enabled:first', form).focus();

                /* highlight input contents when requested */
                if (settings.select)
				{
					if (settings.selectOnlyFirst)
					{
						settings.select = false;
					}
                    input.select();
                }
				/* highlight input contents when requested, but only at the first time*/
                if (settings.selectOnlyFirst)
				{
					if (settings.selectOnlyFirst)
					{
						settings.selectOnlyFirst = false;
					}
                    input.select();
                }

                /* discard changes if pressing esc */
                input.keydown(function(e) {
                    if (e.keyCode == 27) {
                        e.preventDefault();
                        //self.reset();
                        reset.apply(form, [settings, self]);
                    }
                });

                /* discard, submit or nothing with changes when clicking outside */
                /* do nothing is usable when navigating with tab */
                var t;
                if ('cancel' == settings.onblur) {
                    input.blur(function(e) {
                        /* prevent canceling if submit was clicked */
                        t = setTimeout(function() {
                            reset.apply(form, [settings, self]);
                        }, 500);
                    });
                } else if ('submit' == settings.onblur) {
                    input.blur(function(e) {
                        /* prevent double submit if submit was clicked */
                        t = setTimeout(function() {
                            form.submit();
                        }, 200);
                    });
                } else if ($.isFunction(settings.onblur)) {
                    input.blur(function(e) {
                        settings.onblur.apply(self, [input.val(), settings]);
                    });
                } else {
                    input.blur(function(e) {
                      /* TODO: maybe something here */
                    });
                }

                form.submit(function(e) {

                    if (t) {
                        clearTimeout(t);
                    }

                    /* do no submit */
                    e.preventDefault();

                    /* call before submit hook. */
                    /* if it returns false abort submitting */
                    if (false !== onsubmit.apply(form, [settings, self])) {
                        /* custom inputs call before submit hook. */
                        /* if it returns false abort submitting */
                        if (false !== submit.apply(form, [settings, self])) {

                          /* check if given target is function */
                          if ($.isFunction(settings.target)) {
                              var str = settings.target.apply(self, [input.val(), settings]);
                              $(self).html(str);
                              self.editing = false;
                              callback.apply(self, [self.innerHTML, settings]);
                              /* TODO: this is not dry */
                              if (!$.trim($(self).html())) {
                                  $(self).html(settings.placeholder);
                              }
                          } else {
                              /* add edited content and id of edited element to POST */
                              var submitdata = {};
                              submitdata[settings.name] = input.val();
                              submitdata[settings.id] = self.id;
                              /* add extra data to be POST:ed */
                              if ($.isFunction(settings.submitdata)) {
                                  $.extend(submitdata, settings.submitdata.apply(self, [self.revert, settings]));
                              } else {
                                  $.extend(submitdata, settings.submitdata);
                              }

                              /* quick and dirty PUT support */
                              if ('PUT' == settings.method) {
                                  submitdata['_method'] = 'put';
                              }

                              /* show the saving indicator */
                              $(self).html(settings.indicator);

                              /* defaults for ajaxoptions */
                              var ajaxoptions = {
                                  type    : 'POST',
                                  data    : submitdata,
                                  dataType: 'html',
                                  url     : settings.target,
                                  success : function(result, status) {
                                      if (ajaxoptions.dataType == 'html') {
                                        $(self).html(result);
                                      }
                                      self.editing = false;
                                      callback.apply(self, [result, settings]);
                                      if (!$.trim($(self).html())) {
                                          $(self).html(settings.placeholder);
                                      }
                                  },
                                  error   : function(xhr, status, error) {
                                      onerror.apply(form, [settings, self, xhr]);
                                  }
                              };

                              /* override with what is given in settings.ajaxoptions */
                              $.extend(ajaxoptions, settings.ajaxoptions);
                              $.ajax(ajaxoptions);

                            }
                        }
                    }

                    /* show tooltip again */
                    $(self).prop('title', settings.tooltip);

                    return false;
                });
            });

            /* privileged methods */
            this.reset = function(form) {
                /* prevent calling reset twice when blurring */
                if (this.editing) {
                    /* before reset hook, if it returns false abort reseting */
                    if (false !== onreset.apply(form, [settings, self])) {
                        $(self).html(self.revert);
                        self.editing   = false;
                        if (!$.trim($(self).html())) {
                            $(self).html(settings.placeholder);
                        }
                        /* show tooltip again */
                        if (settings.tooltip) {
                            $(self).prop('title', settings.tooltip);
                        }
                    }
                }
            };
        });

    };


    $.editable = {
        types: {
            defaults: {
                element : function(settings, original) {
                    var input = $('<input type="hidden"></input>');
                    $(this).append(input);
                    return(input);
                },
                content : function(string, settings, original) {
                    $(':input:first', this).val(string);
                },
                reset : function(settings, original) {
                  original.reset(this);
                },
                buttons : function(settings, original) {
                    var form = this;
                    if (settings.submit) {
                        /* if given html string use that */
                        if (settings.submit.match(/>$/)) {
                            var submit = $(settings.submit).click(function() {
                                if (submit.prop("type") != "submit") {
                                    form.submit();
                                }
                            });
                        /* otherwise use button with given string as text */
                        } else {
                            var submit = $('<button type="submit" />');
                            submit.html(settings.submit);
                        }
                        $(this).append(submit);
                    }
                    if (settings.cancel) {
                        /* if given html string use that */
                        if (settings.cancel.match(/>$/)) {
                            var cancel = $(settings.cancel);
                        /* otherwise use button with given string as text */
                        } else {
                            var cancel = $('<button type="cancel" />');
                            cancel.html(settings.cancel);
                        }
                        $(this).append(cancel);

                        $(cancel).click(function(event) {
                            //original.reset();
                            if ($.isFunction($.editable.types[settings.type].reset)) {
                                var reset = $.editable.types[settings.type].reset;
                            } else {
                                var reset = $.editable.types['defaults'].reset;
                            }
                            reset.apply(form, [settings, original]);
                            return false;
                        });
                    }
                }
            },
            text: {
                element : function(settings, original) {
                    var input = $('<input />');
                    if (settings.width  != 'none') { input.width(settings.width);  }
                    if (settings.height != 'none') { input.height(settings.height); }
                    /* https://bugzilla.mozilla.org/show_bug.cgi?id=236791 */
                    //input[0].setAttribute('autocomplete','off');
                    input.prop('autocomplete','off');
                    $(this).append(input);
                    return(input);
                }
            },
            textarea: {
                element : function(settings, original) {
                    var textarea = $('<textarea />');
                    if (settings.rows) {
                        textarea.prop('rows', settings.rows);
                    } else if (settings.height != "none") {
                        textarea.height(settings.height);
                    }
                    if (settings.cols) {
                        textarea.prop('cols', settings.cols);
                    } else if (settings.width != "none") {
                        textarea.width(settings.width);
                    }
                    $(this).append(textarea);
                    return(textarea);
                }
            },
            select: {
               element : function(settings, original) {
                    var select = $('<select />');
                    $(this).append(select);
                    return(select);
                },
                content : function(data, settings, original) {
                    /* If it is string assume it is json. */
                    if (String == data.constructor) {
                        eval ('var json = ' + data);
                    } else {
                    /* Otherwise assume it is a hash already. */
                        var json = data;
                    }
                    for (var key in json) {
                        if (!json.hasOwnProperty(key)) {
                            continue;
                        }
                        if ('selected' == key) {
                            continue;
                        }
                        var option = $('<option />').val(key).append(json[key]);
                        $('select', this).append(option);
                    }
                    /* Loop option again to set selected. IE needed this... */
                    $('select', this).children().each(function() {
                        if ($(this).val() == json['selected'] ||
                            $(this).text() == $.trim(original.revert)) {
                                $(this).prop('selected', 'selected');
                        }
                    });
                }
            }
        },

        /* Add new input type */
        addInputType: function(name, input) {
            $.editable.types[name] = input;
        }
    };

    // publicly accessible defaults
    $.fn.editable.defaults = {
        name       : 'value',
        id         : 'id',
        type       : 'text',
        width      : 'auto',
        height     : 'auto',
        event      : 'click.editable',
        onblur     : 'cancel',
        loadtype   : 'GET',
        loadtext   : 'Loading...',
        placeholder: 'Click to edit',
        loaddata   : {},
        submitdata : {},
        ajaxoptions: {}
    };

})(jQuery);

/*
* jQuery.splitter.js - animated splitter plugin
*
* version 1.1 (2010/11/18)
*
* Dual licensed under the MIT and GPL licenses:
*   http://www.opensource.org/licenses/mit-license.php
*   http://www.gnu.org/licenses/gpl.html
*/

/**
* jQuery.splitter() plugin implements a two-pane resizable animated window, using existing DIV elements for layout.
* For more details and demo visit: http://krikus.com/js/splitter
*
* @example $("#splitterContainer").splitter({splitVertical:true,A:$('#leftPane'),B:$('#rightPane'),closeableto:0});
* @desc Create a vertical splitter with toggle button
*
* @example $("#splitterContainer").splitter({minAsize:100,maxAsize:300,splitVertical:true,A:$('#leftPane'),B:$('#rightPane'),slave:$("#rightSplitterContainer"),closeableto:0});
* @desc Create a vertical splitter with toggle button, with minimum and maximum width for plane A and bind resize event to the slave element
*
* @name splitter
* @type jQuery
* @param Object options Options for the splitter ( required)
* @cat Plugins/Splitter
* @return jQuery
* @author Kristaps Kukurs (contact@krikus.com)
*/



;(function($){

	$.fn.splitter = function(args){
		args = args || {};
		return this.each(function() {
			var _ghost;		//splitbar  ghosted element
			var splitPos;	 // current splitting position
			var _splitPos;	 // saved splitting position
			var _initPos;	//initial mouse position
			var _ismovingNow=false;	// animaton state flag

			// Default opts
			var direction = (args.splitHorizontal? 'h':'v');
			var opts = $.extend({
			minAsize:0, //minimum width/height in PX of the first (A) div.
			maxAsize:0, //maximum width/height  in PX of the first (A) div.
			minBsize:0, //minimum width/height in PX of the second (B) div.
			maxBsize:0, //maximum width/height  in PX of the second (B) div.
			ghostClass: 'working',// class name for _ghosted splitter and hovered button
			invertClass: 'invert',//class name for invert splitter button
			animSpeed: 250 //animation speed in ms
			},{
			v:{ // Vertical
				moving:"left",sizing: "width", eventPos: "pageX",splitbarClass:"splitbarV",buttonClass: "splitbuttonV", cursor: "e-resize"
			},
			h: { // Horizontal
				moving:"top",sizing: "height", eventPos: "pageY",splitbarClass:"splitbarH",buttonClass: "splitbuttonH",  cursor: "n-resize"
			}
			}[direction], args);

			//setup elements
			var splitter = $(this);
			var mychilds =splitter.children(); //$(">*", splitter[0]);
			var A = args.A;	// left/top frame
			var B = args.B;// right/bottom frame
			var slave=args.slave;//optional, elemt forced to receive resize event

			//Create splitbar
			var C=$('<div><span></span></div>');
			A.after(C);
			C.prop({"class": opts.splitbarClass,unselectable:"on"}).css({"cursor":opts.cursor,"user-select": "none", "-webkit-user-select": "none","-khtml-user-select": "none", "-moz-user-select": "none"})
			.bind("mousedown", startDrag);

			if(opts.closeableto!=undefined){
				var Bt=$('<div></div>').css("cursor",'pointer');
				C.append(Bt);
				Bt.prop({"class": opts.buttonClass, unselectable: "on"});
				Bt.hover(function(){$(this).addClass(opts.ghostClass);},function(){$(this).removeClass(opts.ghostClass);});
				Bt.mousedown(function(e){if(e.target != this)return;Bt.toggleClass(opts.invertClass).hide();splitTo((splitPos==opts.closeableto)?_splitPos:opts.closeableto,true);return false;});
			}
//reset size to default.
var perc=(((C.position()[opts.moving]-splitter.offset()[opts.moving])/splitter[opts.sizing]())*100).toFixed(1);
splitTo(perc,false,true);
// resize  event handlers;
splitter.bind("resize",function(e, size){if(e.target!=this)return;splitTo(splitPos,false,true);});
$(window).bind("resize",function(){splitTo(splitPos,false,true);});

//C.onmousedown=startDrag
			function startDrag(e) {
			if(e.target != this)return;
		 _ghost = _ghost || C.clone(false).insertAfter(A);
				splitter._initPos=C.position();
				splitter._initPos[opts.moving]-=C[opts.sizing]();
_ghost.addClass(opts.ghostClass).css('position','absolute').css('z-index','250').css("-webkit-user-select", "none").width(C.width()).height(C.height()).css(opts.moving,splitter._initPos[opts.moving]);
mychilds.css("-webkit-user-select", "none");	// Safari selects A/B text on a move
A._posSplit = e[opts.eventPos];

$(document).bind("mousemove", performDrag).bind("mouseup", endDrag);
			}
//document.onmousemove=performDrag
			function performDrag(e) {
			if (!_ghost||!A) return;
				var incr = e[opts.eventPos]-A._posSplit;
				_ghost.css(opts.moving, splitter._initPos[opts.moving]+incr);
			}
//C.onmouseup=endDrag
			function endDrag(e){
				var p=_ghost.position();
				_ghost.remove(); _ghost = null;
				mychilds.css("-webkit-user-select", "text");// let Safari select text again
				$(document).unbind("mousemove", performDrag).unbind("mouseup", endDrag);
				var perc=(((p[opts.moving]-splitter.offset()[opts.moving])/splitter[opts.sizing]())*100).toFixed(1);
				splitTo(perc,(splitter._initPos[opts.moving]>p[opts.moving]),false);
				splitter._initPos=0;
			}
//Perform actual splitting and animate it;
	function splitTo(perc,reversedorder,fast) {
if(_ismovingNow||perc==undefined)return;//generally MSIE problem
_ismovingNow=true;
if(splitPos&&splitPos>10&&splitPos<90)//do not save accidental events
		_splitPos=splitPos;
splitPos=perc;

var barsize=C[opts.sizing]()+(2*parseInt(C.css('border-'+opts.moving+'-width')));//+ border. cehap&dirty
var splitsize=splitter[opts.sizing]();
if(opts.closeableto!=perc){
var percpx=Math.max(parseInt((splitsize/100)*perc),opts.minAsize);
if(opts.maxAsize)percpx=Math.min(percpx,opts.maxAsize);

}else{
var percpx=parseInt((splitsize/100)*perc,0);
}
if(opts.maxBsize){
if((splitsize-percpx)>opts.maxBsize)
percpx=splitsize-opts.maxBsize;
}
if(opts.minBsize){
if((splitsize-percpx)<opts.minBsize)
percpx=splitsize-opts.minBsize;
}
var sizeA=Math.max(0,(percpx-barsize));
var sizeB=Math.max(0,(splitsize-percpx));
splitsize=(splitsize-barsize);

//A.prop('title','- '+sizeA);  B.prop('title','- '+sizeB);
 if(fast){
					A.show().css(opts.sizing,sizeA+'px');
					B.show().css(opts.sizing,sizeB+'px');
					Bt.show();
					if (!$.browser.msie ){
					mychilds.trigger("resize");if(slave)slave.trigger("resize");
					}
				_ismovingNow=false;
				if(opts.onResize){
					opts.onResize(sizeA, sizeB);
				}
				return true;
				}
	if(reversedorder){//reduces flickering if total percentage becomes more  than 100 (possible while animating)
					var anob={};
					anob[opts.sizing]=sizeA+'px';
					A.show().animate(anob,opts.animSpeed,function(){Bt.fadeIn('fast');if($(this)[opts.sizing]()<2){this.style.display='none';B.stop(true,true);B[opts.sizing](splitsize+'px');}});
					var anob2={};
					anob2[opts.sizing]=sizeB+'px';
					B.show().animate(anob2,opts.animSpeed,function(){Bt.fadeIn('fast');if($(this)[opts.sizing]()<2){this.style.display='none';A.stop(true,true);A[opts.sizing](splitsize+'px')}});
	}else{
					var anob={};
					anob[opts.sizing]=sizeB+'px';
					B.show().animate(anob,opts.animSpeed,function(){Bt.fadeIn('fast');if($(this)[opts.sizing]()<2){this.style.display='none';A.stop(true,true);A[opts.sizing](splitsize+'px')}});
					var anob={};
					anob[opts.sizing]=sizeA+'px';
					A.show().animate(anob,opts.animSpeed,function(){Bt.fadeIn('fast');if($(this)[opts.sizing]()<2){this.style.display='none';B.stop(true,true);B[opts.sizing](splitsize+'px');}});
}
//trigger resize evt
splitter.queue(function(){
setTimeout(function(){
splitter.dequeue();
_ismovingNow=false;
if(opts.onResize){
	opts.onResize(sizeA, sizeB);
}
mychilds.trigger("resize");if(slave)slave.trigger("resize");
}, opts.animSpeed+5);
});

 }//end splitTo()





});//end each
	};//end splitter

})(jQuery);

function strtotime (str, now) {
    // Convert string representation of date and time to a timestamp
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/strtotime    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   improved by: Brett Zamir (http://brett-zamir.me)    // +   bugfixed by: Wagner B. Soares
    // +   bugfixed by: Artur Tchernychev
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');    // *     returns 4: 1241418600
    var i, match, s, strTmp = '',
        parse = '';

    strTmp = str;    strTmp = strTmp.replace(/\s{2,}|^\s|\s$/g, ' '); // unecessary spaces
    strTmp = strTmp.replace(/[\t\r\n]/g, ''); // unecessary chars
    if (strTmp == 'now') {
        return (new Date()).getTime() / 1000; // Return seconds, not milli-seconds
    } else if (!isNaN(parse = Date.parse(strTmp))) {        return (parse / 1000);
    } else if (now) {
        now = new Date(now * 1000); // Accept PHP-style seconds
    } else {
        now = new Date();    }

    strTmp = strTmp.toLowerCase();

    var __is = {        day: {
            'sun': 0,
            'mon': 1,
            'tue': 2,
            'wed': 3,            'thu': 4,
            'fri': 5,
            'sat': 6
        },
        mon: {            'jan': 0,
            'feb': 1,
            'mar': 2,
            'apr': 3,
            'may': 4,            'jun': 5,
            'jul': 6,
            'aug': 7,
            'sep': 8,
            'oct': 9,            'nov': 10,
            'dec': 11
        }
    };
     var process = function (m) {
        var ago = (m[2] && m[2] == 'ago');
        var num = (num = m[0] == 'last' ? -1 : 1) * (ago ? -1 : 1);

        switch (m[0]) {        case 'last':
        case 'next':
            switch (m[1].substring(0, 3)) {
            case 'yea':
                now.setFullYear(now.getFullYear() + num);                break;
            case 'mon':
                now.setMonth(now.getMonth() + num);
                break;
            case 'wee':                now.setDate(now.getDate() + (num * 7));
                break;
            case 'day':
                now.setDate(now.getDate() + num);
                break;            case 'hou':
                now.setHours(now.getHours() + num);
                break;
            case 'min':
                now.setMinutes(now.getMinutes() + num);                break;
            case 'sec':
                now.setSeconds(now.getSeconds() + num);
                break;
            default:                var day;
                if (typeof(day = __is.day[m[1].substring(0, 3)]) != 'undefined') {
                    var diff = day - now.getDay();
                    if (diff == 0) {
                        diff = 7 * num;                    } else if (diff > 0) {
                        if (m[0] == 'last') {
                            diff -= 7;
                        }
                    } else {                        if (m[0] == 'next') {
                            diff += 7;
                        }
                    }
                    now.setDate(now.getDate() + diff);                }
            }
            break;

        default:            if (/\d+/.test(m[0])) {
                num *= parseInt(m[0], 10);

                switch (m[1].substring(0, 3)) {
                case 'yea':                    now.setFullYear(now.getFullYear() + num);
                    break;
                case 'mon':
                    now.setMonth(now.getMonth() + num);
                    break;                case 'wee':
                    now.setDate(now.getDate() + (num * 7));
                    break;
                case 'day':
                    now.setDate(now.getDate() + num);                    break;
                case 'hou':
                    now.setHours(now.getHours() + num);
                    break;
                case 'min':                    now.setMinutes(now.getMinutes() + num);
                    break;
                case 'sec':
                    now.setSeconds(now.getSeconds() + num);
                    break;                }
            } else {
                return false;
            }
            break;        }
        return true;
    };

    match = strTmp.match(/^(\d{2,4}-\d{2}-\d{2})(?:\s(\d{1,2}:\d{2}(:\d{2})?)?(?:\.(\d+))?)?$/);    if (match != null) {
        if (!match[2]) {
            match[2] = '00:00:00';
        } else if (!match[3]) {
            match[2] += ':00';        }

        s = match[1].split(/-/g);

        for (i in __is.mon) {            if (__is.mon[i] == s[1] - 1) {
                s[1] = i;
            }
        }
        s[0] = parseInt(s[0], 10);
        s[0] = (s[0] >= 0 && s[0] <= 69) ? '20' + (s[0] < 10 ? '0' + s[0] : s[0] + '') : (s[0] >= 70 && s[0] <= 99) ? '19' + s[0] : s[0] + '';
        return parseInt(this.strtotime(s[2] + ' ' + s[1] + ' ' + s[0] + ' ' + match[2]) + (match[4] ? match[4] / 1000 : ''), 10);
    }
     var regex = '([+-]?\\d+\\s' + '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?' + '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday' + '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday)' + '|(last|next)\\s' + '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?' + '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday' + '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday))' + '(\\sago)?';

    match = strTmp.match(new RegExp(regex, 'gi')); // Brett: seems should be case insensitive per docs, so added 'i'
    if (match == null) {
        return false;    }

    for (i = 0; i < match.length; i++) {
        if (!process(match[i].split(' '))) {
            return false;        }
    }

    return (now.getTime() / 1000);
}

function in_array (needle, haystack, argStrict) {
    // Checks if the given value exists in the array
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/in_array    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: vlado houba
    // +   input by: Billy
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);    // *     returns 1: true
    // *     example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
    // *     returns 2: false
    // *     example 3: in_array(1, ['1', '2', '3']);
    // *     returns 3: true    // *     example 3: in_array(1, ['1', '2', '3'], false);
    // *     returns 3: true
    // *     example 4: in_array(1, ['1', '2', '3'], true);
    // *     returns 4: false
    var key = '',        strict = !! argStrict;

    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {                return true;
            }
        }
    } else {
        for (key in haystack) {            if (haystack[key] == needle) {
                return true;
            }
        }
    }
    return false;
}