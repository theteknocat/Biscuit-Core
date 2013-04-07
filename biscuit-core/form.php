<?php
/**
 * Functions for rendering form fields from single field templates
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: form.php 14744 2012-12-01 20:50:43Z teknocat $
 */
class Form {
	/**
	 * Prevent instantiation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function __construct() {
	}
	/**
	 * Output a single-line text field
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag.
	 * @param string $default_value Default value to populate the field with
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function text($id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		return self::build_field_tag("text",$options);
	}
	/**
	 * Output a password field
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag.
	 * @param string $default_value Default value to populate the field with
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function password($id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		if (empty($options['show_strength_meter'])) {
			// No strength meter by default. Has to be explicitly requested.
			$options['show_strength_meter'] = false;
		}
		return self::build_field_tag("password",$options);
	}
	/**
	 * Output a multi-line text area
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param string $default_value Default value to populate the field with
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function textarea($id,$name,$label,$default_value,$rows,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['rows'] = $rows;
		if (empty($options['cols'])) {
			$options['cols'] = '60';
		}
		return self::build_field_tag("textarea",$options);
	}
	/**
	 * Output a single-line drop-down select box
	 *
	 * @param array $data_set Set of values and labels to populate the select list. This is an indexed array of associative arrays in the format array(array('value' => 'something','label' => 'Field Name'))
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param string $default_value Default value to be selected
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function select($data_set,$id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['data_set'] = $data_set;
		if (empty($options['first_item_label'])) {
			$options['first_item_label'] = 'Please select...';
		}
		return self::build_field_tag("select",$options);
	}
	/**
	 * Output a multiple select box
	 *
	 * @param array $data_set Set of values and labels to populate the select list. This is an indexed array of associative arrays in the format array(array('value' => 'something','label' => 'Field Name'))
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param string $default_value Default value to be selected
	 * @param int $height Height (number of lines) for the select box
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function select_multiple($data_set,$id,$name,$label,$default_value,$height,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['data_set'] = $data_set;
		$options['is_multiple'] = true;
		$options['height']      = $height;
		return self::build_field_tag("select_multiple",$options);
	}
	/**
	 * Output a set of radio select buttons
	 *
	 * @param array $data_set Set of values and labels to populate the radio buttons. This is an indexed array of associative arrays in the format array(array('value' => 'something','label' => 'Field Name'))
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param string $default_value Default value to be selected
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function radios($data_set,$id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['data_set']      = $data_set;
		return self::build_field_tag("radios",$options);
	}
	/**
	 * Output a single checkbox field, including a hidden field for the unchecked value
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param array $default_value Default value to be checked
	 * @param mixed $unchecked_value The value to submit if the checkbox is not checked. Set to NULL if you don't want to use this.
	 * @param string $required Whether or not the field is required
	 * @param string $is_valid Whether or not the field is invalid
	 * @param string $options 
	 * @return text
	 * @author Peter Epp
	 */
	public static function checkbox($checked_value,$id,$name,$label,$default_value,$unchecked_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['checked_value']   = $checked_value;
		$options['unchecked_value'] = $unchecked_value;
		return self::build_field_tag("checkbox",$options);
	}
	/**
	 * Output a set of checkboxes for multiple selection
	 *
	 * @param array $data_set Set of values and labels to populate the checkboxes. This is an indexed array of associative arrays in the format array(array('value' => 'something','label' => 'Field Name'))
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param array $default_value Default value(s) to be checked
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function checkbox_multiple($data_set,$id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['data_set']       = $data_set;
		return self::build_field_tag("checkbox_multiple",$options);
	}
	/**
	 * Output a file field
	 *
	 * @param array $data_set Set of values and labels to populate the select list
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param mixed $finfo Array of info about the existing file, or false if none.
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function file($id,$name,$label,$finfo,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,null,$required,$is_valid,$options);
		$options['finfo']          = $finfo;
		return self::build_field_tag("file",$options);
	}
	/**
	 * Build a file browser field that use the stand alone tiny mce file manager
	 *
	 * @param string $id 
	 * @param string $name 
	 * @param string $label 
	 * @param string $required 
	 * @param string $is_valid 
	 * @param string $tiny_mce Reference to the Tiny Mce extension
	 * @param string $options 
	 * @return void
	 * @author Peter Epp
	 */
	public static function managed_file($id,$name,$label,$default_value,$required,$is_valid,$media_type,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		$options['media_type'] = $media_type;
		$options['no_file_manager'] = false;
		if (Biscuit::instance()->module_exists('FileManager')) {
			$options['js_callback'] = "FileManagerActivate.standalone('attr_{$id}')";
			$options['button_class'] = 'file-button';
		} else if (Biscuit::instance()->extension_exists('TinyMce')) {
			$options['js_callback'] = "tinyBrowserPopUp('{$media_type}','attr_{$id}')";
			$options['button_class'] = $media_type.'-button';
		} else {
			$options['no_file_manager'] = true;
		}
		return self::build_field_tag("managed_file",$options);
	}
	/**
	 * Output a date-picker field. Requires the Calendar module to function.
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag.
	 * @param string $default_value Default value to populate the field with
	 * @param array $options Any additional variables needed by the template
	 * @return text
	 * @author Peter Epp
	 */
	public static function date_picker($id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		if (!isset($options['style'])) {
			$options['style'] = '';
		}
		return self::build_field_tag("date_picker",$options);
	}
	/**
	 * Render a jQuery slider for choosing a value
	 *
	 * @param string $id 
	 * @param string $name 
	 * @param string $label 
	 * @param string $default_value 
	 * @param string $required 
	 * @param string $is_valid 
	 * @param string $min 
	 * @param string $max 
	 * @return void
	 * @author Peter Epp
	 */
	public static function slider($id,$name,$label,$default_value,$required,$is_valid,$min = 0,$max = 100,$step = 1,$options = array()) {
		$options['min'] = $min;
		$options['max'] = $max;
		$options['step'] = $step;
		if (empty($options['units'])) {
			$options['units'] = '';
		}
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		return self::build_field_tag('slider',$options);
	}
	/**
	 * Render a text field with a popup colour picker
	 *
	 * @param string $id 
	 * @param string $name 
	 * @param string $label 
	 * @param string $default_value 
	 * @param string $required 
	 * @param string $is_valid 
	 * @return void
	 * @author Peter Epp
	 */
	public static function colorpicker($id,$name,$label,$default_value,$required,$is_valid,$options = array()) {
		$options = self::prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options);
		return self::build_field_tag('colorpicker',$options);
	}
	/**
	 * Render common form header (normally opening form tag and request token field)
	 *
	 * @param object $model Reference to the model
	 * @param null|string $form_id Optional - field id to use instead of hyphenized model name plus "-form"
	 * @return void
	 * @author Peter Epp
	 */
	public static function header($model, $form_id = null, $form_action = "") {
		if (is_object($model) && empty($form_id)) {
			$model_name = get_class($model);
			$form_id = AkInflector::underscore($model_name);
			$form_id = str_replace("_","-",$form_id).'-form';
			Console::log("Form ID: ".$form_id);
		} else if (!is_object($model) && empty($form_id)) {
			// Make something up
			$form_id = 'form-'.time();
		}
		$enctype = '';
		if (is_object($model) && $model->has_upload_fields()) {
			$enctype = ' enctype="multipart/form-data"';
		}
		if (empty($form_action)) {
			$form_action = Request::uri();
		}
		return Crumbs::capture_include('views/forms/header.php',array('form_id' => $form_id, 'form_action' => $form_action, 'enctype' => $enctype));
	}
	/**
	 * Render a common form footer
	 *
	 * @param object $controller Reference to the controller
	 * @param object $model Reference to the model
	 * @return string
	 * @author Peter Epp
	 */
	public static function footer($controller, $model, $has_del_button, $submit_label = "Save", $custom_cancel_url = null, $del_rel = '') {
		if (!empty($del_rel)) {
			$del_rel = ' rel="'.$del_rel.'"';
		} else {
			$del_rel = ' rel="this '.AkInflector::humanize(Crumbs::normalized_model_name($model)).'"';
		}
		if (!is_object($model)) {
			$model = null;
			$has_del_button = false;
		}
		return Crumbs::capture_include('views/forms/footer.php',array(
			'controller' => $controller,
			'model' => $model,
			'submit_label' => $submit_label,
			'has_del_button' => $has_del_button,
			'custom_cancel_url' => $custom_cancel_url,
			'del_rel' => $del_rel
		));
	}
	/**
	 * Set any defaults for optional variables and render the field from the template
	 *
	 * @param string $type Form field type
	 * @param string $options All options needed by the template to render the field
	 * @return text
	 * @author Peter Epp
	 */
	private static function build_field_tag($type,$options) {
		return Crumbs::capture_include('views/forms/'.$type.'.ffield',$options);
	}
	/**
	 * Prep the options common to all field types
	 *
	 * @param string $id Value for the id attribute
	 * @param string $label Text content for the label tag
	 * @param string $default_value Default value to be selected
	 * @param array $options Any additional variables needed by the template
	 * @return array
	 * @author Peter Epp
	 */
	private static function prep_common_options($id,$name,$label,$default_value,$required,$is_valid,$options) {
		// Translate the label before making modifications to it for output:
		$label = __($label);
		if (empty($label)) {
			$label = "&nbsp;";
		}
		$options['id']            = $id;
		$options['name']          = $name;
		$options['label']         = $label;
		$options['default_value'] = $default_value;
		$options['required']      = $required;
		if (Request::is_post()) {
			// Only set this to the actual value on POST requests. We don't want the field to be hilighted on a normal GET request
			$options['is_valid']  = $is_valid;
		} else {
			$options['is_valid']  = true;
		}
		return $options;
	}
	/**
	 * Turn an array of models into an array in the data set format needed for rendering a select list using the select field helper methods
	 *
	 * @param array $models Array of model instances
	 * @param string $label_attribute The name of the attribute to use for the select list labels
	 * @param string $value_attribute The name of the attribute to use for the select list values
	 * @return array
	 * @author Peter Epp
	 */
	public static function models_to_select_data_set($models,$value_attribute,$label_attribute) {
		if (empty($models)) {
			return array();
		}
		$select_list_array = array();
		foreach ($models as $model) {
			$select_list_array[] = array(
				'value' => addslashes(Crumbs::entitize_utf8($model->$value_attribute())),
				'label' => Crumbs::entitize_utf8($model->$label_attribute())
			);
		}
		return $select_list_array;
	}
}
?>