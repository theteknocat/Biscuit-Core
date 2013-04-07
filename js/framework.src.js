//
// Biscuit Javascript Framework - Web 2.0 client-side compliment to the PHP back end.
// Requires jQuery and jQuery UI
//
// Author: Peter Epp

var Biscuit = {
	Version: '1.3.5',
	Debug: false,
	Language: 'en_CA',
	Console: {
		log: function(message) {
			if (window.console != undefined && window.console.log != undefined && Biscuit.Debug) {
				console.log(message);
			}
		}
	},
	Cookie: function(key, value, options) {
		if (arguments.length > 1 && String(value) !== "[object Object]") {
			// key and at least value given, set cookie...
			options = jQuery.extend({}, options);

			if (value === null || value === undefined) {
				options.expires = -1;
			}

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setDate(t.getDate() + days);
			}

			value = String(value);

			return (document.cookie = [
				encodeURIComponent(key), '=',
				options.raw ? value : encodeURIComponent(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path ? '; path=' + options.path : '',
				options.domain ? '; domain=' + options.domain : '',
				options.secure ? '; secure' : ''
				].join(''));
		}

		// key and possibly options given, get cookie...
		options = value || {};
		var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
		return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
	},
	Crumbs: {
		ApplyCommonButtonStyles: function(container_selector) {
			if (container_selector == undefined || typeof(container_selector) != 'string') {
				var container_selector = 'body';
			}
			$(container_selector+' .delete-button').button({
				icons: {
					primary: 'ui-icon-trash'
				}
			});
			$(container_selector+' .new-button').button({
				icons: {
					primary: 'ui-icon-plus'
				}
			});
			$(container_selector+' .edit-button').button({
				icons: {
					primary: 'ui-icon-pencil'
				}
			});
			$(container_selector+' .cancel-button').button({
				icons: {
					primary: 'ui-icon-cancel'
				}
			});
			$(container_selector+' .file-button').button({
				icons: {
					primary: 'ui-icon-document'
				}
			});
			$(container_selector+' .image-button').button({
				icons: {
					primary: 'ui-icon-image'
				}
			});
			$(container_selector+' .media-button').button({
				icons: {
					primary: 'ui-icon-video'
				}
			});
			$(container_selector+' .save-button').button({
				icons: {
					primary: 'ui-icon-disk'
				}
			});
			$(container_selector+' .prev-button').button({
				icons: {
					primary: 'ui-icon-triangle-1-w'
				}
			});
			$(container_selector+' .next-button').button({
				icons: {
					secondary: 'ui-icon-triangle-1-e'
				}
			});
			$(container_selector+' .prev-button-bigjump').button({
				icons: {
					primary: 'ui-icon-seek-prev'
				}
			});
			$(container_selector+' .next-button-bigjump').button({
				icons: {
					secondary: 'ui-icon-seek-next'
				}
			});
			$(container_selector+' .seek-first-button').button({
				icons: {
					primary: 'ui-icon-seek-first'
				}
			});
			$(container_selector+' .seek-last-button').button({
				icons: {
					secondary: 'ui-icon-seek-end'
				}
			});
			$(container_selector+' .documents-button').button({
				icons: {
					primary: 'ui-icon-document'
				}
			});
			$(container_selector+' .images-button').button({
				icons: {
					primary: 'ui-icon-image'
				}
			});
			$(container_selector+' .videos-button').button({
				icons: {
					primary: 'ui-icon-video'
				}
			});
			$(container_selector+' .security-button').button({
				icons: {
					primary: 'ui-icon-key'
				}
			});
			$(container_selector+' .lock-button').button({
				icons: {
					primary: 'ui-icon-locked'
				}
			});
			$(container_selector+' .unlock-button').button({
				icons: {
					primary: 'ui-icon-unlocked'
				}
			});
			$(container_selector+' .home-button').button({
				icons: {
					primary: 'ui-icon-home'
				}
			});
			$(container_selector+' .flag-button').button({
				icons: {
					primary: 'ui-icon-flag'
				}
			});
			$(container_selector+' .mail-button').button({
				icons: {
					primary: 'ui-icon-mail-closed'
				}
			});
			$(container_selector+' .comment-button').button({
				icons: {
					primary: 'ui-icon-comment'
				}
			});
			$(container_selector+' .calendar-button').button({
				icons: {
					primary: 'ui-icon-calendar'
				}
			});
		},
		RelativeTimeUpdate: function() {
			// Updates any elements in the page that show relative time to keep them updated

			if ($('.relative-date').length == 0) {
				// No relative date elements found, so may as well just stop and avoid the extra Javascript overhead
				return;
			}

			// Get the time right now and the times as of midnight today and midnight tomorrow for comparisons:
			var now = new Date;
			var today = new Date;
			today.setHours(0);
			today.setMinutes(0);
			today.setSeconds(0);
			today.setMilliseconds(0);
			var tomorrow = new Date;
			tomorrow.setTime(today.getTime()+(86400*1000));
			// Loop through all relative-date elements and update them
			$('.relative-date').each(function() {
				var gmtdate = $(this).data('date');
				if (!gmtdate) {
					return;
				}
				var curr_date = new Date;
				curr_date.setTime(Date.parse(gmtdate));
				var relsecs = (curr_date - now)/1000;
				if (relsecs == 0) {
					$(this).text(__('time_now'));
				} else if (relsecs > -60 && relsecs <= -1) {
					$(this).text(__('time_under_minute_ago'));
				} else if (relsecs >= 1 && relsecs < 60) {
					$(this).text(__('time_in_under_minute'));
				} else {
					var relmins = relsecs/60;
					var relhours = relsecs/3600;
					var reldays = relsecs/86400;
					var unit = null;
					var value = null;
					var past = null;
					if (relmins > -60 && relmins <= -1) {
						if (Math.round(Math.abs(relmins)) != 1) {
							unit = __('time_minutes');
						} else {
							unit = __('time_minute');
						}
						value = Math.round(Math.abs(relmins));
						past = true;
					} else if (relmins >= 1 && relmins < 60) {
						if (Math.round(Math.abs(relmins)) != 1) {
							unit = __('time_minutes');
						} else {
							unit = __('time_minute');
						}
						value = Math.round(relmins);
						past = false;
					} else if (relhours <= -1 && curr_date >= today) {
						if (Math.round(Math.abs(relhours)) != 1) {
							unit = __('time_hours');
						} else {
							unit = __('time_hour');
						}
						value = Math.round(Math.abs(relhours));
						past = true;
					} else if (relhours >= 1 && curr_date <= tomorrow) {
						if (Math.round(Math.abs(relhours)) != 1) {
							unit = __('time_hours');
						} else {
							unit = __('time_hour');
						}
						value = Math.round(relhours);
						past = false;
					}
					if (unit !== null && value !== null && past !== null) {
						var time_value = value+" "+unit;
						if (past) {
							var output = __('date_ago', [time_value]);
						} else {
							var output = __('date_in', [time_value]);
						}
					} else {
						if (reldays >= 1 && reldays < 2) {
							var output = __('date_tomorrow_at', [curr_date.format("shortTime")]);
						} else if (reldays >= -1 && reldays < 0) {
							var output = __('date_yesterday_at', [curr_date.format("shortTime")]);
						} else if (Math.abs(reldays) < 7) {
							var output = __('date_at', [curr_date.format("dddd"), curr_date.format("shortTime")]);
						} else {
							if (curr_date.format('yyyy') != now.format('yyyy')) {
								var date_format = 'mmmm dS, yyyy';
							} else {
								var date_format = 'mmmm dS';
							}
							var output = __('date_at', [curr_date.format(date_format), curr_date.format("shortTime")]);
						}
					}
					$(this).text(output);
				}
			});
			// Update everything again in one second's time
			setTimeout('Biscuit.Crumbs.RelativeTimeUpdate();', 1000);
		},
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
		ShowCoverThrobber: function(parent_id, message) {
			if (message === undefined) {
				var message = '';
			}
			if ($('#'+parent_id+'-throbber').length > 0) {
				// If throbber is already present, just update it's message
				$('#'+parent_id+'-throbber').text(message);
				// Make sure the width is set to a minimum if the new message is empty:
				if (message == '') {
					$('#'+parent_id+'-throbber').css({
						'width': '50px'
					});
				}
				// Adjust left position accordingly:
				var left_pos = (($('#'+parent_id+'-throbber-cover').width()/2)-($('#'+parent_id+'-throbber').width()/2))+'px';
				$('#'+parent_id+'-throbber').css({
					'left': left_pos
				});
			} else {
				// Otherwise create and fade in the throbber
				var curr_position = $('#'+parent_id).css('position');
				if (curr_position != 'relative' && curr_position != 'absolute') {
					$('#'+parent_id).css({
						'position': 'relative'
					});
				}
				$('#'+parent_id).css({
					'overflow': 'visible'
				});
				$('#'+parent_id).append(
					$('<div>')
					.attr('id',parent_id+'-throbber-cover')
					.css({
						'width':  $('#'+parent_id).width()+'px',
						'height': $('#'+parent_id).height()+'px',
						'visibility': 'hidden',
						'position': 'absolute',
						'left': '0',
						'top': '0',
						'z-index': '10000',
						'background': 'transparent url(/framework/themes/sea_biscuit/images/cover-bg.png)'
					})
					.append(
						$('<div>')
						.attr('id',parent_id+'-throbber')
						.css({
							'visibility': 'hidden',
							'position': 'absolute',
							'left': '0',
							'top': '0',
							'padding': '50px 0 0',
							'text-align': 'center',
							'font-size': '20px',
							'color': '#fff',
							'font-family': "'Lucida Grande', 'Lucida Sans Unicode', sans-serif",
							'background': 'url(/framework/themes/sea_biscuit/images/throbber.gif) center 5px no-repeat'
						})
						.text(message)
					)
				);
				// Now calculate position of throbber and text within the space base on it's size:
				if ($('#'+parent_id+'-throbber').height()+50 > $('#'+parent_id).height()) {
					// If the throbber height is greater than the parent container height, remove the text then adjust the height if needed to fit only the
					// throbber image:
					$('#'+parent_id+"-throbber").text('').css({
						'width': '50px',
						'height': '1px'
					});
					if ($('#'+parent_id).height() < 50) {
						$('#'+parent_id+'-throbber-cover').css({
							'height': '50px'
						});
					}
				} else if (message == '') {
					// Set width if there's no text content
					$('#'+parent_id+'-throbber').css({
						'width': '50px'
					});
				}
				var left_pos = (($('#'+parent_id+'-throbber-cover').width()/2)-($('#'+parent_id+'-throbber').width()/2))+'px'
				var top_pos = (($('#'+parent_id+'-throbber-cover').height()/2)-(($('#'+parent_id+'-throbber').height()+55)/2))+'px'
				$('#'+parent_id+'-throbber').css({
					'visibility': 'visible',
					'left': left_pos,
					'top': top_pos
				});
				$('#'+parent_id+'-throbber-cover').css({
					'display': 'none',
					'visibility': 'visible'
				}).fadeIn('fast');
			}
		},
		HideCoverThrobber: function(parent_id) {
			$('#'+parent_id+'-throbber-cover').fadeOut('fast', function() {
				$(this).remove();
				$('#'+parent_id).css({
					'position': '',
					'overflow': ''
				});
			});
		},
		Alert: function(message, title, close_callback, dismiss_button_label) {
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
			if (title == undefined) {
				var title = __('notice');
			}
			if (dismiss_button_label == undefined || dismiss_button_label == '') {
				var dismiss_button_label = __('dismiss_button_label');
			}
			var buttons = {};
			buttons[dismiss_button_label] = function() {
				$(this).dialog('close');
			}
			return $('#'+content_id).dialog({
				modal: true,
				title: title,
				width: 560,
				position: 'center',
				show: 'fade',
				hide: 'fade',
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
			message = '<h4 style="text-align: center"><strong>'+message+'</strong></h4><p style="text-align: center"><img src="/framework/themes/sea_biscuit/images/throbber-light-lrg.gif" alt="'+__('wait_while_loading')+'"></p>';
			$('body').append($('<div>')
				.attr('id',content_id)
				.css({'display': 'none'})
				.html(message)
			);
			// If the window is scrolled down when the dialog opens it scrolls to the top on open but the dialog will try to center based
			// on where the viewport was scrolled to before it opened. This results in the dialog being off-screen. To avoid this issue, we
			// force scroll the window to the top prior to opening the dialog, which then always gets position 30px from the top
			return $('#'+content_id).dialog({
				modal: true,
				title: __('wait_dialog_title'),
				width: 560,
				position: 'center',
				show: 'fade',
				hide: 'fade',
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
				position: 'center',
				show: 'fade',
				hide: 'fade',
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
		},
		Sortable: {
			create: function(selector,post_to,options) {
				if (window.sortable_request_token == undefined && window.sortable_token_form_id == undefined) {
					Biscuit.Console.log("No request token or form id provided for sortable, aborting");
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
				var action = options.action || 'resort';
				var throbber_id = options.throbber_id || null;
				var sortable_options = {
					tolerance: 'pointer',
					update: function(event,ui) {
						var sortable_id = this.id;
						update_func(sortable_id);
						var params = $('#'+this.id).sortable('serialize',{key: array_name+'[]'});
						params += '&request_token='+window.sortable_request_token+'&token_form_id='+window.sortable_token_form_id;
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
				}
				if (options.axis != undefined) {
					sortable_options.axis = options.axis;
				}
				if (options.handle != undefined) {
					sortable_options.handle = options.handle;
				}
				$(selector).sortable(sortable_options);
				$(selector).disableSelection();
			}
		},
		Forms: {
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
				// Apply "busy" mouse cursor to body:
				$('body').css({
					'cursor': 'progress'
				})
			},
			EnableSubmit: function(form_id) {
				// Cycle through all elements with the classname of "SubmitButton" and enable them
				window.top.$('#'+form_id+' .SubmitButton').each(function() {
					$(this).attr('disabled','');
					$(this).removeClass('working');
					$(this).val(Biscuit.Crumbs.Forms.btn_text[form_id]);
				});
				// Restory body mouse cursor
				$('body').css({
					'cursor': ''
				})
			},
			Submit: function(form_id) {
				// Submit the form normally
				// First ensure that the jQuery submit event handler is unbound, otherwise it'll be called instead of normal submit
				$('#'+form_id).unbind('submit');
				// Now we can submit
				$('#'+form_id).submit();
			},
			AddDeleteConfirmationHandlers: function() {
				$('.delete-button').live('click',function() {
					var item_type = null;
					var item_title = null;
					var additional_text = null;
					if ($(this).attr('rel') != undefined && $(this).attr('rel') != '') {
						// Old school - use REL attribute for values used in delete confirmation dialog
						var rel_bits = $(this).attr('rel').split('|');
						if (rel_bits.length > 1) {
							item_type = rel_bits[0];
							item_title = rel_bits[1];
							var del_item_name = "the "+item_type+" \""+item_title+"\"";
							if (rel_bits.length > 2) {
								additional_text = rel_bits[2];
							}
						} else if (rel_bits.length > 0) {
							item_title = rel_bits[0];
							confirmation_message += __('delete_confirm_text',[rel_bits[0]])+'</strong></h4>';
						}
					} else {
						// New school - use HTML5 data attributes
						item_type = $(this).data('item-type');
						item_title = $(this).data('item-title');
						additional_text = $(this).data('additional-text');
					}
					if ($(this).data('delete-function') && $.isFunction(eval($(this).data('delete-function')))) {
						var delete_function = eval($(this).data('delete-function'));
					} else {
						var delete_function = false;
					}
					if (item_title) {
						if (item_type) {
							var item_name = "the "+item_type+" \""+item_title+"\"";
						} else {
							var item_name = item_title;
						}
						var delete_button_label = __('delete_button_label_custom',[item_title]);
					} else {
						var item_name = __('generic_item_name');
						var delete_button_label = __('delete_button_label');
					}
					var confirmation_message = '<h4><strong>'+__('delete_confirm_text',[item_name])+'</strong></h4>';
					if (additional_text) {
						confirmation_message += '<p>'+additional_text+'</p>';
					}
					confirmation_message += "<p>"+__('cannot_undo_warning')+"</p>";
					if ($.isFunction(delete_function)) {
						var target_element = this;
					} else {
						var target_url = $(this).attr('href');
						if (target_url.match(/\?/)) {
							target_url += '&';
						} else {
							target_url += '?';
						}
						target_url += 'delete_confirmed=1';
					}
					Biscuit.Crumbs.Confirm(confirmation_message, function() {
						if ($.isFunction(delete_function)) {
							delete_function(target_element);
						} else {
							top.location.href = target_url;
						}
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

			},
			FieldFullAutoAdvance: function(element, e) {
				Biscuit.Console.log("Key pressed: "+e.which);
				var my_max_length = $(element).attr('maxlength');
				var my_curr_value = $(element).val();
				// Ignore all special keys (non characters):
				var special_keys = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,33,34,35,36,37,38,39,40,41,42,43,44,45,46,224]
				if (my_curr_value.length == my_max_length && !special_keys.inArray(e.which)) {
					Biscuit.Console.log("Max field length reached and no special key pressed, advance to next field:");
					var fields = $(element).parents('form:eq(0)').find('button,input,textarea,select');
					var index = fields.index(element);
					if ( index > -1 && ( index + 1 ) < fields.length ) {
						var next_field = fields.eq( index + 1 );
						Biscuit.Console.log(next_field);
						next_field.focus();
					}
				}

			}
		}
	},
	Ajax: {
		complete_callback: null,
		Request: function(url,biscuit_request_type,options) {
			this.update_container_id = null;
			var proceed = true;
			options.url = url;
			if (options.beforeSend != undefined) {
				proceed = false;
				Biscuit.Console.log('You must NOT provide a custom beforeSend() function to the Ajax request function.');
			}
			if ($.isFunction(options.complete)) {
				Biscuit.Ajax.complete_callback = options.complete;
			} else {
				Biscuit.Ajax.complete_callback = null;
			}
			if (biscuit_request_type == 'update' && options.update_container_id != undefined) {
				this.update_container_id = '#'+options.update_container_id;
				options.update_container_id = null;		// We don't want to pass this value to the jQuery ajax request function
				options.complete = function(xhr,text_status) {
					$('body').css({
						'cursor': 'default'
					});
					$(Biscuit.Ajax.update_container_id).html(xhr.responseText);
					if ($.isFunction(Biscuit.Ajax.complete_callback)) {
						Biscuit.Ajax.complete_callback();
					}
				}
			} else {
				options.complete = function(xhr,text_status) {
					$('body').css({
						'cursor': ''
					});
					if ($.isFunction(Biscuit.Ajax.complete_callback)) {
						Biscuit.Ajax.complete_callback();
					}
				}
			}
			options.beforeSend = function(xhr) {
				Biscuit.Ajax.SetRequestHeaders(biscuit_request_type,xhr);
			}
			if (proceed) {
				$('body').css({
					'cursor': 'progress'
				});
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
						$('#'+login_form_id).unbind('submit');
						$('#'+login_form_id).submit();
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
			if (typeof(settings.error) != 'function' && xhr.status && xhr.status != 404 && xhr.status != 410) {
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
	}
}

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
}

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
				if (xhr.status == 400 || xhr.status == 406) {
					var response = jQuery.parseJSON(xhr.responseText);
				}
				if (xhr.status == 400) {
					Biscuit.Crumbs.Alert(response.message, __('error_box_title'));
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
					Biscuit.Crumbs.Alert(__('uncaught_exception',[err_msg]), __('error_box_title'));
				}
				Biscuit.Crumbs.Forms.EnableSubmit(Validator._id);
				$('body').css({
					'cursor': 'progress'
				})
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
				ddd:  __(dF.strings.dayNames[D]),
				dddd: __(dF.strings.dayNames[D + 7]),
				m:    m + 1,
				mm:   pad(m + 1),
				mmm:  __(dF.strings.monthNames[m]),
				mmmm: __(dF.strings.monthNames[m + 12]),
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

// Day and month name strings. These get passed through i18n properties, so define them in properties file for translation
dateFormat.strings = {
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

// Translation shortcut
var __ = $.i18n.prop;

$(document).ready(function() {
	if (typeof(jQuery) == undefined) {
		alert("jQuery is required for Framework scripts to function!");
	} else {
		// Apply common button styles:
		Biscuit.Crumbs.ApplyCommonButtonStyles();
		// Add delete confirmation handlers to any and all delete buttons:
		Biscuit.Crumbs.Forms.AddDeleteConfirmationHandlers();
		// Initialize default setup for Ajax requests
		Biscuit.Ajax.DefaultSetup();
		// Initialize internationalization:
		$.i18n.properties({
			name:     'Messages',
			path:     '/var/cache/js/',
			mode:     'both',
			language: Biscuit.Language
		});
		// Initialize auto-advance of any text form fields when max length of the field is reached
		$('input.text').keyup(function(e) {
			Biscuit.Crumbs.Forms.FieldFullAutoAdvance(this, e);
		});
		Biscuit.Crumbs.RelativeTimeUpdate();
	}
});
