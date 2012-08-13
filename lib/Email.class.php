<?php

/**
 * Provide an email class which uses PHPMailer to send emails
 * It can handle templates
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Tools
 */
class Email extends Object
{

	/**
	 * All occured errors
	 * @var array
	 */
	public $errors = array();

	/**
	 * Holds the last error
	 * @var string
	 */
	public $last_error = "";

	/**
	 * Determines if we just print the message or realy send it (true to just print)
	 * @var boolean
	 */
	public $debug = false;

	/**
	 * Add an error to the error array and set the provided $msg as the last error
	 *
	 * @param string $msg
	 *   The error message
	 */
	public function error($msg) {
		//Set the last error
		$this->last_error = $msg;

		//Add the error the our error-array
		$this->errors[] = $msg;
	}

	/**
	 * Send an e-mail template
	 *
	 * if $need_confirmation is set, the receiver get a request to confirm the reading of this email
	 * it must be a valid email address provided couse there the confirmation will be send.
	 *
	 * @param string $template
	 *   the Template key
	 * @param string $language
	 *   the language
	 * @param mixed $receiver
	 *   the user object, an array with ('email', 'name') or the plain email where to send the email
	 * @param array $tpl_vals
	 *   the Template replace vars as an array (optional, default = array())
	 * @param string $attachments
	 *   attach files provide only filepathes in an array(file1,file2,file3,....)  (optional, default = array())
	 * @param string $need_confirmation
	 *   Set the confirmation email (optional, default = '')
	 *
	 * @return true on success, else false
	 *
	 * @uses send($receiver, $from, $subject, $body, $attachments = array(), $need_confirmation)
	 */
	public function send_tpl($template, $language, $receiver, Array $tpl_vals = array(), $attachments = array(), $need_confirmation = '') {
		//try to load the given $template with the given $language
		$mail_template = new MailTemplateObj($template, $language);

		//If template not exists within the selected language try to load it with english language
		if (!$mail_template->load_success() || empty($mail_template->body) || empty($mail_template->subject)) {

			unset($mail_template);
			$mail_template = new MailTemplateObj($template, $this->core->current_language);

			//If template not exists within the current language try to load it with english language
			if (!$mail_template->load_success() || empty($mail_template->body) || empty($mail_template->subject)) {

				unset($mail_template);
				$mail_template = new MailTemplateObj($template, 'en');
			}
		}
		//If this template also not exist send a debug email that we miss this template and return false
		if (!$mail_template->load_success() || empty($mail_template->body) || empty($mail_template->subject)) {
			debug_mail("Missing Mailtemplate", "I missed on the server core message template: ".$template);
			return false;
		}

		//Setup all template variables, it will replace the variables with their values
		$mail_template->parse($tpl_vals);
		if($this->debug == true) {
			echo "Receiver: ".$receiver."\n";
			echo "From: ".$this->core->config['core']['debug_email']."\n";
			echo "Subject: ".$mail_template->subject."\n";
			echo "Body: \n".$mail_template->body."\n";
			return true;
		}
		//Send the email and return the result. It will use the debug email as the sender.
		return $this->send($receiver, $this->core->config['core']['debug_email'], $mail_template->subject, $mail_template->body, $attachments, $need_confirmation);
	}

	/**
	 * Send an e-mail
	 *
	 * if $need_confirmation is set, the receiver get a request to confirm the reading of this email
	 * it must be a valid email address provided couse there the confirmation will be send.
	 *
	 * @param string $receiver
	 *   the receiver email as a string or array("email","name")
	 * @param string $from
	 *   the sender email as a string or array("email","name")
	 * @param string $subject
	 *   the email subject
	 * @param string $body
	 *   the email body
	 * @param string $attachments
	 *   attach files provide only filepathes in an array(file1,file2,file3,....)  (optional, default = array())
	 * @param string $need_confirmation
	 *   Set the confirmation email(optional, default = '')
	 *
	 * @return true on success, else false, if false, error messages will be stored in $errors array
	 */
	public function send($receiver, $from, $subject, $body, $attachments = array(), $need_confirmation = '') {
		//initialize the phpmailer the bool parameter true tells the phpmailer to throw exceptions so we can abort if something went wrong
		$mail = new PHPMailer(true);
		try {
			//Check if we have the array form ('email', 'name') if so add it
			if (is_array($receiver)) {
				$mail->AddAddress($receiver[0], $receiver[1]);
			}
			//Check if receiver is a User object, if so we try to get an address for that user and add the values
			elseif ($receiver instanceof UserObj) {
				$address = $receiver->get_address_by_group();
				if (empty($address)) {
					throw new Exception("No valid address for given receiver found");
				}
				$mail->AddAddress($address['email']);
			}
			//Nothing matches so we just add it as a string
			else {
				$mail->AddAddress($receiver);
			}

			//Check if we have the array form ('email', 'name') if so add it
			if (is_array($from)) {
				$mail->SetFrom($from[0], $from[1]);
			}
			//Nothing matches so we just add it as a string
			else {
				$mail->SetFrom($from);
			}

			$mail->CharSet = "utf-8";
			$mail->Subject = $subject;

			//Set the confirmation receiver email, if empty it will be disabled
			$mail->ConfirmReadingTo = $need_confirmation;

			//Setup the message body
			$mail->MsgHTML(nl2br($body));

			//Add attachments
			foreach ($attachments AS $attachment) {
				$mail->AddAttachment($attachment);
			}

			//Set the email as HTML
			$mail->IsHTML(true);

			//Send the email and return the result if mail was send
			return $mail->Send();
		}
		catch (phpmailerException $e) {
			//Error occured, add the message and return false
			$this->error($e->errorMessage());
			return false;
		}
		catch (Exception $e) {
			//Error occured, add the message and return false
			$this->error($e->errorMessage());
			return false;
		}
	}

}

?>