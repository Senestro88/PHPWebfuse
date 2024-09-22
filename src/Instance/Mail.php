<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;

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

    // PUBLIC VARIABLES
    // PUBLIC METHODS

    /**
     * Construct a new Mail instance
     * 
     * @param array $config The mail configuration data which consists of host, username, password, mode (tls or ssl), port (tls: 587 and ssl:465), and wordwrap
     */
    public function __construct(array $config = array()) {
        // Initialize mail configuration
        $this->config($config);
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
     * @return bool|string
     * 
     * Return true on success, otherwise false or string representing error message
     */
    public function sendMail(string $emailFrom, string $emailTo, string $title, string $message, array $attachments = array(), bool $authenticate = true): bool|string {
        $result = "";

        // Check if PHPMailer class exists
        if (class_exists("\PHPMailer\PHPMailer\PHPMailer")) {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer();
            try {
                // Reset attachments to only include valid files
                $attachments = $this->resetAttachments($attachments);
                // Configure mailer settings
                $mailer->SMTPDebug = 0;
                $mailer->isSMTP();
                $mailer->WordWrap = $this->wordwrap;
                $mailer->Host = $this->host;
                $mailer->SMTPAuth = $authenticate;
                $mailer->SMTPAutoTLS = true;
                $mailer->Username = $this->username;
                $mailer->Password = $this->password;
                $mailer->SMTPSecure = $this->mode;
                $mailer->Port = $this->port;
                $mailer->SMTPOptions = array('ssl' => array('verify_peer' => $authenticate, 'verify_peer_name' => $authenticate, 'allow_self_signed' => !$authenticate));
                // Set mail content
                $mailer->setFrom($emailFrom);
                $mailer->addAddress($emailTo);
                $mailer->isHTML(true);
                $this->insertAttachments($mailer, $attachments);
                $mailer->Subject = $title;
                $mailer->Body = "<!DOCTYPE html><html><body style='font-family: monospace, sans-serif;font-size: 16px;font-weight: normal;text-align:left;'>" . $message . "</body></html>";
                $mailer->AltBody = strip_tags($message);
                // Send mail
                $mailer->send();
                $mailer->clearAllRecipients();
                $result = true;
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
    private function config(array $config = array()) {
        // Update mail configuration with provided settings
        $this->host = isset($config['host']) && Utils::isNotEmptyString($config['host']) ? (string) $config['host'] : "localhost";
        $this->username = isset($config['username']) && Utils::isNotEmptyString($config['username']) ? (string) $config['username'] : "";
        $this->password = isset($config['password']) && Utils::isNotEmptyString($config[' password']) ? (string) $config['password'] : "";
        $this->mode = isset($config['mode']) && Utils::isNotEmptyString($config['mode']) ? (string) $config['mode'] : "tls";
        $this->port = isset($config['port']) && Utils::isNumeric($config['port']) ? (int) $config['port'] : 587;
        $this->wordwrap = isset($config['wordwrap']) && Utils::isNumeric($config['wordwrap']) ? (int) $config['wordwrap'] : 100;
    }

    /**
     * Reset attachments to only include valid files
     * 
     * @param array $attachments
     * @return array
     */
    private function resetAttachments(array $attachments = array()): array {
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
}