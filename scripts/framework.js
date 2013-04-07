//
// Biscuit Javascript Framework - Web 2.0 client-side compliment to the PHP back end.
// NOTICE: This framework requires prototype JS. If you choose to use it in your project,
// ensure that it is included after prototype. You can either include prototype manually, or install
// the Web 2.0 plugin.
//
// Author: Peter Epp

var Biscuit = {};

Biscuit.Console = {
	log: function(message) {
		if (window.console != undefined && window.console.log != undefined) {
			console.log(message);
		}
	}
}

Biscuit.Version = '1.0.0';

Biscuit.Session = {};
Biscuit.Ajax = {};

// Crumbs are little helpers and widgets
Biscuit.Crumbs = {}
Biscuit.Crumbs.Forms = {}
Biscuit.Crumbs.Sortable = {}

// This function will find all forms on the page with a CSS class of "admin" and check it every 5 minutes for changes. If changes were made it will ping the
// server to keep the current login session alive.

Biscuit.Session.KeepAlive = {
	ping_timer: null,
	init_form_observer: function(observe_timer) {
		if (observe_timer == undefined) {
			var observe_timer = (5*60);		// 5 minutes
		}
		$$('.admin').each(function(el) {
			//observe_timer = 5;
			var observer = new Form.Observer(el,observe_timer,function() {
				Biscuit.Session.KeepAlive.ping();
			});
		});
	},
	start_ping_timer: function(interval) {
		if (interval == undefined) {
			var interval = (1000*60*5);		// 5 minutes
		}
		top.Biscuit.Session.KeepAlive.ping();
		this.ping_timer = setTimeout("top.Biscuit.Session.KeepAlive.init_timed_ping("+interval+");",interval);
	},
	cancel_ping_timer: function() {
		clearTimeout(this.ping_timer);
	},
	ping: function() {
		var currTime = new Date();
		var now = currTime.getTime();
		new Ajax.Request('/ping/'+now,{
			method: 'get',
			requestHeaders: Biscuit.Ajax.RequestHeaders('ping'),
			evalScripts: true
		});
	}
}

Biscuit.Ajax.RequestHeaders = function(type) {
	return {
		'X-Biscuit-Ajax-Request': 'true',
		'X-Biscuit-Request-Type': type
	} 
}

Biscuit.Ajax.LoginHandler = function(login_form_id,container_id) {
	if (container_id === undefined) {
		container_id = login_form_id;
	}
	$(login_form_id).observe("submit",function(event) {
		Event.stop(event);
		Biscuit.Crumbs.Forms.DisableSubmit(login_form_id);
		var params = Form.serializeElements($(login_form_id).getElements(),true);
		var form_action = $(login_form_id).getAttribute('action');
		var url = "";
		if (form_action !== undefined && form_action !== null) {
			if (typeof(form_action) == "object") {
				url = form_action.value;
			}
			else if (typeof(form_action) == "string") {
				url = form_action;
			}
		}
		if (url == "") {
			url = top.location.href;
		}
		new Ajax.Request(url,{
			parameters: params,
			requestHeaders: Biscuit.Ajax.RequestHeaders('login'),
			onComplete: function(transport) {
				if (transport.responseText === '-ERROR' || transport.responseText === '' || transport.responseText == null) {
					Biscuit.Crumbs.Forms.EnableSubmit(login_form_id);
					Effect.Shake(login_form_id,{distance: 12, duration: 0.3});
				}
				else if (transport.responseText === "+OK") {
					$(login_form_id).submit();
				}
			}
		});
	})
}

Biscuit.Ajax.FormValidator = function(form_id,options) {
	if (options === undefined) {
		var options = {}
	}
	if ($(form_id) == null) {
		alert('Cannot find form: '+form_id);
		return;
	}
	this._id = form_id;
	if (options.ajax_submit === undefined || options.ajax_submit === false) {
		this._submitter = Biscuit.Crumbs.Forms.Submit;
	}
	else {
		if (options.ajax_submit === true && (options.update_div === undefined && options.custom_ajax_submitter === undefined)) {
			alert('Cannot proceed with Ajax submit after validation without a div id or a custom submit callback!');
			return;
		}
		if (options.complete_callback !== undefined) {
			this.complete_callback = options.complete_callback;
		}
		if (options.custom_ajax_submitter !== undefined) {
			this._submitter = options.custom_ajax_submitter;
		}
		else {
			this.update_div = options.update_div;
			this._submitter = this.Submit;
		}
	}
	if (options.throbber_id !== undefined) {
		this.throbber_id = options.throbber_id;
	}
	else {
		this.throbber_id = null;
	}
	if (options.request_type !== undefined) {
		this.request_type = options.request_type;
	}
	else {
		this.request_type = 'validation';
	}
	if (options.use_ping_timer !== undefined) {
		this.use_ping_timer = options.use_ping_timer;
	}
	else {
		this.use_ping_timer = false;
	}
	this.Validate();
}

Object.extend(Biscuit.Ajax.FormValidator.prototype, {
	Validate: function() {
		Biscuit.Crumbs.Forms.DisableSubmit(this._id);
		Biscuit.Crumbs.ShowThrobber(this.throbber_id);
		var params = Form.serializeElements($(this._id).getElements(),true);
		// Add file fields manually since prototype doesn't serialize them by default (must have changed since a previous version)
		$$('#'+this._id+' input[type=file]').each(function(el) {
			if (el.name.match(/\[\]/)) {
				if (params[el.name] == undefined) {
					params[el.name] = [];
				}
				params[el.name].push(el.value);
			} else {
				params[el.name] = el.value;
			}
		});
		var form_action = $(this._id).getAttribute('action');
		var url = "";
		if (form_action !== undefined && form_action !== null) {
			if (typeof(form_action) == "object") {
				url = form_action.value;
			}
			else if (typeof(form_action) == "string") {
				url = form_action;
			}
		}
		if (url == "") {
			url = top.location.href;
		}
		var Validator = this;		// Store a reference for the success function
		new Ajax.Request(url,{
			method: 'post',
			parameters: params,
			requestHeaders: Biscuit.Ajax.RequestHeaders(this.request_type),
			onSuccess: function(transport) {
				var resp = transport.responseText;
				if (resp == '+OK') {
					if (Validator.use_ping_timer) {
						Biscuit.Session.KeepAlive.start_ping_timer();
					}
					Validator._submitter(Validator._id);
				}
				else {
					alert(resp);
					Biscuit.Crumbs.Forms.EnableSubmit(Validator._id);
					Biscuit.Crumbs.HideThrobber(Validator.throbber_id);
				}
			}
		});
	},
	Submit: function() {
		// Submit the form over AJAX
		var params = Form.serializeElements($(this._id).getElements(),true);
		var form_action = $(this._id).getAttribute('action');
		if (form_action == "" || form_action === undefined || form_action === null) {
			form_action = top.location.href;
		}
		if (this.complete_callback !== null) {
			var my_callback = this.complete_callback;
		}
		else {
			var my_callback = function() {
				// noop
			}
		}
		new Ajax.Updater(this.update_div,form_action,{
			method: 'post',
			parameters: params,
			requestHeaders: Biscuit.Ajax.RequestHeaders('update'),
			evalScripts: true,			// It's up to the server-side script to send back the appropriate redirect request or update callback
			onComplete: my_callback
		});
	}
});

Biscuit.Crumbs.ShowThrobber = function(throbber_id) {
	if (throbber_id === undefined || throbber_id === null) {
		var throbber_id = 'throbber';
	}
	if (window.top.$(throbber_id)) {
		window.top.$(throbber_id).show();
	}
}

Biscuit.Crumbs.HideThrobber = function(throbber_id) {
	if (throbber_id === undefined || throbber_id === null) {
		var throbber_id = 'throbber';
	}
	if (window.top.$(throbber_id)) {
		window.top.$(throbber_id).hide();
	}
}

Biscuit.Crumbs.Forms.btn_text = {}

Biscuit.Crumbs.Forms.DisableSubmit = function(form_id) {
	// Cycle through all elements with the classname of "SubmitButton" and disable them
	window.top.$$('#'+form_id+' .SubmitButton').each(function(el) {
		if (Biscuit.Crumbs.Forms.btn_text[form_id] == undefined) {
			Biscuit.Crumbs.Forms.btn_text[form_id] = el.value;
		}
		el.disabled = true;
		el.value = "Hang on...";
	});
}

Biscuit.Crumbs.Forms.EnableSubmit = function(form_id) {
	// Cycle through all elements with the classname of "SubmitButton" and enable them
	window.top.$$('#'+form_id+' .SubmitButton').each(function(el) {
		el.disabled = false;
		el.value = Biscuit.Crumbs.Forms.btn_text[form_id];
	});
}

Biscuit.Crumbs.Forms.Submit = function(form_id) {
	// Submit the form normally
	$(form_id).submit();
}

Biscuit.Crumbs.Sortable.create = function(container,tag,post_to,scroll,options) {
	if (options.hoverclass !== undefined) {
		$$('#'+container+' '+tag).each(function(el) {
			el.observe("mouseover",function(event) {
				el.addClassName(options.hoverclass);
			});
			el.observe("mouseout",function(event) {
				el.removeClassName(options.hoverclass);
			});
		});
	}
	var array_name = options.array_name || 'sort_array';
	var change_func = options.onChange || function() {};
	var update_func = options.onUpdate || function() {};
	if (options.constraint !== undefined) {
		var constraint = options.constraint;
	} else {
		var constraint = 'vertical';
	}
	var handles = options.handles || false;
	var only = options.only || null;
	var overlap = options.overlap || 'vertical';
	var action = options.action || 'resort';
	Position.includeScrollOffsets = true;
	Sortable.create(container,{
		tag: tag,
		only: only,
		handles: handles,
		scroll: scroll,
		constraint: constraint,
		overlap: overlap,
		onChange: change_func,
		onUpdate: function(el) {
			var params = Sortable.serialize(container,{
				name: array_name
			}).toQueryParams();
			new Ajax.Request(post_to+'/'+action,{
				method: 'get',
				parameters: params,
				requestHeaders: Biscuit.Ajax.RequestHeaders('server_action')
			});
			update_func();
		}
	});
}

String.prototype.stripSlashes = function(str) {
	var str=this.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
}

Array.prototype.inArray = function(value) {
	for (i=0; i < this.length; i++) {
		if (this[i] == value) {
			return true;
			break;
		}
	}
	return false;
}
