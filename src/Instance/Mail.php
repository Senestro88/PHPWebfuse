<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;

/**
 * @author Senestro
 */
class Mail {
    // PRIVATE VARIABLE

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
     * @param array $config The mail configuration data which are the host, username, mode (tls or ssl), port (tls: 587 and ssl:465), wordwrap
     */
    public function __construct(array $config = array()) {
        $this->overrideConfig($config);
    }

    /**
     * Send a mail
     * @param string $emailFrom
     * @param string $emailTo
     * @param string $title
     * @param string $messgae
     * @param array $attachments
     * @param array $config Set to override the configuration passed to the constructor
     * @return bool|string
     *
     * Return true on success, otherwise false or string representing error message
     */
    public function sendMail(string $emailFrom, string $emailTo, string $title, string $messgae, array $attachments = array(), array $config = array()): bool|string {
        $this->overrideConfig($config);
        $result = "";
        if(class_exists("\PHPMailer\PHPMailer\PHPMailer")) {
            if(Utils::isNotEmptyString($this->host) && Utils::isNotEmptyString($this->username) && Utils::isNotEmptyString($this->password)) {
                $isLocalhost = Utils::isLocalhost();
                $mailer = new \PHPMailer\PHPMailer\PHPMailer();
                try {
                    foreach($attachments as $index => $attachment) {
                        if(File::isNotFile($attachment)) {
                            unset($attachments[$index]);
                        }
                    }
                    $mailer->SMTPDebug = 0;
                    $mailer->isSMTP();
                    $mailer->WordWrap = $this->wordwrap;
                    $mailer->Host = $this->host;
                    if(!$isLocalhost) {
                        $mailer->SMTPAuth = true;
                        $mailer->SMTPAutoTLS = true;
                        $mailer->Username = $this->username;
                        $mailer->Password = $this->password;
                        $mailer->SMTPSecure = $this->mode;
                    }
                    $mailer->Port = $this->port;
                    if($isLocalhost) {
                        $mailer->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                    }
                    $mailer->setFrom($emailFrom, explode("@", $emailFrom)[0]);
                    $mailer->addAddress($emailTo, explode("@", $emailTo)[0]);
                    $mailer->isHTML(true);
                    foreach($attachments as $attachment) {
                        $mailer->addAttachment($attachment);
                    }
                    $mailer->Subject = $title;
                    $mailer->Body = "<!DOCTYPE html><html><body style='font-family: monospace, sans-serif;font-size: 16px;font-weight: normal;text-align:left;'>" . $messgae . "</body></html>";
                    $mailer->AltBody = strip_tags($messgae);
                    $mailer->send();
                    $mailer->clearAllRecipients();
                    $result = true;
                } catch(\Throwable $e) {
                    $result = "Unable to mail message to " . $emailTo . " [" . $mailer->ErrorInfo . "]";
                }
            } else {
                $result = "To sent a mail to " . $emailTo . ", the host, username and password must be provided.";
            }
        } else {
            $result = "To send a mail, the PHPMailer class [\PHPMailer\PHPMailer\PHPMailer] must exists.";
        }
        return $result;
    }

    // PRIVATE METHODS

    /**
     * Override configuration
     * @param array $config
     */
    private function overrideConfig(array $config = array()) {
        if(isset($config['host']) && Utils::isNotEmptyString($config['host'])) {
            $this->host = $config['host'];
        }
        if(isset($config['username']) && Utils::isNotEmptyString($config['username'])) {
            $this->username = $config['username'];
        }
        if(isset($config['password']) && Utils::isNumeric($config['password'])) {
            $this->password = (int) $config['password'];
        }
        if(isset($config['mode']) && Utils::isNotEmptyString($config['mode'])) {
            $this->mode = $config['mode'];
        }
        if(isset($config['port']) && Utils::isNumeric($config['port'])) {
            $this->port = (int) $config['port'];
        }
        if(isset($config['wordwrap']) && Utils::isInt($config['wordwrap'])) {
            $this->wordwrap = $config['wordwrap'];
        }
    }
}
