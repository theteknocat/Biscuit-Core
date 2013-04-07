<?php
Console::log("        Captcha: Loading open source captcha library");
require_once('plugins/simple_captcha/captcha.php');

class Captcha extends AbstractPluginController {
	function action_index() {
		if ($this->Biscuit->page_name == "captcha") {
			$this->Biscuit->render_with_template(false);
			$this->tool = new SimpleCaptcha('captcha');
			$this->Biscuit->content_type("image/jpeg");
			$this->render();
		}
	}
	/**
	 * Does the user input match the captcha?
	 *
	 * @param string $user_input
	 * @return bool
	 * @author Peter Epp
	 */
	function matches($user_input) {
		$captcha = Session::get('captcha');
		$user_input = $user_input;
		return ($user_input == $captcha);
	}
	function render_widget() {
		include('views/plugins/captcha/widget.php');
	}
	function url() {
		return '/captcha/'.time();
	}
}
?>
