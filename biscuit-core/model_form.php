<?php
/**
 * Generate form fields using the Form helper class by building the arguments needed automatically from model instance and attribute name
 *
 * @package Core
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: model_form.php 14328 2011-10-03 17:07:34Z teknocat $
 */
class ModelForm {
	/**
	 * Prevent public instantiation
	 *
	 * @author Peter Epp
	 */
	private function __construct() {
	}
	/**
	 * Render a hidden form field containing a model attribute. No view for this as that's really not required.
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @return string
	 * @author Peter Epp
	 */
	public static function hidden($model,$attribute_name,$value_override = null) {
		$args = self::args_from_model($model,$attribute_name,'hidden');
		if (!empty($value_override)) {
			$args['attr_value'] = $value_override;
		}
		return '<input type="hidden" name="'.$args['field_name'].'" value="'.$args['attr_value'].'">';
	}
	/**
	 * Render text field from model
	 *
	 * @param object $model 
	 * @param string $attribute_name 
	 * @return string
	 * @author Peter Epp
	 */
	public static function text($model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'text',$options);
		$all_args = func_get_args();
		if (!empty($all_args[3])) {
			$args['options'] = array_merge($args['options'],$all_args[3]);
		}
		$field_code = Form::text($attribute_name,$args['field_name'],$args['attr_label'],Crumbs::html_entity_decode(H::purify_text($args['attr_value'])),$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'text',$instructions);
	}
	/**
	 * Render password field from model
	 *
	 * @param object $model 
	 * @param string $attribute_name 
	 * @return string
	 * @author Peter Epp
	 */
	public static function password($model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'text',$options);
		$all_args = func_get_args();
		if (!empty($all_args[3])) {
			$args['options'] = array_merge($args['options'],$all_args[3]);
		}
		$field_code = Form::password($attribute_name,$args['field_name'],$args['attr_label'],'',$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'text',$instructions);
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
	public static function textarea($model, $attribute_name, $allow_html = false, $allowed_html = null, $instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'textarea',$options);
		if ($allow_html) {
			if ($allowed_html == 'all') {
				// Allow ALL HTML without any purification
				$purified_value = $args['attr_value'];
			} else {
				$purify_filters = array();
				if (!empty($allowed_html)) {
					$purify_filters['allowed'] = $allowed_html;
				}
				$purified_value = H::purify_html($args['attr_value'],$purify_filters);
			}
		} else {
			$purified_value = Crumbs::html_entity_decode(H::purify_text($args['attr_value']));
		}
		$all_args = func_get_args();
		if (!empty($all_args[5])) {
			$args['options'] = array_merge($args['options'],$all_args[5]);
		}
		$field_code = Form::textarea($attribute_name,$args['field_name'],$args['attr_label'],$purified_value,10,$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'textarea',$instructions);
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
	public static function select($data_set,$model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'select',$options);
		$field_code = Form::select($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'select',$instructions);
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
	public static function select_multiple($data_set,$model,$attribute_name,$height,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'multi-select',$options);
		$field_code = Form::select_multiple($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$height,$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'select-multi',$instructions);
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
	public static function radios($data_set,$model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'radios',$options);
		$field_code = Form::radios($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'radios',$instructions);
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
	public static function checkbox($checked_value, $unchecked_value, $model, $attribute_name, $instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'checkbox',$options);
		$field_code = Form::checkbox($checked_value, $attribute_name, $args['field_name'], $args['attr_label'], $args['attr_value'], $unchecked_value, $args['is_required'], $args['is_valid'], $args['options']);
		return self::render_complete_field($model,$field_code,'checkbox',$instructions);
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
	public static function checkbox_multiple($data_set,$model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'multi-checkbox',$options);
		$field_code = Form::checkbox_multiple($data_set,$attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'checkbox-multi',$instructions);
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
	public static function file($model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'file',$options);
		$args['field_name'] = $attribute_name.'_file';
		$finfo = $model->file_info($attribute_name);
		$field_code = Form::file($attribute_name,$args['field_name'],$args['attr_label'],$finfo,$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'file',$instructions);
	}
	/**
	 * Render managed file select field from model
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $tiny_mce 
	 * @param string $instructions 
	 * @return void
	 * @author Peter Epp
	 */
	public static function managed_file($model,$attribute_name,$media_type,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'managed-file',$options);
		$args['options']['instructions'] = $instructions;
		$field_code = Form::managed_file($attribute_name,$args['field_name'],$args['attr_label'],Crumbs::html_entity_decode(H::purify_text($args['attr_value'])),$args['is_required'],$args['is_valid'],$media_type,$args['options']);
		return self::render_complete_field($model,$field_code,'managed-file');
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
	public static function date_picker($model,$attribute_name,$instructions = null,$options = array()) {
		$args = self::args_from_model($model,$attribute_name,'date-picker',$options);
		$field_code = Form::date_picker($attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$args['options']);
		return self::render_complete_field($model,$field_code,'date-picker',$instructions);
	}
	/**
	 * Render a slider widget
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $min 
	 * @param string $max 
	 * @param string $step 
	 * @param string $instructions 
	 * @return void
	 * @author Peter Epp
	 */
	public static function slider($model, $attribute_name, $min, $max, $step = 1, $instructions = null, $options = array()) {
		$args = self::args_from_model($model,$attribute_name,'slider');
		$field_code = Form::slider($attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$min,$max,$step,$options);
		return self::render_complete_field($model,$field_code,'slider',$instructions);
	}
	/**
	 * Render a color picker widget field
	 *
	 * @param string $model 
	 * @param string $attribute_name 
	 * @param string $instructions 
	 * @return void
	 * @author Peter Epp
	 */
	public static function colorpicker($model, $attribute_name, $instructions = null, $options = array()) {
		$args = self::args_from_model($model,$attribute_name,'colorpicker');
		$field_code = Form::colorpicker($attribute_name,$args['field_name'],$args['attr_label'],$args['attr_value'],$args['is_required'],$args['is_valid'],$options);
		return self::render_complete_field($model,$field_code,'colorpicker',$instructions);
	}
	/**
	 * Render a complete field wrapped in a p tag with tiger stripe and instructions properly laid out
	 *
	 * @author Peter Epp
	 * @return string
	 */
	private static function render_complete_field($model, $field_code, $field_type, $instructions = null) {
		$Navigation = Biscuit::instance()->ExtensionNavigation();
		$stripe = $Navigation->tiger_stripe("striped_".get_class($model)."_form");
		$class_extra = '';
		if (!empty($instructions)) {
			$instructions = <<<HTML

	<span class="instructions">$instructions</span>
HTML;
		}
		if ($field_type == 'slider') {
			$tag = 'div';
			$class_extra = 'complex-element';
		} else {
			$tag = 'p';
		}
		$code = <<<HTML
<$tag class="$stripe $class_extra">
	$field_code
	$instructions
</$tag>
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
	private static function args_from_model($model,$attribute_name,$field_type,$options = array()) {
		$model_class = Crumbs::normalized_model_name($model);
		$data_name = AkInflector::underscore(AkInflector::singularize($model_class));
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
		$args['options'] = array_merge($args['options'],$options);
		return $args;
	}
}
?>