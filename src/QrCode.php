<?php

namespace PHPWebfuse;

use \PHPWebfuse\Utils;

use chillerlan\QRCode\QRCode as CQRCode;
use \chillerlan\QRCode\QROptions as CQROptions;
use \chillerlan\QRCode\Common\EccLevel as CEccLevel;
use \chillerlan\QRCode\Data\QRMatrix as CQRMatrix;
use \chillerlan\QRCode\Output\QRGdImagePNG as CQRGdImagePNG;
use \chillerlan\QRCode\Output\QRCodeOutputException as CQRCodeOutputException;
use chillerlan\QRCode\Output\QRGdImage as CQRGdImage;
use \chillerlan\QRCode\Output\QROutputInterface as CQROutputInterface;

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
     * @param bool $base64 Whether to return the image as a base64 encoded string (default: false).
     * 
     * @return bool|string Returns false on failure, but return a filename if filename parameter is provided, else the raw image data or the base64 version if base64 parameter is set to true
     */
    public static function createFromText(?string $data = null, ?string $logo, ?string $filename = null, int $quality = 90, int $scaling = 8, bool $base64 = false): bool|string {
        try {
            // Set the internal character encoding to UTF-8
            Utils::setMBInternalEncoding(false, "UTF-8");
            // Initialize QR code generation options
            $options = new CQROptions;
            $options->version = 7; // QR code version (determines size)
            // Choose the output interface depending on whether a logo is provided
            $options->outputInterface = self::validFile($logo) ? QrCodeLogoOutputInterface::class : CQRGdImage::class;
            $options->scale = $scaling; // Scale factor for the QR code image
            $options->outputBase64 = $base64; // Return the image as a base64 string
            $options->drawLightModules = true; // Render the light modules (background spaces)
            $options->eccLevel = CEccLevel::H; // Error correction level (H = high for better fault tolerance)
            $options->quality = $quality; // Set image quality
            $options->bgColor = array(255, 255, 255); // Set background color (white)
            $options->imageTransparent = true; // Enable transparent background
            $options->outputType = CQROutputInterface::GDIMAGE_PNG; // Output type (PNG)
            // Customize the QR code appearance
            $options->drawCircularModules = true; // Use circular modules instead of square ones
            $options->circleRadius = 0.45; // Set the radius for circular modules
            $options->keepAsSquare = array(CQRMatrix::M_FINDER, CQRMatrix::M_FINDER_DOT, CQRMatrix::M_ALIGNMENT_DARK); // Keep these parts as square
            // Add space for a logo if a valid logo file is provided
            $options->addLogoSpace = self::validFile($logo);
            $options->logoSpaceWidth = 12; // Width for the logo space
            $options->logoSpaceHeight = 12; // Height for the logo space
            // Create a new QR code instance with the configured options
            $qrcode = new CQRCode($options);
            // Generate the QR code output with or without a logo
            if (self::validFile($logo)) {
                // Use a custom output interface for the logo
                $outputInterface = new QrCodeLogoOutputInterface($options, $qrcode->getQRMatrix());
                $result = $outputInterface->dump(null, $logo); // Generate QR code with logo
            } else {
                $result = (new CQRCode($options))->render($data); // Generate standard QR code
            }
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
        // Ensure the text is not empty
        if (!empty($text)) {
            // Create a temporary file to hold the text content
            $filename = Utils::createTemporaryFilename("png");
            // Save the text content to the temporary file
            if (File::saveContentToFile($filename, $text, false, false)) {
                // Read the QR code from the file
                $data = self::readFromFile($filename);
                // If the result is a string, assign it to the result
                if (\is_string($data)) {
                    $result = $data;
                }
            }
            // Delete the temporary file after reading
            File::deleteFile($filename);
        }
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

    // PRIVATE METHODS

    private static function validFile(?string $filename): bool {
        return \is_file($filename) && \is_readable($filename);
    }
}

/**
 * @author Senestro
 */
final class QrCodeLogoOutputInterface extends CQRGdImagePNG {
    /**
     * @throws \chillerlan\QRCode\Output\CQRCodeOutputException
     */
    public function dump(string|null $file = null, string|null $logo = null): string {
        $logo ??= '';
        // Set returnResource to true to skip further processing for now
        $this->options->returnResource = true;
        // Of course, you could accept other formats too (such as resource or Imagick)
        // I'm not checking for the file type either for simplicity reasons (assuming PNG)
        if (!is_file($logo) || !is_readable($logo)) {
            throw new CQRCodeOutputException('Invalid QrCode logo');
        } else {
            // There's no need to save the result of dump() into $this->image here
            parent::dump($file);
            $im = imagecreatefrompng($logo);
            if ($im === false) {
                throw new CQRCodeOutputException('QrCode: imagecreatefrompng() error');
            } else {
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
                $imageData = $this->dumpImage();
                $this->saveToFile($imageData, $file);
                if ($this->options->outputBase64) {
                    $imageData = $this->toBase64DataURI($imageData);
                }
                return $imageData;
            }
        }
    }
}
