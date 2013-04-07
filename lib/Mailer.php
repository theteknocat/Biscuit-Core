<?php
require_once("lib/phpmailer/class.phpmailer.php");
/**
 * Wrapper for PHPMailer class that sends email using a template
 *
 * @package default
 * @author Peter Epp
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
	 * @param string $template_name Template filename relative to either site or framework root
	 * @param string $mailer_options Array of options. Must at least contain a "To" element with an email address as it's value.
	 *        Can optionally include "From" (from email address), "FromName", "ToName" and "Subject"
	 * @param string $local_variables An array of variables you want to use in the template. This is optional in case you want to use this to send generic messages.
	 *        You can use any variables you want to use in your template.
	 * @return void
	 * @author Lee O'Mara
	 * @author Peter Epp
	 */
	function send_mail($template_name, $mailer_options, $local_variables=array()) {
		Console::log("                        Mailer Initializing...");
		$this->CharSet = 'UTF-8';
		$this->smtp_settings();
		$args = func_get_args();
		if (!empty($mailer_options['From'])) {
			$this->From		= $mailer_options['From'];     
		}
		else if (defined(OWNER_EMAIL)) {
			$this->From 	= OWNER_EMAIL;
		}
		else {
			$this->From		= "noreply@".Request::host();
		}
		$this->Sender = OWNER_EMAIL;
		if (!empty($mailer_options['FromName'])) {
			$this->FromName	= $mailer_options['FromName'];
		}
		else if (defined("OWNER_FROM")) {
			$this->FromName	= OWNER_FROM;
		}
		if (!empty($mailer_options['ReplyTo'])) {
			if (!empty($mailer_options['ReplyToName'])) {
				$reply_to_name = $mailer_options['ReplyToName'];
			} else {
				$reply_to_name = '';
			}
			$this->AddReplyTo($mailer_options['ReplyTo'], $reply_to_name);
		}
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
			$this->Body = Crumbs::capture_include("views/email/".$template_name.".html_email.php",$local_variables);
			$this->AltBody = Crumbs::capture_include("views/email/".$template_name.".email.php", $local_variables);
			$this->IsHTML(true);
		} else {
			$this->Body = Crumbs::capture_include("views/email/".$template_name.".email.php", $local_variables);
		}
		if (defined("EMAIL_WORDWRAP")) {
			$this->WordWrap = EMAIL_WORDWRAP;
		}
		Console::log($this);
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
	function AddRecipients($type,$recipients,$recipient_names = null) {
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
	function AddRecipient($type,$email,$name) {
		if (SERVER_TYPE != 'PRODUCTION') {
			$this->AddAddress(TECH_EMAIL);
			return;
		}
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
	function smtp_settings() {
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
	// TODO Develope email functions for queuing mail and sending mail from a queue
	function queue_mail($template_name, $mailer_options=array(), $local_variables=array()) {
		// When I grow up, I want to push emails into a mail queue database table
	}
	function send_mail_from_queue($queue_id) {
		// When I grow up, I want to read the next unsent mail from the database queue and send it off with the send_mail function.
		// Then you'll be able to setup a cron job on a server that can call me up from a URL
	}
}
?>