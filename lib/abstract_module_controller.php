<?php
/**
 * And abstract controller for use with modules. It requires the abstract module class, the two of which together provide the following functionality:
 * - Dispatch to actions
 * - Register Biscuit object
 * - Set view file based on action
 * - Setting page
 * - Check database installation for module and install tables if module has the appropriate methods for providing data needed for installation
 * - Check permissions for actions
 *
 * @package Modules
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class AbstractModuleController extends AbstractModule {
	/**
	 * A reference to the Biscuit object
	 *
	 * @var Biscuit
	 **/
	public $Biscuit;
	/**
	 * Whether or not dependencies have been checked
	 *
	 * @var bool
	 */
	protected $_dependencies_checked = false;
	/**
	 * Whether or not action-specific dependencies have been checked
	 *
	 * @var bool
	 */
	protected $_action_dependencies_checked = false;
	/**
	 * List of modules and extensions this module depends on
	 *
	 * @var array
	 */
	protected $_dependencies = array();
	/**
	 * Whether or not the module is primary
	 *
	 * @var bool
	 */
	protected $_is_primary;
	/**
	 * List of validation errors
	 *
	 * @var array
	 */
	protected $_validation_errors = array();
	/**
	 * List of fields that failed validation
	 *
	 * @var array
	 */
	protected $_invalid_fields = array();
	/**
	 * Array of model factory objects
	 *
	 * @var array
	 */
	private static $_model_factories = array();
	/**
	 * List of models used by the module
	 *
	 * @var array
	 */
	protected $_models = array();
	/**
	 * The first model in the list
	 *
	 * @var string
	 */
	protected $_primary_model;
	/**
	 * Array of sorting options for the index action in the format array("column_name" => "[ASC/DESC]")
	 *
	 * @var string
	 */
	protected $_index_sort_options = array();
	/**
	 * List of actions that require an ID in the request, in addition to the base actions that always require an id (show, edit, delete)
	 *
	 * @var string
	 */
	protected $_actions_requiring_id = array();
	/**
	 * List of actions that can be cached in addition to the defaults
	 *
	 * @var array
	 */
	protected $_cacheable_actions = array();
	/**
	 * List of actions that may not be cached that are cacheable by default
	 *
	 * @var array
	 */
	protected $_uncacheable_actions = array();
	/**
	 * The page on which the current module is installed as primary
	 *
	 * @var string
	 */
	protected $_primary_page;
	/**
	 * Place to cache models instantiated when call a URL for the show action so they don't get instantiated more than once for each requested ID
	 *
	 * @var array
	 */
	protected $_models_for_show_url = array();
	/**
	 * Action to run on successful save when it's an ajax request
	 *
	 * @var string
	 */
	protected $_successful_save_ajax_action = 'index';
	/**
	 * Parameters passed by user input
	 *
	 * @var array
	 */
	public $params = array();
	/**
	 * Dispatch to action methods if found. Before the action_{action-name} is fired,
     * before_filter(if found) is run. If before_filter returns false, the program exits.
	 *
	 * @abstract
	 * @param array $params 
	 * @return void
	 */	
	public function run() {
		if (!empty($this->_models)) {
			// Ensure that the first element of the array is current:
			reset($this->_models);
			// Set the primary model name to the key name of the current element:
			$this->_primary_model = key($this->_models);
		}
		if (!$this->is_primary()) {
			if (method_exists($this,'action_secondary')) {
				Console::log("                        Dispatching to ".get_class($this)."::action_secondary()");
				$this->action_secondary();
			}
		}
		else {
			if (Request::is_ajax() && Request::type() == "validation") {
				$this->action_ajax_validate();
			}
			else {
				$this->check_action_dependencies();
				$action_method = 'action_'.$this->action();
				if (!method_exists($this, $action_method)) {
					$base_action_name = $this->base_action_name($this->action());
					if (!$base_action_name) {
						trigger_error(get_class($this)."::{$action_method}() not defined!", E_USER_ERROR);
					} else {
						$action_method = 'action_'.$base_action_name;
					}
				}
				if (Permissions::can_execute($this,$this->action())) {
					Console::log("                        Dispatching to ".get_class($this)."::{$action_method}()");
					$this->$action_method();
				}
				else {
					if ($this->Biscuit->ModuleAuthenticator()->user_is_logged_in()) {
						Session::flash('user_error',__("You do not have permission to access the requested page."));
						if ($this->action() != 'index' && $this->user_can_index()) {
							Response::redirect($this->url());
						} else {
							Response::redirect('/');
						}
					} else {
						Session::flash('user_message',__("Please login to access the requested page."));
						$access_level = Permissions::access_level_for($this,$this->action());
						$access_info_factory = new ModelFactory('AccessLevels');
						$access_info = $access_info_factory->find($access_level);
						Session::flash('login_redirect',Request::uri());
						Response::redirect($access_info->login_url());
					}
				}
			}
		}
	}
	/**
	 * Get or set the current action
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function action($action_name = null) {
		if (is_string($action_name)) {
			return ($this->params['action'] = $action_name);
		}
		if (!empty($this->params['action'])) {
			return $this->params['action'];
		}
		return "index";
	}
	/**
	 * Default index action, no sorting
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_index() {
		if (!empty($this->_models)) {
			$model_name = $this->infer_model_name('index');
			$data_name = AkInflector::underscore(AkInflector::pluralize($model_name));
			$models = $this->$model_name->find_all($this->_index_sort_options);
			Event::fire('instantiated_models',$models);
			$this->set_view_var($data_name,$models);
		}
		$this->render();
	}
	/**
	 * Respond to a show request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_show() {
		$model = $this->model_for_showing($this->params['id']);
		if (!$model) {
			throw new RecordNotFoundException();
		}
		$this->enforce_canonical_show_url($model);
		$model_name = $this->infer_model_name('show');
		$data_name = AkInflector::underscore(AkInflector::singularize($model_name));
		$this->set_show_title($model);
		Event::fire('instantiated_model',$model);
		$this->set_view_var($data_name,$model);
		$this->render();
	}
	/**
	 * Check the URL for the show action for a model compared to the request URI.  If they don't match, redirect to the proper show URL
	 * (with any query string in tact).  This will enforce the correct, canonical show URL (with friendly slug, if available) for the requested
	 * record.
	 *
	 * @param string $model 
	 * @return void
	 * @author Peter Epp
	 */
	protected function enforce_canonical_show_url($model) {
		$show_url = $this->url('show',$model->id());
		$request_uri = Request::uri();
		$query_string = '';
		if (preg_match('/\?/',$request_uri)) {
			// Separate the query string, if present
			$query_string = substr($request_uri,strpos($request_uri,'?'));
			$request_uri = substr($request_uri,0,strpos($request_uri,'?'));
		}
		if ($show_url != $request_uri) {
			Response::redirect($show_url.$query_string, true);
		}
	}
	/**
	 * Return an instance of a model for the "show" action. This is a special case that caches the models that get instantiated in an array by ID so they never
	 * need to be looked up more than once, either for the actual show action or when calling for the "show" URL, which tries to add a friendly suffix using the model
	 *
	 * @param string $id 
	 * @param string $real_action 
	 * @return void
	 * @author Peter Epp
	 */
	protected function model_for_showing($id, $real_action = null) {
		$model_name = $this->infer_model_name('show', $real_action);
		if (empty($this->_models_for_show_url[$model_name][$id])) {
			$this->_models_for_show_url[$model_name][$id] = $this->$model_name->find($id);
		}
		return $this->_models_for_show_url[$model_name][$id];
	}
	/**
	 * Shortcut to the edit action for new items
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_new() {
		$this->action_edit('new');
	}
	/**
	 * Respond to/process an edit request
	 *
	 * @param string $mode 'edit' (default) or 'new'
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_edit($mode = 'edit') {
		if (empty($mode)) {
			$mode = 'edit';
		}
		$this->Biscuit->set_never_cache();	// Never cache edit form, since request token changes every time
		$model_name = $this->infer_model_name($mode);
		$data_name = AkInflector::underscore(AkInflector::singularize($model_name));
		$this->set_edit_title($mode,$model_name);
		$show_action = 'show';
		if ($model_name != $this->_primary_model) {
			$show_action .= '_'.AkInflector::underscore($model_name);
		}
		if (empty($this->params['id'])) {
			$data_defaults = array();
			if (!Request::is_post() && !empty($this->params[$data_name.'_defaults'])) {
				$data_defaults = $this->params[$data_name.'_defaults'];
			}
			$model = $this->$model_name->create($data_defaults);
		} else {
			$model = $this->$model_name->find($this->params['id']);
			if (!$model) {
				throw new RecordNotFoundException();
			}
			if (Request::is_post()) {
				$old_show_url = $this->url($show_action,$model->id());
			}
		}
		if (Request::is_post()) {
			if (!empty($this->params[$data_name])) {
				// Replace attributes with user input:
				$model->set_attributes($this->params[$data_name]);
			}
			Event::fire('instantiated_model',$model);
			if ($model->save()) {
				$this->_models_for_show_url[get_class($model)][$model->id()] = $model;		// Ensure the cached model for showing is updated before trying to get the new URL
				$new_show_url = $this->url($show_action,$model->id());
				Event::fire('successful_save',$model,$old_show_url,$new_show_url);
				$this->success_save_response($this->return_url($model_name));
			}
			else {
				Event::fire('failed_save',$model);
				$this->failed_save_response($model,$data_name);
			}
		}
		else {
			Event::fire('instantiated_model',$model);
			$this->set_view_var($data_name,$model);
			$this->render();
		}
	}
	/**
	 * Set the action to run on successful save when it's an ajax request
	 *
	 * @param string $action_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function set_successful_save_ajax_action($action_name) {
		$this->_successful_save_ajax_action = $action_name;
	}
	/**
	 * Validate the edit action
	 *
	 * @return bool
	 * @author Peter Epp
	 */
	protected function validate_edit() {
		$model_name = $this->infer_model_name('edit');
		$data_name = AkInflector::underscore(AkInflector::singularize($model_name));

		if (empty($this->params['id'])) {
			$item = $this->$model_name->create();
		} else {
			$item = $this->$model_name->find($this->params['id']);
		}
		if (!empty($this->params[$data_name])) {
			$item->set_attributes($this->params[$data_name]);
		}
		$is_valid = $item->validate();
		if (!$is_valid) {
			$this->_validation_errors = $item->errors();
			$this->_invalid_fields = $item->invalid_attributes();
		}
		return $is_valid;
	}
	/**
	 * Whether or not a specified input field is valid
	 *
	 * @param string $field_name 
	 * @return bool
	 * @author Peter Epp
	 */
	public function field_is_valid($field_name) {
		return !in_array($field_name, $this->_invalid_fields);
	}
	/**
	 * Set the title for the edit page based on mode ('new' or 'edit') and the model name
	 *
	 * @param string $mode 
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function set_edit_title($mode,$model_name) {
		$this->title(AkInflector::titleize($mode.' '.AkInflector::singularize($model_name)));
	}
	/**
	 * Set the title for the show page based on the model name
	 *
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	protected function set_show_title($model) {
		$model_name = get_class($model);
		$this->title(AkInflector::titleize(sprintf(__("View %s"),__(AkInflector::singularize($model_name)))));
	}
	/**
	 * Delete an item and return a response based on the success or failure of the delete operation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_delete() {
		$model_name = $this->infer_model_name('delete');
		$model = $this->$model_name->find($this->params['id']);
		if (!$model) {
			throw new RecordNotFoundException();
		}
		Event::fire('instantiated_model',$model);
		if ($this->confirm_deletion($model,$model_name)) {
			// Before we proceed with delete, get the URL we'll need to pass to the successful_delete event. This is just in case the module has a custom url()
			// method that may need to still lookup the model in order to create the URL before it gets deleted, which would cause an error if it's already
			// been trashed. An example is the page content module.
			$show_action = 'show';
			if ($model_name != $this->_primary_model) {
				$show_action .= '_'.AkInflector::underscore($model_name);
			}
			$url = $this->url($show_action,$model->id());
			// Either the request is post or the delete_confirmed parameter was provided. Proceed with delete operation.
			if (!$model->delete()) {
				Session::flash('user_error', sprintf(__("Failed to remove the %s with ID %d"),__(AkInflector::titleize(AkInflector::singularize($model_name))),$model->id()));
			} else {
				Event::fire("successful_delete",$model,$url);
			}
			if (!Request::is_ajax()) {
				Response::redirect($this->return_url($model_name));
			}
			else {
				$action = $this->get_index_action($model_name);
				$action_method = 'action_'.$action;
				$this->action($action);
				$this->$action_method();
			}
		}
	}
	/**
	 * Check if model deletion has been confirmed and if not render a confirmation form
	 *
	 * @param object $model 
	 * @return bool
	 * @author Peter Epp
	 */
	protected function confirm_deletion($model,$model_name) {
		if (!Request::is_post() && (empty($this->params['delete_confirmed']) || $this->params['delete_confirmed'] != 1)) {
			$data_name = AkInflector::underscore(AkInflector::singularize($model_name));
			// Delete was not confirmed because the request is GET and there was no delete_confirmed parameter. Render the delete confirmation page.
			$this->title(__("Confirm Deletion"));
			if (method_exists($model,'__toString')) {
				$representation = $model;
			} else {
				$representation = sprintf(__("the %s with ID %d",__(AkInflector::humanize(AkInflector::underscore($model_name))),$model->id()));
			}
			$this->set_view_var('representation',$representation);
			$this->set_view_var('cancel_url',$this->return_url($model_name));
			$this->render();
			return false;
		}
		return true;
	}
	/**
	 * Resort the items in a model based on a sorting array
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_resort() {
		$model_name = $this->infer_model_name('resort');
		$data_name = AkInflector::underscore(AkInflector::singularize($model_name));
		$this->$model_name->resort($this->params[$data_name.'_sort']);
		if (!Request::is_ajax()) {
			Response::redirect($this->return_url());
		}
	}
	/**
	 * Call validation and return a response for use by the Biscuit JS Ajax validation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function action_ajax_validate() {
		Console::log('                        Performing AJAX validation for '.get_class($this).' '.$this->action().' action');
		$this->_validation_errors = array();
		$this->_invalid_fields = array();
		$message = "";
		$is_valid = false;
		if (empty($this->params)) {
			$message = __("No data submitted!");
		}
		else {
			$action = $this->action();
			if (substr($action,0,3) == "new") {
				$action = "edit".substr($action,3);
			}
			$validation_method = "validate_".$action;
			if (!method_exists($this, $validation_method)) {
				if (substr($action,0,4) == 'edit') {
					$is_valid = $this->validate_edit();
				} else {
					$this->_validation_errors[] = sprintf(__("ERROR! No validation method for the %s action. I was looking for %s->%s()"),$action,get_class($this),$validation_method);
				}
			}
			else {
				$is_valid = call_user_func(array($this, $validation_method));
			}
		}
		if (!$is_valid) {
			Response::http_status(406);
		}
		$this->Biscuit->render_json(array("messages" => array_values($this->_validation_errors), "invalid_fields" => $this->_invalid_fields));
	}
	/**
	 * Infer the model name based on the request
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function infer_model_name($base_action, $real_action = null) {
		if (empty($real_action)) {
			$action = $this->action();
		} else {
			$action = $real_action;
		}
		if (strlen($action) > strlen($base_action)) {
			$model_name = AkInflector::camelize(substr($action,strlen($base_action)));
			if (array_key_exists($model_name,$this->_models)) {
				return $this->_models[$model_name];
			}
			trigger_error("Invalid model name '{$model_name}' for action '{$action}'", E_USER_ERROR);
		} else {
			return $this->_primary_model;
		}
	}
	/**
	 * Determine the return url to use after an action such as edit or delete is complete
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function return_url($model_name = null) {
		if (!empty($this->params['return_url'])) {
			return $this->params['return_url'];
		} else {
			$index_action = $this->get_index_action($model_name);
			if ($this->user_can($index_action)) {
				return $this->url($index_action);
			} else {
				return '/';
			}
		}
	}
	/**
	 * Put user input into the module's $params array
	 *
	 * @param array $params 
	 * @author Peter Epp
	 */
	public function set_params($params) {
		$this->params = $params;
		if (!$this->action()) {
			$this->action('index');
		} 
	}
	/**
	 * Include and instantiate a factory for each of the models needed by this controller.
	 *
	 * @param array $models List of model names
	 * @return void
	 * @author Peter Epp
	 */
	public function load_models() {
		if (!empty($this->_models)) {
			$my_folder = $this->base_path();
			$parent_folder = $this->parent_base_path();
			$models = $this->_models;
			foreach ($models as $model_label => $model_name) {
				$model_file_name = AkInflector::underscore($model_name).".php";
				if (!class_exists($model_name)) {	// In case it was already included as a common model
					$model_path = $my_folder."/models/".$model_file_name;
					$model_full_path = Crumbs::file_exists_in_load_path($model_path);
					if (!$model_full_path && !empty($parent_folder)) {
						$parent_model_path = $parent_folder.'/models/'.$model_file_name;
						$model_full_path = Crumbs::file_exists_in_load_path($parent_model_path);
					}
					if ($model_full_path) {
						require_once $model_full_path;
					} else {
						throw new ModuleException("Model file not found: ".$model_path." (checked for file in both site and framework)");
					}
				}
				if (class_exists($model_name)) {
					// Check if there's a custom factory for this model:
					$custom_factory_name = $model_name."Factory";
					$custom_factory_path = $my_folder."/factories/".AkInflector::underscore($custom_factory_name).".php";
					if ($custom_factory_full_path = Crumbs::file_exists_in_load_path($custom_factory_path)) {
						require_once $custom_factory_full_path;
						$this->$model_label = new $custom_factory_name();
					}
					else {
						$this->$model_label = new ModelFactory($model_name);
					}
				}
				else {	// In case the included file didn't have the right class in it
					throw new ModuleException("Model not found: ".$model_name);
				}
			}
		}
	}
	/**
	 * Register a reference of the Biscuit in this module
	 *
	 * @abstract
	 * @param string $page The Biscuit object
	 * @return void
	 * @author Peter Epp
	 */
	public function register_biscuit($biscuit_object) {
		$this->Biscuit = $biscuit_object;
		$this->Theme   = $biscuit_object->Theme;
	}
	/**
	 * Register a JS file with Biscuit
	 *
	 * @param string $js_file Name of the file relative to the module's js folder
	 * @return void
	 * @author Peter Epp
	 */
	protected function register_js($position,$js_file,$stand_alone = false) {
		$my_folder = $this->base_path();
		$this->Theme->register_js($position,"{$my_folder}/js/".$js_file,$stand_alone);
	}
	/**
	 * Register a CSS file with Biscuit
	 *
	 * @param array $css_file Associative array of media type and filename relative to the module's css folder
	 * @return void
	 * @author Peter Epp
	 */
	protected function register_css($css_file,$for_ie = false,$ie_version = 'all') {
		$my_folder = $this->base_path();
		$css_file['filename'] = "{$my_folder}/css/".$css_file['filename'];
		if ($for_ie) {
			if ($ie_version == 'all') {
				$this->Biscuit->register_ie_css($css_file);
			} else if ($ie_version == 6) {
				$this->Biscuit->register_ie6_css($css_file);
			}
		} else {
			$this->Biscuit->register_css($css_file);
		}
	}
	/**
	 * Am I the primary module in the stack?  In other words, if I'm in a stack with other modules that want to perform actions (such as edit, delete)
	 * that I also want to perform, should I perform them, or just hang back and play a secondary role?
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function is_primary($set_value = null) {
		if ($set_value !== null && is_bool($set_value)) {
			$this->_is_primary = $set_value;
		}
		else {
			return $this->_is_primary;
		}
	}
	public function primary_model() {
		return $this->_primary_model;
	}
	/**
	 * Render the module
	 *
	 * @abstract
	 * @return void
	 **/
	protected function render($action_name = false,$render_now = false) {
		if ($action_name === false) {
			$action_name = $this->action();
		}
		if ($action_name == 'new' || substr($action_name,0,4) == 'new_') {
			$action_name = 'edit'.substr($action_name,3);
		}
		$this->Biscuit->render_module($this,$action_name,$render_now);
	}
	/**
	 * Set the body title of the page
	 *
	 * @abstract
	 * @return void
	 * @param string $title
	 **/
	protected function title($title) {
		$this->Biscuit->set_title($title);
	}
	/**
	 * Check to see if a module's common dependencies are met. To use this function, add a $_dependencies property to your module with an array of dependent
	 * module names with numeric keys.
	 *
	 * @abstract
	 * @return bool Whether or not dependencies are met
	 * @author Peter Epp
	 */
	public function check_dependencies() {
		if (empty($this->_dependencies)) {
			return;
		}
		if (!$this->_dependencies_checked) {
			$deps_met = false;
			$missing_deps = array();
			$this->_dependencies_checked = true;
			$failed = 0;
			foreach ($this->_dependencies as $key => $dep_name) {
				if (is_int($key) || ($key == 'primary' && $this->is_primary())) {
					if (!$this->Biscuit->module_exists($dep_name) && !$this->Biscuit->extension_exists($dep_name)) {
						try {
							$this->Biscuit->init_extension($dep_name);
						} catch (ExtensionException $e) {
							$failed += 1;
							$missing_deps[] = $dep_name;
						}
					}
				}
			}
			if ($failed > 0) {
				trigger_error(get_class($this)." cannot run because it is missing the following dependencies:\n\n".implode(", ",$missing_deps), E_USER_ERROR);
			}
		}
	}
	/**
	 * Check to see if the module's action-specific dependencies have been met.To use this function, add a $_dependencies property to your module with an array of dependent
	 * module names with string keys per the action they apply to.  If an action has more than one dependency, the value must be a comma-separated list
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function check_action_dependencies() {
		if (empty($this->_dependencies)) {
			return;
		}
		if (!$this->_action_dependencies_checked) {
			$deps_met = false;
			$missing_deps = array();
			$this->_action_dependencies_checked = true;
			$failed = 0;
			foreach ($this->_dependencies as $key => $dep_name) {
				if (is_string($key) && $key == $this->action()) {
					$action_dependencies = explode(',',$dep_name);
					foreach ($action_dependencies as $action_dep_name) {
						if (!$this->Biscuit->module_exists($action_dep_name) && !$this->Biscuit->extension_exists($action_dep_name)) {
							try {
								$this->Biscuit->init_extension($action_dep_name);
							} catch (ExtensionException $e) {
								$failed += 1;
								$missing_deps[] = $action_dep_name;
							}
						}
					}
				}
			}
			if ($failed > 0) {
				trigger_error(get_class($this)."::".$this->action()."() cannot run because it is missing the following dependencies:\n\n".implode(", ",$missing_deps), E_USER_ERROR);
			}
		}
	}
	/**
	 * Return the URL for the current module based on it's current action. Redefine this function in your module if you have special cases
     * 
     * The default URL is the index action.
	 *
	 * @static
	 * @param string $action (optional)
	 * @param integer $id (optional)
	 * @param string $attribute (optional)
	 * @return string root relative URL
	 **/
	public function url($action='index', $id=null) {
		// Otherwise lookup the page on which it's primary and use that if not "*", otherwise current page
		$primary_page = $this->primary_page();
		if ($primary_page != '*') {
			$page_slug = $primary_page;
		} else {
			$page_slug = $this->Biscuit->Page->slug();
		}
		$base_action_name = $this->base_action_name($action);
		$id_actions = array('show','edit','delete');
		if (!empty($this->_actions_requiring_id)) {
			$id_actions = array_merge($id_actions,$this->_actions_requiring_id);
		}
		if (in_array($base_action_name,$id_actions)) {
			if (!$id) {
				Console::log("                        URL for '".$action."' action requires an id");
			}
			$url = "/".$page_slug."/".$action."/".$id;
			if ($base_action_name == 'show') {
				$url .= $this->friendly_show_slug($action, $id);
			}
			return $url;
		}
		if ($action != 'index') {
			return "/".$page_slug."/".$action;
		}
		return "/".$page_slug;
	}
	/**
	 * Get a friendly slug to add to the "show" URL, if available
	 *
	 * @param string $action 
	 * @param string $id 
	 * @return void
	 * @author Peter Epp
	 */
	protected function friendly_show_slug($action, $id) {
		$url_suffix = '';
		$model = $this->model_for_showing($id, $action);
		if (is_object($model)) {
			$url_suffix = $model->friendly_slug();
			if (!empty($url_suffix)) {
				$url_suffix = '/'.$url_suffix;
			}
		}
		return $url_suffix;
	}
	/**
	 * Find the first page on which this module is installed as primary
	 *
	 * @return string
	 * @author Peter Epp
	 */
	protected function primary_page() {
		if (empty($this->_primary_page)) {
			$my_name = Crumbs::normalized_module_name($this);
			$my_primary_page = DB::fetch("SELECT `page_name` FROM `module_pages` WHERE `module_id` = (SELECT `id` FROM `modules` WHERE `name` = '{$my_name}') AND `is_primary` = 1");
			$this->_primary_page = reset($my_primary_page);	// Always take the first item in the results
			Console::log("Primary page for ".$my_name." module: ".print_r($this->_primary_page,true));
		}
		return $this->_primary_page;
	}
	/**
	 * Normalize the action name by determining it's base name if it includes a model name (eg. 'edit_album' => 'edit')
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function base_action_name($action_name) {
		$base_actions = array('index','show','edit','new','delete','resort');
		if (in_array($action_name,$base_actions)) {
			return $action_name;
		}
		$base_action_name = null;
		foreach ($base_actions as $action) {
			if (strlen($action_name) > strlen($action) && substr($action_name,0,strlen($action)) == $action) {
				$base_action_name = $action;
				break;
			}
		}
		if (!$base_action_name) {
			return $action_name;
		}
		return $base_action_name;
	}
	/**
	 * Send a response for a successful save based on the request
	 *
	 * @param mixed $save_result The result of the save
	 * @param string $redirect_url The url to redirect to for normal post responses
	 * @param array $error_messages The list of error messages to display if save failed
	 * @param bool $force_callback_on_failure Whether or not to run the form's JS callback as well as display errors on a failed save submitted to an iframe
	 * @return void
	 * @author Peter Epp
	 */
	protected function success_save_response($redirect_url) {
		if (Request::is_ajaxy_iframe_post()) {
			Session::flash_unset('user_success');
			Session::flash_unset('user_message');
			Session::flash_unset('user_error');
			$this->Biscuit->render_js($this->params['success_callback'].';');		// Ensure that a ping timer, if started, gets cancelled
		}
		else {
			if (!Request::is_ajax()) {
				Response::redirect($redirect_url);
			}
			else {
				$this->action($this->_successful_save_ajax_action);
				call_user_func(array($this,"action_".$this->_successful_save_ajax_action));
			}
		}
	}
	/**
	 * Send a response for a failed save based on the request
	 *
	 * @param string $model_ref 
	 * @param string $item_name_for_view 
	 * @return void
	 * @author Peter Epp
	 */
	protected function failed_save_response(&$model_ref,$item_name_for_view = "") {
		$error_message = "<strong>".__("Please make the following corrections").":</strong><br><br>".implode("<br>",$model_ref->errors());
		if (Request::is_ajaxy_iframe_post()) {
			Session::flash_unset('user_message');
			$output = 'Biscuit.Crumbs.Alert("'.$error_message.'");';
			if (empty($this->params['error_callback']) && $this->run_success_callback_on_error()) {
				$output .= '
'.$this->params['success_callback'];
			}
			else {
				$output .= '
'.$this->params['error_callback'];
			}
			$this->Biscuit->render_js($output.';');
		}
		else {
			if (!Request::is_ajax()) {
				Session::flash('user_error',$error_message);
			}
			if (empty($item_name_for_view)) {
				$item_name_for_view = strtolower(get_class($model_ref));
			}
			$this->set_view_var($item_name_for_view, $model_ref);
			$this->render();
		}
	}
	/**
	 * Whether or not to run an ajax callback on error. This is used for forms submitted to a hidden iframe.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function run_success_callback_on_error() {
		return (Request::form('run_success_callback_on_error') == 1);
	}
	/**
	 * Set a variable that will be made into a local variable for the view at render time
	 *
	 * @param string $key Variable name
	 * @param string $value Variable value
	 * @return mixed Variable value
	 * @author Peter Epp
	 * @author Lee O'Mara
	 */
	protected function set_view_var($key,$value) {
		return $this->Biscuit->set_view_var($key,$value);
	}
	/**
	 * Return the base folder name for the module
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function base_path() {
		return $this->module_base_path(get_class($this));
	}
	/**
	 * Return the base folder name for the parent module if it is not a direct descendent of AbstractModuleController.
	 *
	 * @return string|null
	 * @author Peter Epp
	 */
	public function parent_base_path() {
		$parent_class = get_parent_class($this);
		if ($parent_class != 'AbstractModuleController') {
			return $this->module_base_path($parent_class);
		}
		return null;
	}
	/**
	 * Return the name of the index action based on a model name
	 *
	 * @param string $model_name 
	 * @return string
	 * @author Peter Epp
	 */
	protected function get_index_action($model_name) {
		if (!empty($model_name) && $model_name != $this->primary_model()) {
			return 'index_'.AkInflector::underscore($model_name);
		}
		return 'index';
	}
	/**
	 * Return the base folder name for a given module class name
	 *
	 * @param $class_name string Name of the class for which to return the folder name
	 * @return string
	 * @author Peter Epp
	 */
	private function module_base_path($class_name) {
		$folder = AkInflector::underscore($class_name);
		if (substr($folder,0,7) == 'custom_') {
			$folder = substr($folder,7);
		}
		if (substr($folder,-8) == "_manager") {
			$folder = substr($folder,0,-8);
		}
		return $folder;
	}
	/**
	 * Return the names of all the models used by the module
	 *
	 * @return array
	 * @author Peter Epp
	 */
	public function all_model_names() {
		$model_names = array();
		if (!empty($this->_models)) {
			foreach ($this->_models as $normalized_name => $actual_model_name) {
				$model_names[] = $actual_model_name;
			}
		}
		return $model_names;
	}
	/**
	 * Determines whether or not a page request cane be cached. By default, secondary actions, 'index' and 'show' are cacheable, while all others are not.
	 *
	 * A module can override the defaults by defining $_cacheable_actions and/or $_uncacheable_actions properties as arrays of action names, or by redefining the
	 * this method.
	 *
	 * @return void
	 * @author Peter Epp
	 * @throws ModuleException
	 */
	protected function can_cache_action() {
		if (method_exists($this, 'set_cacheables')) {
			// Hook for setting of cacheable or uncacheable actions based on custom rules
			$this->set_cacheables();
		}
		$action = $this->action();
		$base_action = $this->base_action_name($action);
		$uncacheable_actions = (array)$this->_uncacheable_actions;
		$cacheable_actions = array_merge(array('index','show'), (array)$this->_cacheable_actions);
		$cacheable_actions = array_diff($cacheable_actions,$uncacheable_actions);
		return ((!$this->is_primary() && !in_array('secondary', $uncacheable_actions)) || ($this->is_primary() && (in_array($action, $cacheable_actions) || in_array($base_action, $cacheable_actions))));
	}
	/**
	 * Right before request dispatch, set no caching if the module says the request cannot be cached
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_dispatch_request() {
		if (!$this->can_cache_action()) {
			$this->Biscuit->set_never_cache();
		}
	}
	/**
	 * Add all directories needed by the module to the main directory list
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_directory_setup() {
		if (!empty($this->_models)) {
			$models = $this->_models;
			foreach ($models as $model_label => $model_name) {
				$model = $this->$model_label->create();
				$model_dirs = $model->upload_directory_list();
				if (!empty($model_dirs)) {
					foreach ($model_dirs as $directory) {
						$this->Biscuit->add_to_directory_list($directory);
					}
				}
			}
		}
	}
	/**
	 * Add breadcrumb based on the current action if it is not "index" and the current module is primary.
	 *
	 * @param Navigation $Navigation 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_build_breadcrumbs($Navigation) {
		if ($this->action() != 'index' && $this->is_primary()) {
			$link_label = $this->Biscuit->Page->title();	// We use the page title as the module's action method would have set it to override the default page title for the view prior to this call.
			if (!empty($this->params['id'])) {
				$url = $this->url($this->action(),$this->params['id']);
			} else {
				$url = $this->url($this->action());
			}
			$Navigation->add_breadcrumb($url,$link_label);
		}
	}
}
