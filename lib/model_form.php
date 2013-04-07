<?php
/**
 * Generate form fields using the Form helper class by building the arguments needed automatically from model instance and attribute name
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class ModelForm extends Form {
	/**
	 * Prevent public instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
	}
	/**
	 * Render text field from model
	 *
	 * @param object $model 
	 * @param string $attribute_name 
	 * @return string
	 * @author Peter Epp
	 */
	public static function text($model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'text');
		$all_args = func_get_args();
		if (!empty($all_args[3])) {
			$args['options'] = array_merge($args['options'],$all_args[3]);
		}
		$field_code = parent::text($attribute_name,$args['field_name'],$args['attr_label'],H::purify_text($args['attr_value']),$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render password field from model
	 *
	 * @param object $model 
	 * @param string $attribute_name 
	 * @return string
	 * @author Peter Epp
	 */
	public static function password($model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'text');
		$all_args = func_get_args();
		if (!empty($all_args[3])) {
			$args['options'] = array_merge($args['options'],$all_args[3]);
		}
		$field_code = parent::password($attribute_name,$args['field_name'],$args['attr_label'],'',$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render textarea field from model
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $allow_html 
	 * @param string $allowed_html 
	 * @param string $instructions 
	 * @return void
	 * @author Peter Epp
	 */
	public static function textarea($model, $attribute_name, $allow_html = false, $allowed_html = null, $instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'textarea');
		if ($allow_html) {
			$purify_filters = array();
			if (!empty($allowed_html)) {
				$purify_filters['allowed'] = $allowed_html;
			}
			$purified_value = H::purify_html($args['attr_value'],$purify_filters);
		} else {
			$purified_value = H::purify_text($args['attr_value']);
		}
		$all_args = func_get_args();
		if (!empty($all_args[5])) {
			$args['options'] = array_merge($args['options'],$all_args[5]);
		}
		$field_code = parent::textarea($attribute_name,$args['field_name'],$args['attr_label'],$purified_value,10,$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render select field from model
	 *
	 * @param string $data_set 
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function select($data_set,$model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'select');
		$field_code = parent::select($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code, $instructions);
	}
	/**
	 * Render a multiple select box from model
	 *
	 * @param string $data_set 
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function select_multiple($data_set,$model,$attribute_name,$height,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'multi-select');
		$field_code = parent::select_multiple($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$height,$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code, $instructions);
	}
	/**
	 * Render radio buttons from model
	 *
	 * @param string $data_set 
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function radios($data_set,$model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'radios');
		$field_code = parent::radios($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render checkbox from model
	 *
	 * @param string $checked_value 
	 * @param string $unchecked_value 
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function checkbox($checked_value, $unchecked_value, $model, $attribute_name, $instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'checkbox');
		$field_code = parent::checkbox($checked_value, $attribute_name, $args['field_name'], $args['attr_label'], $args['attr_value'], $unchecked_value, $args['is_required'], $args['is_valid']);
		return self::render_complete_field($model,$field_code, $instructions);
	}
	/**
	 * Render multiple checkboxes from model
	 *
	 * @param string $data_set 
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function checkbox_multiple($data_set,$model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'multi-checkbox');
		$field_code = parent::checkbox_multiple($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render file upload field from model
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function file($model,$attribute_name,$instructions = null) {
		$args = self::args_from_model($model,$attribute_name,'file');
		$finfo = $model->file_info($attribute_name);
		$field_code = parent::file($attribute_name,$args['field_name'],$args['attr_label'],$finfo,$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render date picker from model. Requires Calendar module to be installed on the page calling this method.
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return string
	 * @author Peter Epp
	 */
	public static function date_picker($model,$attribute_name,$instructions = null) {
		$calendar_obj = Biscuit::instance()->ModuleCalendar();
		$args = self::args_from_model($model,$attribute_name,'date-picker');
		$field_code = parent::date_picker($attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$calendar_obj,$args['is_required'],$args['is_valid']);
		return self::render_complete_field($model,$field_code,$instructions);
	}
	/**
	 * Render a complete field wrapped in a p tag with tiger stripe and instructions properly laid out
	 *
	 * @author Peter Epp
	 * @return string
	 */
	private static function render_complete_field($model,$field_code, $instructions = null) {
		$Navigation = Biscuit::instance()->ExtensionNavigation();
		$stripe = $Navigation->tiger_stripe("striped_".get_class($model)."_form");
		if (!empty($instructions)) {
			$instructions = <<<HTML

	<span class="instructions">$instructions</span>
HTML;
		}
		$code = <<<HTML
<p class="$stripe">
	$field_code
	$instructions
</p>
HTML;
		return $code;
	}
	/**
	 * Build all the arguments needed for a field using the model
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $field_type 
	 * @return array
	 * @author Peter Epp
	 */
	private static function args_from_model($model,$attribute_name,$field_type) {
		$data_name = AkInflector::underscore(AkInflector::singularize(get_class($model)));
		$args['field_name']  = $data_name.'['.$attribute_name.']';
		$args['attr_value']  = call_user_func(array($model,$attribute_name));
		$args['attr_label']  = call_user_func(array($model,$attribute_name."_label"));
		$args['is_required'] = call_user_func(array($model,$attribute_name."_is_required"));
		$args['is_valid']    = call_user_func(array($model,$attribute_name."_is_valid"));
		$args['field_type']  = call_user_func(array($model,$attribute_name."_field_type"));
		$args['options']     = array();
		switch($field_type) {
			case 'text':
			case 'password':
				if (preg_match('/\(([0-9]+)\)/',$args['field_type'],$matches)) {
					$max_length = (int)$matches[1];
					if (!empty($max_length)) {
						$args['options']['maxlength'] = $max_length;
						if ($max_length < 30) {
							$args['options']['width'] = ($max_length*10)+10;
						}
					}
				}
				break;
		}
		return $args;
	}
}
?>