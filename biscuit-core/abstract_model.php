<?php
/**
 * An abstract model for use with plugin models
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0 $Id: abstract_model.php 14737 2012-11-30 22:56:56Z teknocat $
 */
class AbstractModel extends EventObserver {
	/**
	 * A list of any error messages resulting from save or validation
	 *
	 * @var string
	 */
	protected $_error_messages = array();
	/**
	 * List of attributes that failed validation
	 *
	 * @var string
	 */
	protected $_invalid_attributes = array();
	/**
	 * Whether or not the model has been validated
	 *
	 * @var string
	 */
	protected $_validated = null;
	/**
	 * List of attributes that have file uploads
	 *
	 * @var array
	 */
	protected $_attributes_with_uploads = array();
	/**
	 * Attributes that use the file manager to link to a file
	 *
	 * @var string
	 */
	protected $_file_managed_attributes = array();
	/**
	 * List of attributes that cannot be empty (for validation)
	 *
	 * @var array
	 */
	protected $_required_attributes = array();
	/**
	 * Nice labels for attributes that don't translate to the desired label with AkInflector::humanize()
	 *
	 * @var array
	 */
	protected $_attr_labels = array();
	/**
	 * Associative array of all the object attributes
	 *
	 * @var array
	 */
	protected $_attributes = array();
	/**
	 * Associative array of other attributes that are not actual properties of this object
	 *
	 * @var array
	 */
	protected $_other_attributes = array();
	/**
	 * Array of the names of attributes that are private - as in ones that may not be populated from user input
	 *
	 * @var array
	 */
	protected $_private_attribute_names = array();
	/**
	 * List of other models associated with this one
	 *
	 * @author Peter Epp
	 */
	protected $_associated_models = array();
	/**
	 * Associative array of arrays of the attributes of associated models used by the current models. If left empty, all will be assumed
	 *
	 * @var array
	 */
	protected $_associated_attributes = array();
	/**
	 * Other models that this model has one of
	 *
	 * @var array
	 */
	protected $_has_one = array();
	/**
	 * Place to define the foreign keys for has one model relationships if not using the default
	 *
	 * @var array
	 */
	protected $_has_one_key_names = array();
	/**
	 * Place to cache the models that this model has one of
	 *
	 * @var array
	 */
	protected $_the_single_models_i_have = array();
	/**
	 * Other models that this model has many of
	 *
	 * @var array
	 */
	protected $_has_many = array();
	/**
	 * Place to define the foreign keys for has many model relationships if not using the default
	 *
	 * @var array
	 */
	protected $_has_many_key_names = array();
	/**
	 * Place to cache the other models this model has many of
	 *
	 * @var array
	 */
	protected $_the_many_models_i_have = array();
	/**
	 * Other models this model belongs to
	 *
	 * @var array
	 */
	protected $_belongs_to = array();
	/**
	 * Place to define the foreign keys for belongs to model relationships if not using the default
	 *
	 * @var array
	 */
	protected $_belongs_to_key_names = array();
	/**
	 * Place to cache the models this model belongs to
	 *
	 * @var array
	 */
	protected $_the_single_models_i_belong_to = array();
	/**
	 * Other models this model both has many of and belongs to
	 *
	 * @var array
	 */
	protected $_has_and_belongs_to_many = array();
	/**
	 * Place to cache the other models this model both has and belongs to
	 *
	 * @var array
	 */
	protected $_the_models_i_have_and_belong_to = array();
	/**
	 * Ignore captcha when validating if otherwise required. Defaults to false. Useful in cases where another model might need to save
	 * during a post request that normally requires a captcha that should not fail validation as a result of the captcha not correctly submitted
	 *
	 * @var bool
	 */
	protected $_ignore_captcha = false;
	/**
	 * Whether or not the model uses a custom ID
	 *
	 * @var bool
	 */
	protected $_uses_custom_id = false;
	/**
	* Constructor for the AbstractModel class
	*
	* @param $attributes (array)  an associative array of attributes
	* @return AbstractModel object
	**/
	public function __construct(){
		Console::log("                        New model created: ".get_class($this));
		$this->_validated = false;		// Mark as un-validated by default
		Event::add_observer($this);
	}
	/**
	 * Create a friendly URL slug for this model. Will return a value if the model has either a "title" or "name" attribute. If neither are present, nothing
	 * will be returned. Redefine this function in your model if you have a different attribute to use for the friendly slug.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function friendly_slug() {
		if (method_exists($this,'friendly_slug_value')) {
			$slug_value = $this->friendly_slug_value();
		} else if (method_exists($this,'slug_attribute')) {
			$slug_value = $this->_get_attribute($this->slug_attribute());
		} else {
			if ($this->_has_attribute('title')) {
				$slug_value = $this->_get_attribute('title');
			} else if ($this->_has_attribute('name')) {
				$slug_value = $this->_get_attribute('name');
			}
		}
		if (!empty($slug_value)) {
			return Crumbs::clean_for_url($slug_value);
		}
		return '';
	}
	/**
	 * Produced a teaser truncated to teaser length defined in system settings (or 400 by default if not defined) if the model has a method
	 * defining the name of an attribute to use for generating a teaser
	 *
	 * @return string|null
	 * @author Peter Epp
	 */
	public function teaser() {
		if ($this->_has_attribute('teaser')) {
			return $this->_get_attribute('teaser');
		}
		if (method_exists($this,'teaser_attribute')) {
			if (defined('TEASER_LENGTH')) {
				$teaser_length = TEASER_LENGTH;
			} else {
				$teaser_length = 400;
			}
			$text = trim(H::purify_text($this->_get_attribute($this->teaser_attribute())));
			if (strlen($text) <= $teaser_length) {
				return $text;
			}
			while (empty($teaser)) {
				$teaser = substr($text,0,strpos($text," ",$teaser_length));
				$teaser_length -= 5;
			}
			$teaser = rtrim($teaser, '.,:;\'"');
			$teaser .= '...';
			return $teaser;
		}
		return null;
	}
	/**
	 * Multi-purpose magic caller:
	 *
	 * 1) Check whether or not an attribute is required. Call like this: $your_model->attr_name_is_required()
	 *
	 * 2) Check whether or not an attribute is valid (basic check for emptiness). Call like this: $your_model->attr_name_is_valid()
	 *    If an attribute requires special validation, write an attr_name_is_valid method for the attribute in your model
	 *
	 * 3) Return the label for an attribute for output. Call like this: $your_model->attr_name_label()
	 *
	 * 4) Get and set attributes. To get an attribute, call $your_model->attr_name(). To set an attribute call $your_model->set_attr_name($value)
	 *
	 * @param string $method 
	 * @param string $args 
	 * @return mixed
	 * @author Peter Epp
	 */
	public function __call($method_name,$args) {
		if (method_exists($this,$method_name)) {
			// The method exists on the object already, but for some reason PHP decided to defer to the magic caller anyway.
			// This seems to happen in PHP 5.2.11 when you call a protected or private method from an external context. We
			// therefore need a way to catch that, so this is my workaround
			throw new ModuleException("An attempt was made to call ".get_class($this)."::".$method_name.", which exists on the object, but PHP deferred to the magic __call() method. You probably defined the method as private or protected but tried to call it outside the context of the ".get_class($this)." object instance.");
		}
		if ($this->_has_attribute($method_name)) {
			return $this->_get_attribute($method_name);
		} else {
			if (isset($args[0])) {
				$attr_value = $args[0];
			} else {
				$attr_value = null;
			}
			$allowed_methods = array(
				'set_',
				'has_',
				'_is_required',
				'_is_valid',
				'_label',
				'_field_type',
				'_field_default'
			);
			foreach ($allowed_methods as $allowed_method) {
				if (preg_match('/^'.$allowed_method.'/',$method_name) || preg_match('/'.$allowed_method.'$/', $method_name)) {
					if (substr($allowed_method,0,1) == '_') {	// Begins with an underscore, assume method name is suffixed
						$attr_name = substr($method_name, 0, -strlen($allowed_method));
						$method_name = '_attr'.substr($method_name, -strlen($allowed_method));
						break;
					} else if (substr($allowed_method,-1) == '_') {	// Ends with an underscore, assume method name is prefixed
						$attr_name = substr($method_name, strlen($allowed_method));
						$method_name = '_'.substr($method_name, 0, strlen($allowed_method)).'attribute';
						break;
					}
				}
			}
			if (!empty($attr_name)) {
				if ($method_name == '_has_attribute') {
					return $this->_has_attribute($attr_name);
				} else if ($this->_has_attribute($attr_name) && method_exists($this,$method_name)) {
					return $this->$method_name($attr_name, $attr_value);
				}
			}
			array_unshift($args, $method_name);
			$related_models = call_user_func_array(array($this,'_get_related'),$args);
			if ($related_models !== 'invalid') {
				return $related_models;
			}
		}
		// Throw an exception if we couldn't find the appropriate method to call
		throw new ModuleException("Cannot call method: ".get_class($this)."::".$method_name."('".$attr_name."')");
	}
	/**
	 * Get models related to this model as defined by the relationship properties
	 *
	 * @param string $attr_name 
	 * @return mixed
	 * @author Peter Epp
	 */
	protected function _get_related($attr_name) {
		$other_args = func_get_args();
		array_shift($other_args);
		$related_model_name = AkInflector::camelize(AkInflector::singularize($attr_name));
		if (!in_array($related_model_name, $this->_has_one) && !in_array($related_model_name, $this->_has_many) && !in_array($related_model_name, $this->_belongs_to) && !array_key_exists($related_model_name, $this->_has_and_belongs_to_many)) {
			// Didn't work as singular, try pluralizing the related model name. If that fails, so be it
			$related_model_name = AkInflector::pluralize($related_model_name);
		}
		if (in_array($related_model_name, $this->_has_one)) {
			if (!empty($this->_has_one_key_names[$related_model_name])) {
				$my_id_attribute = $this->_has_one_key_names[$related_model_name];
			} else {
				$my_id_attribute = AkInflector::underscore(Crumbs::normalized_model_name($this)).'_id';
			}
			if (empty($this->_the_single_models_i_have[$related_model_name])) {
				$this->_the_single_models_i_have[$related_model_name] = ModelFactory::instance($related_model_name)->find_by($my_id_attribute, $this->id());
			}
			return $this->_the_single_models_i_have[$related_model_name];
		}
		if (in_array($related_model_name, $this->_has_many)) {
			if (!empty($this->_has_many_key_names[$related_model_name])) {
				$my_id_attribute = $this->_has_many_key_names[$related_model_name];
			} else {
				$my_id_attribute = AkInflector::underscore(Crumbs::normalized_model_name($this)).'_id';
			}
			if (empty($this->_the_many_models_i_have[$related_model_name])) {
				if (!empty($other_args[0])) {
					$sort_options = $other_args[0];
				}
				if (empty($sort_options) || !is_array($sort_options)) {
					$sort_options = array();
				}
				$limits = '';
				if (!empty($other_args[1]) && is_string($other_args[1])) {
					$limits = $other_args[1];
				}
				$this->_the_many_models_i_have[$related_model_name] = ModelFactory::instance($related_model_name)->find_all_by($my_id_attribute, $this->id(), $sort_options, '', $limits);
			}
			return $this->_the_many_models_i_have[$related_model_name];
		}
		if (in_array($related_model_name, $this->_belongs_to)) {
			if (!empty($this->_belongs_to_key_names[$related_model_name])) {
				$related_id_attribute = $this->_belongs_to_key_names[$related_model_name];
			} else {
				$related_id_attribute = $attr_name.'_id';
			}
			if ($this->_has_attribute($related_id_attribute)) {
				if ($this->$related_id_attribute()) {
					if (empty($this->_the_single_models_i_belong_to[$related_model_name])) {
						$this->_the_single_models_i_belong_to[$related_model_name] = ModelFactory::instance($related_model_name)->find($this->$related_id_attribute());
					}
					return $this->_the_single_models_i_belong_to[$related_model_name];
				}
				return null;
			}
		}
		if (array_key_exists($related_model_name, $this->_has_and_belongs_to_many)) {
			$connecting_table_name = $this->_has_and_belongs_to_many[$related_model_name];
			$my_id_attribute = AkInflector::underscore(Crumbs::normalized_model_name($this)).'_id';
			$related_id_attribute = AkInflector::singularize($attr_name).'_id';
			if (empty($this->_the_models_i_have_and_belong_to[$related_model_name])) {
				if (!empty($other_args[0])) {
					$sort_options = $other_args[0];
				}
				if (empty($sort_options) || !is_array($sort_options)) {
					$sort_options = array();
				}
				$limits = '';
				if (!empty($other_args[1]) && is_string($other_args[1])) {
					$limits = $other_args[1];
				}
				$this->_the_models_i_have_and_belong_to[$related_model_name] = ModelFactory::find_all_related($connecting_table_name, $my_id_attribute, $this->id(), $related_model_name, $related_id_attribute, $sort_options, '', $limits);
			}
			return $this->_the_models_i_have_and_belong_to[$related_model_name];
		}
		return 'invalid';
	}
	/**
	 * Whether or not an attribute is required. First checks against the database descriptor, then against _required_attributes array to allow overriding the database.
	 *
	 * @param string $attr_name 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function _attr_is_required($attr_name) {
		$required = false;
		if (array_key_exists($attr_name,$this->_attributes)) {
			$required = $this->_attributes[$attr_name]['is_required'];
		} else if (array_key_exists($attr_name,$this->_other_attributes)) {
			$required = $this->_other_attributes[$attr_name]['is_required'];
		}
		if (!$required) {
			$required = in_array($attr_name,$this->_required_attributes);
		}
		return $required;
	}
	/**
	 * Whether or not an attribute has a value, or a file upload if applicable
	 *
	 * @param string $attr_name 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function _attr_is_valid($attr_name) {
		$required_method = $attr_name.'_is_required';
		if ($this->$required_method($attr_name)) {
			if ($attr_name == 'id' && $this->is_new()) {
				return true;
			} else {
				if ($this->_attr_has_upload($attr_name) && $this->is_new()) {
					if (Request::is_ajax() && !$this->user_input($attr_name.'_file')) {
						// On Ajax requests we just see if the filename value has been submitted
						return false;
					} else if (!Request::is_ajax()) {
						// On normal request, if a filename is already set for the attribute use file_exists to validate:
						$filename = $this->$attr_name();
						if (!empty($filename)) {
							return file_exists(SITE_ROOT.$this->upload_path($attr_name).'/'.$filename);
						}
						// Otherwise check to see if it's actually an uploaded file:
						$uploaded_file = Request::files($attr_name.'_file');
						if (empty($uploaded_file) || !is_uploaded_file($uploaded_file['tmp_name'])) {
							return false;
						}
					}
				} else if (stristr($attr_name,'email')) {
					// If attribute is named something that contains "email" do valid email check
					return Crumbs::valid_email($this->$attr_name());
				} else {
					// Special consideration needs to be taken for date fields that have default db values of all zeros
					$attr_type = $attr_name.'_field_type';
					$attr_default = $attr_name.'_field_default';
					if ($this->$attr_name() === null || ($this->$attr_type() == 'date' && $this->$attr_name() == '0000-00-00') || ($this->$attr_type() == 'datetime' && $this->$attr_name() == '0000-00-00 00:00:00')) {
						return false;
					}
				}
			}
		}
		return true;
	}
	/**
	 * Whether or not an attribute has file uploads
	 *
	 * @param string $attr_name 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function _attr_has_upload($attr_name) {
		return in_array($attr_name,$this->_attributes_with_uploads);
	}
	/**
	 * Whether or not an attribute is a reference to a file from the file manager
	 *
	 * @param string $attr_name 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function _attr_has_managed_file($attr_name) {
		return in_array($attr_name,$this->_file_managed_attributes);
	}
	/**
	 * Return the label for an attribute name
	 *
	 * @param string $attr_name 
	 * @return string
	 * @author Peter Epp
	 */
	protected function _attr_label($attr_name) {
		if (!empty($this->_attr_labels[$attr_name])) {
			$label = $this->_attr_labels[$attr_name];
		} else {
			$label = AkInflector::humanize($attr_name,'all');
		}
		return $label;
	}
	/**
	 * Return the type of a field
	 *
	 * @param string $attr_name 
	 * @return string
	 * @author Peter Epp
	 */
	protected function _attr_field_type($attr_name) {
		if (array_key_exists($attr_name,$this->_attributes)) {
			return $this->_attributes[$attr_name]['type'];
		} else if (array_key_exists($attr_name,$this->_other_attributes)) {
			return $this->_other_attributes[$attr_name]['type'];
		}
	}
	/**
	 * Return the database default value for a field
	 *
	 * @param string $attr_name 
	 * @return mixed
	 * @author Peter Epp
	 */
	protected function _attr_field_default($attr_name) {
		if (array_key_exists($attr_name,$this->_attributes)) {
			return $this->_attributes[$attr_name]['default'];
		} else if (array_key_exists($attr_name,$this->_other_attributes)) {
			return $this->_other_attributes[$attr_name]['default'];
		}
	}
	/**
	 * Whether or not the model is new or an existing one in the database
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function is_new() {
		return (!$this->id() && $this->id() !== 0 && $this->id() !== '0');
	}
	/**
	 * Validate that all required attributes are set
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function validate() {
		$attributes = $this->get_attributes();
		$other_attributes = $this->get_other_attributes();
		if (!empty($other_attributes)) {
			$all_attributes = array_merge($attributes,$other_attributes);
		} else {
			$all_attributes = $attributes;
		}
		foreach ($all_attributes as $attr_name => $attr_value) {
			$validation_method = $attr_name.'_is_valid';
			if (!$this->$validation_method($attr_name)) {
				$this->set_error($attr_name);
			}
		}
		if ($this->user_input('captcha_required') == 1 && !$this->_ignore_captcha) {
			if (!Captcha::matches($this->user_input('security_code'))) {
				$this->set_error("security_code",__("Provide the correct security code shown in the image. If you cannot read the code, please click the link next to it for a new one."));
			}
		}
		$is_valid = (!$this->errors());
		if ($is_valid && method_exists($this,"_set_attribute_defaults")) {
			$this->_set_attribute_defaults();
		}
		$this->_has_been_validated(true);
		return $is_valid;
	}
	/**
	 * Strip private attributes, if defined, from supplied user input
	 *
	 * @param string $user_input 
	 * @return array
	 * @author Peter Epp
	 */
	public function strip_private_attributes($user_input) {
		if (!empty($user_input) && !empty($this->_private_attribute_names)) {
			foreach ($this->_private_attribute_names as $attribute_name) {
				if (array_key_exists($attribute_name, $user_input)) {
					unset($user_input[$attribute_name]);
				}
			}
		}
		return $user_input;
	}
	/**
	 * Set or get the validation status
	 *
	 * @param string $bool 
	 * @return void
	 * @author Peter Epp
	 */
	protected function _has_been_validated($bool = null) {
		if ($bool !== null) {
			$this->_validated = $bool;
		}
		else {
			return $this->_validated;
		}
	}
	/**
	 * Define the model attributes from attribute descriptors based on the DB description
	 *
	 * @param array $attr_descriptions Descriptions of the attributes retrieved from the database by the factory
	 * @return void
	 * @author Peter Epp
	 */
	public function define_attributes($attribute_descriptors) {
		$this->_attributes = $attribute_descriptors;
	}
	/**
	 * Define other attributes associated with this model from descriptors based on the DB description
	 *
	 * @param string $attribute_descriptors 
	 * @return void
	 * @author Peter Epp
	 */
	public function define_other_attributes($attribute_descriptors) {
		$this->_other_attributes = $attribute_descriptors;
	}
	/**
	 * Return the list of associated model names
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function associated_models() {
		return $this->_associated_models;
	}
	/**
	 * Return the list of attributes used for a specified associated model, if present, or false if not
	 *
	 * @param string $associated_model_name 
	 * @return array|bool
	 * @author Peter Epp
	 */
	public function associated_attributes($model_name) {
		if (!empty($this->_associated_attributes[$model_name])) {
			return $this->_associated_attributes[$model_name];
		} else {
			return false;
		}
	}
	/**
	* Set attribute values
	*
	* @param array $attributes
	* @author Lee O'Mara
	*/
	public function set_attributes($attribute_values) {
		if (!empty($attribute_values)) {
			foreach ($attribute_values as $attr_name => $value) {
				$set_method = 'set_'.$attr_name;
				if (is_string($value)) {
					$value = trim($value);
				}
				$this->$set_method($value);
			}
		}
		$this->_set_default_attribute_values();
	}
	/**
	 * Set default values for any empty attributes
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _set_default_attribute_values() {
		foreach ($this->_attributes as $attr_name => $attribute) {
			if (empty($attribute['value']) && $attribute['value'] !== 0 && $attribute['value'] !== '0' && $attr_name != 'id') {
				// A value for the attribute hasn't been provided
				$default_value_method = $attr_name.'_field_default';
				$use_value = $this->$default_value_method();
				$set_method = 'set_'.$attr_name;
				$this->$set_method($use_value);
			}
		}
	}
	/**
	 * Set the value of an attribute, if it exists. If not, setting will fail
	 *
	 * @param string $attr_name 
	 * @param string $value 
	 * @return bool Success or failure
	 * @author Peter Epp
	 */
	protected function _set_attribute($attr_name, $value) {
		if (array_key_exists($attr_name,$this->_attributes)) {
			$this->_attributes[$attr_name]['value'] = $value;
			return true;
		} else if (array_key_exists($attr_name,$this->_other_attributes)) {
			$this->_other_attributes[$attr_name]['value'] = $value;
			return true;
		}
		return false;
	}
	/**
	 * Return all the attribute values in an associative array
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function get_attributes() {
		if (!empty($this->_attributes)) {
			$my_vars = array();
			foreach ($this->_attributes as $key => $attribute) {
				$my_vars[$key] = $this->_get_attribute($key);
			}
			return $my_vars;
		}
		else {
			return null;
		}
	}
	/**
	 * Return a specified attribute of the object
	 *
	 * @param string $name Name of the attribute
	 * @return mixed
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	protected function _get_attribute($key) {
		if (array_key_exists($key,$this->_attributes) && (!empty($this->_attributes[$key]['value']) || $this->_attributes[$key]['value'] === 0 || $this->_attributes[$key]['value'] === '0') && $this->_attributes[$key]['value'] !== '0000-00-00' && $this->_attributes[$key]['value'] !== '0000-00-00 00:00:00') {
			return $this->_attributes[$key]['value'];
		} else if (array_key_exists($key,$this->_other_attributes) && (!empty($this->_other_attributes[$key]['value']) || $this->_other_attributes[$key]['value'] === 0 || $this->_other_attributes[$key]['value'] === '0') && $this->_other_attributes[$key]['value'] !== '0000-00-00' && $this->_other_attributes[$key]['value'] !== '0000-00-00 00:00:00') {
			return $this->_other_attributes[$key]['value'];
		}
		return null;
	}
	/**
	 * Whether or not the model has a given attribute
	 *
	 * @param string $key 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function _has_attribute($key) {
		return (array_key_exists($key,$this->_attributes) || array_key_exists($key,$this->_other_attributes));
	}
	/**
	 * Return all the other attribute values in an associative array
	 *
	 * @return mixed
	 * @author Peter Epp
	 */
	public function get_other_attributes() {
		if (!empty($this->_other_attributes)) {
			$my_vars = array();
			foreach ($this->_other_attributes as $key => $attribute) {
				$my_vars[$key] = $this->_get_attribute($key);
			}
			return $my_vars;
		}
		else {
			return null;
		}
	}
	/**
	 * Purify all attributes on the model using HTML purifier.  This relies on HTML purifier, but does not check for it's existence. You must check for HTML Purifier
	 * in the run function of your plugin whose model(s) need to purify their attributes.
	 *
	 * @param string $mode "text" or "html"
	 * @return void
	 * @author Peter Epp
	 */
	protected function _purify_attributes($mode = "text",$filters = array()) {
		// Prevent nasty errors by forcing mode to "text" if an invalid argument value was provided:
		$mode = strtolower($mode);
		if ($mode != "text" && $mode != "html") {
			$mode = "text";
		}
		$dirty_attributes = $this->get_attributes();
		$purify_method = "purify_array_".$mode;
		if ($mode == "text") {
			$args = array($dirty_attributes);
		}
		else {
			$args = array($dirty_attributes,$filters);
		}
		$purified_attributes = call_user_func_array(array("H",$purify_method),$args);
		$this->set_attributes($purified_attributes);
	}
	/**
	 * Return the list of attributes that failed validation (getter for $_invalid_attributes)
	 *
	 * @param return $this 
	 * @return void
	 * @author Peter Epp
	 */
	public function invalid_attributes()	{		return $this->_invalid_attributes;	}
	/**
	 * Whether or not the model has any file upload fields
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function has_upload_fields() {
		return (!empty($this->_attributes_with_uploads));
	}
	/**
	 * Save data to the object's defined database table
	 *
	 * @param array $data Associative array of data with keys matching the database columns to store
	 * @return void
	 * @author Peter Epp
	 */
	public function save($bypass_validation = false) {
		if (!$bypass_validation && !$this->_has_been_validated() && !$this->validate()) {
			return false;
		}
		if ($bypass_validation && method_exists($this,"_set_attribute_defaults")) {
			$this->_set_attribute_defaults();
		}
		Console::log("                        Saving ".get_class($this));
		if ($this->_handle_file_upload_or_removal()) {
			// Filter attributes for saving to only those that are not empty and belong in the database
			$attributes = $this->get_attributes();
			$db_table = $this->_db_table();
			if (!$this->is_new()) {
				// If id is included in array, update the existing row
				Console::log("                        Updating existing data...");
				$query = "UPDATE `".$db_table."` SET ";
			} else {
				// Otherwise insert a new row
				Console::log("                        Inserting new data...");
				$query = "INSERT INTO `".$db_table."` SET ";
				if (method_exists($this, 'set_custom_id')) {
					$attributes['id'] = $this->set_custom_id();
					$this->_uses_custom_id = true;
				}
			}
			//  Add the data to the query string:
			$query .= DB::query_from_data($attributes);
			$pdo_data = DB::pdo_assoc_array($attributes);
			if (!$this->is_new()) {
				$pdo_data[':id'] = $this->id();
				$query .= " WHERE `id` = :id";
				$result = DB::query($query, $pdo_data);
			} else {
				if ($this->_uses_custom_id) {
					$result = DB::query($query, $pdo_data);
					if ($result) {
						$this->set_id($attributes['id']);
					}
				} else {
					$result = DB::insert($query, $pdo_data);		// False or new row id
					if ($result) {
						$this->set_id($result);
					}
				}
			}
			if ($result) {
				$this->_record_updated_date();
				$result = $this->_save_associated_data();
			}
			return $result;
		}
		return false;
	}
	/**
	 * Save data to associated models
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function _save_associated_data() {
		if (!empty($this->_associated_models)) {
			$data = $this->get_other_attributes();
			if (!empty($data)) {
				$success_count = 0;
				foreach ($this->_associated_models as $model_name) {
					$foreign_key = AkInflector::underscore($model_name).'_id';
					$model = ModelFactory::instance($model_name)->find($this->$foreign_key());
					if (!$model) {
						$model = $factory->create();
					}
					$model->set_attributes($data);
					$saved = $model->save();
					if (!$saved) {
						$model_errors = $model->errors();
						foreach($model_errors as $error_msg) {
							$this->set_error($error_msg);
						}
						break;
					} else {
						$success_count += 1;
					}
					unset($factory,$model);
				}
				return ($success_count == count($this->_associated_models));
			}
		}
		return true;
	}
	/**
	 * Handle processing of new upload and removal of existing file
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _handle_file_upload_or_removal() {
		if (!empty($this->_attributes_with_uploads)) {
			foreach ($this->_attributes_with_uploads as $attribute_name) {
				if ($this->user_input('remove_'.$attribute_name)) {
					$this->_remove_file($attribute_name);
				}
				$this->_process_upload($attribute_name);
			}
		}
		return (!$this->errors());
	}
	/**
	 * Process uploaded file, if any, for a given attribute
	 *
	 * @param string $attribute_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function _process_upload($attribute_name) {
		$set_attribute = 'set_'.$attribute_name;
		$upload_path = $this->upload_path($attribute_name);
		$file_fieldname = $attribute_name.'_file';
		Console::log("                        Checking for uploaded file...");
		$all_uploads = Request::files();
		if (!empty($all_uploads) && !empty($all_uploads[$file_fieldname])) {
			if (DEBUG) {
				Console::log_var_dump('Uploaded files for '.get_class($this),$all_uploads[$attribute_name.'_file']);
			}
			$old_filename = '';
			if (!$this->is_new() && $this->$attribute_name() !== null) {
				$old_filename = $this->$attribute_name();
			}
			$uploaded_file = new FileUpload($all_uploads[$file_fieldname], $upload_path);
			if ($uploaded_file->is_okay()) {
				// uploaded, processed okay
				Console::log("                        Successfully uploaded file as: ".$uploaded_file->file_name());
				if (!empty($old_filename)) {
					Console::log("                        Deleting old file: ".$old_filename);
					Crumbs::delete_file_or_folder(SITE_ROOT.$upload_path."/".$old_filename);
				}
				$this->$set_attribute($uploaded_file->file_name());
			} elseif ($uploaded_file->no_file_sent()) {
				Console::log("                        No file uploaded, chill"); // let validation catch the error, if one exists
			} else {
				$this->set_error($attribute_name,sprintf(__("Upload of <strong>%s</strong> failed: %s"),$uploaded_file->file_name(), $uploaded_file->get_error_message()));
			}
		} else {
			Console::log("                        No file upload data, chill");
		}
	}
	/**
	 * Delete a row from the DB table for the current model
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	public function delete() {
		$db_table = $this->_db_table();
		$id = $this->id();
		if ($this->_delete_uploaded_files()) {
			$this->_record_updated_date();
			return DB::query("DELETE FROM `{$db_table}` WHERE `id` = ?", array($id));
		}
		return false;
	}
	/**
	 * Delete all files for this model
	 *
	 * @return bool Success
	 * @author Peter Epp
	 */
	protected function _delete_uploaded_files() {
		$success_count = 0;
		$upload_count = 0;
		if (!empty($this->_attributes_with_uploads)) {
			foreach ($this->_attributes_with_uploads as $attribute_name) {
				if ($this->$attribute_name() != null) {
					$upload_count += 1;
					if ($this->_remove_file($attribute_name)) {
						$success_count += 1;
					}
				}
			}
		}
		return ($success_count == $upload_count);
	}
	/**
	 * Delete the file associated with a given attribute
	 *
	 * @param string $attribute_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function _remove_file($attribute_name) {
		Console::log("                        Deleting ".$attribute_name." file...");
		$full_file_path = SITE_ROOT.$this->upload_path($attribute_name).'/'.$this->$attribute_name();
		if (Crumbs::delete_file_or_folder($full_file_path)) {
			$attribute_setter = 'set_'.$attribute_name;
			$this->$attribute_setter('');
		} else {
			$this->set_error(null, sprintf(__("Failed to delete file: %s"),__($this->$attribute_name())));
			return false;
		}
		return true;
	}
	/**
	 * Return info about a file attribute - size, date, download URL and filename
	 *
	 * @param string $attribute_name Name of the attribute whose file information you want to retrieve
	 * @return array Or boolean - array of data if file exists, false if file does not exist
	 * @author Peter Epp
	**/
	public function file_info($attribute_name) {
		Console::log("                        ".get_class($this).": get file info for attribute: ".$attribute_name);
		if ($this->$attribute_name() != null) {
			$full_file_path = $this->upload_path($attribute_name).'/'.$this->$attribute_name();
			if (file_exists(SITE_ROOT.$full_file_path)) {
				Console::log("                        File found: ".$full_file_path);
				$file_size = Crumbs::formatted_file_size($full_file_path);
				$file_date = date('M j Y', filemtime(SITE_ROOT.$full_file_path));
				$file_info = array(
					"size" => $file_size,
					"date" => $file_date,
					"download_url" => $full_file_path,
					"file_name" => $this->$attribute_name(),
					"is_image" => false
				);
				$image_type = exif_imagetype(SITE_ROOT.$full_file_path);
				if ($image_type == IMAGETYPE_JPEG || $image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
					$file_info['is_image'] = true;
					// Add on some useful image data:
					$image_size = getimagesize(SITE_ROOT.$full_file_path);
					Console::log("Image info:\n".print_r($image_size,true));
					$file_info['image_width']  = $image_size[0];
					$file_info['image_height'] = $image_size[1];
					$file_info['mime']         = $image_size['mime'];
				}
				return $file_info;
			}
		}
		Console::log("                        File not found, moving on...");
		return false;
	}
	/**
	 * Return info about an image file attribute - size, date, download URL, thumbnail URL, filename, image dimensions and type, thumbnail dimensions and type as well as HTML image tag attributes of both
	 *
	 * @param string $attribute_name 
	 * @return mixed Array of data or false if no file exists
	 * @author Peter Epp
	 */
	public function image_info($attribute_name) {
		Console::log("                        ".get_class($this).": get file info for image");
		if ($this->$attribute_name() != null) {
			$full_file_path = $this->upload_path($attribute_name).'/'.$this->$attribute_name();
			$full_thumb_path = $this->thumbnail_path($attribute_name).'/_'.$this->$attribute_name();
			$full_original_path = $this->upload_path($attribute_name).'/_originals/'.$this->$attribute_name();
			if (!file_exists(SITE_ROOT.$full_original_path)) {
				$full_original_path = null;
			}
			if (file_exists(SITE_ROOT.$full_file_path)) {
				Console::log("                        File found: ".$full_file_path);
				$file_size = Crumbs::formatted_file_size($full_file_path);
				$file_date = date('M j Y', filemtime(SITE_ROOT.$full_file_path));
				list($width,$height,$type,$attributes) = getimagesize(SITE_ROOT.$full_file_path);
				list($thumb_width,$thumb_height,$thumb_type,$thumb_attributes) = getimagesize(SITE_ROOT.$full_thumb_path);
				return array(
					"file_size"        => $file_size,
					"file_date"        => $file_date,
					"download_url"     => $full_file_path,
					"thumbnail_url"    => $full_thumb_path,
					"original_url"     => $full_original_path,
					"file_name"        => $this->$attribute_name(),
					"width"            => $width,
					"height"           => $height,
					"type"             => $type,
					"attributes"       => $attributes,
					"thumb_width"      => $thumb_width,
					"thumb_height"     => $thumb_height,
					"thumb_type"       => $thumb_type,
					"thumb_attributes" => $thumb_attributes
				);
			} else {
				Console::log("                        File not found, moving on...");
			}
		}
		return false;
	}
	/**
	 * Return the upload path for an attribute, or the base path if no special upload folder has been defined for the specified attribute.
	 *
	 * @param string $attribute 
	 * @return string
	 * @author Peter Epp
	 */
	public function upload_path($attribute_name) {
		$path = '/var/uploads/'.AkInflector::underscore(Crumbs::normalized_model_name($this));
		if (count($this->_attributes_with_uploads) > 1) {
			$path .= '/'.AkInflector::pluralize($attribute_name);
		}
		return $path;
	}
	/**
	 * Return the path to thumbnail images (if it exists) for a given attribute
	 *
	 * @param string $attribute_name 
	 * @return mixed
	 * @author Peter Epp
	 */
	public function thumbnail_path($attribute_name) {
		$thumb_path = $this->upload_path($attribute_name).'/_thumbs';
		if (file_exists(SITE_ROOT.$thumb_path)) {
			return $thumb_path;
		}
		return false;
	}
	/**
	 * Set the validation error message for a given attribute
	 *
	 * @param string $attr_name The name of the attribute on which the validation error occurred
	 * @param string|null $message Optional - a message to override the default
	 * @return void
	 * @author Peter Epp
	 */
	public function set_error($attr_name,$message = null) {
		if (!empty($attr_name) && !in_array($attr_name,$this->_invalid_attributes)) {
			$this->_invalid_attributes[] = $attr_name;
		}
		if (empty($this->_error_messages[$attr_name])) {
			if (empty($message)) {
				// If no specific message is supplied, set the message using some logic:
				$error_method = $attr_name.'_error_message';
				if (method_exists($this,$error_method)) {
					// If a method is defined on the model for specifying the error for this attribute, use it:
					$message = $this->$error_method();
				} else if ($this->_attr_has_upload($attr_name) || $this->_attr_has_managed_file($attr_name)) {
					// Otherwise, if the attribute contains an uploaded file, set an appropriate message:
					$message = sprintf(__("Select a file for %s"),__($this->_attr_label($attr_name)));
				} else if ($attr_name == 'email' || $attr_name == 'email_address') {
					// If it's an email address:
					$message = __("Provide a valid email address");
				} else {
					// Generic error message assuming that the field need only not be blank:
					$message = sprintf(__("Provide a value for %s"),__($this->_attr_label($attr_name)));
				}
			}
			$this->_error_messages[$attr_name] = $message;
		}
	}
	/**
	 * Return the _error_messages property
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function errors() {
		if (!empty($this->_error_messages)) {
			return $this->_error_messages;
		}
		return false;
	}
	/**
	 * Record the date this model was updated
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function _record_updated_date() {
		$my_name = get_class($this);
		if ($my_name == 'GenericModel') {
			$my_name = $this->_model_name;
		}
		$last_update = date("Y-m-d H:i:s");
		DB::query("REPLACE INTO `model_last_updated` SET `model_name` = '{$my_name}', `date` = '{$last_update}'");
	}
	/**
	 * Return the name of the database table. This essentially does the same thing as the db_table() method of the model factory, but
	 * for use within the model which doesn't have an instance of it's factory.
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function _db_table() {
		$my_name = get_class($this);
		if (method_exists($my_name,'db_tablename')) {
			return call_user_func(array($my_name,'db_tablename'));
		}
		else {
			$model_class = Crumbs::normalized_model_name($this);
			return AkInflector::tableize($model_class);
		}
	}
	/**
	 * Shortcut to Request::user_input() method
	 *
	 * @param string $key 
	 * @return void
	 * @author Peter Epp
	 */
	protected function user_input($key) {
		return Request::user_input($key);
	}
	/**
	 * Return the string representation of the model
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function __toString() {
		$friendly_model_name = ucwords(AkInflector::humanize(AkInflector::underscore(get_class($this))));
		if ($this->_has_attribute('title')) {
			$name = $this->title();
		}
		if ($this->_has_attribute('name')) {
			$name = $this->name();
		}
		if (method_exists($this,'title_attribute')) {
			$title_attribute = $this->title_attribute();
			$name = $this->$title_attribute();
		}
		if (!empty($name)) {
			return sprintf(__('the %s "%s"'),$friendly_model_name,$name);
		}
		return sprintf(__('the %s with ID %d'),$friendly_model_name,$this->id());
	}
}
