<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;
use \PHPWebfuse\Qrcode\OutputDumper;

use \chillerlan\QRCode\QRCode as ChillerlanQRCode;
use \chillerlan\QRCode\QROptions as ChillerlanQROptions;
use \chillerlan\QRCode\Common\EccLevel as ChillerlanEccLevel;
use \chillerlan\QRCode\Common\Mode as ChillerlanMode;
use \chillerlan\QRCode\Common\Version as ChillerlanVersion;
use \chillerlan\QRCode\Data\QRMatrix as ChillerlanQRMatrix;
use \chillerlan\QRCode\Output\QRGdImagePNG as ChillerlanQRGdImagePNG;
use \chillerlan\QRCode\Output\QRCodeOutputException as ChillerlanQRCodeOutputException;
use \chillerlan\QRCode\Output\QRGdImage as ChillerlanQRGdImage;
use \chillerlan\QRCode\Output\QROutputInterface as ChillerlanQROutputInterface;
use \Zxing\QrReader;

/**
 * @author Senestro
 */

class QrCode {
    // PRIVATE VARIABLE
    // PUBLIC VARIABLES
    public static ?\Throwable $lastThrowable = null;

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
            $options = new ChillerlanQROptions;
            $options->version = ChillerlanVersion::AUTO; // QR code version (determines size)
            // Choose the output interface depending on whether a logo is provided
            $options->outputInterface = OutputDumper::class;
            $options->scale = $scaling; // Scale factor for the QR code image
            $options->outputBase64 = $asBase64; // Return the image as a base64 string
            $options->drawLightModules = true; // Render the light modules (background spaces)
            $options->eccLevel = is_string($logo) ? ChillerlanEccLevel::H : ChillerlanEccLevel::Q; // Error correction level
            $options->quality = $quality; // Set image quality
            $options->bgColor = array(255, 255, 255); // Set background color (white)
            $options->imageTransparent = $transparent; // Enable transparent background
            $options->outputType = "png"; // Output type (PNG)
            $options->drawCircularModules = true; // Use circular modules instead of square ones
            $options->circleRadius = 0.45; // Set the radius for circular modules
            self::setCreateFromTextModules($options);
            // Add space for a logo if a valid logo file is provided
            $options->addLogoSpace = is_string($logo);
            $options->logoSpaceWidth = 12; // Width for the logo space
            $options->logoSpaceHeight = 12; // Height for the logo space
            $qrcode = new ChillerlanQRCode($options);
            $qrcode->addByteSegment($data);
            $outputInterface = new OutputDumper($options, $qrcode->getQRMatrix());
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
            self::setLastThrowable($e);
        }
        return false;
    }

    /**
     * Reads QR code data from an image file.
     * 
     * @param string $filename The path to the QR code image file.
     * 
     * @return bool|string Returns decoded data from QR code or false on failure.
     */
    public static function readFile(string $filename): bool | string {
        try {
            if (File::isFile($filename)) {
                $filename = realpath($filename);
                $reader = new QrReader($filename);
                $result = $reader->text();
                return \is_string($result) ? $result : false;
            }
        } catch (\Throwable $e) {
            self::setLastThrowable($e);
        }
        return false;
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
            $options = new ChillerlanQROptions;
            $options->readerUseImagickIfAvailable = false; // Prefer GD over Imagick
            $options->readerGrayscale = true; // Read in grayscale for better contrast
            $options->readerIncreaseContrast = true; // Enhance contrast for better readability
            $result = (new ChillerlanQRCode($options))->readFromFile($filename);
            $result = $result->data;
        } catch (\Throwable $e) {
            self::setLastThrowable($e);
        }
        return $result;
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
            $options = new ChillerlanQROptions;
            $options->readerUseImagickIfAvailable = false; // Prefer GD over Imagick
            $options->readerGrayscale = true; // Read in grayscale for better contrast
            $options->readerIncreaseContrast = true; // Enhance contrast for better readability
            $result = (new ChillerlanQRCode($options))->readFromBlob($text);
            $result = $result->data;
        } catch (\Throwable $e) {
            self::setLastThrowable($e);
        }
        return $result;
    }

    /**
     * Retrieves the last throwable instance.
     *
     * This method returns the most recent throwable instance that was stored
     * using the `setLastThrowable` method. If no throwable has been set, it returns null.
     *
     * @return \Throwable|null The last throwable instance or null if none is set.
     */
    public static function getLastThrowable(): ?\Throwable {
        return self::$lastThrowable;
    }

    // PRIVATE METHODS

    private static function setCreateFromTextModules(ChillerlanQROptions $options): void {
        // Keep these parts as square
        $options->keepAsSquare = array(
            ChillerlanQRMatrix::M_FINDER,
            ChillerlanQRMatrix::M_FINDER_DARK,
            ChillerlanQRMatrix::M_FINDER_DOT,
            ChillerlanQRMatrix::M_FINDER_DOT_LIGHT,
            ChillerlanQRMatrix::M_ALIGNMENT,
            ChillerlanQRMatrix::M_ALIGNMENT_DARK,
            ChillerlanQRMatrix::M_DARKMODULE,
            ChillerlanQRMatrix::M_DARKMODULE_LIGHT,
            ChillerlanQRMatrix::M_DATA,
            ChillerlanQRMatrix::M_DATA_DARK,
            ChillerlanQRMatrix::M_FORMAT,
            ChillerlanQRMatrix::M_FORMAT_DARK,
            ChillerlanQRMatrix::M_LOGO,
            ChillerlanQRMatrix::M_LOGO_DARK,
            ChillerlanQRMatrix::M_NULL,
            ChillerlanQRMatrix::M_QUIETZONE_DARK,
            ChillerlanQRMatrix::M_SEPARATOR_DARK,
            ChillerlanQRMatrix::M_TIMING,
        );
        $options->moduleValues = array();
    }

    /**
     * Sets the last throwable instance.
     *
     * This method allows storing the most recent exception or error object 
     * that implements the Throwable interface. It is useful for tracking
     * or logging errors globally.
     *
     * @param \Throwable $e The throwable instance to be stored.
     * @return void
     */
    private static function setLastThrowable(\Throwable $e): void {
        self::$lastThrowable = $e;
    }
}
