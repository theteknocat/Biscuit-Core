<?php
/**
 * Controller for Contact plugin
 * 
 * @author Peter Epp
 * @version 
 * @copyright 
 * @package Plugins
 **/
class ContactManager extends AbstractPluginController {
	/**
	 * List of error messages from send email validation
	 *
	 * @var string
	 */
	var $email_errors = array();
	/**
	 * List of other plugins this one is dependent on
	 *
	 * @var array
	 */
	var $dependencies = array("Authenticator");
	/**
	 * Default contact page name. This is needed for the method that builds a contact link list for Tiny MCE. If yours is different,
	 * copy this plugin into your site or write a custom extension and change this value.
	 * TODO: find a better way to handle this
	 *
	 * @var string
	 */
	var $default_url = "/contact";
	/**
	 * Set model name and include the model class, allowing an extension-point for the model.
	 *
	 * @param string $model_name 
	 * @return void
	 * @author Peter Epp
	 */
	function ContactManager($model_name = "Contact") {
		require_once("plugins/".$model_name.".php");
		$this->model_name = $model_name;
	}
	/**
	 * Run the plugin
	 *
	 * @author Peter Epp
	 */
	function run($params) {
		if ($this->dependencies_met()) {
			$this->Biscuit->register_js("contact_manager.js");
			parent::run($params); // dispatch to action_...
		}
		else {
			Console::log("                        ContactManager died because it can't live without Authenticator");
		}
	}
	/**
	 * Index action - by default find all items in the database and render
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_index() {
		$this->contacts = $this->Model("find_all");
		$this->render();
	}
	/**
	 * Show action - retrieve one item from the database and render.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_show() {
		$this->contact = $this->Model("find",array($this->params['id']));
		$this->render();
	}
	/**
	 * Call validation and return a response for use by the Biscuit JS Ajax validation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_ajax_validate() {
		Console::log('                        Performing AJAX validation for '.get_class($this));
		if (!empty($this->params)) {
			if ($this->params['validation_type'] == "email") {
				if ($this->validate_send_email()) {
					$output = '+OK';
				}
				else {
					$output = "Please make the following corrections:\n\n-".implode("\n-",$this->email_errors);
				}
			}
			else if ($this->params['validation_type'] == "admin_edit") {
				// grab from user input
				$contact_data = $this->params['contact_data'];
				if (!empty($this->params['id'])) {
					$contact_data['id'] = $this->params['id'];
				}
				$model_name = $this->model_name;
				$contact = new $model_name($contact_data);
				if ($contact->validate()) {
					$output = '+OK';
				}
				else {
					Session::flash_unset('user_message');
					$output = "Please make the following corrections:\n\n-".implode("\n-",$contact->errors());
				}
			}
		}
		else {
			$output = 'No data submitted!';
		}
		Console::log('                        Validation result: '.$output);
		$this->Biscuit->render($output);
	}
	function validate_send_email() {
		$this->email_errors = array();
		if (empty($this->params['contact']['sender_name'])) {
			$this->email_errors[] = "Enter your name";
		}
		if (!Crumbs::valid_email($this->params['contact']['sender_email'])) {
			$this->email_errors[] = "Enter a valid email address";
		}
		if (empty($this->params['contact']['subject'])) {
			$this->email_errors[] = "Enter a subject";
		}
		if (empty($this->params['contact']['message_body'])) {
			$this->email_errors[] = "Enter a message to send";
		}
		if (!Captcha::matches($this->params['security_code'])) {
			$this->email_errors[] = "Enter the security code shown in the image";
		}
		return (empty($this->email_errors));
	}
	/**
	 * Send an email to a contact with specified ID
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_send_email() {
		$this->title("Send Email");
		if (Request::is_post()) {
			if ($this->validate_send_email()) {
				$result = $this->send_the_message();
				if (!Request::is_ajax()) {
					if ($result != "+OK") {
						Session::flash("user_message",$result);
					}
					else {
						Session::flash("user_message","Your message was sent successfully.");
					}
					Response::redirect($this->url());
				}
				else {
					if ($result != "+OK") {
						Session::flash("user_message",$result);
						$this->contact = $this->Model("find",array($this->params['id']));
						$this->render();
					}
					else {
						$this->contact = $this->Model("find",array($this->params['id']));
						$this->render('confirm_email');
					}
				}
			}
			else {
				if (!Request::is_ajax()) {
					Session::flash('user_message',"<strong>Please make the following corrections:</strong><br><br>".implode("<br>",$this->email_errors));
				}
				$this->contact = $this->Model("find",array($this->params['id']));
				$this->render();
			}
		}
		else {
			$contact = $this->Model("find",array($this->params['id']));
			if (!$contact) {
				$err_msg = 'The selected contact cannot be found. Please contact a system administrator to notify them of the problem so they can correct the issue.';
				if (Request::is_ajax()) {
					$this->render($err_msg);
				} else {
					Session::flash('user_message',$err_msg);
					Response::redirect($this->url());
				}
			} else {
				$this->contact = &$contact;
				$this->render();
			}
		}
	}
	/**
	 * Send an email out
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function send_the_message() {
		$message_data = H::purify_array_text($this->params['contact']);
		$recipient = $this->Model("find",array($this->params['id']));
		$options = array(
			"To"          => $recipient->email(),
			"From"        => $message_data['sender_email'],
			"FromName"    => $message_data['sender_name'],
			"Subject"     => $message_data['subject']
		);
		$message_vars = array(
			'message_body'    => $message_data['message_body'],
			'sender_name'     => $message_data['sender_name'],
			'sender_email'    => $message_data['sender_email']
		);
		$mail = new Mailer();
		return $mail->send_mail("standard_contact",$options,$message_vars);
	}
	/**
	 * Edit an item
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_edit() {
		$this->title("Edit Contact");
		// Grab existing:
		$contact = $this->Model("find",array($this->params['id']));
		if (!empty($this->params['contact_data'])) {
			// grab from user input
			$contact_data = array_merge(array('id' => $this->params['id']),$this->params['contact_data']);
			// Replace attributes with user input:
			$contact->set_attributes($contact_data);
		}
		if (Request::is_post()) { // successful save
			if ($contact->save()) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($contact,"contact");
			}
		}
		else {
			$this->contact = &$contact;
			$this->render();
		}
	}
	/**
	 * Create a new item
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_new() {
		$this->title("New Contact");
		if (empty($this->params['contact_data'])) {
			// create default, empty item
			$contact_data = array();
		} else {
			// grab from user input
			$contact_data = $this->params['contact_data'];
		}
		// Create a new item from the user input:
		$model_name = $this->model_name;
		$contact = new $model_name($contact_data);
		if (Request::is_post()) { // successful save
			if ($contact->save()) {
				$this->success_save_response($this->url());
			}
			else {
				$this->failed_save_response($contact,"contact");
			}
		}
		else {
			$this->contact = &$contact;
			$this->render();
		}
	}
	/**
	 * Custom user_can function that checks if a contact is permanent, if id is supplied, as part of the permission check
	 *
	 * @param string $action 
	 * @return void
	 * @author Peter Epp
	 */
	function user_can($action) {
		if ($action == "delete" && !empty($this->params['id'])) {
			$contact = $this->Model("find",array($this->params['id']));
			return (parent::user_can("delete") && !$contact->is_permanent());
		}
		return parent::user_can($action);
	}
	/**
	 * Delete an item and return a response based on the success or failure of the delete operation
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function action_delete() {
		if (!$this->item_delete($this->params['id'])) {
			Session::flash('user_message', "Failed to remove item");
		}
		if (!Request::is_ajax()) {
			Response::redirect($this->url());
		}
		else {
			$this->params['action'] = 'index';
			$this->action_index();
		}
	}
	/**
	 * Delete an item
	 *
	 * @param integer ID
	 * @return boolean success?
	 **/
	function item_delete($id) {
		$contact = $this->Model("find",array($id));
		return $contact->delete();
	}
	/**
	 * Enforce the presence of some data(notably ID) for certain actions. This function
	 * is called before the action by AbstractPluginController#run
	 *
	 * @return boolean
	 **/
	function before_filter() {
		if (in_array($this->params['action'], array('edit', 'delete', 'show', 'send_email'))) {
			// require ID
			return (!empty($this->params['id']));
		}
		return true;
	}
	/**
	 * Add email contact links to the Tiny MCE link list.
	 *
	 * @return array
	 * @author Peter Epp
	 */
	function act_on_build_mce_link_list() {
		$contacts = Contact::find_all("first_name, last_name");
		$list_contacts = array();
		foreach ($contacts as $contact) {
			$list_contacts[] = array(
				"title" => "Email: ".$contact->full_name(),
				"url"   => STANDARD_URL.$this->default_url."/send_email/".$contact->id()
			);
		}
		$this->Biscuit->plugins["MCELinkList"]->add_to_list($list_contacts,"Email Contacts");
	}
	/**
	 * Supply the name (or names) of the database table(s) used by this plugin
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function db_tablename() {
		return $this->Model("db_tablename");
	}
	/**
	 * Return the query to create a new table for this plugin
	 *
	 * @param mixed $table_name Either an array or string containing the names of the tables used by the plugin
	 * @return void
	 * @author Peter Epp
	 */
	function db_create_query($table_name) {
		return $this->Model("db_create_query",array($table_name));
	}
}
?>
