//
// Biscuit Javascript Framework - Web 2.0 client-side compliment to the PHP back end.
// Requires jQuery and jQuery UI
//
// Author: Peter Epp

var Biscuit = {
	Version: '1.2.5',
	Debug: false,
	Language: 'en_CA'
};

Biscuit.Console = {
	log: function(message) {
		if (window.console != undefined && window.console.log != undefined && Biscuit.Debug) {
			console.log(message);
		}
	}
};

Biscuit.Session = {
	remaining_time: null,
	check_timer: null,
	decrement_timer: null,
	display_remaining_timer: null,
	refresh_dialog_obj: null,
	KeepAlive: {
		ping: function() {
			var currTime = new Date();
			var now = currTime.getTime();
			Biscuit.Ajax.Request('/ping/'+now,'server_action',{
				type: 'ping'
			});
		}
	},
	InitTracker: function() {
		if (this.remaining_time != null) {
			// Check session expiry every second
			this.check_timer = setTimeout('Biscuit.Session.CheckExpiry();',1000);
			this.decrement_timer = setTimeout('Biscuit.Session.DecrementTime();',1000);
		}
	},
	CheckExpiry: function() {
		if (this.remaining_time <= 120) {
			// 2 minute warning
			this.ExpiryRefreshHandler();
		} else {
			this.check_timer = setTimeout('Biscuit.Session.CheckExpiry();',1000);
		}
	},
	DecrementTime: function() {
		// Decrease by one second
		if (this.remaining_time >= 0) {
			this.remaining_time -= 1;
			this.decrement_timer = setTimeout('Biscuit.Session.DecrementTime();',1000);
		}
	},
	ExpiryRefreshHandler: function() {
		this.refresh_already_handled = true;
		clearTimeout(this.check_timer);	// Just to make sure
		var message = '<h4><strong>'+__('login_expiry')+'</strong></h4>';
		var remaining_time = '<strong>'+this.FormattedRemainingTime()+'</strong>';
		message += '<p id="session-time-remaining">'+__('login_time_remaining',[remaining_time])+'</p>';
		this.display_remaining_timer = setTimeout('Biscuit.Session.UpdateRemainingTimeDisplay();',1005);
		this.refresh_dialog_obj = Biscuit.Crumbs.Confirm(message,function() {
			clearTimeout(Biscuit.Session.display_remaining_timer);
			var currTime = new Date();
			var now = currTime.getTime();
			Biscuit.Ajax.Request('/?'+now,'session_refresh',{
				success: function(data) {
					Biscuit.Session.remaining_time = data.remaining_session_time;
					Biscuit.Session.check_timer = setTimeout('Biscuit.Session.CheckExpiry();',1000);
				},
				error: function() {
					Biscuit.Crumbs.Alert('<h4><strong>'+__('session_refresh_fail')+'</strong></h4>');
				}
			});
		},__('confirm_session_extend'),function() {
			clearTimeout(Biscuit.Session.display_remaining_timer);
			clearTimeout(Biscuit.Session.decrement_timer);
			Biscuit.Session.Logout(false);
		},__('cancel_session_extend'));
	},
	UpdateRemainingTimeDisplay: function() {
		if (this.remaining_time < 0) {
			this.refresh_dialog_obj.dialog('close');
			this.refresh_dialog_obj = null;
			this.LoginDialog();
		} else {
			var remaining_time = '<strong>'+this.FormattedRemainingTime()+'</strong>';
			$('#session-time-remaining').html(__('login_time_remaining',[remaining_time]));
			this.display_remaining_timer = setTimeout('Biscuit.Session.UpdateRemainingTimeDisplay();',1000);
		}
	},
	LoginDialog: function() {
		// Open a new dialog with a login form:
		var loading_dialog = Biscuit.Crumbs.LoadingBox(__('login_form_retrieving'));
		Biscuit.Ajax.Request('/login','update',{
			data: {
				'login_dialog_request': 1
			},
			type: 'get',
			success: function(html) {
				loading_dialog.dialog('close');
				Biscuit.Crumbs.Confirm('<h4 class="attention"><strong>'+__('login_dialog_title')+'</strong></h4>'+html,function() {
					var pending_login_dialog = Biscuit.Crumbs.LoadingBox(__('checking_credentials'));
					var params = $('#login-form').serialize();
					params += '&login_dialog_request=1';
					Biscuit.Ajax.Request('/login','login',{
						data: params,
						type: 'post',
						success: function(data) {
							pending_login_dialog.dialog('close');
							Biscuit.Session.remaining_time = data.remaining_session_time;
							this.decrement_timer = setTimeout('Biscuit.Session.DecrementTime();',1000);
							Biscuit.Session.check_timer = setTimeout('Biscuit.Session.CheckExpiry();',1000);
							Biscuit.Crumbs.Alert(__('login_success'));
						},
						error: function() {
							pending_login_dialog.dialog('close');
							Biscuit.Crumbs.Alert(__('invalid_credentials'),__('Notice'),function() {
								Biscuit.Session.LoginDialog();
							});
						}
					});
				},__('login'),function() {
					top.location.href = top.location.href;
				});
			},
			error: function() {
				loading_dialog.dialog('close');
				Biscuit.Crumbs.Alert(__('login_form_retrieval_fail'));
			}
		});
	},
	FormattedRemainingTime: function() {
		var minutes = 0;
		var seconds = 0;
		if (this.remaining_time >= 60) {
			minutes = Math.floor(this.remaining_time/60);
			seconds = this.remaining_time-(minutes*60);
		} else {
			minutes = 0;
			seconds = this.remaining_time;
		}
		if (minutes < 10) {
			minutes = '0'+minutes;
		}
		if (seconds < 10) {
			seconds = '0'+seconds;
		}
		return minutes+':'+seconds;
	},
	Logout: function(is_auto) {
		var pathname = top.location.pathname;
		if (pathname == '/') {
			pathname = '';
		}
		if (pathname.substr(0,1) != '/' && pathname.length > 0) {
			pathname = '/'+pathname;
		}
		top.location.href = pathname+'/logout?js_auto_logout='+(is_auto ? 1 : 0);
	}
};

Biscuit.Crumbs = {
	ShowThrobber: function(throbber_id) {
		if (throbber_id === undefined || throbber_id === null) {
			var throbber_id = 'throbber';
		}
		if (window.top.$('#'+throbber_id).length > 0) {
			window.top.$('#'+throbber_id).show();
		}
	},
	HideThrobber: function(throbber_id) {
		if (throbber_id === undefined || throbber_id === null) {
			var throbber_id = 'throbber';
		}
		if (window.top.$('#'+throbber_id).length > 0) {
			window.top.$('#'+throbber_id).hide();
		}
	},
	Alert: function(message, title, close_callback) {
		// Display a jQuery UI modal alert box with an "Ok" button
		var currTime = new Date();
		var now = currTime.getTime();
		var content_id = 'biscuit-alert-content-'+now;
		$('body').append($('<div>')
			.attr('id',content_id)
			.css({'display': 'none'})
			.html(message)
		);
		// If the window is scrolled down when the dialog opens it scrolls to the top on open but the dialog will try to center based
		// on where the viewport was scrolled to before it opened. This results in the dialog being off-screen. To avoid this issue, we
		// force scroll the window to the top prior to opening the dialog, which then always gets position 30px from the top
		window.scrollTo(0,0);
		if (title == undefined) {
			var title = __('notice');
		}
		var dismiss_button_label = __('dismiss_button_label');
		var buttons = {};
		buttons[dismiss_button_label] = function() {
			$(this).dialog('close');
		}
		return $('#'+content_id).dialog({
			modal: true,
			title: title,
			width: 560,
			position: ['center',30],
			resizable: false,
			close: function(event, ui) {
				$('#'+content_id).remove();
				if (close_callback != undefined && typeof(close_callback) == 'function') {
					close_callback();
				}
			},
			buttons: buttons
		});
	},
	LoadingBox: function(message) {
		// Display a jQuery UI modal dialog box with no buttons. Script that calls this must close it when done.
		var currTime = new Date();
		var now = currTime.getTime();
		var content_id = 'biscuit-alert-content-'+now;
		if (message == undefined) {
			var message = __('wait_while_loading');
		}
		message = '<h4 style="text-align: center"><strong>'+message+'</strong></h4><p style="text-align: center"><img src="/framework/themes/admin/images/throbber-light-lrg.gif" alt="'+__('wait_while_loading')+'"></p>';
		$('body').append($('<div>')
			.attr('id',content_id)
			.css({'display': 'none'})
			.html(message)
		);
		// If the window is scrolled down when the dialog opens it scrolls to the top on open but the dialog will try to center based
		// on where the viewport was scrolled to before it opened. This results in the dialog being off-screen. To avoid this issue, we
		// force scroll the window to the top prior to opening the dialog, which then always gets position 30px from the top
		window.scrollTo(0,0);
		return $('#'+content_id).dialog({
			modal: true,
			title: __('wait_dialog_title'),
			width: 560,
			position: ['center',30],
			resizable: false,
			close: function(event, ui) {
				$('#'+content_id).remove();
			},
			buttons: {}
		});
	},
	Confirm: function(message,confirm_callback,primary_action,cancel_callback,cancel_action_label) {
		// Display a jQuery UI modal alert box with an "Ok" button
		var currTime = new Date();
		var now = currTime.getTime();
		var content_id = 'biscuit-confirmation-content-'+now;
		$('body').append($('<div>')
			.attr('id',content_id)
			.css({'display': 'none'})
			.html(message)
		);
		// If the window is scrolled down when the dialog opens it scrolls to the top on open but the dialog will try to center based
		// on where the viewport was scrolled to before it opened. This results in the dialog being off-screen. To avoid this issue, we
		// force scroll the window to the top prior to opening the dialog, which then always gets position 30px from the top
		window.scrollTo(0,0);
		var buttons = {};
		if (primary_action == undefined) {
			var primary_action = __('confirm_button_label');
		}
		if (cancel_action_label == undefined) {
			var cancel_action_label = __('cancel_button_label');
		}
		buttons[primary_action] = function() {
			confirm_callback();
			$(this).dialog('close');
		}
		buttons[cancel_action_label] = function() {
			if (cancel_callback != undefined && typeof(cancel_callback) == 'function') {
				cancel_callback();
			}
			$(this).dialog('close');
		}
		return $('#'+content_id).dialog({
			modal: true,
			closeOnEscape: false,
			title: __('confirm_box_title'),
			width: 560,
			position: ['center',30],
			resizable: false,
			open: function(event, ui) {
				$('#'+content_id).prev().find('.ui-dialog-titlebar-close').hide();
				$('#'+content_id).next('.ui-dialog-buttonpane').find('button:first').addClass('attention');
			},
			close: function(event, ui) {
				$('#'+content_id).remove();
			},
			buttons: buttons
		});
	}
};

Biscuit.Ajax = {
	Request: function(url,biscuit_request_type,options) {
		this.update_container_id = null;
		var proceed = true;
		options.url = url;
		if (options.beforeSend != undefined) {
			proceed = false;
			Biscuit.Console.log('You must NOT provide a custom beforeSend() function to the Ajax request function.');
		}
		if (biscuit_request_type == 'update' && options.update_container_id != undefined && options.complete == undefined) {
			this.update_container_id = '#'+options.update_container_id;
			options.update_container_id = null;		// We don't want to pass this value to the jQuery ajax request function
			options.complete = function(xhr,text_status) {
				$(Biscuit.Ajax.update_container_id).html(xhr.responseText);
				if (Biscuit.Ajax.complete_callback != null) {
					Biscuit.Ajax.complete_callback();
				}
			}
		}
		options.beforeSend = function(xhr) {
			Biscuit.Ajax.SetRequestHeaders(biscuit_request_type,xhr);
		}
		if (proceed) {
			jQuery.ajax(options);
		}
	},
	SetRequestHeaders: function(biscuit_request_type,xhr) {
		xhr.setRequestHeader('X-Biscuit-Ajax-Request', 'true');
		xhr.setRequestHeader('X-Biscuit-Request-Type', biscuit_request_type);
	},
	LoginHandler: function(login_form_id,container_id) {
		if (container_id == undefined) {
			var container_id = login_form_id;
		}
		$('#'+login_form_id).submit(function() {
			Biscuit.Crumbs.Forms.DisableSubmit(login_form_id);
			var params = $(this).serialize();
			var form_action = $(this).attr('action');
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
			Biscuit.Ajax.Request(url,'login',{
				data: params,
				type: 'post',
				success: function(data,text_status,xhr) {
					top.location.href = data.redirect_page;
				},
				error: function(xhr,text_status) {
					Biscuit.Crumbs.Forms.EnableSubmit(login_form_id);
					if (xhr.status == 400 || xhr.status == 406) {
						var response = jQuery.parseJSON(xhr.responseText);
						var message = response.message;
						// Higlight and alert:
						$('#'+container_id).effect('highlight', {color: '#ea4e2a'}, 800, function() {
							Biscuit.Crumbs.Alert(message,__('login_failed'),function() {
								$('#attr_username').focus();
							});
						});
					} else {
						var err_msg = xhr.status+" - "+xhr.statusText;
						Biscuit.Crumbs.Alert(__('uncaught_exception',[err_msg]));
					}
				}
			});
			return false;
		});
	},
	DefaultErrorHandler: function(e, xhr, settings, exception) {
		// Throw up an alert about an Ajax error if no error handler function was defined for the request
		if (typeof(settings.error) != 'function' && xhr.status != 404 && xhr.status != 410) {
			var err_msg = xhr.status+" - "+xhr.statusText;
			Biscuit.Crumbs.Alert(__('uncaught_exception',[err_msg]));
		}
	},
	DefaultSetup: function() {
		$.ajaxSetup({
			beforeSend: function(xhr) {
				Biscuit.Ajax.SetRequestHeaders('update',xhr);
			}
		});
		// Register a default Ajax Error handler. We do it this way rather than providing an "error" property above because this way the default kicks in if no
		// "error" property exists as a function of the request object, which is how it knows whether or not to defer to the default.
		$(document).ajaxError(function(e, xhr, settings, exception) {
			Biscuit.Ajax.DefaultErrorHandler(e, xhr, settings, exception);
		});
	}
};

Biscuit.Ajax.FormValidator = function(form_id,options) {
	if (options === undefined) {
		var options = {}
	}
	if ($(form_id) == null) {
		Biscuit.Crumbs.Alert('Cannot find form: '+form_id);
		return;
	}
	this._id = form_id;
	if (options.ajax_submit === undefined || options.ajax_submit === false) {
		this._submitter = Biscuit.Crumbs.Forms.Submit;
	}
	else {
		if (options.ajax_submit === true && (options.update_div === undefined && options.custom_ajax_submitter === undefined && options.complete_callback === undefined)) {
			Biscuit.Crumbs.Alert('<h4><strong>Form is mis-configured! Ajax submission requires at least one of:</strong></h4><ul><li>ID of a DIV to update with response message</li><li>A callback to run upon successful completion</li><li>A custom submit handler function</li></ul>');
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
	this.Validate();
};

Biscuit.Ajax.FormValidator.prototype = {
	Validate: function() {
		Biscuit.Crumbs.Forms.DisableSubmit(this._id);
		Biscuit.Crumbs.ShowThrobber(this.throbber_id);
		var params = $('#'+this._id).serialize();
		// serialize doesn't include file fields, so add those on now:
		$('#'+this._id+' input[type=file]').each(function() {
			params += "&"+escape($(this).attr('name'))+"="+escape($(this).val());
		});
		var url = $('#'+this._id).attr('action');
		if (url == "") {
			url = top.location.href;
		}
		var Validator = this;		// Store a reference for the success function
		Biscuit.Ajax.Request(url,this.request_type,{
			data: params,
			type: 'post',
			success: function(data,text_status,xhr) {
				$('#'+Validator._id+' *').removeClass('error');
				Validator._submitter(Validator._id);
			},
			error: function(xhr,text_status) {
				var response = jQuery.parseJSON(xhr.responseText);
				if (xhr.status == 400) {
					Biscuit.Crumbs.Alert(response.message, "Errors Occurred");
				} else if (xhr.status == 406) {
					var messages = response.messages;
					var bad_fields = response.invalid_fields;
					$('#'+Validator._id+' *').removeClass('error');
					Validator.HilightBadFields(bad_fields);
					if (messages.length > 0) {
						// Compile the error messages from the json response into a nice HTML for the alert dialog:
						var message = '<h4><strong>'+__('please_make_corrections')+'</strong></h4><ul>';
						for (var i=0;i < messages.length;i++) {
							message += '<li>'+messages[i]+'</li>';
						}
						message += '</ul>';
						// Display the dialog:
						Biscuit.Crumbs.Alert(message, __('error_box_title'), function() {
							if (bad_fields.length > 0) {
								$('input.error, select.error, textarea.error, .error input').get(0).focus();	// Focus on the first erroneous field, if any
							}
						});
					}
				} else {
					var err_msg = xhr.status+" - "+xhr.statusText;
					Biscuit.Crumbs.Alert(__('uncaught_exception',[err_msg]));
				}
				Biscuit.Crumbs.Forms.EnableSubmit(Validator._id);
				Biscuit.Crumbs.HideThrobber(Validator.throbber_id);
			}
		})
	},
	HilightBadFields: function(bad_fields) {
		if (bad_fields.length > 0) {
			Biscuit.Console.log("Highlight invalid fields:");
			for (var i in bad_fields) {
				curr_field = bad_fields[i];
				if (typeof(curr_field) == "string") {
					Biscuit.Console.log(bad_fields[i]);
					var bad_field = $('#attr_'+bad_fields[i]);
					if (bad_field) {
						bad_field.addClass("error");
						var hilight_field = $(bad_field.parent().children('label').get(0));
						if (hilight_field.length == 0) {
							hilight_field = bad_field
						}
						hilight_field.addClass("error");
					}
				}
			}
		}
	},
	Submit: function() {
		// Submit the form over AJAX
		var params = $('#'+this._id).serialize();
		var url = $('#'+this._id).attr('action');
		if (url == "") {
			url = top.location.href;
		}
		if (this.complete_callback != undefined) {
			var my_callback = this.complete_callback;
		}
		if (this.update_div != undefined) {
			var my_update_div = this.update_div;
		}
		Biscuit.Ajax.Request(url,'update',{
			data: params,
			type: 'post',
			success: function(response_value) {
				if (my_update_div != undefined) {
					$('#'+my_update_div).html(response_value);
				}
				if (my_callback != undefined && typeof(my_callback) == 'function') {
					my_callback(response_value);
				}
			},
			error: function(xhr, text_status) {
				var response = jQuery.parseJSON(xhr.responseText);
				Biscuit.Ajax.Alert("<h4><strong>"+__('error_occurred')+"</strong></h4><p>"+response.message+"</p>");
			}
		});
	}
}

Biscuit.Crumbs.Forms = {
	btn_text: {},
	DisableSubmit: function(form_id) {
		// Cycle through all elements with the classname of "SubmitButton" and disable them
		window.top.$('#'+form_id+' .SubmitButton').each(function() {
			if (Biscuit.Crumbs.Forms.btn_text[form_id] == undefined) {
				Biscuit.Crumbs.Forms.btn_text[form_id] = $(this).val();
			}
			$(this).blur();
			$(this).attr('disabled','disabled');
			$(this).addClass('working');
			$(this).val(" ");
		});
	},
	EnableSubmit: function(form_id) {
		// Cycle through all elements with the classname of "SubmitButton" and enable them
		window.top.$('#'+form_id+' .SubmitButton').each(function() {
			$(this).attr('disabled','');
			$(this).removeClass('working');
			$(this).val(Biscuit.Crumbs.Forms.btn_text[form_id]);
		});
	},
	Submit: function(form_id) {
		// Submit the form normally
		// First ensure that the jQuery submit event handler is unbound, otherwise it'll be called instead of normal submit
		$('#'+form_id).unbind('submit');
		// Now we can submit
		$('#'+form_id).submit();
	},
	AddDeleteConfirmationHandlers: function() {
		$('.delete-button').click(function() {
			var confirmation_message = '<h4><strong>';
			if ($(this).attr('rel') != undefined && $(this).attr('rel') != '') {
				var rel_bits = $(this).attr('rel').split('|');
				if (rel_bits.length > 1) {
					var item_type = rel_bits[0];
					var item_title = rel_bits[1];
					var del_item_name = "the "+item_type+" \""+item_title+"\"";
					confirmation_message += __('delete_confirm_text',[del_item_name])+"</strong></h4>";
					if (rel_bits.length > 2) {
						confirmation_message += "<p>"+rel_bits[2]+"</p>";
					}
					var delete_button_label = __('delete_button_label_custom',[item_title]);
				} else {
					confirmation_message += __('delete_confirm_text',[rel_bits[0]])+'</strong></h4>';
					var delete_button_label = __('delete_button_label_custom',[rel_bits[0]]);
				}
			} else {
				confirmation_message += __('delete_confirm_text',['the selected item'])+"</strong></h4>";
				var delete_button_label = __('delete_button_label');
			}
			confirmation_message += "<p>"+__('cannot_undo_warning')+"</p>";
			var target_url = $(this).attr('href');
			if (target_url.match(/\?/)) {
				target_url += '&';
			} else {
				target_url += '?';
			}
			target_url += 'delete_confirmed=1';
			Biscuit.Crumbs.Confirm(confirmation_message, function() {
				top.location.href = target_url;
			}, delete_button_label);
			return false;
		});
	},
	AddValidation: function(form_id) {
		// Run validation on submit of specified form after a quarter-second delay. Delay ensures scripts like Tiny MCE get to do their thing before the form is submitted.
		// This works around issues where Tiny MCE field values get submitted empty when they are not.
		$('#'+form_id).submit(function(){
			setTimeout("new Biscuit.Ajax.FormValidator('"+form_id+"');",250);
			return false;
		});
		
	}
};

Biscuit.Crumbs.Sortable = {
	create: function(selector,post_to,options) {
		if (window.sortable_request_token == undefined) {
			Biscuit.Console.log("No request token provided for sortable, aborting");
			return;
		}
		if (options.hoverclass !== undefined) {
			$('#'+container+' '+tag).mouseover(function() {
				$(this).addClass(options.hoverclass);
			});
			$('#'+container+' '+tag).mouseover(function() {
				$(this).removeClass(options.hoverclass);
			});
		}
		var array_name = options.array_name || 'sort_array';
		var update_func = options.onUpdate || function() {};
		var complete_func = options.onFinish || function() {};
		var error_func = options.onError || function() {};
		var axis = options.axis || 'y';
		var handle = options.handle || false;
		var action = options.action || 'resort';
		var throbber_id = options.throbber_id || null;
		$(selector).sortable({
			tolerance: 'pointer',
			handle: handle,
			axis: axis,
			update: function(event,ui) {
				var sortable_id = this.id;
				update_func(sortable_id);
				var params = $('#'+this.id).sortable('serialize',{key: array_name+'[]'});
				params += '&request_token='+window.sortable_request_token;
				var url = post_to+'/'+action;
				Biscuit.Crumbs.ShowThrobber(throbber_id);
				Biscuit.Ajax.Request(url,'server_action',{
					data: params,
					type: 'post',
					complete: function() {
						Biscuit.Crumbs.HideThrobber(throbber_id);
						complete_func(sortable_id);
					}
				});
			}
		});
		$(selector).disableSelection();
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

// Translation shortcut
var __ = $.i18n.prop;

$(document).ready(function() {
	if (typeof(jQuery) == undefined) {
		alert("jQuery is required for Framework scripts to function!");
	} else {
		// Always try to add delete confirmation handlers to all delete buttons on any page
		Biscuit.Crumbs.Forms.AddDeleteConfirmationHandlers();
	}
	// Initialize default setup for Ajax requests
	Biscuit.Ajax.DefaultSetup();
	// Initialize internationalization:
	$.i18n.properties({
		name:     'Messages',
		path:     '/js/bundles/',
		mode:     'both',
		language: Biscuit.Language
	});
});
