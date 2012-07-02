/**
 * http://github.com/valums/file-uploader
 *
 * Multiple file upload component with progress-bar, drag-and-drop.
 * Â© 2010 Andrew Valums andrew(at)valums.com
 *
 * Licensed under MIT, GNU GPL and GNU LGPL 2 or later.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

var qq = qq || {};
var unique_ids = 0;
/**
 * Class that creates our multiple file upload widget
 */
qq.FileUploader = function(o){
    this._options = {
        // container element DOM node (ex. $(selector)[0] for jQuery users)
        element: null,
        // url of the server-side upload script, should be on the same domain
        action: '/server/upload',
        // additional data to send, name-value pairs
        params: {},
		multiple: false,
        // ex. ['jpg', 'jpeg', 'png', 'gif'] or []
        allowedExtensions: [],

		//if filled, a "file" entry will be pre created
        pre_values: {},

        // size limit in bytes, 0 - no limit
        // this option isn't supported in all browsers
        sizeLimit: 0,
        onSubmit: function(id, fileName){},
        onComplete: function(id, fileName, responseJSON){},
		onCompleteFunction: '',

        //
        // UI customizations

        template: '<div class="qq-uploader">' +
                '<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>' +
                '<div class="qq-upload-button form_button ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only ui-button-text soopfw-proccessed">Upload a file</div>' +
                //'<div class="qq-upload-button soopfw-proccessed">Upload a file</div>' +
                '<table class="qq-upload-list" cellspacing="0" cellpadding="0"></table>' +
             '</div>',

		// template for one item in file list
        fileTemplate: '<tr>' +
                '<td class="qq-upload-file"></td>' +
                '<td class="qq-upload-spinner" style="width:30px;"></td>' +
                '<td class="qq-upload-size" style="width:70px;text-align:right;"></td>' +
                '<td class="qq-upload-cancel" style="width:70px;text-align:right;"><a href="javascript:void(0);">Cancel</a></td>' +
                '<td class="qq-upload-delete" style="width:40px;text-align:center;"><a href="javascript:void(0);"><img src="/1x1_spacer.gif" class="ui-icon-soopfw ui-icon-soopfw-cancel"/></a></td>' +
                '<td class="qq-upload-hidden_inputs" style="width:1px;"></td>' +
            '</tr>',

        classes: {
            // used to get elements from templates
            button: 'qq-upload-button',
            drop: 'qq-upload-drop-area',
            dropActive: 'qq-upload-drop-area-active',
            list: 'qq-upload-list',

            file: 'qq-upload-file',
            spinner: 'qq-upload-spinner',
            size: 'qq-upload-size',
            cancel: 'qq-upload-cancel',
			hidden_inputs: 'qq-upload-hidden_inputs',
			'delete': 'qq-upload-delete',
            // added to list item when upload completes
            // used in css to hide progress spinner
            success: 'qq-upload-success'
        },
        messages: {
            //serverError: "Some files were not uploaded, please contact support and/or try again.",
            typeError: "{file} has invalid extension. Only {extensions} are allowed.",
            sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
            emptyError: "{file} is empty, please select files again without it."
        },
        showMessage: function(message){
            alert(message);
        }
    };

    $.extend(this._options, o);

    this._element = this._options.element;

    if (this._element.nodeType != 1){
        throw new Error('element param of FileUploader should be dom node');
    }

    this._element.innerHTML = this._options.template;

    // number of files being uploaded
    this._filesInProgress = 0;

    // easier access
    this._classes = this._options.classes;

    this._handler = this._createUploadHandler();

    this._bindCancelEvent();

    var self = this;
    this._button = new qq.UploadButton({
        element: this._getElement('button'),
        multiple: qq.UploadHandlerXhr.isSupported(),
        onChange: function(input){
            self._onInputChange(input);
        }
    });

    this._setupDragDrop();

	if(this._options.pre_values.fid != undefined) {
		var uniqueid = qq.getUniqueId();
		this._addToList(uniqueid, this._options.pre_values.file_name);
		this._updateProgress(uniqueid, this._options.pre_values.file_size, this._options.pre_values.file_size);
		this._set_upload_finished(uniqueid, this._options.pre_values.file_name, this._options.pre_values);
	}
};

qq.FileUploader.prototype = {
    setParams: function(params){
        this._options.params = params;
    },
    /**
     * Returns true if some files are being uploaded, false otherwise
     */
    isUploading: function(){
        return !!this._filesInProgress;
    },
    /**
     * Gets one of the elements listed in this._options.classes
     *
     * First optional element is root for search,
     * this._element is default value.
     *
     * Usage
     *  1. this._getElement('button');
     *  2. this._getElement(item, 'file');
     **/
    _getElement: function(parent, type){
        if (typeof parent == 'string'){
            // parent was not passed
            type = parent;
            parent = this._element;
        }
		var element = $("."+this._options.classes[type], parent)[0];
        if (element == undefined){
            throw new Error('element not found ' + type);
        }

        return element;
    },
    _error: function(code, fileName){
        var message = this._options.messages[code];
        message = message.replace('{file}', this._formatFileName(fileName));
        message = message.replace('{extensions}', this._options.allowedExtensions.join(', '));
        message = message.replace('{sizeLimit}', this._formatSize(this._options.sizeLimit));
        this._options.showMessage(message);
    },
    _formatFileName: function(name){
        if (name.length > 33){
            name = name.slice(0, 19) + '...' + name.slice(-13);
        }
        return name;
    },
    _isAllowedExtension: function(fileName){
        var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '';
        var allowed = this._options.allowedExtensions;

        if (!allowed.length){return true;}

        for (var i=0; i<allowed.length; i++){
            if (allowed[i].toLowerCase() == ext){
                return true;
            }
        }

        return false;
    },
    _setupDragDrop: function(){
        function isValidDrag(e){
            var dt = e.dataTransfer,
                // do not check dt.types.contains in webkit, because it crashes safari 4
                isWebkit = navigator.userAgent.indexOf("AppleWebKit") > -1;

            // dt.effectAllowed is none in Safari 5
            // dt.types.contains check is for firefox
            return dt && dt.effectAllowed != 'none' && (dt.files || (!isWebkit && dt.types.contains && dt.types.contains('Files')));
        }

        var self = this,
            dropArea = this._getElement('drop');

		$(dropArea).hide();

        var hideTimeout;
        qq.attach(document, 'dragenter', function(e){
            e.preventDefault();
        });

        qq.attach(document, 'dragover', function(e){
            if (isValidDrag(e)){

                if (hideTimeout){
                    clearTimeout(hideTimeout);
                }

                if (dropArea == e.target || qq.contains(dropArea,e.target)){
                    var effect = e.dataTransfer.effectAllowed;
                    if (effect == 'move' || effect == 'linkMove'){
                        e.dataTransfer.dropEffect = 'move'; // for FF (only move allowed)
                    } else {
                        e.dataTransfer.dropEffect = 'copy'; // for Chrome
                    }
					$(dropArea).addClass(self._classes.dropActive);
                    e.stopPropagation();
                } else {
                    $(dropArea).show()
                    e.dataTransfer.dropEffect = 'none';
                }

                e.preventDefault();
            }
        });

        qq.attach(document, 'dragleave', function(e){
            if (isValidDrag(e)){

                if (dropArea == e.target || qq.contains(dropArea,e.target)){
					$(dropArea).removeClass(self._classes.dropActive);
                    e.stopPropagation();
                } else {

                    if (hideTimeout){
                        clearTimeout(hideTimeout);
                    }

                    hideTimeout = setTimeout(function(){
						$(dropArea).hide();
                    }, 77);
                }
            }
        });

        qq.attach(dropArea, 'drop', function(e){
			$(dropArea).hide();
            self._uploadFileList(e.dataTransfer.files);
            e.preventDefault();
        });
    },
    _createUploadHandler: function(){
        var self = this,
            handlerClass;

        if(qq.UploadHandlerXhr.isSupported()){
            handlerClass = 'UploadHandlerXhr';
        } else {
            handlerClass = 'UploadHandlerForm';
        }

        var handler = new qq[handlerClass]({
            action: this._options.action,
            onProgress: function(id, fileName, loaded, total){
                // is only called for xhr upload
                self._updateProgress(id, loaded, total);
            },
            onComplete: function(id, fileName, result){
				self._upload_success(id, fileName, result);
            }
        });

        return handler;
    },

	_upload_success: function (id, fileName, result, size) {
		var self = this;
		self._filesInProgress--;

		// mark completed
		var item = self._getItemByFileId(id);
		if(size != undefined) {
			self._set_upload_finished(id, fileName, size);
		}
		else {
			parse_ajax_result(result, function(result) {
				self._set_upload_finished(id, fileName, result);
				self._options.onComplete(id, fileName, result);

			}, null, null, function() {
				$(self._getElement(item, 'delete')).html(Soopfw.t("error")).show();
				$(self._getElement(item, 'spinner')).hide();
				$(self._getElement(item, 'cancel')).hide();
			});
		}
	},
	_set_upload_finished: function(id, fileName, result) {

		var self = this;
		var item = self._getItemByFileId(id);
		$(self._getElement(item, 'delete')).show();
		$(self._getElement(item, 'spinner')).hide();
		$(self._getElement(item, 'cancel')).hide();
		$(item).addClass(self._classes.success);
		var css_class = self._options.css_class;

		var elm_id = self._options.input_id;
		var elm_name = self._options.input_name;

		if(self._options.multiple == false) {
			if(self.lastelm != null) {
				ajax_request(self._options.action, {'fid': self.lastfile}, function() {
					$(self.lastelm).remove();
					/**
					 * Need to have this set twice (this and below) because if an ajax request is in progress to
					 * delete a file the last element will be already be overriden with the current one and than
					 * after delete ajax request finished it will delete the current one which is wrong
					 */
					self.lastelm = item;
					self.lastfile = result.fid;
				});
			}
			else {
				self.lastelm = item;
				self.lastfile = result.fid;
			}

		}
		if(self._options.multiple) {
			elm_name = elm_name + "[]";
		}
		$(self._getElement(item, 'hidden_inputs')).append(
			create_element({input: 'input', attr: {type: 'hidden', name: elm_name, id: elm_id, "class": css_class, value: result.fid}})
		);
		$(self._getElement(item, 'delete')).click(function(e) {
			var deleteElement = $(this).parent();
			ajax_request(self._options.action, {'fid': result.fid}, function() {
				deleteElement.remove();
				if(self._options.multiple == false) {
					self.lastelm = null;
				}

			});
		});

		if(result.file_size != undefined && result.file_size != null) {
			$(self._getElement(item, 'size')).html(self._formatSize(result.file_size));
		}
		if(!empty(self._options.onCompleteFunction)) {
			eval(self._options.onCompleteFunction+"();");
		}

	},

    _onInputChange: function(input){

        if (this._handler instanceof qq.UploadHandlerXhr){

            this._uploadFileList(input.files);

        } else {

            if (this._validateFile(input)){
                this._uploadFile(input);
            }

        }

        this._button.reset();
    },
    _uploadFileList: function(files){
        var valid = true;

        var i = files.length;
        while (i--){
            if (!this._validateFile(files[i])){
                valid = false;
                break;
            }
        }

        if (valid){
            var i = files.length;
            while (i--){this._uploadFile(files[i]);}
        }
    },
    _uploadFile: function(fileContainer){
        var id = this._handler.add(fileContainer);
        var name = this._handler.getName(id);
        this._options.onSubmit(id, name);
        this._addToList(id, name);
        this._handler.upload(id, this._options.params);
    },
    _validateFile: function(file){
        var name,size;

        if (file.value){
            // it is a file input
            // get input value and remove path to normalize
            name = file.value.replace(/.*(\/|\\)/, "");
        } else {
            // fix missing properties in Safari
            name = file.fileName != null ? file.fileName : file.name;
            size = file.fileSize != null ? file.fileSize : file.size;
        }

        if (! this._isAllowedExtension(name)){
            this._error('typeError',name);
            return false;

        } else if (size === 0){
            this._error('emptyError',name);
            return false;

        } else if (size && this._options.sizeLimit && size > this._options.sizeLimit){
            this._error('sizeError',name);
            return false;
        }

        return true;
    },
    _preAddToList: function(id, fileName, fileSize){
        var item = $(this._options.fileTemplate);
		$(item).attr("uuid", id);
		$(this._getElement('list')).append(item);
		$(this._getElement(item, 'file')).html(this._formatFileName(fileName));

		if(fileSize != undefined) {
			$(this._getElement(item, 'size')).show().html(this._formatSize(fileSize));
		}


    },
    _addToList: function(id, fileName){
		this._preAddToList(id, fileName);
        this._filesInProgress++;
    },
    _updateProgress: function(id, loaded, total){
        var text = "";
        if (loaded != total){
            text = Math.round(loaded / total * 100) + '% from ' + this._formatSize(total);
        } else {
            text = this._formatSize(total);
        }
		$(this._getElement(this._getItemByFileId(id), 'size')).show().html(text);
    },
    _formatSize: function(bytes){
        var i = -1;
        do {
            bytes = bytes / 1024;
            i++;
        } while (bytes > 99);

        return Math.max(bytes, 0.1).toFixed(1) +' '+ ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i];
    },
    _getItemByFileId: function(id){
		return $(this._getElement('list')).find('tr[uuid="'+id+'"]');
    },
    /**
     * delegate click event for cancel link
     **/
    _bindCancelEvent: function(){
        var self = this,
            list = this._getElement('list');

        qq.attach(list, 'click', function(e){
            e = e || window.event;
            var target = e.target || e.srcElement;

            if ($(target).hasClass(self._classes.cancel)){
                qq.preventDefault(e);

                var item = target.parentNode;
                self._handler.cancel($(item).attr("uuid"));
				$(item).remove();
            }
        });

    }
};

qq.UploadButton = function(o){
    this._options = {
        element: null,
        // if set to true adds multiple attribute to file input
        multiple: false,
        // name attribute of file input
        name: 'file',
        onChange: function(input){},
        hoverClass: 'qq-upload-button-hover',
        focusClass: 'qq-upload-button-focus'
    };

    $.extend(this._options, o);

    this._element = this._options.element;

	// make button suitable container for input
	$(this._element)
	.css('position', 'relative')
	.css('overflow', 'hidden')
	// Make sure browse button is in the right side
    // in Internet Explorer
	.css('direction', 'ltr')

    this._input = this._createInput();
};

qq.UploadButton.prototype = {
    /* returns file input element */
    getInput: function(){
        return this._input;
    },
    /* cleans/recreates the file input */
    reset: function(){
        if (this._input.parentNode){
            $(this._input).remove();
        }

		$(this._element).removeClass(this._options.focusClass);
        this._input = this._createInput();
    },
    _createInput: function(){
        var input = document.createElement("input");

        if (this._options.multiple){
            input.setAttribute("multiple", "multiple");
        }

        input.setAttribute("type", "file");
        input.setAttribute("name", this._options.name);

		$(input)
		.css('position', 'absolute')
            // in Opera only 'browse' button
            // is clickable and it is located at
            // the right side of the input
        .css('right', 0)
        .css('top', 0)
        .css('zIndex', 1)
        .css('fontSize', '460px')
        .css('lineHeight', '516px')
        .css('margin', 0)
        .css('padding', 0)
        .css('cursor', 'pointer')
        .css('opacity', 0)
        .css('width', 'auto')
        //.css('bottom', "-495px")
        //.css('left', "-10248px")


        this._element.appendChild(input);

        var self = this;
        qq.attach(input, 'change', function(){
            self._options.onChange(input);
        });

        qq.attach(input, 'mouseover', function(){
			$(self._element).removeClass(self._options.hoverClass);
        });
        qq.attach(input, 'mouseout', function(){
			$(self._element).removeClass(self._options.hoverClass);
        });
        qq.attach(input, 'focus', function(){
			$(self._element).removeClass(self._options.focusClass);
        });
        qq.attach(input, 'blur', function(){
			$(self._element).removeClass(self._options.focusClass);
        });

        // IE and Opera, unfortunately have 2 tab stops on file input
        // which is unacceptable in our case, disable keyboard access
        if (window.attachEvent){
            // it is IE or Opera
            input.setAttribute('tabIndex', "-1");
        }

        return input;
    }
};

/**
 * Class for uploading files using form and iframe
 */
qq.UploadHandlerForm = function(o){
    this._options = {
        // URL of the server-side upload script,
        // should be on the same domain to get response
        action: '/upload',
        // fires for each file, when iframe finishes loading
        onComplete: function(id, fileName, response){}
    };
    $.extend(this._options, o);

    this._inputs = {};
};
qq.UploadHandlerForm.prototype = {
    /**
     * Adds file input to the queue
     * Returns id to use with upload, cancel
     **/
    add: function(fileInput){
        fileInput.setAttribute('name', 'qqfile');
        var id = 'qq-upload-handler-iframe' + qq.getUniqueId();

        this._inputs[id] = fileInput;

        // remove file input from DOM
        if (fileInput.parentNode){
            $(fileInput).remove();
        }

        return id;
    },
    /**
     * Sends the file identified by id and additional query params to the server
     * @param {Object} params name-value string pairs
     */
    upload: function(id, params){
        var input = this._inputs[id];

        if (!input){
            throw new Error('file with passed id was not added, or already uploaded or cancelled');
        }

        var fileName = this.getName(id);

        var iframe = this._createIframe(id);
        var form = this._createForm(iframe, params);
        form.appendChild(input);

        var self = this;
        this._attachLoadEvent(iframe, function(){
            self._options.onComplete(id, fileName, self._getIframeContentJSON(iframe));

            delete self._inputs[id];
            // timeout added to fix busy state in FF3.6
            setTimeout(function(){
                $(iframe).remove();
            }, 1);
        });

        form.submit();
		$(form).remove();

        return id;
    },
    cancel: function(id){
        if (id in this._inputs){
            delete this._inputs[id];
        }

        var iframe = document.getElementById(id);
        if (iframe){
            // to cancel request set src to something else
            // we use src="javascript:false;" because it doesn't
            // trigger ie6 prompt on https
            iframe.setAttribute('src', 'javascript:false;');

            $(iframe).remove();
        }
    },
    getName: function(id){
        // get input value and remove path to normalize
        return this._inputs[id].value.replace(/.*(\/|\\)/, "");
    },
    _attachLoadEvent: function(iframe, callback){
        qq.attach(iframe, 'load', function(){
            // when we remove iframe from dom
            // the request stops, but in IE load
            // event fires
            if (!iframe.parentNode){
                return;
            }

            // fixing Opera 10.53
            if (iframe.contentDocument &&
                iframe.contentDocument.body &&
                iframe.contentDocument.body.innerHTML == "false"){
                // In Opera event is fired second time
                // when body.innerHTML changed from false
                // to server response approx. after 1 sec
                // when we upload file with iframe
                return;
            }

            callback();
        });
    },
    /**
     * Returns json object received by iframe from server.
     */
    _getIframeContentJSON: function(iframe){
        // iframe.contentWindow.document - for IE<7
        var doc = iframe.contentDocument ? iframe.contentDocument: iframe.contentWindow.document,
            response;

        try{
            response = eval("(" + doc.body.innerHTML + ")");
        } catch(err){
            response = {};
        }

        return response;
    },
    /**
     * Creates iframe with unique name
     */
    _createIframe: function(id){
        // We can't use following code as the name attribute
        // won't be properly registered in IE6, and new window
        // on form submit will open
        // var iframe = document.createElement('iframe');
        // iframe.setAttribute('name', id);

        var iframe = $('<iframe src="javascript:false;" name="' + id + '" />');
        // src="javascript:false;" removes ie6 prompt on https

        iframe.setAttribute('id', id);
		$(iframe).hide();
        document.body.appendChild(iframe);

        return iframe;
    },
    /**
     * Creates form, that will be submitted to iframe
     */
    _createForm: function(iframe, params){
        // We can't use the following code in IE6
        // var form = document.createElement('form');
        // form.setAttribute('method', 'post');
        // form.setAttribute('enctype', 'multipart/form-data');
        // Because in this case file won't be attached to request
        var form = $('<form method="post" enctype="multipart/form-data"></form>');

        var queryString = '?';
        for (var key in params){
            queryString += '&' + key + '=' + encodeURIComponent(params[key]);
        }

        form.setAttribute('action', this._options.action + queryString);
        form.setAttribute('target', iframe.name);
        $(form).hide();
        document.body.appendChild(form);

        return form;
    }
};

/**
 * Class for uploading files using xhr
 */
qq.UploadHandlerXhr = function(o){
    this._options = {
        // url of the server-side upload script,
        // should be on the same domain
        action: '/upload',
        onProgress: function(id, fileName, loaded, total){},
        onComplete: function(id, fileName, response){}
    };
    $.extend(this._options, o);

    this._files = {};
    this._xhrs = [];
};

// static method
qq.UploadHandlerXhr.isSupported = function(){
    return typeof File != "undefined" &&
        typeof (new XMLHttpRequest()).upload != "undefined";
};

qq.UploadHandlerXhr.prototype = {
    /**
     * Adds file to the queue
     * Returns id to use with upload, cancel
     **/
    add: function(file){
		var id = qq.getUniqueId();
		this._files[id] = file;
		return id;
    },
    /**
     * Sends the file identified by id and additional query params to the server
     * @param {Object} params name-value string pairs
     */
    upload: function(id, params){
        var file = this._files[id],
            name = this.getName(id),
            size = this.getSize(id);

        if (!file){
            throw new Error('file with passed id was not added, or already uploaded or cancelled');
        }

        var xhr = this._xhrs[id] = new XMLHttpRequest();
        var self = this;

        xhr.upload.onprogress = function(e){
            if (e.lengthComputable){
                self._options.onProgress(id, name, e.loaded, e.total);
            }
        };

        xhr.onreadystatechange = function(){
            // the request was aborted/cancelled
            if (!self._files[id]){
                return;
            }

            if (xhr.readyState == 4){

                self._options.onProgress(id, name, size, size);

                if (xhr.status == 200){
                    var response;

                    try {
                        response = eval("(" + xhr.responseText + ")");
                    } catch(err){
                        response = {};
                    }

                    self._options.onComplete(id, name, response);

                } else {
                    self._options.onComplete(id, name, {});
                }

                self._files[id] = null;
                self._xhrs[id] = null;
            }
        };

        // build query string
        var queryString = '?qqfile=' + encodeURIComponent(name);
        for (var key in params){
            queryString += '&' + key + '=' + encodeURIComponent(params[key]);
        }

        xhr.open("POST", this._options.action + queryString, true);
        xhr.send(file);
    },
    cancel: function(id){
        this._files[id] = null;

        if (this._xhrs[id]){
            this._xhrs[id].abort();
            this._xhrs[id] = null;
        }
    },
    getName: function(id){
        // fix missing name in Safari 4
        var file = this._files[id];
        return file.fileName != null ? file.fileName : file.name;
    },
    getSize: function(id){
        // fix missing size in Safari 4
        var file = this._files[id];
        return file.fileSize != null ? file.fileSize : file.size;
    }
};

// Useful generic functions
/**
 * Returns a unique id
 * @return int unique id
 */
qq.getUniqueId = (function(){
    //var id = 0;
    return function(){
        return unique_ids++;
    };
})();

// Events
qq.attach = function(element, type, fn){
    if (element.addEventListener){
        element.addEventListener(type, fn, false);
    } else if (element.attachEvent){
        element.attachEvent('on' + type, fn);
    }
};
qq.detach = function(element, type, fn){
    if (element.removeEventListener){
        element.removeEventListener(type, fn, false);
    } else if (element.attachEvent){
        element.detachEvent('on' + type, fn);
    }
};

qq.preventDefault = function(e){
    if (e.preventDefault){
        e.preventDefault();
    } else{
        e.returnValue = false;
    }
};

// Node manipulations
qq.contains = function(parent, descendant){
    if (parent.contains){
        return parent.contains(descendant);
    } else {
        return !!(descendant.compareDocumentPosition(parent) & 8);
    }
};


Soopfw.behaviors.AjaxFileUploadSetup = function() {
	if(Soopfw.config.ajax_file_uploads_already_processed == undefined) {
		Soopfw.config.ajax_file_uploads_already_processed = {};
	}

	if(Soopfw.config.system_ajax_file_uploads != undefined) {
		for(i = 0; i < Soopfw.config.system_ajax_file_uploads.length; i++) {
			var row = Soopfw.config.system_ajax_file_uploads[i];

			var element = $("#file-uploader-"+row.id)[0];
			if(Soopfw.config.ajax_file_uploads_already_processed[row.id] != undefined || element == undefined) {
				continue;
			}

			var options = {
				// pass the dom node (ex. $(selector)[0] for jQuery users)
				element: element,
				css_class: row.css_class,
				input_id: row.id,
				input_name: row.name,
				allowedExtensions: row.extensions,
				sizeLimit: row.size_limit,
				// path to server-side upload script
				action: row.action
			};
			if(!empty(row.on_complete)) {
				options['onCompleteFunction'] = row.on_complete;
			}
			if(!empty(row.pre_values)) {
				options['pre_values'] = row.pre_values;
			}
			var uploader = new qq.FileUploader(options);
			Soopfw.config.ajax_file_uploads_already_processed[row.id] = true;

		}
	}
};

