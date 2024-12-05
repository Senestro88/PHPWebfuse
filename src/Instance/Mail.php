<?php

namespace PHPWebfuse\Instance;

use PHPMailer\PHPMailer\SMTP;
use \PHPWebfuse\Utils;
use \PHPWebfuse\File;
use  \PHPMailer\PHPMailer\PHPMailer;

/**
 * @author Senestro
 */
class Mail {
    // PRIVATE VARIABLES

    /**
     * @var string The default mail host
     */
    private string $host = "localhost";

    /**
     * @var string The default mail username
     */
    private string $username = "";

    /**
     * @var string The default mail password
     */
    private string $password = "";

    /**
     * @var string The default mail mode, value can be `ssl` or `tls`
     */
    private string $mode = "tls";

    /**
     * @var int The default mail port, value can be TLS Port => 587, SSL Port => 465
     */
    private int $port = 587;

    /**
     * @var int The default amount of characters to word wrap when sending mail
     */
    private int $wordwrap = 100;


    private bool $verifyPeer = false;
    private bool $verifyPeerName = false;
    private bool $allowSelfSigned = false;

    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Construct a new Mail instance
     * 
     * @param array $config The mail configuration data which consists of host, username, password, mode (tls or ssl), port (tls: 587 and ssl:465), wordwrap, verifyPeer, verifyPeerName, and allowSelfSigned
     */
    public function __construct(array $config = array()) {
        // Initialize mail configuration
        $this->configure($config);
    }

    /**
     * Send a mail
     * 
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $title
     * @param string $message
     * @param array $attachments
     * @param bool $authenticate
     * @param bool $debug
     * @return bool|string
     * 
     * Return true on success, otherwise false or string representing error message
     */
    public function sendMail(string $emailFrom, string $emailTo, string $title, string $message, array $attachments = array(), bool $authenticate = true, bool $debug = false): bool|string {
        $result = "";
        // Check if PHPMailer class exists
        if (class_exists("\PHPMailer\PHPMailer\PHPMailer")) {
            $mailer = new PHPMailer();
            try {
                // Reset attachments to only include valid files
                $attachments = $this->validateAttachments($attachments);
                // Configure mailer settings
                $mailer->SMTPDebug = $debug ? SMTP::DEBUG_CONNECTION : SMTP::DEBUG_OFF;
                $mailer->WordWrap = $this->wordwrap;
                $mailer->Host = $this->host;
                $this->setAuthentication($mailer, $authenticate);
                $mailer->Port = $this->port;
                $mailer->SMTPOptions = array('ssl' => array('verify_peer' => $this->verifyPeer, 'verify_peer_name' => $this->verifyPeerName, 'allow_self_signed' => $this->allowSelfSigned));
                // Set mail content
                $mailer->setFrom($emailFrom);
                $mailer->addAddress($emailTo);
                $mailer->isHTML(true);
                $this->insertAttachments($mailer, $attachments);
                $mailer->Subject = $title;
                $mailer->Body = "<!DOCTYPE html><html><body style='font-family: monospace, sans-serif;font-size: 16px;font-weight: normal;text-align:left;'>" . $message . "</body></html>";
                $mailer->AltBody = strip_tags($message);
                // Send mail
                $result = $mailer->send();
                $mailer->clearAllRecipients();
            } catch (\Throwable $e) {
                $result = "Unable to mail message to " . $emailTo . " [" . $mailer->ErrorInfo . "]";
            }
        } else {
            $result = "To send a mail, the PHPMailer class [\PHPMailer\PHPMailer\PHPMailer] must exists.";
        }
        return $result;
    }

    // PRIVATE METHODS

    /**
     * Override configuration
     * 
     * @param array $config
     */
    private function configure(array $config = array()) {
        // Update mail configuration with provided settings
        $this->host = isset($config['host']) && Utils::isNotEmptyString($config['host']) ? (string) $config['host'] : "localhost";
        $this->username = isset($config['username']) && Utils::isNotEmptyString($config['username']) ? (string) $config['username'] : "";
        $this->password = isset($config['password']) && Utils::isNotEmptyString($config['password']) ? (string) $config['password'] : "";
        $this->mode = isset($config['mode']) && Utils::isNotEmptyString($config['mode']) ? (string) $config['mode'] : "tls";
        $this->port = isset($config['port']) && Utils::isNumeric($config['port']) ? (int) $config['port'] : 587;
        $this->wordwrap = isset($config['wordwrap']) && Utils::isNumeric($config['wordwrap']) ? (int) $config['wordwrap'] : 100;
        $this->verifyPeer = isset($config['verifyPeer']) && Utils::isBool($config['verifyPeer']) ? (bool) $config['verifyPeer'] : false;
        $this->verifyPeerName = isset($config['verifyPeerName']) && Utils::isBool($config['verifyPeerName']) ? (bool) $config['verifyPeerName'] : false;
        $this->allowSelfSigned = isset($config['allowSelfSigned']) && Utils::isBool($config['allowSelfSigned']) ? (bool) $config['allowSelfSigned'] : true;
    }

    /**
     * Reset attachments to only include valid files
     * 
     * @param array $attachments
     * @return array
     */
    private function validateAttachments(array $attachments = array()): array {
        foreach ($attachments as $index => $attachment) {
            if (File::isNotFile($attachment)) {
                unset($attachments[$index]);
            }
        }
        return $attachments;
    }

    /**
     * Insert attachments into the mailer
     * 
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param array $attachments
     */
    private function insertAttachments(\PHPMailer\PHPMailer\PHPMailer $mailer, array $attachments = array()): void {
        foreach ($attachments as $attachment) {
            if (File::isFile($attachment)) {
                $mailer->addAttachment($attachment);
            }
        }
    }

    /**
     * Set authentication for the mailer
     * 
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param bool $authenticate
     */
    private function setAuthentication(\PHPMailer\PHPMailer\PHPMailer $mailer, bool $authenticate = true): void {
        if ($authenticate) {
            $mailer->isSMTP();
            $mailer->SMTPAuth = true; // Whether to use SMTP authentication.
            $mailer->SMTPAutoTLS = true; // Whether to enable TLS encryption automatically if a server supports it, even if SMTPSecure is not set to 'tls'.
            $mailer->Username = $this->username; // SMTP username.
            $mailer->Password = $this->password; // SMTP password.
            $mailer->SMTPSecure = $this->mode; // What kind of encryption to use on the SMTP connection.
        }
    }
}
