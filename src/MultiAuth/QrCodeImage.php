<?php

namespace PHPWebfuse\MultiAuth;

class QrCodeImage extends \PHPWebfuse\Methods
{
    // PUBLIC METHODS

    public function __construct()
    {
    }

    /**
     * Generates a URL that is used to show a QR code.
     *
     * @param string $accountName The account name to show and identify
     * @param string $secret The secret is the generated secret unique to that user
     * @param string|null $issuer Where you log in to
     */
    public function createBase64Image(string $accountName, string $secret, ?string $issuer = null): string
    {
        if ($accountName === "" || strpos($accountName, ':') !== false) {
            throw \PHPWebfuse\MultiAuth\QrException::InvalidAccountName($accountName);
        }
        if ($secret === "") {
            throw \PHPWebfuse\MultiAuth\QrException::InvalidSecret();
        }
        $label = $accountName;
        $content = 'otpauth://totp/%s?secret=%s';
        if ($issuer !== null) {
            if ($issuer === "" || strpos($issuer, ':') !== false) {
                throw \PHPWebfuse\MultiAuth\QrException::InvalidIssuer($issuer);
            }
            // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
            $label = $issuer . ':' . $label;
            $content .= '&issuer=%s';
        }
        $content = htmlspecialchars_decode(sprintf($content, $label, $secret, $issuer));
        if (!defined('QR_MODE_NUL')) {
            parent::loadLib("phpqrcode" . DIRECTORY_SEPARATOR . "qrlib");
        }
        $tempPathname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebfuse['directories']['data'] . DIRECTORY_SEPARATOR . 'multiauth' . DIRECTORY_SEPARATOR . 'temp'));
        if (self::makeDir($tempPathname) && class_exists('\QRcode')) {
            $absolutePath = $tempPathname . '' . parent::randUnique("key") . '.png';
            \QRcode::png($content, $absolutePath, QR_ECLEVEL_Q, 20, 2);
            if (parent::isFile($absolutePath)) {
                clearstatcache(false, $absolutePath);
                $mime = mime_content_type($absolutePath);
                $baseEncode = base64_encode((string) parent::getFileContent($absolutePath));
                $data = 'data:' . $mime . ';base64,' . $baseEncode;
                parent::deleteFile($absolutePath);
                return $data;
            }
        }
        return "";
    }

    /**
     * Display the QR code to browser
     *
     * @param string $accountName The account name to show and identify
     * @param string $secret The secret is the generated secret unique to that user
     * @param string|null $issuer Where you log in to
     */
    public function createOuputImage(string $accountName, string $secret, ?string $issuer = null): void
    {
        if ($accountName === "" || strpos($accountName, ':') !== false) {
            throw \PHPWebfuse\MultiAuth\QrException::InvalidAccountName($accountName);
        }
        if ($secret === "") {
            throw \PHPWebfuse\MultiAuth\QrException::InvalidSecret();
        }
        $label = $accountName;
        $content = 'otpauth://totp/%s?secret=%s';
        if ($issuer !== null) {
            if ($issuer === "" || strpos($issuer, ':') !== false) {
                throw \PHPWebfuse\MultiAuth\QrException::InvalidIssuer($issuer);
            }
            // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
            $label = $issuer . ':' . $label;
            $content .= '&issuer=%s';
        }
        $content = htmlspecialchars_decode(sprintf($content, $label, $secret, $issuer));
        if (!defined('QR_MODE_NUL')) {
            parent::loadLib("phpqrcode" . DIRECTORY_SEPARATOR . "qrlib");
        }
        $tempPathname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebfuse['directories']['data'] . DIRECTORY_SEPARATOR . 'multiauth' . DIRECTORY_SEPARATOR . 'temp'));
        if (self::makeDir($tempPathname) && class_exists('\QRcode')) {
            $absolutePath = $tempPathname . '' . parent::randUnique("key") . '.png';
            \QRcode::png($content, $absolutePath, QR_ECLEVEL_Q, 4, 2);
            if (parent::isFile($absolutePath)) {
                $image = imagecreatefrompng($absolutePath);
                if (parent::isNotFalse($image) && !headers_sent()) {
                    $contentType = mime_content_type($absolutePath);
                    header("Expires: Mon, 7 Apr 1997 01:00:00 GMT");
                    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
                    header("Cache-Control: no-store, no-cache, must-revalidate");
                    header("Cache-Control: post-check=0, pre-check=0", false);
                    header("Content-Type: " . $contentType);
                    header("Pragma: no-cache");
                    imagepng($image, null, 9);
                    imagedestroy($image);
                }
            }
        }
    }
}