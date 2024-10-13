<?php

namespace PHPWebfuse;

use \Spatie\Image\Image as SpatieImage;
use \Spatie\Image\Drivers\ImageDriver as SpatieImageDriver;


/**
 * Class Image
 * This class provides image manipulation methods.
 * It relies on external thumbnail libraries and utility functions to manage images.
 * 
 * @package PHPWebfuse
 * @author Senestro
 */
class Image {
    // PRIVATE VARIABLE

    // PUBLIC VARIABLES

    // PUBLIC METHODS

    /**
     * Prevent the constructor from being initialized
     */
    private function __construct() {
    }

    /**
     * Rotate an image by a specific number of degrees and optionally resize it.
     * 
     * @param string $image       The path to the image file.
     * @param int    $degrees     The number of degrees to rotate the image.
     * @param int    $size        The size of the thumbnail after rotation.
     * @param bool   $usePixels   Whether the size is in pixels (true) or percentage (false).
     * @param bool   $replace     If true, replace the original file. Otherwise, create a new file.
     * @return bool|string        Returns the path of the rotated image if successful, or false on failure.
     */
    public static function rotate(string $image, int $degrees, int $size, bool $usePixels = false, bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Set the thumbnail properties
            $thumb->Thumbsize = $size;
            $thumb->Rotate  = $degrees;
            // Whether to use percentage or pixel-based resizing
            $thumb->Percentage = !$usePixels;
            $thumb->Backgroundcolor = "#ffffff00"; // Transparent background
            // Set the output path for the rotated image
            $location = self::setOutputPath($thumb, $image, $replace);
            // Create the rotated thumbnail image
            $thumb->Createthumb($image, "file");
            // Return the location of the new image or false if failed
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }

    /**
     * Apply a greyscale filter to an image with optional noise and sepia effects.
     * 
     * @param string $image    The path to the image file.
     * @param int    $noise    The level of noise to add to the image (0 for none).
     * @param int    $sephia   The level of sepia effect to apply (0 for none).
     * @param bool   $replace  If true, replace the original file. Otherwise, create a new file.
     * @return bool|string     Returns the path of the processed image if successful, or false on failure.
     */
    public static function greyscale(string $image, int $noise = 0, int $sephia = 0, bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Apply greyscale with noise and sepia filters
            $thumb->Ageimage = array(1, $noise, $sephia);
            // Set the output path for the modified image
            $location = self::setOutputPath($thumb, $image, $replace);
            // Create the modified thumbnail image
            $thumb->Createthumb($image, "file");
            // Return the location of the new image or false if failed
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }

    /**
     * Adjust the brightness of an image.
     * 
     * @param string $image      The path to the image file.
     * @param int    $brightness The brightness level to set (-100 to 100).
     * @param bool   $replace    If true, replace the original file. Otherwise, create a new file.
     * @return bool|string       Returns the path of the modified image if successful, or false on failure.
     */
    public static function brightness(string $image, int $brightness = 0, bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Set the brightness level (1: adjust brightness, $brightness: level of brightness)
            $thumb->Brightness = array(1, $brightness);
            // Set the output path for the modified image
            $location = self::setOutputPath($thumb, $image, $replace);
            // Create the modified thumbnail image
            $thumb->Createthumb($image, "file");
            // Return the location of the new image or false if failed
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }

    /**
     * Apply a blur effect to an image.
     * 
     * @param string $image   The path to the image file.
     * @param bool   $replace If true, replace the original file. Otherwise, create a new file.
     * @return bool|string    Returns the path of the blurred image if successful, or false on failure.
     */
    public static function blur(string $image, bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Set the blur effect
            $thumb->Blur = true;
            // Set the output path for the blurred image
            $location = self::setOutputPath($thumb, $image, $replace);
            // Create the blurred thumbnail image
            $thumb->Createthumb($image, "file");
            // Return the location of the new image or false if failed
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }

    /**
     * Add a binder effect (frame, shadow, etc.) to an image.
     * 
     * @param string $image   The path to the image file.
     * @param int    $spacing The spacing of the binder effect.
     * @param bool   $replace If true, replace the original file. Otherwise, create a new file.
     * @return bool|string    Returns the path of the modified image if successful, or false on failure.
     */
    public static function binder(string $image, int $spacing, bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Set binder properties
            $thumb->Framewidth = 10; // Set frame width
            $thumb->Framecolor = '#FFFFFF'; // Set frame color
            $thumb->Backgroundcolor = '#D0DEEE'; // Set background color
            $thumb->Shadow = true; // Enable shadow effect
            $thumb->Binder = true; // Enable binder effect
            $thumb->Binderspacing = $spacing; // Set binder spacing
            // Set the output path for the modified image
            $location = self::setOutputPath($thumb, $image, $replace);
            // Create the modified thumbnail image
            $thumb->Createthumb($image, "file");
            // Return the location of the new image or false if failed
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }


    // PRIVATE METHODS

    /**
     * Retrieve the EThumbnail object used to handle thumbnail creation.
     * This ensures the EThumbnail class is loaded before it is used.
     * 
     * @return \EThumbnail Returns an instance of the EThumbnail class.
     */
    private static function getThumb(): \EThumbnail {
        // Check if the EThumbnail class is already loaded
        if (!\in_array("\EThumbnail", \get_declared_classes())) {
            // Load the EThumbnail plugin if it's not already declared
            Utils::loadPlugin("EThumbnail");
        }
        // Return a new instance of EThumbnail
        return new \EThumbnail;
    }

    /**
     * Set the output path for the processed image file.
     * This function determines whether to replace the original file or create a new one with a unique name.
     * 
     * @param \EThumbnail &$thumb   A reference to the EThumbnail object.
     * @param string $image         The original image path.
     * @param bool $replace         Whether to replace the original file.
     * @return string               The full path of the output file.
     */
    private static function setOutputPath(\EThumbnail &$thumb, string $image, bool $replace = false): string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Resolve the real path of the image
            $image = realpath($image);
            $basename = basename($image);
            $dirname = Path::arrange_dir_separators_v2(dirname($image), true);
            // Generate a new filename if not replacing the original
            $name = $replace ? $basename : File::removeExtension($basename) . "-" . time() . "." . File::getExtension($image);
            // Set the output path for the thumbnail
            $thumb->Thumblocation = $dirname;
            $thumb->Thumbfilename = $name;
            // Return the full path of the output file
            return $dirname . $name;
        }
        // Return the original image path if the file doesn't exist
        return $image;
    }
}
