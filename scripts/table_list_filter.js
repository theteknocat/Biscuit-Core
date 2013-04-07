var TableFilter = {
	table_id: "",
	execute_timeout: null,
	init: function(table_id) {
		if (jQuery('#filter-fields').length > 0) {
			this.table_id = table_id;
			// In case a user doesn't have JS the filter fields may be hidden by default, in which case show them now:
			if (jQuery("#filter-fields").css("display") == "none") {
				jQuery("#filter-fields").show();
			}
			var sfield = jQuery("#s-keywords");
			sfield.focus();
			jQuery("#s-clear").click(function() {
				TableFilter.Clear();
			});
			jQuery("#s-clear").attr("disabled",true);
			sfield.keyup(function(event) {
				clearTimeout(TableFilter.execute_timeout);
				TableFilter.execute_timeout = setTimeout("TableFilter.Execute();",500);
			});
			jQuery('#filter-fields input[type=checkbox],#filter-fields input[type=radio]').click(function() {
				TableFilter.Execute();
			});
		}
	},
	GetKeywordExpr: function() {
		var sfield = jQuery("#s-keywords");
		if (sfield.val() != "") {
			var expr = new RegExp(sfield.val(),"i");
		}
		else {
			var expr = "";
		}
		return expr;
	},
	GetCheckedFields: function() {
		var checked_fields = []
		var index = 0;
		jQuery('#filter-fields input[type=checkbox],#filter-fields input[type=radio]').each(function() {
			if (this.checked) {
				checked_fields[index] = this.id;
				index += 1;
			}
		});
		return checked_fields;
	},
	Execute: function() {
		clearTimeout(this.execute_timeout);
		Biscuit.Crumbs.ShowThrobber('filter-throbber');
		var total_count = 0;
		jQuery('#'+this.table_id+' tbody tr').each(function(index) {
			var keywords_match = TableFilter.KeywordsMatch(this);
			var checked_fields_match = TableFilter.CheckedFieldsMatch(this);
			if (keywords_match && checked_fields_match) {
				jQuery(this).show();
				total_count += 1;
			}
			else {
				jQuery(this).hide();
			}
		});
		if (jQuery("#none-found")) {
			if (total_count > 0) {
				jQuery("#none-found").hide();
			}
			else {
				jQuery("#none-found").show();
			}
		}
		TableFilter.UpdateDisplayCount(total_count);
		TableFilter.SetClearButtonState();
		TableFilter.ReStripe();
		Biscuit.Crumbs.HideThrobber('filter-throbber');
		jQuery("#s-keywords").focus();
	},
	KeywordsMatch: function(row_obj) {
		var expr = this.GetKeywordExpr();
		var row_content = TableFilter.CleanContent(row_obj);
		return (row_content.match(expr));
	},
	CleanContent: function(row_object) {
		var stripped = "";
		jQuery(row_object).children().not('.nosearch').each(function() {
			stripped = stripped + jQuery(this).text()+"\n";
		});
		return stripped;
	},
	CheckedFieldsMatch: function(row_obj) {
		var checkboxes_and_radios = jQuery('#filter-fields input[type=checkbox],#filter-fields input[type=radio]');
		if (checkboxes_and_radios.length > 0) {
			var checked_fields = this.GetCheckedFields();
			if (checked_fields.length > 0) {
				var match_count = 0;
				for (var i=0;i < checked_fields.length;i++) {
					var fieldname = checked_fields[i];
					if (jQuery("#"+fieldname).hasClass("un-filtered")) {
						// If an "un-filtered" field is checked, count it as a match
						match_count += 1;
					} else {
						var filter_class = "is_"+fieldname.substr(7);
						var filter_field = jQuery(row_obj).children().children("input[type=hidden]."+filter_class)[0];
						if (filter_field != undefined && filter_field.value == 1) {
							match_count += 1;
						}
					}
				}
				return (match_count == checked_fields.length);
			}
		}
		// Always return true if there are no checkboxes or none of them are checked
		return true;
	},
	SetClearButtonState: function() {
		var sfield = jQuery("#s-keywords");
		var checked_fields = this.GetCheckedFields();
		if (sfield.val() != "" || checked_fields.length > 0) {
			jQuery("#s-clear").removeAttr("disabled");
		}
		else {
			jQuery("#s-clear").attr("disabled",true);
		}
	},
	ReStripe: function() {
		// Re-stripe all the rows after turning some on and off
		var real_count = 1;
		jQuery('#'+this.table_id+' tbody tr').each(function(index) {
			if (index > 0) {
				if (jQuery(this).css("display") != "none") {
					jQuery(this).removeClass("roweven");
					jQuery(this).removeClass("rowodd");
					if (real_count%2 == 0) {
						jQuery(this).addClass("roweven");
					}
					else {
						jQuery(this).addClass("rowodd");
					}
					real_count += 1;
				}
			}
		});
	},
	Clear: function() {
		var sfield = jQuery("#s-keywords");
		sfield.val("");
		jQuery('#filter-fields input[type=checkbox],#filter-fields input[type=radio]').each(function() {
			this.checked = false;
		});
		jQuery("#filter-fields input.un-filtered[type=radio]").each(function() {
			this.checked = true;
		});
		jQuery('#'+this.table_id+' tr').each(function(index) {
			if (index > 0) {
				jQuery(this).show();
			}
		});
		TableFilter.ReStripe();
		sfield.focus();
		jQuery("#s-clear").attr("disabled",true);
		this.UpdateDisplayCount();
	},
	UpdateDisplayCount: function(total_matches) {
		var count_container = jQuery('#filter-count');
		if (count_container != undefined) {
			if (total_matches != undefined) {
				total_matches = total_matches+"";
				count_container.html(total_matches);
			} else {
				var total_rows = jQuery('#'+this.table_id+' tbody tr').length;
				total_rows = total_rows+"";
				count_container.html(total_rows);
			}
		}
	}
}
