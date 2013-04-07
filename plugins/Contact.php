<?php
/**
 * Model for the Cantact plugin
 *
 * @package Plugins
 * @author Peter Epp
 */
class Contact extends AbstractModel {
	var $designation;		// eg. Mr. Mrs. etc
	var $first_name;
	var $last_name;
	var $email;
	var $company;
	var $title;
	var $work_phone;
	var $home_phone;
	var $mobile_phone;
	var $toll_free_phone;
	var $fax;
	var $address1;
	var $address2;
	var $city;
	var $province;
	var $postal;
	var $is_permanent;
	/**
	 * Find one item in the database
	 *
	 * @param mixed $id 
	 * @return object Instance of this model
	 * @author Your Name
	 */
	function find($id) {
		$id = (int)$id;
		return Contact::contact_from_query("SELECT * FROM contacts WHERE id = ".$id);
	}
	/**
	 * Find all items in the database
	 *
	 * @param int $album_id 
	 * @return void
	 * @author Peter Epp
	 */
	function find_all($sort_params="") {
		if (!empty($sort_params)) {
			$sorting = " ORDER BY ".$sort_params;
		}
		else {
			$sorting = " ORDER BY last_name, first_name";
		}
		$query = "SELECT * FROM contacts".$sorting;
		return Contact::contacts_from_query($query);
	}
	// Put your getters here, eg:
	function designation() {		return $this->get_attribute('designation');		}
	function first_name()  {		return $this->get_attribute('first_name');		}
	function last_name()   {		return $this->get_attribute('last_name');		}
	function nick_name()   {		return $this->get_attribute('nick_name');		}
	/**
	 * Concatenation of designation (if present), first name and last name in that order
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function full_name()   {
		$name = '';
		if ($this->designation()) {
			$name .= $this->designation().' ';
		}
		$name .= $this->first_name().' '.$this->last_name();
		return $name;
	}
	function email()           {		return $this->get_attribute('email');			}
	function company()         {		return $this->get_attribute('company');			}
	function title()           {		return $this->get_attribute('title');			}
	function work_phone()      {		return $this->get_attribute('work_phone');		}
	function home_phone()      {		return $this->get_attribute('home_phone');		}
	function mobile_phone()    {		return $this->get_attribute('mobile_phone');	}
	function toll_free_phone() {		return $this->get_attribute('toll_free_phone');	}
	function fax()             {		return $this->get_attribute('fax');				}
	function address1()        {		return $this->get_attribute('address1');		}
	function address2()        {		return $this->get_attribute('address2');		}
	function city()            {		return $this->get_attribute('city');			}
	function province()        {		return $this->get_attribute('province');		}
	function postal()          {		return $this->get_attribute('postal');			}
	function is_permanent()    {		return $this->get_attribute('is_permanent');	}

	/**
	 * Validate user input
	 *
	 * @return void
	 * @author Peter Epp
	 */
	function validate() {
		// Put your validation code here, eg:
		if ($this->first_name() == null) {
			$this->set_error("Enter a first name");
		}
		if ($this->last_name() == null) {
			$this->set_error("Enter a last name");
		}
		if ($this->email() != null && !Crumbs::valid_email($this->email())) {
			$this->set_error("Enter a valid email address, or leave it blank");
		}
		return (!$this->errors());
	}
	/**
	 * Build an object from a database query
	 *
	 * @param string $query Database query
	 * @return object
	 * @author Peter Epp
	 */
	function contact_from_query($query) {
		return parent::model_from_query("Contact",$query);
	}
	/**
	 * Build a collection of objects from a database query
	 *
	 * @param string $query Database query
	 * @return array Collection of news/event objects
	 * @author Peter Epp
	 */
	function contacts_from_query($query) {
		return parent::models_from_query("Contact",$query);
	}
	function db_tablename() {
		return 'contacts';
	}
	function db_create_query() {
		return 'CREATE TABLE  `contacts` (
		`id` INT( 8 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`designation` VARCHAR(5) DEFAULT NULL,
		`first_name` VARCHAR(255) NOT NULL,
		`last_name` VARCHAR(255) NOT NULL,
		`nick_name` VARCHAR(255) DEFAULT NULL,
		`email` VARCHAR(255) NOT NULL,
		`company` VARCHAR(255) DEFAULT NULL,
		`title` VARCHAR(255) DEFAULT NULL,
		`work_phone` VARCHAR(14) DEFAULT NULL,
		`home_phone` VARCHAR(14) DEFAULT NULL,
		`mobile_phone` VARCHAR(14) DEFAULT NULL,
		`toll_free_phone` VARCHAR(14) DEFAULT NULL,
		`fax` VARCHAR(14) DEFAULT NULL,
		`address1` VARCHAR(255) DEFAULT NULL,
		`address2` VARCHAR(255) DEFAULT NULL,
		`city` VARCHAR(255) DEFAULT NULL,
		`province` VARCHAR(72) DEFAULT NULL,
		`postal` VARCHAR(7) DEFAULT NULL,
		`is_permanent` INT(1) NOT NULL DEFAULT 0
		) TYPE = MyISAM';
	}
}
?>
