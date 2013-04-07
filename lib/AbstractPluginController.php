<?php
/**
 * And abstract controller for use with plugins. It requires the abstract plugin class, the two of which together provide the following functionality:
 * - Dispatch to actions
 * - Register Biscuit object
 * - Set view file based on action
 * - Setting page
 * - Check database installation for plugin and install tables if plugin has the appropriate methods for providing data needed for installation
 * - Check permissions for actions
 *
 * @package Plugins
 * @author Peter Epp
 */
class AbstractPluginController extends AbstractPlugin {
	/**
	 * Whether or not to check the role of this plugin
	 *
	 * @var string
	 */
	
	var $is_secondary = false;
	/**
	 * A reference to the Biscuit object
	 *
	 * @var object
	 **/
	var $Biscuit;
	/**
	 * Whether or not dependencies have been checked
	 *
	 * @var bool
	 */
	var $_dependencies_checked = false;
	/**
	 * Whether or not the plugin is primary
	 *
	 * @var bool
	 */
	var $_is_primary;
	/**
	 * Register a reference of the Biscuit in this plugin
	 *
	 * @abstract
	 * @param string $page The Biscuit object
	 * @return void
	 * @author Peter Epp
	 */
	function register_page(&$page) {
		$this->Biscuit = &$page;
	}
	/**
	 * Dispatch to action methods if found. Before the action_{action-name} is fired,
     * before_filter(if found) is run. If before_filter returns false, the program exits.
	 *
	 * @abstract
	 * @param array $params 
	 * @return void
	 */	
	function run($params=array()) {
		$this->check_install();		// Check if the DB is installed, and install it if needed and the plugin has the right methods.
		if ($this->dependencies_met()) {
			$this->params = $params;
			if (empty($this->params['action'])) {
				$this->params['action']  = 'index';
			} 
			if (!$this->Biscuit->request_is_bad) {
				Console::log("                        Requested action: ".$this->params['action']);
				// Automatic default response to ajax validation requests. For other validation request types you will need to customize your plugin to respond to them.
				if (!$this->Biscuit->request_is_bad && Request::is_ajax() && Request::type() == "validation" && method_exists($this,'action_ajax_validate') && $this->is_primary()) {
					// Dispatch to ajax validation if requested
					$this->action_ajax_validate();
				}
				else {
					if (!$this->Biscuit->request_is_bad || $this->params['action'] =="index") {
						if ($this->is_primary()) {
							$action_name = 'action_'.$this->params['action'];
							// Otherwise dispatch to action as normal
							if (method_exists($this, $action_name)) {
								if (Permissions::can_execute($this,$this->params['action'])) {
									Console::log("                        Dispatching to ".get_class($this)."::{$action_name}()");
									$this->$action_name();
								} else {
									if (!Request::is_ajax()) {
										if (Authenticator::user_is_logged_in()) {
											Response::redirect($this->url());
										} else {
											Response::redirect(Authenticator::login_url().'?ref_page='.Request::uri());
										}
									}
									else {
										$this->params['action'] = "index";
										$this->action_index();
									}
								}
							} else {
								Console::log("                        ".get_class($this)."::{$action_name}() not defined, moving on");
							}
						}
						else {
							if (method_exists($this,'action_secondary')) {
								Console::log("                        Dispatching to ".get_class($this)."::action_secondary()");
								$this->action_secondary();
							}
						}
					}
					else {
						Console::log("                        BAD REQUEST! Not dispatching to action!");
					}
				}
			}
		}
	}
	/**
	 * Call a static function on the defined model
	 *
	 * @param string $method Name of the method to call on the model
	 * @param array $params An array of the parameters to pass to the method
	 * @return void
	 * @author Peter Epp
	 */
	function Model($method,$params = array()) {
		$model = $this->ModelName();
		if ($model) {
			return call_user_func_array(array($model,$method),$params);
		}
		else {
			trigger_error("No model defined for ".get_class($this)."!!!",E_USER_ERROR);
		}
	}
	/**
	 * Return the name of the model for this controller
	 *
	 * @return mixed Model name, if the class exists, or false
	 * @author Peter Epp
	 */
	function ModelName() {
		if (empty($this->model_name)) {
			$my_name = get_class($this);
			$model_name = substr($my_name,0,-7);		// Chop off "Manager"
		}
		else {
			$model_name = $this->model_name;
		}
		if (!class_exists($model_name)) {
			Console::log("AbstractPluginController: Model does not exist: ".$model_name);
			return false;
		}
		return $model_name;
	}
	/**
	 * Am I the primary plugin in the stack?  In other words, if I'm in a stack with other plugins that want to perform actions (such as edit, delete)
	 * that I also want to perform, should I perform them, or just hang back and play a secondary role?
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function is_primary($set_value = null) {
		if ($set_value !== null && is_bool($set_value)) {
			$this->_is_primary = $set_value;
		}
		else {
			Console::log("                        Plugin is primary: ".(($this->_is_primary === true) ? "true" : "false"));
			return $this->_is_primary;
		}
	}
	/**
	 * Render the plugin
	 *
	 * @abstract
	 * @return void
	 **/
	function render($action_name = false,$render_now = false) {
		if ($action_name === false) {
			$action_name = $this->params['action'];
		}
		if ($action_name == 'new') {
			$action_name = 'edit';
		}
		$this->Biscuit->render_plugin(&$this,$action_name,$render_now);
	}
	/**
	 * Set the body title of the page
	 *
	 * @abstract
	 * @return void
	 * @param string $title
	 **/
	function title($title) {
		$this->Biscuit->set_title($title);
	}
	/**
	 * Check to see if a plugin's dependencies are met. To use this function, add a $dependencies property to your plugin with an array of dependent plugin names
	 *
	 * @abstract
	 * @return bool Whether or not dependencies are met
	 * @author Peter Epp
	 */
	function dependencies_met() {
		if (!$this->_dependencies_checked) {
			$this->_dependencies_checked = true;
			if (!empty($this->dependencies)) {
				$dep_count = 0;
				foreach ($this->dependencies as $v) {
					if ($this->Biscuit->plugin_exists($v)) {
						$dep_count += 1;
					}
				}
				$deps_met = ($dep_count == count($this->dependencies));
				if (!$deps_met) {
					Console::log(get_class($this)." died because it cannot live without: ".implode(", ",$this->dependencies));
				}
				return $deps_met;
			}
		}
		return true;
	}
	/**
	 * Return the URL for the current plugin based on it's current action. Redefine this function in your plugin if you have special cases
     * 
     * The default URL is the index action.
	 *
	 * @static
	 * @param string $action (optional)
	 * @param integer $id (optional)
	 * @return string root relative URL
	 **/
	function url($action=null, $id=null) {
		switch ($action) {
			case 'download':
				$download_err_msg = "We apologize for the inconvenience, but our server is currently experiencing difficulties locating your file. This error has been logged, and a system administrator will take care of it as soon as possible.";
				if (empty($id) || !is_object($id)) {
					Console::log("                        URL for 'download' action requires an object instance");
					Session::flash("user_message",$download_err_msg);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				if (!method_exists($this,"item_file_path")) {
					Console::log("                        ERROR! Method ".get_class($this)."::item_file_path() Not Found!");
					Session::flash("user_message",$download_err_msg);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				// In this case "$id" is not the id, but a copy of the model
				$filepath = $this->item_file_path($id);
				if (empty($filepath) || !$filepath) {
					Console::log(get_class($this)."::item_file_path() did not return a value! Download cannot be completed");
					Session::flash("user_message",$download_err_msg);
					Response::redirect("/".$this->Biscuit->full_page_name);
				}
				return $filepath;
				break;
			case 'show':
			case 'edit':
			case 'delete':
			case 'send_email':
				if (!$id) {
					Console::log("                        URL for '".$action."' action requires an id");
				}
				return "/".$this->Biscuit->full_page_name."/".$action."/".$id;
				break;
			case 'new':
				return "/".$this->Biscuit->full_page_name."/new";
				break;
			case 'index':
			default:
				return "/".$this->Biscuit->full_page_name;
				break;
		}
	}
	/**
	 * Execute the appropriate response to a save.  This will either be a redirect, JS output, or nothing depending on whether the form was submitted normally or to an hidden iframe (ajax-like) and whether or not the save was successful
	 *
	 * @param mixed $save_result The result of the save
	 * @param string $redirect_url The url to redirect to for normal post responses
	 * @param array $error_messages The list of error messages to display if save failed
	 * @param bool $force_callback_on_failure Whether or not to run the form's JS callback as well as display errors on a failed save submitted to an iframe
	 * @return void
	 * @author Peter Epp
	 */
	function success_save_response($redirect_url,$default_action = "index") {
		if (Request::is_ajaxy_iframe_post()) {
			Session::flash_unset('user_message');
			$this->Biscuit->render_js($this->params['success_callback'].';top.Biscuit.Session.KeepAlive.cancel_ping_timer();');		// Ensure that a ping timer, if started, gets cancelled
		}
		else {
			if (!Request::is_ajax()) {
				Response::redirect($redirect_url);
			}
			else {
				$this->params['action'] = $default_action;
				call_user_func(array($this,"action_".$default_action));
			}
		}
	}
	function failed_save_response(&$model_ref,$item_name_for_view = "") {
		if (Request::is_ajaxy_iframe_post()) {
			Session::flash_unset('user_message');
			$output = 'alert("'.implode('\n-',$model_ref->errors()).'");';
			if (empty($this->params['error_callback']) && $this->run_success_callback_on_error()) {
				$output .= '
'.$this->params['success_callback'];
			}
			else {
				$output .= '
'.$this->params['error_callback'];
			}
			$this->Biscuit->render_js($output.';top.Biscuit.Session.KeepAlive.cancel_ping_timer();');
		}
		else {
			if (!Request::is_ajax()) {
				Session::flash('user_message',"<strong>Please make the following corrections:</strong><br><br>".implode("<br>",$model_ref->errors()));
			}
			if (empty($item_name_for_view)) {
				$item_name_for_view = strtolower(get_class($model_ref));
			}
			$this->$item_name_for_view = &$model_ref;
			$this->render();
		}
	}
	/**
	 * Whether or not to run an ajax callback on error. This is used for forms submitted to a hidden iframe.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function run_success_callback_on_error() {
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
	function set_view_var($key,$value) {
		return $this->Biscuit->set_var($key,$value);
	}
}
?>