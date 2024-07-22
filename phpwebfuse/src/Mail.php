<?php
namespace PHPWebFuse;
/**
 *
 */
class Mail extends \PHPWebFuse\Methods {
	// PRIVATE VARIABLES

	private $host = "localhost";
	private $username = "";
	private $password = "";
	private $mode = "tls"; // `ssl` or `tls`
	private $port = 587; // TLS Port => 587, SSL Port => 465
	private $wordwrap = 100;

	// PUBLIC METHODS

	/**
	 * @param array $config - The mail configuration data which are the host, username, mode (tls or ssl), port (tls: 587 and ssl:465), wordwrap 
	 */
	public function __construct(array $config = array()) {$this->overrideConfiguration($config);}

	/**
	 * Send a mail
	 *
	 * @param string $emailFrom
	 * @param string $emailTo
	 * @param string $title
	 * @param string $messgae
	 * @param array $attachments: Defaults to 'array()'
	 * @param array $config: Defaults to 'array()'
	 * @return bool | string
	 *
	 * Return true on success, otherwise false or string representing error message
	 */
	public function sendMail(string $emailFrom, string $emailTo, string $title, string $messgae, array $attachments = array(), array $config = array()): bool | string {
		$this->overrideConfiguration($config);
		$result = "";
		if (class_exists("\PHPMailer\PHPMailer\PHPMailer")) {
			if (parent::isNotEmptyString($this->host) && parent::isNotEmptyString($this->username) && parent::isNotEmptyString($this->password)) {
				$isLocalhost = parent::isLocalhost();
				$mailer = new \PHPMailer\PHPMailer\PHPMailer();
				try {
					foreach ($attachments as $index => $attachment) {if (parent::isNotFile($attachment)) {unset($attachments[$index]);}}
					$mailer->SMTPDebug = 0;
					$mailer->isSMTP();
					$mailer->WordWrap = $this->wordwrap;
					$mailer->Host = $this->host;
					if (!$isLocalhost) {
						$mailer->SMTPAuth = true;
						$mailer->SMTPAutoTLS = true;
						$mailer->Username = $this->username;
						$mailer->Password = $this->password;
						$mailer->SMTPSecure = $this->mode;
					}
					$mailer->Port = $this->port;
					if ($isLocalhost) {$mailer->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));}
					$mailer->setFrom($emailFrom, explode("@", $emailFrom)[0]);
					$mailer->addAddress($emailTo, explode("@", $emailTo)[0]);
					$mailer->isHTML(true);
					foreach ($attachments as $attachment) {$mailer->addAttachment($attachment);}
					$mailer->Subject = $title;
					$mailer->Body = "<!DOCTYPE html><html><body style='font-family: monospace, sans-serif;font-size: 16px;font-weight: normal;text-align:left;'>" . $messgae . "</body></html>";
					$mailer->AltBody = strip_tags($messgae);
					$mailer->send();
					$mailer->clearAllRecipients();
					$result = true;
				} catch (\Throwable $e) {$result = "Unable to mail message to " . $emailTo . " [" . $mailer->ErrorInfo . "]";}
			} else { $result = "To sent a mail to " . $emailTo . ", the host, username and password must be provided.";}
		} else { $result = "To send a mail, the PHPMailer class [\PHPMailer\PHPMailer\PHPMailer] must exists.";}
		return $result;
	}

	// PRIVATE METHODS
	private function overrideConfiguration(array $config = array()) {
		if (isset($config['host']) && parent::isNotEmptyString($config['host'])) {$this->host = $config['host'];}
		if (isset($config['username']) && parent::isNotEmptyString($config['username'])) {$this->username = $config['username'];}
		if (isset($config['password']) && parent::isNumeric($config['password'])) {$this->password = (int) $config['password'];}
		if (isset($config['mode']) && parent::isNotEmptyString($config['mode'])) {$this->mode = $config['mode'];}
		if (isset($config['port']) && parent::isNumeric($config['port'])) {$this->port = (int) $config['port'];}
		if (isset($config['wordwrap']) && parent::isInt($config['wordwrap'])) {$this->wordwrap = $config['wordwrap'];}
	}
}