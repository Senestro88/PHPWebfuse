<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;

use chillerlan\QRCode\QRCode as CQRCode;
use \chillerlan\QRCode\QROptions as CQROptions;
use \chillerlan\QRCode\Common\EccLevel as CEccLevel;
use chillerlan\QRCode\Common\Mode as CMode;
use chillerlan\QRCode\Common\Version as CVersion;
use \chillerlan\QRCode\Data\QRMatrix as CQRMatrix;
use \chillerlan\QRCode\Output\QRGdImagePNG as CQRGdImagePNG;
use \chillerlan\QRCode\Output\QRCodeOutputException as CQRCodeOutputException;
use chillerlan\QRCode\Output\QRGdImage as CQRGdImage;
use \chillerlan\QRCode\Output\QROutputInterface as CQROutputInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * @author Senestro
 */
class QrCode {
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES

    public static string $errorMessage = "";

    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Creates a QR code image from a given text.
     * 
     * @param string|null $data The text data to be encoded into the QR code.
     * @param string|null $logo The logo image to place at the middle of the QR code.
     * @param string|null $filename The filename to save the generated QR code image. If null, the image is returned.
     * @param int $quality The quality of the generated QR code image (default: 90).
     * @param int $scaling The scaling factor of the QR code (default: 8).
     * @param bool $transparent Whether to enable transparent background (default: false).
     * @param bool $asBase64 Whether to return the image as a base64 encoded string (default: false).
     * 
     * @return bool|string Returns false on failure, but return a filename if filename parameter is provided, else the raw image data or the base64 version if base64 parameter is set to true
     */
    public static function createFromText(string $data, ?string $logo = null, ?string $filename = null, int $quality = 90, int $scaling = 8, bool $transparent = false, bool $asBase64 = false): bool|string {
        try {
            // Set the internal character encoding to UTF-8
            Utils::setMBInternalEncoding(false, "UTF-8");
            // Initialize QR code generation options
            $options = new CQROptions;
            $options->version = CVersion::AUTO; // QR code version (determines size)
            // Choose the output interface depending on whether a logo is provided
            $options->outputInterface = QrCodeGDOutputInterfacePNG::class;
            $options->scale = $scaling; // Scale factor for the QR code image
            $options->outputBase64 = $asBase64; // Return the image as a base64 string
            $options->drawLightModules = true; // Render the light modules (background spaces)
            $options->eccLevel = is_string($logo) ? CEccLevel::H : CEccLevel::Q; // Error correction level
            $options->quality = $quality; // Set image quality
            $options->bgColor = array(255, 255, 255); // Set background color (white)
            $options->imageTransparent = $transparent; // Enable transparent background
            $options->outputType = "png"; // Output type (PNG)
            $options->drawCircularModules = true; // Use circular modules instead of square ones
            $options->circleRadius = 0.45; // Set the radius for circular modules
            // Keep these parts as square
            $options->keepAsSquare = array(
                CQRMatrix::M_FINDER,
                CQRMatrix::M_FINDER_DARK,
                CQRMatrix::M_FINDER_DOT,
                CQRMatrix::M_FINDER_DOT_LIGHT,
                CQRMatrix::M_ALIGNMENT,
                CQRMatrix::M_ALIGNMENT_DARK,
                CQRMatrix::M_FORMAT,
                CQRMatrix::M_FORMAT_DARK
            );
            // Add space for a logo if a valid logo file is provided
            $options->addLogoSpace = is_string($logo);
            $options->logoSpaceWidth = 12; // Width for the logo space
            $options->logoSpaceHeight = 12; // Height for the logo space
            $qrcode = new CQRCode($options);
            $qrcode->addByteSegment($data);
            $outputInterface = new QrCodeGDOutputInterfacePNG($options, $qrcode->getQRMatrix());
            $result = $outputInterface->dump(null, $logo);
            // If a filename is provided, save the result to the specified file
            if (Utils::isNonNull($filename)) {
                if (!File::saveContentToFile($filename, $result, false, false)) {
                    return false; // Return false if file saving fails
                } else {
                    unset($result); // Clear result to free memory
                    return realpath($filename); // Return the real path to the saved file
                }
            }
            // Return the generated QR code image or base64 string
            return $result;
        } catch (\Throwable $e) {
            // Save the error message
            self::$errorMessage = $e->getMessage();
        }
        // Return false in case of an error
        return false;
    }

    /**
     * Reads and processes QR code data from a text string.
     * 
     * @param string $text The text content representing QR code data.
     * 
     * @return bool|string Returns decoded data from QR code or false on failure.
     */
    public static function readFromText(string $text): bool|string {
        $result = false;
        try {
            // Set up options for reading the QR code
            $options = new CQROptions;
            $options->readerUseImagickIfAvailable = false; // Prefer GD over Imagick
            $options->readerGrayscale = true; // Read in grayscale for better contrast
            $options->readerIncreaseContrast = true; // Enhance contrast for better readability
            // Read the QR code data from the file
            $qrResult = (new CQRCode($options))->readFromBlob($text);
            // Extract the data from the QR result
            $result = $qrResult->data;
        } catch (\Throwable $e) {
            // Save the error message
            self::$errorMessage = $e->getMessage();
        }
        // Return false on error
        return $result;
    }

    /**
     * Reads QR code data from an image file.
     * 
     * @param string $filename The path to the QR code image file.
     * 
     * @return bool|string Returns decoded data from QR code or false on failure.
     */
    public static function readFromFile(string $filename): bool|string {
        $result = false;
        try {
            // Set up options for reading the QR code
            $options = new CQROptions;
            $options->readerUseImagickIfAvailable = false; // Prefer GD over Imagick
            $options->readerGrayscale = true; // Read in grayscale for better contrast
            $options->readerIncreaseContrast = true; // Enhance contrast for better readability
            // Read the QR code data from the file
            $qrResult = (new CQRCode($options))->readFromFile($filename);
            // Extract the data from the QR result
            $result = $qrResult->data;
        } catch (\Throwable $e) {
            // Save the error message
            self::$errorMessage = $e->getMessage();
        }
        // Return false on error
        return $result;
    }
}


// FINAL INNER CLASSES

/**
 * @author Senestro
 */
final class QrCodeGDOutputInterfacePNG extends CQRGdImagePNG {
    public function dump(string|null $file = null, string|null $logo = null): string {
        // Set returnResource to true to skip further processing for now
        $this->options->returnResource = true;
        parent::dump($file);
        if ($this->validImage($logo)) {
            $im = imagecreatefrompng($logo);
            if (Utils::isNotFalse($im)) {
                // Get logo image size
                $w = imagesx($im);
                $h = imagesy($im);
                // Set new logo size, leave a border of 1 module (no proportional resize/centering)
                $lw = (($this->options->logoSpaceWidth - 2) * $this->options->scale);
                $lh = (($this->options->logoSpaceHeight - 2) * $this->options->scale);
                // Set the qrcode size
                $ql = ($this->matrix->getSize() * $this->options->scale);
                // Scale the logo and copy it over. done!
                imagecopyresampled($this->image, $im, (($ql - $lw) / 2), (($ql - $lh) / 2), 0, 0, $lw, $lh, $w, $h);
            }
        }
        $data = $this->dumpImage();
        $this->saveToFile($data, $file);
        $data = $this->options->outputBase64 ? $this->toBase64DataURI($data) : $data;
        return $data;
    }

    private function validImage(?string $filename = null): bool {
        return \is_string($filename) ? \is_file($filename) && \is_readable($filename)  : false;
    }
}
