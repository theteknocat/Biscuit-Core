/**
 * Convert a single file-input element into a 'multiple' input list
 *
 * Usage:
 *
 *   1. Create a file input element (no name)
 *      eg. <input type="file" id="first_file_element">
 *
 *   2. Create a DIV for the output to be written to
 *      eg. <div id="files_list"></div>
 *
 *   3. Instantiate a MultiSelector object, passing in the DIV and an (optional) maximum number of files
 *      eg. var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 3 );
 *
 *   4. Add the first element
 *      eg. multi_selector.addElement( document.getElementById( 'first_file_element' ) );
 *
 *   5. That's it.
 *
 *   You might (will) want to play around with the addListRow() method to make the output prettier.
 *
 *   You might also want to change the line 
 *       element.name = 'file_' + this.count;
 *   ...to a naming convention that makes more sense to you.
 * 
 * Licence:
 *   Use this however/wherever you like, just don't blame me if it breaks anything.
 *
 * Credit:
 *   If you're nice, you'll leave this bit:
 *  
 *   Class by Stickman -- http://www.the-stickman.com
 *      with thanks to:
 *      [for Safari fixes]
 *         Luis Torrefranca -- http://www.law.pitt.edu
 *         and
 *         Shawn Parker & John Pennypacker -- http://www.fuzzycoconut.com
 *      [for duplicate name bug]
 *         'neal'
 */
function MultiSelector( list_target, max, options ){

	if (options === undefined) {
		// make an empty object so we don't get errors checking properties
		var options = {}
	}
	// Where to write the list
	this.list_target = list_target;
	// How many elements?
	this.count = 0;
	// How many elements?
	this.id = 0;
	// Is there a maximum?
	if( max ){
		this.max = max;
	} else {
		this.max = -1;
	};
	// What to prefix the upload field names with
	if (options.name_prefix === undefined) {
		this.name_prefix = 'file_';
	}
	else {
		this.name_prefix = options.name_prefix;
	}
	// Whether or not the fields should be submitted as an array for the processing script. If set to true "[]" will be appended to the field name, otherwise it will be appended with a number
	if (options.use_array === undefined) {
		this.use_array = false;
	}
	else {
		this.use_array = options.use_array;
	}
	// What CSS style to use for hiding the form elements
	if (options.hidden_style === undefined) {
		this.hidden_style = {
			'position': 'absolute',
			'left': '-1000px'
		}
	}
	else {
		this.hidden_style = options.hidden_style;
	}
	// A CSS classname to use for hiding the form elements.  This will take precedence over the style to ensure the default style is not used when a classname is provided.
	if (options.hidden_classname === undefined) {
		this.hidden_classname = null;
	}
	else {
		this.hidden_classname = options.hidden_classname;
	}
	/**
	 * Add a new file input element
	 */
	this.addElement = function( element ){
		// Make sure it's a file input element
		if( element.tagName == 'INPUT' && element.type == 'file' ){
			// Element name
			if (this.use_array === true) {
				element.name = this.name_prefix + '[]';
			}
			else {
				element.name = this.name_prefix + this.id++;
			}
			// Add reference to this object
			element.multi_selector = this;
			// What to do when a file is selected
			element.observe('change',function() {
				// New file input
				var new_element = document.createElement( 'input' );
				new_element.type = 'file';
				// Add new element
				this.insert({'before': new_element})
				// Apply 'update' to element
				this.multi_selector.addElement( new_element );
				// Update list
				this.multi_selector.addListRow( this );
				// Hide this
				if (this.multi_selector.hidden_classname !== null) {
					this.className = this.multi_selector.hidden_classname;
				}
				else {
					this.setStyle(this.multi_selector.hidden_style);
				}
			});
			// If we've reached maximum number, disable input element
			if ( this.max != -1 && this.count >= this.max ) {
				element.disabled = true;
			};
			// File element counter
			this.count++;
			// Most recent element
			this.current_element = element;
		} else {
			// This can only be applied to file input elements!
			alert( 'Error: not a file input element' );
		};
	};

	/**
	 * Add a new row to the list of files
	 */
	this.addListRow = function( element ){
		// Row div
		var new_row = document.createElement( 'div' );
		// Delete button
		var new_row_button = document.createElement( 'input' );
		new_row_button.type = 'button';
		new_row_button.value = 'Delete';
		// References
		new_row.element = element;
		// Delete function
		new_row_button.observe('click',function() {
			// Remove item from list
			this.up().remove();
			// Remove element from form
			this.up().element.remove();
			// Decrement counter
			this.up().element.multi_selector.count--;
			// Re-enable input element (if it's disabled)
			this.up().element.multi_selector.current_element.disabled = false;
			// Appease Safari
			//    without it Safari wants to reload the browser window
			//    which nixes your already queued uploads
			return false;
		});
		// Set row value
		new_row.update(element.value);
		// Add button
		new_row.insert( new_row_button );
		// Add it to the list
		this.list_target.insert( new_row );
		
	};
};