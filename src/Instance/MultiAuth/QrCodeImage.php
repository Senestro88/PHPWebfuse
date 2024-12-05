<?php

namespace PHPWebfuse\Instance\MultiAuth;

use \PHPWebfuse\Utils;
use \PHPWebfuse\File;
use \PHPWebfuse\Path;
use \PHPWebfuse\Instance\MultiAuth\QrException;
use PHPWebfuse\Instance\QrCode;

class QrCodeImage {
    // PRIVATE VARIABLES
    // PUBLIC METHODS

    public function __construct() {
    }

    /**
     * Generates a URL that is used to show a QR code.
     *
     * @param string $accountName The account name to show and identify
     * @param string $secret The secret is the generated secret unique to that user
     * @param string|null $issuer Where you log in to
     * @return string
     * @throws \PHPWebfuse\Instance\MultiAuth\QrException
     */
    public function createBase64Image(string $accountName, string $secret, ?string $issuer = null): string {
        if (Utils::isEmptyString($accountName) || Utils::containText(":", $accountName)) {
            throw QrException::InvalidAccountName($accountName);
        } else {
            if (Utils::isEmptyString($secret)) {
                throw QrException::InvalidKey();
            } else {
                $label = $accountName;
                $content = 'otpauth://totp/%s?secret=%s';
                if (Utils::isNonNull($issuer)) {
                    if (Utils::isEmptyString($issuer) || Utils::containText(":", $issuer)) {
                        throw QrException::InvalidIssuer($issuer);
                    } else {
                        // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
                        $label = $issuer . ':' . $label;
                        $content .= '&issuer=%s';
                    }
                }
                $content = htmlspecialchars_decode(sprintf($content, $label, $secret, $issuer));
                $qrcode = new QrCode($content);
                $result = $qrcode->createResult();
                return $result->getDataUri();
            }
        }
    }

    /**
     * Display the QR code to browser
     *
     * @param string $accountName The account name to show and identify
     * @param string $secret The secret is the generated secret unique to that user
     * @param string|null $issuer Where you log in to
     * @throws \PHPWebfuse\Instance\MultiAuth\QrException
     */
    public function createOutputImage(string $accountName, string $secret, ?string $issuer = null): void {
        if (Utils::isEmptyString($accountName) || Utils::containText(":", $accountName)) {
            throw QrException::InvalidAccountName($accountName);
        } else {
            if (Utils::isEmptyString($secret)) {
                throw QrException::InvalidKey();
            } else {
                $label = $accountName;
                $content = 'otpauth://totp/%s?secret=%s';
                if ($issuer !== null) {
                    if (Utils::isEmptyString($issuer) || Utils::containText(":", $issuer)) {
                        throw QrException::InvalidIssuer($issuer);
                    } else {
                        // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
                        $label = $issuer . ':' . $label;
                        $content .= '&issuer=%s';
                    }
                }
                $content = htmlspecialchars_decode(sprintf($content, $label, $secret, $issuer));
                $qrcode = new QrCode($content);
                $result = $qrcode->createResult();
                $dataPath = Path::insert_dir_separator(Path::arrange_dir_separators(PHPWEBFUSE['DIRECTORIES']['DATA'] . DIRECTORY_SEPARATOR . 'multiauth' . DIRECTORY_SEPARATOR . 'temp'));
                if ((File::createDir($dataPath))) {
                    $absolutePath = $dataPath . '' . Utils::randUnique("key") . '.png';
                    $result->saveToFile($absolutePath);
                    if (File::isFile($absolutePath)) {
                        $image = imagecreatefrompng($absolutePath);
                        if (Utils::isNotFalse($image) && !headers_sent()) {
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
                        File::deleteFile($absolutePath);
                    }
                }
            }
        }
    }
}
