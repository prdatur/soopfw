var used_uuids = {};
var Soopfw = {

}
var soopfw_ajax_queue = {};
$.extend(Soopfw, {
	behaviors: [],
	prio_behaviors: [],
	tab_behaviors: {},
	late_behaviors: [],
	current_tab: '',
	already_loaded_files: {},
	internal: {
		progressbars: {}
	},

	/**
	 * Holds all bindings for a specified tab.
	 */
	tab_bindings: {},
	
	/**
	 * Add a tab 
	 * 
	 * @param string
	 *   The tab id.
	 */
	add_tab: function(tab) {
		if (Soopfw.tab_bindings[tab] === undefined) {
			Soopfw.tab_bindings[tab] = {};
		}
		Soopfw.current_tab = tab;
	},
	
	/**
	 * Add tab behaviors, it will use the current tab as the id.
	 */
	add_tab_behaviors: function(func) {
		if (empty(Soopfw.current_tab)) {
			// We need to add the behavior to a normal one because we have no current tab active (maybe called directly without tab).
			Soopfw.behaviors[Soopfw.uuid()] = func;			
			return;
		}
		if (Soopfw.tab_behaviors[Soopfw.current_tab] === undefined) {
			Soopfw.tab_behaviors[Soopfw.current_tab] = [];
		}
		Soopfw.tab_behaviors[Soopfw.current_tab].push(func);
	},
	
	/**
	 * Opens a chooser dialog where just the buttons appear to execute a user defined action
	 *
	 * @param string $title
	 *   the title
	 * @param array $buttons
	 *   the buttons
	 */
	chooser_dialog: function($title, $buttons) {

		var html = $('<div id="ui-dialog' + uuid() + '"></div>');
		foreach($buttons, function(k, v) {
			$buttons[k] = function() {
				v();
				$(html).dialog("destroy");
			}
		});
		$(html).dialog({
			dialogClass: 'chooser_dialog',
			width: 'auto',
			minWidth: 'auto',
			minHeight: 'auto',
			height: 'auto',
			title: $title,
			buttons: $buttons
		});
	},

	/**
	 * Returns the size of the provided object.
	 * 
	 * @param object obj
	 *   The object to be counted.
	 *   
	 * @return int The object size.
	 */
	obj_size: function(obj) {
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	},

	/**
	 * Behavious all js function should implement this instead of Jquery document ready
	 * Will be reloaded with every ajax_html and normal page request
	 */
	reload_behaviors: function() {
		//Priority behaviours will be loaded first
		for(var behavior_x in Soopfw.prio_behaviors) {
			if(Soopfw.prio_behaviors.hasOwnProperty(behavior_x)) {
				if(jQuery.isFunction(Soopfw.prio_behaviors[behavior_x])) {
					Soopfw.prio_behaviors[behavior_x]();
				}
			}
		}
		for(var behavior_i in Soopfw.behaviors) {
			if(Soopfw.behaviors.hasOwnProperty(behavior_i)) {
				if(jQuery.isFunction(Soopfw.behaviors[behavior_i])) {
					Soopfw.behaviors[behavior_i]();
				}
			}
		}
		
		foreach (Soopfw.tab_behaviors[Soopfw.current_tab], function(k, behavior) {
			if(jQuery.isFunction(behavior)) {
				behavior();
			}
		});
		
		for(var behavior_y in Soopfw.late_behaviors) {
			if(Soopfw.late_behaviors.hasOwnProperty(behavior_y)) {
				if(jQuery.isFunction(Soopfw.late_behaviors[behavior_y])) {
					Soopfw.late_behaviors[behavior_y]();
				}
			}
		}
		Soopfw.system_footer_behaviour();
	},

	/**
	 * MUST BE IMPLEMENTED
	 * Makes a table sortable (desc asc)
	 * @param Object table the jquery table object
	 */
	table_sort: function (table) {

		$(table).find("thead > tr > td").each(function() {

		})

	},

	/**
	 * Translation function, key as an english text, args as an object {search => replace}.
	 * 
	 * @param string key
	 *   The english key (text).
	 * @param object args
	 *   The arguments which will be replaced within the text.
	 */
	t: function(key, args) {
		
		var translation = key.toLowerCase();
		if(LANG[translation] !== undefined && LANG[translation] !== '' && LANG.hasOwnProperty(translation)) {
			translation = LANG[translation];
		}
		else {
			translation = key;
		}
		
		if(args != undefined) {
			foreach(args, function(k, v) {
				translation = str_replace(k, v, translation);
			});
		}
		return translation;
	},

	/**
	 * Init a ajax queue with given identifier.
	 * 
	 * @param string identifier
	 *   The progress identifier.
	 */
	ajax_queue_init: function(identifier) {
		soopfw_ajax_queue[identifier] = [];
	},

	/**
	 * Adds to the given identifier queue an ajax call with the ajax options see Jquery ajax options for a complete list
	 * of ajax_options.
	 * 
	 * @param string identifier
	 *   The progress identifier.
	 * @param object ajax_options
	 */
	ajax_queue: function(identifier, ajax_options) {
		soopfw_ajax_queue[identifier].push(ajax_options);
	},

	/**
	 * Start the queue.
	 * 
	 * @param string identifier
	 *   The progress identifier.
	 */
	ajax_queue_start: function(identifier) {
		Soopfw.ajax_queue_worker(identifier);
	},

	/**
	 * Should not be called directly, will process the queue and on complete it will fetch next
	 * queue item and process until queue is empty
	 *
	 * @param string identifier
	 *   The progress identifier.
	 */
	ajax_queue_worker: function(identifier) {
		if(!empty(soopfw_ajax_queue[identifier])) {
			var o = soopfw_ajax_queue[identifier].shift();
			if(o == undefined) {
				return;
			}
			var old_complete = o.complete;
			o.complete = function() {
				if(old_complete != undefined) {
					old_complete();
				}
				Soopfw.ajax_queue_worker(identifier);
			}
			$.ajax(o);
		}
	},

	/**
	 * Append a progress bar to append_element.
	 * 
	 * @param string identifier
	 *   The progress identifier.
	 * @param int max_value
	 *   the max value.
	 * @param string init_text
	 *   The text which will be displayed while first run is active.
	 * @param mixed append_element
	 *   Can be an jquery string or element object.
	 */
	progress: function(identifier, max_value, init_text, append_element) {
		$(append_element).append(
			create_element({input: 'div', attr: {"class": 'progress_bar progressbar_'+identifier}, append:[
				create_element({input: 'div', attr: {"class": 'progressbar_message', html: init_text}}),
				create_element({input: 'div', attr: {"class": 'progressbar_bar_bg'}, append:[
					create_element({input: 'div', attr: {"class": 'progressbar_bar'}}),
					create_element({input: 'span', attr: {"class": 'progressbar_percent'}})
				]})
			]})
		);

		Soopfw.internal.progressbars[identifier] = {
			'max_value': max_value,
			'current': 0
		};
	},

	/**
	 * Updates the progressbar.
	 * 
	 * @param string identifier
	 *   The progress identifier.
	 * @param string init_text 
	 *   the Text to be written.
	 * @param int percent_override 
	 *   Normaly it will self calculate the percent, but on finish it is usefull to override it to 100.
	 */
	progress_update: function(identifier, init_text, percent_override) {
		if(!empty(init_text)) {
			$("div.progressbar_"+identifier+" div.progressbar_message").html(init_text);
		}
		var percent = 0;
		if(!empty(percent_override)) {
			percent = percent_override;
		}
		else {
			Soopfw.internal.progressbars[identifier].current++;
			percent = parseInt((100/Soopfw.internal.progressbars[identifier].max_value)*Soopfw.internal.progressbars[identifier].current);
		}

		$("div.progressbar_"+identifier+" div.progressbar_bar").css("width", percent+"%");
		$("div.progressbar_"+identifier+" span.progressbar_percent").html(percent+"%");
	},

	/**
	 * Append an ajax load to the given div.
	 *
	 * @param mixed div 
	 *   Can be an jquery string or element object.
	 * @param string id 
	 *   an unique identifier for this ajax_loader.
	 */
	ajax_loader: function(div, id) {
		if(document.getElementById("ajax_loader_"+id) != undefined) {
			$("#ajax_loader_"+id).remove();
			return;
		}
		$(div).append(
			create_element({input: 'div', attr: {id: 'ajax_loader_'+id, "class": 'ajax_loader'}, append:[
					create_element({input: 'img', attr: {src: Soopfw.config.template_path + '/images/ajax_loader_small.gif', valign:'absmiddle'}}),
					create_element({input: 'span', attr: {html: Soopfw.t("Loading content"), valign:'middle'}})
			]})
		);
	},

	/**
	 * Call an ajax_html ajax request to the given module, action with args and display the output html in a dialog
	 * After successfull load the ajax behaviours will be reloaded.
	 *
	 * @param string title 
	 *   the title of the dialog.
	 * @param string module 
	 *   The module.
	 * @param string action
	 *   the action to be called.
	 * @param array args 
	 *   The arguments for the action.
	 * @param array get_params 
	 *   An array with get params.
	 *   
	 * @return string the created dialog id.
	 */
	default_action_dialog: function(title, module, action, args, options, get_params) {
		if(args != undefined && args != null) {
			args = '/'+implode('/', args);
		}
		else {
			args = "";
		}

		var get_param_string = '';
		if (!empty(get_params)) {
			var params = [];
			foreach (get_params, function(k,v) {
				params.push(k + '=' + v);
			});
			get_param_string = '?' + implode('&', params);
		}


		var id = module;
		if(action != undefined && action !== true) {
			id += action;
			action = '/'+action;
		}
		else {
			action = '';
		}

		var url = module+action+args;


		if(!url.match(/^\//)) {
			url = '/'+url;
		}

		if(!url.match(/\.ajax_html$/i)) {
			url += '.ajax_html';
		}

		url += get_param_string;

		options = $.extend({
			title: title,
			modal: true,
			width: 500,
			open: function(event, ui) {
				Soopfw.reload_behaviors();
			}
		}, options);

		var matches = window.location.pathname.match(/^\/admin\/.*/g);
		if(matches != null && matches.length > 0) {
			url = '/admin' + url;
		}
		id = 'jquery_dialog_' + id;
		wait_dialog();
		$.ajax({
			url: url,
			dataType: 'html',
			close: function() {
				$(this).dialog("destroy").remove();
			},
			success: function(result) {

				var matches = result.match(/<title>(.*)<\/title>/g);
				if(matches != null && matches.length > 0) {
					matches = matches[0].replace("<title>","").replace("</title>","");
					if(!empty(matches)) {
						options['title'] = matches;
					}
				}
				$.alerts._hide();
				$('#'+id).remove();
				$('body').append(create_element({input: 'div', attr: {id:id, html: result}}) );
				$('#'+id).dialog(options);
			}
		});
		return id;
	},

	/**
	 * Creates a unique random uuid.
	 * 
	 * @param int length
	 *   The length of the string.
	 *   
	 * @return string The uuid.
	 */
	uuid: function(length) {
		if(length == undefined) {
			length = 32;
		}
		return randomID(length);
	},

	/**
	 * Redirects the user to the url what was configurated through 
	 * php within the redirect_url setting.
	 */
	redirect: function() {

		var url = Soopfw.config.redirect_url;
		if(!empty(url)) {
			Soopfw.location(url);
		}
	},

	/**
	 * Points the browser to the given url.
	 */
	location: function(url) {
		document.location.href = url;
	}
});

// Create a private scope.
(function( $, on ){
	// We are going to be overriding the core on() method in the
	// jQuery library. But, we do want to have access to the core
	// version of the on() method for binding. Let's get a reference
	// to it for later use.
	var coreOnMethod = $.fn.on;
	
	$.fn.on = function( types, selector, data, fn, /*INTERNAL*/ one ){	
		
		// Store the current binding in all present tabs.
		var that = this;
		foreach (Soopfw.tab_bindings, function(k, v) {
			if (Soopfw.tab_bindings[k][types] === undefined) {
				Soopfw.tab_bindings[k][types] = [];
			}
			Soopfw.tab_bindings[k][types].push(that);
		});
		// Bind the wrapper as the event handler using the core,
		// underlying on() method.
		return(coreOnMethod.call( this, types, selector, data, fn, one ));

	};

})( jQuery );

Soopfw.system_footer_behaviour = function() {
	$(".disabledSelection :not(.enabledSelection)").disableSelection();
	$.datepicker.setDefaults( $.datepicker.regional[ Soopfw.config.current_language ] );
	$('input[type="text"].datepicker').datepicker();
	$('input[type="text"].datetimepicker').datetimepicker();

	$(".controlnavigation li").hover(function(){
		$(this).find(".dropdownbox").show();
	},function(){
		$(this).find(".dropdownbox").hide();
	});

	var editor_styles = [];
	$('head > link[type="text/css"]').each(function() {
		editor_styles.push($(this).attr('href'));
	});

	editor_styles.push(Soopfw.config.template_path+'/css/jquery.sceditor.overrides.css');
	$('.wysiwyg_bbcode:not(.soopfw-proccessed)').sceditorBBCodePlugin({
		style: editor_styles,
		height: 300
	}).addClass("soopfw-proccessed");

	$('.Tagfield:not(.soopfw-proccessed)').addClass("soopfw-proccessed").each(function() {
		var options = {};
		var src = $(this).attr('autocomplete_source');
		var min_length = $(this).attr('autocomplete_min_length');

		if (!empty(src)) {
			options['tagSource'] = src;
		}
		else if (Soopfw.config['taginput_source_' + $(this).attr('source_id')] != undefined) {
			options['tagSource'] = Soopfw.config['taginput_source_' + $(this).attr('source_id')];
		}

		if (!empty(options['tagSource'])) {
			options['autocompleteMinLength'] = min_length;
		}
		$(this).tagit(options);
	});


	process_form_buttons();


};

function process_form_buttons() {
	$('.form_button:not(.soopfw-proccessed),.button:not(.soopfw-proccessed)').each(function() {
		var options = {}
		var class_array = $(this).prop("class").split(" ");
		var icons = 0;
		if($(this).attr("text") == "false") {
			options['text'] = false;
		}
		for(var i in class_array) {
			if(!class_array.hasOwnProperty(i)) {
				continue;
			}

			if(class_array[i].substr(0, 8) == "ui-icon-") {
				if(icons == 0) {
					options['icons'] = {
						'primary': class_array[i]
					};
				}
				else if(icons == 1) {
					options['icons']['secondary'] = class_array[i];
				}

				icons++;
				if(icons == 2) {
					break;
				}
			}
		}
		$(this).button(options);
		$(this).addClass("soopfw-proccessed");
	});
}

function getRandomNumber(range)
{
	return Math.floor(Math.random() * range);
}

function getRandomChar()
{
	var chars = "0123456789abcdefghijklmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
	return chars.substr( getRandomNumber(62), 1 );
}

function randomID(size)
{
	var str = "";
	for(var i = 0; i < size; i++)
	{
		str += getRandomChar();
	}
	while(used_uuids[str] != undefined) {
		str = "";
		for(var x = 0; x < size; x++)
		{
			str += getRandomChar();
		}
	}
	return str;
}

var tabs_loaded = {};
$(document).ready(function() {

	$.extend(true, $.ui.dialog.prototype.options, {
		modal: true,
		close: function() {
			$(this).dialog("destroy").remove();
		}
	});

	Soopfw.behaviors.system_setup_tabs = function() {
		if(Soopfw.config.load_tabs != undefined) {
			for(var i = 0; i < Soopfw.config.load_tabs.length; i++) {
				var row = Soopfw.config.load_tabs[i];
				if(!empty(tabs_loaded[row.id])) {
					continue;
				}
				var fx = null;
				if(!empty(row.effect)) {
					fx = { effect: row.effect, duration: 200};
				}
				tabs_loaded[row.id] = true;
				$("#"+row.id).tabs({
					show: fx,
					hide: fx,
					before_load: function(ui) {
						//$(ui.panel).html("");
						//Soopfw.ajax_loader(ui.panel,"tabs");
					}
				});
			}
		}
	};

	Soopfw.behaviors.SystemSetup = function() {
		if (!empty(Soopfw.config.core_message_timeout)) {
			foreach (Soopfw.config.core_message_timeout, function(message_id, timeout) {
				window.setTimeout(function() {
					var message_elm = $('*[data-core-message-mid="' + message_id + '"]');
					if (!empty(message_elm)) {
						var timeout_elm = message_elm;
						var message_container = message_elm.parents('*[data-core-message-container]');
						if ($('*[data-core-message-mid]', message_container).length <= 1) {
							timeout_elm = message_container.parents('*[data-core-message-timeout-handler]');

						}
						$(timeout_elm).fadeOut(400, function() { $(this).remove(); });
					}
				}, timeout * 1000);
			});
			Soopfw.config.core_message_timeout = [];
		}
	};

	Soopfw.behaviors.SystemAjaxForm = function() {
		$('div[ajax_form]').each(function(){
			var _form = this;
			$(".ajax_submit_handler", $(this)).off("click").on('click', function() {
				var values = get_form_by_class("inputs_" + $(_form).attr('id'),"name", true);
				$.ajax({
					url: $(_form).attr('action'),
					type: $(_form).attr('method'),
					data: values,
					dataType: $(_form).attr('ajax_return_type'),
					success: function(result) {

						var form_name =  $(_form).attr('id');
						if($(_form).attr('ajax_return_type') == 'html') {
							if (Soopfw.config['system_ajax_form_return_type_handler'] != undefined && Soopfw.config['system_ajax_form_return_type_handler'][form_name] != undefined) {
								var function_name = Soopfw.config['system_ajax_form_return_type_handler'][form_name];
								if(/^[a-zA-Z0-9_.]+$/g.test(function_name)) {
									eval(function_name + "(result);");
								}
								return;
							}
						}

						parse_ajax_result(result, function(result,code,desc) {
							success_alert('Data successfully saved\n'+desc, function() {
								if(Soopfw.config['js_function_callback_' + form_name] != undefined) {
									for(var i in Soopfw.config['js_function_callback_' + form_name]) {
										if(!Soopfw.config['js_function_callback_' + form_name].hasOwnProperty(i)) {
											continue;
										}
										var function_name = Soopfw.config['js_function_callback_' + form_name][i];

										if(/^[a-zA-Z0-9_.]+$/g.test(function_name)) {
											eval(function_name+"(result, code, desc);");
										}
									}
								}
							});
						});

					}
				});
			});
		});

		$('.filefield_delete_file').off('click').on('click', function() {
			$(this).parent().remove();
		});
	};

	Soopfw.reload_behaviors();
});

