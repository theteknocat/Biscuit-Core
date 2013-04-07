<?php
class Menu extends AbstractModel {
	protected $_attr_labels = array(
		'var_name' => 'Variable Name'
	);
	public function var_name_is_valid() {
		$var_name = $this->var_name();
		$first_char = '';
		if ($var_name != null) {
			$first_char = substr($var_name,0,1);
		}
		$is_valid = ($var_name != null && preg_match('/([A-Za-z0-9_]+)/',$var_name) && !preg_match('/([0-9]+)/',$first_char));
		if ($is_valid) {
			$menu_exists = (DB::fetch("SELECT `id` FROM `menus` WHERE `var_name` != ?",$this->var_name()) != null);
			if ($menu_exists) {
				$is_valid = false;
				$this->set_error('var_name','Provide a variable name that is not already used for another menu');
			}
		} else {
			// Set a custom error message for this attribute
			$this->set_error('var_name','Provide a variable name that does not begin with a number and contains only numbers, letters and underscores');
		}
		return $is_valid;
	}
}
?>