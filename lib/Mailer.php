<?php
require_once("lib/vendor/phpmailer/class.phpmailer.php");
/**
 * Wrapper for PHPMailer class that sends email using a template
 *
 * @package Core
 * @author Peter Epp
 * @author Lee O'Mara
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class Mailer extends PHPMailer {
	/**
	 * Send an email. If you want to use SMTP for sending, add/update the following globals in the system_settings database table:
	 *
	 * USE_SMTP_MAIL = 1		// Flag to enable SMTP use
	 * SMTP_HOST = mail.myhost.com
	 * SMTP_USE_AUTH = 1		// Only if needed
	 * SMTP_USERNAME = username
	 * SMTP_PASSWORD = xxxxxxxx
	 *
	 * @param string $template Template filename relative to either site or framework root
	 * @param string $mailer_options Array of options. Must at least contain a "To" element with an email address as it's value.
	 *        Can optionally include "From" (from email address), "FromName", "ToName" and "Subject"
	 * @param string $local_variables An array of variables you want to use in the template. This is optional in case you want to use this to send generic messages.
	 *        You can use any variables you want to use in your template.
	 * @return void
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	public function send_mail($template, $mailer_options, $local_variables=array()) {
		Console::log("                        Mailer Initializing...");
		$this->smtp_settings();
		$args = func_get_args();
		if (!empty($mailer_options['From'])) {
			$this->From		= $mailer_options['From'];     
		}
		else if (defined(OWNER_EMAIL) && OWNER_EMAIL != '') {
			$this->From 	= OWNER_EMAIL;
		}
		else {
			$this->From		= "noreply@".Request::host();
		}
		if (!empty($mailer_options['FromName'])) {
			$this->FromName	= $mailer_options['FromName'];
		}
		else if (defined("OWNER_FROM") && OWNER_FROM != '') {
			$this->FromName	= OWNER_FROM;
		}
		$this->Sender = OWNER_EMAIL;
		if (!empty($mailer_options['Subject'])) {
			$this->Subject	= $mailer_options['Subject'];
		}
		else {
			$this->Subject	= "<No Subject>";
		}
		if (!empty($mailer_options['Priority'])) {
			$this->Priority = $mailer_options['Priority'];
		}
		if (empty($mailer_options['To']) && empty($mailer_options['CC']) && empty($mailer_options['BCC'])) {
			return 'Error sending mail: No recipients provided';
		}
		if (!empty($mailer_options['To'])) {
			$toname = null;
			if (!empty($mailer_options['ToName'])) {
				$toname = $mailer_options['ToName'];
			}
			$this->AddRecipients("To",$mailer_options['To'],$toname);
		}
		if (!empty($mailer_options['CC'])) {
			$toname = null;
			if (!empty($mailer_options['CCName'])) {
				$toname = $mailer_options['CCName'];
			}
			$this->AddRecipients("CC",$mailer_options['CC'],$toname);
		}
		if (!empty($mailer_options['BCC'])) {
			$toname = null;
			if (!empty($mailer_options['BCCName'])) {
				$toname = $mailer_options['BCCName'];
			}
			$this->AddRecipients("BCC",$mailer_options['BCC'],$toname);
		}
		if (!empty($mailer_options['Attachments'])) {
			foreach ($mailer_options['Attachments'] as $attachment) {
				if (!stristr($attachment,SITE_ROOT)) {
					if (substr($attachment,0,1) != "/") {
						$attachment = "/".$attachment;
					}
					$attachment = SITE_ROOT.$attachment;
				}
				$this->AddAttachment($attachment);
			}
		}
		// capture body from template
		$use_html = (!empty($mailer_options["use_html"]) && $mailer_options["use_html"] === true);
		if ($use_html) {
			$this->Body = Crumbs::capture_include($template.".html_email.php",$local_variables);
			$this->AltBody = Crumbs::capture_include($template.".email.php", $local_variables);
			$this->IsHTML(true);
		} else {
			$this->Body = Crumbs::capture_include($template.".email.php", $local_variables);
		}
		if (defined("EMAIL_WORDWRAP")) {
			$this->WordWrap = EMAIL_WORDWRAP;
		}
		if (!$this->Send()) {
			return 'Error sending mail: '.$this->ErrorInfo;
		}
		else {
			return '+OK';
		}
	}
	/**
	 * Add one or more recipients, either as "To", "CC", or "BCC"
	 *
	 * @param string $type "To", "CC" or "BCC"
	 * @param mixed $recipients Either a string containing one email address, or an array containing multiple
	 * @param string $recipient_names Either a string containing one name, or an array containing multiple
	 * @return void
	 * @author Peter Epp
	 */
	protected function AddRecipients($type,$recipients,$recipient_names = null) {
		if (is_array($recipients)) {
			for ($i=0;$i < count($recipients);$i++) {
				$toname = "";
				if (!empty($recipient_names) && !empty($recipient_names[$i])) {
					$toname = $recipient_names[$i];
				}
				$this->AddRecipient($type,$recipients[$i],$toname);
			}
		}
		else {
			$toname = "";
			if (!empty($recipient_names)) {
				$toname = $recipient_names;
			}
			$this->AddRecipient($type,$recipients,$toname);
		}
	}
	/**
	 * Add one recipient
	 *
	 * @param string $type "To", "CC" or "BCC"
	 * @param string $email The email address
	 * @param string $name The recipient name
	 * @return void
	 * @author Peter Epp
	 */
	protected function AddRecipient($type,$email,$name) {
		switch($type) {
			case "To":
				$this->AddAddress($email,$name);
				break;
			case "CC":
				$this->AddCC($email,$name);
				break;
			case "BCC":
				$this->AddBCC($email,$name);
				break;
		}
	}
	/**
	 * Configure phpmailer SMTP settings based on Biscuit global settings
	 *
	 * @return void
	 * @author Peter Epp
	 */
	protected function smtp_settings() {
		if (defined("USE_SMTP_MAIL") && USE_SMTP_MAIL == true && defined("SMTP_HOST")) {
			Console::log("                        Mailer is using SMTP: ".SMTP_HOST);
			$this->IsSMTP();
			$this->Host = SMTP_HOST;
			if (defined("USE_SMTP_AUTH") && USE_SMTP_AUTH == true && defined("SMTP_USERNAME") && defined("SMTP_PASSWORD")) {
				Console::log("                        Mailer is using SMTP Authentication for: ".SMTP_USERNAME);
				$this->SMTPAuth = true;
				$this->Username = SMTP_USERNAME;
				$this->Password = SMTP_PASSWORD;
			}
		}
	}
}
?>