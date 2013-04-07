//
// Biscuit Javascript Framework - Web 2.0 client-side compliment to the PHP back end.
// NOTICE: This framework requires prototype JS. If you choose to use it in your project,
// ensure that it is included after prototype. You can either include prototype manually, or install
// the Web 2.0 plugin.
//
// Author: Peter Epp

var Biscuit = {
	Version: '1.2.1',
	Debug: false,
	page_last_updated: null,
	page_etag: null
};

Biscuit.Console = {
	log: function(message) {
		if (window.console != undefined && window.console.log != undefined && Biscuit.Debug) {
			console.log(message);
		}
	}
};

Biscuit.Session = {
	KeepAlive: {
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
};

Biscuit.Crumbs = {
	ShowThrobber: function(throbber_id) {
		if (throbber_id === undefined || throbber_id === null) {
			var throbber_id = 'throbber';
		}
		if (window.top.$(throbber_id)) {
			window.top.$(throbber_id).show();
		}
	},
	HideThrobber: function(throbber_id) {
		if (throbber_id === undefined || throbber_id === null) {
			var throbber_id = 'throbber';
		}
		if (window.top.$(throbber_id)) {
			window.top.$(throbber_id).hide();
		}
	}
};

Biscuit.Ajax = {
	RequestHeaders: function(type) {
		return {
			'X-Biscuit-Ajax-Request': 'true',
			'X-Biscuit-Request-Type': type
		} 
	},
	LoginHandler: function(login_form_id,container_id) {
		if (container_id === undefined) {
			container_id = login_form_id;
		}
		$(login_form_id).observe("submit",function(event) {
			Event.stop(event);
			Biscuit.Crumbs.Forms.DisableSubmit(login_form_id);
			var params = Form.serializeElements($(login_form_id).getElements(),true);
			var form_action = $(login_form_id).readAttribute('action');
			var url = "";
			if (form_action !== undefined && form_action !== null && form_action != "") {
				url = form_action;
			} else {
				url = top.location.href;
			}
			new Ajax.Request(url,{
				parameters: params,
				requestHeaders: Biscuit.Ajax.RequestHeaders('login'),
				onSuccess: function(transport) {
					$(login_form_id).submit();
				},
				onFailure: function(transport) {
					Biscuit.Crumbs.Forms.EnableSubmit(login_form_id);
					new Effect.Shake(login_form_id,{distance: 12, duration: 0.3});
					new Effect.Highlight(login_form_id,{startcolor: '#ea4e2a'});
				}
			});
		})
	}
};

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
};

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
				$$('#'+Validator._id+' p').each(function(el) {
					el.removeClassName('error');
				});
				if (Validator.use_ping_timer) {
					Biscuit.Session.KeepAlive.start_ping_timer();
				}
				Validator._submitter(Validator._id);
			},
			onFailure: function(transport) {
				var msg = transport.responseJSON.message;
				var bad_fields = transport.responseJSON.invalid_fields;
				$$('#'+Validator._id+' *').each(function(el) {
					el.removeClassName('error');
				});
				Validator.HilightBadFields(bad_fields);
				if (msg != "") {
					alert(msg);
				}
				$$('input.error, select.error, textarea.error')[0].focus();	// Focus on the first erroneous field
				Biscuit.Crumbs.Forms.EnableSubmit(Validator._id);
				Biscuit.Crumbs.HideThrobber(Validator.throbber_id);
			}
		});
	},
	HilightBadFields: function(bad_fields) {
		if (bad_fields.length > 0) {
			Biscuit.Console.log("Highlight invalid fields:");
			for (var i in bad_fields) {
				curr_field = bad_fields[i];
				if (typeof(curr_field) == "string") {
					Biscuit.Console.log(bad_fields[i]);
					var bad_field = $('attr_'+bad_fields[i]);
					if (bad_field) {
						bad_field.addClassName("error");
						var hilight_field = bad_field.previous('label');
						if (!hilight_field) {
							hilight_field = bad_field
						}
						hilight_field.addClassName("error");
					}
				}
			}
		}
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
				// Noop
			}
		}
		new Ajax.Updater(this.update_div,form_action,{
			method: 'post',
			parameters: params,
			requestHeaders: Biscuit.Ajax.RequestHeaders('update'),
			evalScripts: true,			// It's up to the server-side script to send back the appropriate redirect or update callback
			onComplete: my_callback
		});
	}
});

Biscuit.Crumbs.Forms = {
	btn_text: {},
	DisableSubmit: function(form_id) {
		// Cycle through all elements with the classname of "SubmitButton" and disable them
		window.top.$$('#'+form_id+' .SubmitButton').each(function(el) {
			if (Biscuit.Crumbs.Forms.btn_text[form_id] == undefined) {
				Biscuit.Crumbs.Forms.btn_text[form_id] = el.value;
			}
			el.disabled = true;
			el.value = "Hang on...";
		});
	},
	EnableSubmit: function(form_id) {
		// Cycle through all elements with the classname of "SubmitButton" and enable them
		window.top.$$('#'+form_id+' .SubmitButton').each(function(el) {
			el.disabled = false;
			el.value = Biscuit.Crumbs.Forms.btn_text[form_id];
		});
	},
	Submit: function(form_id) {
		// Submit the form normally
		$(form_id).submit();
	},
	AddDeleteConfirmationHandlers: function() {
		$$('.delete-button').each(function(el) {
			el.observe('click', function(event) {
				var element = Event.element(event);
				var confirmation_message = "Are you sure you want to delete ";
				if (element.rel != undefined && element.rel != '') {
					var rel_bits = element.rel.split('|');
					if (rel_bits.length > 1) {
						var item_type = rel_bits[0];
						var item_title = rel_bits[1];
						confirmation_message += "the "+item_type+" \""+item_title+"\"?";
						if (rel_bits.length > 2) {
							confirmation_message += "\n\n"+rel_bits[2];
						}
					} else {
						confirmation_message += rel_bits[0]+'?';
					}
				} else {
					confirmation_message += "the selected item?";
				}
				confirmation_message += "\n\nThis action cannot be undone.";
				if (confirm(confirmation_message)) {
					var href = element.href;
					if (href.match(/\?/)) {
						href += '&';
					} else {
						href += '?';
					}
					href += 'delete_confirmed=1';
					element.href = href;
				} else {
					Event.stop(event);
				}
			});
		});
	}
};

Biscuit.Crumbs.Sortable = {
	create: function(container,tag,post_to,scroll,options) {
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
		var handle = options.handle || false;
		var only = options.only || null;
		var overlap = options.overlap || 'vertical';
		var action = options.action || 'resort';
		var throbber_id = options.throbber_id || null;
		Position.includeScrollOffsets = true;
		Sortable.create(container,{
			tag: tag,
			only: only,
			handle: handle,
			scroll: scroll,
			constraint: constraint,
			overlap: overlap,
			onChange: change_func,
			onUpdate: function(el) {
				var params = Sortable.serialize(container,{
					name: array_name
				}).toQueryParams();
				Biscuit.Crumbs.ShowThrobber(throbber_id);
				new Ajax.Request(post_to+'/'+action,{
					method: 'get',
					parameters: params,
					requestHeaders: Biscuit.Ajax.RequestHeaders('server_action'),
					onComplete: function() {
						Biscuit.Crumbs.HideThrobber(throbber_id);
					},
					onSuccess: update_func
				});
			}
		});
	}
};

String.prototype.stripSlashes = function(str) {
	var str=this.replace(/\\'/g,'\'');
	str=str.replace(/\\"/g,'"');
	str=str.replace(/\\\\/g,'\\');
	str=str.replace(/\\0/g,'\0');
	return str;
};

Array.prototype.inArray = function(value) {
	for (i=0; i < this.length; i++) {
		if (this[i] == value) {
			return true;
			break;
		}
	}
	return false;
};

$(document).observe('dom:loaded',function() {
	// Always try to add delete confirmation handlers to all delete buttons on any page
	Biscuit.Crumbs.Forms.AddDeleteConfirmationHandlers();
});