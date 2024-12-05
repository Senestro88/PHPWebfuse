<?php

namespace PHPWebfuse;

use PHPWebfuse\Enum\ImageAlignPosition;
use PHPWebfuse\Enum\ImageBorderType;
use PHPWebfuse\Enum\ImageFit;
use PHPWebfuse\Enum\ImageFlipDirection;
use PHPWebfuse\Enum\ImageOrientation;
use Spatie\Image\Enums\AlignPosition as SpatieAlignPosition;
use \Spatie\Image\Enums\BorderType as SpatieBorderType;
use \Spatie\Image\Enums\Fit as SpatieFit;
use \Spatie\Image\Image as SpatieImage;
use Spatie\Image\Enums\ImageDriver as SpatieImageDriver;
use Spatie\Image\Enums\Orientation as SpatieOrientation;
use Spatie\Image\Enums\FlipDirection as SpatieFlipDirection;
use Spatie\Image\Enums\Unit as SpatieUnit;


class Image extends Utils {
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
     * Resizes an image to the specified dimensions.
     *
     * This method loads an image, resizes it to the given width and height, 
     * and saves the resized image to the output path. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be resized.
     * @param int $width The desired width for the resized image.
     * @param int $height The desired height for the resized image.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully resized and saved; false otherwise.
     */
    public static function resize(string $image, int $width, int $height, $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the resized image.
            $output = self::getOutputPath($image, $replace);
            // Resize the image and save it to the output path.
            $loader->resize($width, $height)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Resizes an image while maintaining its aspect ratio to fit within the specified dimensions.
     *
     * This method uses the ImageFit option to determine how the image should be resized. 
     * The background color can be specified for any empty areas in the resized image.
     *
     * @param ImageFit $option The option specifying how the image should fit within the given dimensions.
     * @param string $image The path to the image file to be resized.
     * @param int $width The maximum width for the resized image.
     * @param int $height The maximum height for the resized image.
     * @param string $background The background color to use (default is white).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully resized and saved; false otherwise.
     */
    public static function resizeFit(ImageFit $option, string $image, int $width, int $height, string $background = 'white', $replace = false): bool {
        // Delegate the resizing logic to the setResizeFit method.
        $colorData = Color::getColor($background);
        $background = !empty($colorData) ? $colorData['hex'] : "#FFFFFF";
        return self::setResizeFit($option, $image, $width, $height, $background, $replace);
    }


    /**
     * Adjusts the brightness of an image.
     *
     * This method loads an image, applies the specified brightness level, 
     * and saves the adjusted image to the output path. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be adjusted.
     * @param int $brightness The brightness level to apply. 
     *                        Values can typically range from -100 (darken) to 100 (lighten).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully adjusted and saved; false otherwise.
     */
    public static function brightness(string $image, int $brightness, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the adjusted image.
            $output = self::getOutputPath($image, $replace);
            // Adjust the brightness of the image and save it to the output path.
            $loader->brightness($brightness)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Adjusts the contrast of an image.
     *
     * This method loads an image, applies the specified contrast level, 
     * and saves the adjusted image to the output path. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be adjusted.
     * @param int $level The contrast level to apply. 
     *                   Values can typically range from -100 (lower contrast) to 100 (higher contrast).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully adjusted and saved; false otherwise.
     */
    public static function contrast(string $image, int $level, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the adjusted image.
            $output = self::getOutputPath($image, $replace);
            // Adjust the contrast of the image and save it to the output path.
            $loader->contrast($level)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }


    /**
     * Adjusts the gamma of an image.
     *
     * This method loads an image, applies the specified gamma correction, 
     * and saves the adjusted image to the output path. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be adjusted.
     * @param float $gamma The gamma correction factor. A typical range is 0.1 to 5.0,
     *                     where values less than 1 darken the image, 
     *                     and values greater than 1 lighten it.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully adjusted and saved; false otherwise.
     */
    public static function gamma(string $image, float $gamma, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the adjusted image.
            $output = self::getOutputPath($image, $replace);
            // Apply gamma correction to the image and save it to the output path.
            $loader->gamma($gamma)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Colorizes an image by adjusting its RGB components.
     *
     * This method loads an image, applies the specified red, green, 
     * and blue color adjustments, and saves the colorized image 
     * to the output path. If the replace parameter is set to true, 
     * the original image will be replaced.
     *
     * @param string $image The path to the image file to be adjusted.
     * @param int $red The amount of red to add (0 to 255).
     * @param int $green The amount of green to add (0 to 255).
     * @param int $blue The amount of blue to add (0 to 255).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully adjusted and saved; false otherwise.
     */
    public static function color(string $image, int $red, int $green, int $blue, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the adjusted image.
            $output = self::getOutputPath($image, $replace);
            // Colorize the image with the specified RGB values and save it to the output path.
            $loader->colorize($red, $green, $blue)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Sets the background color of an image.
     *
     * This method loads an image, applies the specified background color, 
     * and saves the adjusted image to the output path. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be adjusted.
     * @param string $color The background color to apply in hex format (e.g., '#ffffff').
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully adjusted and saved; false otherwise.
     */
    public static function background(string $image, string $color, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the adjusted image.
            $output = self::getOutputPath($image, $replace);
            // Set the background color of the image and save it to the output path.
            $loader->background($color)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }


    /**
     * Adds a border to an image.
     *
     * This method applies a border to the image with the specified width and color. 
     * The border type is specified by the ImageBorderType parameter. If the replace 
     * parameter is set to true, the original image will be replaced.
     *
     * @param ImageBorderType $type The type of border to apply.
     * @param string $image The path to the image file to be adjusted.
     * @param int $width The width of the border in pixels.
     * @param string $color The color of the border (default is white).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the border was successfully applied and saved; false otherwise.
     */
    public static function border(ImageBorderType $type, string $image, int $width, string $color = 'white', $replace = false): bool {
        $colorData = Color::getColor($color);
        $color = !empty($colorData) ? $colorData['hex'] : "#FFFFFF";
        return self::setBorder($type, $image, $width, $color, $replace);
    }

    /**
     * Rotates an image based on the specified orientation.
     *
     * This method rotates an image according to the provided ImageOrientation option. 
     * If the replace parameter is set to true, the original image will be replaced.
     *
     * @param ImageOrientation $orientation The orientation to rotate the image (e.g., 90, 180 degrees).
     * @param string $image The path to the image file to be adjusted.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the rotation was successfully applied and saved; false otherwise.
     */
    public static function rotate(ImageOrientation $orientation, string $image, bool $replace = false): bool {
        // Delegate the rotation logic to the setRotation method.
        return self::setRotation($orientation, $image, $replace);
    }

    /**
     * Flips an image in the specified direction.
     *
     * This method flips an image either horizontally or vertically based on the ImageFlipDirection 
     * parameter. If the replace parameter is set to true, the original image will be replaced.
     *
     * @param ImageFlipDirection $direction The direction to flip the image (horizontal or vertical).
     * @param string $image The path to the image file to be adjusted.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was successfully flipped and saved; false otherwise.
     */
    public static function flip(ImageFlipDirection $direction, string $image, bool $replace = false): bool {
        // Delegate the flipping logic to the setFlip method.
        return self::setFlip($direction, $image, $replace);
    }


    /**
     * Applies a blur effect to an image.
     *
     * This method loads an image, applies the specified blur effect, and saves 
     * the blurred image to the output path. If the replace parameter is set to true, 
     * the original image will be replaced.
     *
     * @param string $image The path to the image file to be blurred.
     * @param int $blur The amount of blur to apply. A higher value results in more blur.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the blur effect was successfully applied and saved; false otherwise.
     */
    public static function blur(string $image, int $blur, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the blurred image.
            $output = self::getOutputPath($image, $replace);
            // Apply the blur effect and save the image to the output path.
            $loader->blur($blur)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Applies a pixelation effect to an image.
     *
     * This method loads an image, applies the specified pixelation effect, 
     * and saves the pixelated image to the output path. If the replace parameter 
     * is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be pixelated.
     * @param int $pixelate The size of the pixelation blocks to apply. A higher value increases the pixel size.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the pixelation effect was successfully applied and saved; false otherwise.
     */
    public static function pixelate(string $image, int $pixelate, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the pixelated image.
            $output = self::getOutputPath($image, $replace);
            // Apply the pixelation effect and save the image to the output path.
            $loader->pixelate($pixelate)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Converts an image to greyscale.
     *
     * This method loads an image, applies a greyscale filter, and saves the modified 
     * image to the output path. If the replace parameter is set to true, the original 
     * image will be replaced.
     *
     * @param string $image The path to the image file to be converted to greyscale.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the greyscale filter was successfully applied and saved; false otherwise.
     */
    public static function greyscale(string $image, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the greyscale image.
            $output = self::getOutputPath($image, $replace);
            // Apply the greyscale filter and save the image to the output path.
            $loader->greyscale()->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }


    /**
     * Applies a sepia tone effect to an image.
     *
     * This method loads an image, applies a sepia filter, and saves the modified 
     * image to the output path. If the replace parameter is set to true, 
     * the original image will be replaced.
     *
     * @param string $image The path to the image file to be modified.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the sepia filter was successfully applied and saved; false otherwise.
     */
    public static function sepia(string $image, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the sepia-toned image.
            $output = self::getOutputPath($image, $replace);
            // Apply the sepia filter and save the image to the output path.
            $loader->sepia()->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Sharpens an image by the specified amount.
     *
     * This method loads an image, applies a sharpening effect with the given amount, 
     * and saves the sharpened image to the output path. If the replace parameter 
     * is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be sharpened.
     * @param float $amount The amount of sharpening to apply (0 to 100).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the sharpening effect was successfully applied and saved; false otherwise.
     */
    public static function sharpen(string $image, float $amount, bool $replace = false): bool {
        // Load the image from the specified path.
        $loader = self::loadImage($image);
        // Check if the loaded image is an instance of SpatieImage.
        if ($loader instanceof SpatieImage) {
            // Get the output path for the sharpened image.
            $output = self::getOutputPath($image, $replace);
            // Apply the sharpening effect and save the image to the output path.
            $loader->sharpen($amount)->save($output);
            // Return true if the output file exists, false otherwise.
            return File::isFile($output);
        }
        // Return false if the image could not be loaded.
        return false;
    }

    /**
     * Adds a watermark to an image.
     *
     * This method applies a watermark image to the target image, positioning it 
     * according to the specified alignment and fit options. Additional parameters 
     * like padding, dimensions, and transparency (alpha) can be specified. 
     * If the replace parameter is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be watermarked.
     * @param string $watermark The path to the watermark image.
     * @param ImageAlignPosition $position The alignment position for the watermark (e.g., top-left, center).
     * @param ImageFit $option The fit option for the watermark (e.g., stretch, contain).
     * @param int $padding The padding to apply around the watermark in pixels.
     * @param int $width The width of the watermark (optional).
     * @param int $height The height of the watermark (optional).
     * @param int $alpha The transparency level of the watermark (0 to 100, where 100 is fully opaque).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the watermark was successfully applied and saved; false otherwise.
     */
    public static function watermark(string $image, string $watermark, ImageAlignPosition $position, ImageFit $option, int $padding, int $width = 0, int $height = 0, int $alpha = 100, bool $replace = false): bool {
        // Delegate the watermark application logic to the setWatermark method.
        return self::setWatermark($position, $option, $image, $watermark, $padding, $width, $height, $alpha, $replace);
    }

    /**
     * Adds text to an image.
     *
     * This method applies text to the target image at the specified position, angle, 
     * and color. The text size and width can be adjusted. If the replace parameter 
     * is set to true, the original image will be replaced.
     *
     * @param string $image The path to the image file to be modified.
     * @param string $text The text to be added to the image.
     * @param string $size The font size of the text.
     * @param string $color The color of the text (default is white).
     * @param int $x The X-coordinate of the text position (default is 0).
     * @param int $y The Y-coordinate of the text position (default is 0).
     * @param int $angle The angle of the text in degrees (default is 0).
     * @param int $width The maximum width for the text box (default is 0, meaning no limit).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the text was successfully applied and saved; false otherwise.
     */
    public static function addText(string $image, string $text, string $size, string $color = "white", int $x = 0, int $y = 0, int $angle = 0, int $width = 0, bool $replace = false): bool {
        $colorData = Color::getColor($color);
        $color = !empty($colorData) ? $colorData['hex'] : "#FFFFFF";
        return self::setText($image, $text, $size, $color, $x, $y, $angle, $width, $replace);
    }

    /**
     * Applies a binder effect to an image by adding a frame, shadow, and spacing.
     * The method returns the path of the new image or false if it fails.
     *
     * @param string $image          Path to the original image file.
     * @param int    $spacing        Spacing for the binder effect.
     * @param int    $frameWidth     Width of the frame (default is 10).
     * @param string $frameColor     Color of the frame (default is "white").
     * @param string $backgroundColor Background color of the frame (default is "white").
     * @param bool   $replace        Whether to replace the original image with the modified one (default is false).
     * 
     * @return bool|string           The path of the new image on success, or false on failure.
     */
    public static function binder(string $image, int $spacing, int $frameWidth = 10, string $frameColor = "white", string $backgroundColor = "columbiablue", bool $replace = false): bool|string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Initialize the thumbnail object
            $thumb = self::getThumb();
            // Set frame width
            $thumb->Framewidth = $frameWidth;
            // Set frame color (default to white if the color name is not found)
            $frameColorData = Color::getColor($frameColor);
            $thumb->Framecolor = !empty($frameColorData) ? $frameColorData['hex'] : "#FFFFFF"; // Default frame color is white
            // Set background color (default to light blue if the color name is not found)
            $backgroundColorData = Color::getColor($backgroundColor);
            $thumb->Backgroundcolor =  !empty($backgroundColorData) ? $backgroundColorData['hex'] : "#D0DEEE"; // Default background color
            // Enable shadow effect
            $thumb->Shadow = true;
            // Enable binder effect
            $thumb->Binder = true;
            // Set binder spacing
            $thumb->Binderspacing = $spacing;
            // Get the output path for the modified image (whether to replace the original or not)
            $location = self::getEThumbOutputPath($thumb, $image, $replace);
            // Create the thumbnail with the applied effects
            $thumb->Createthumb($image, "file");
            // Return the location of the new image if successful, or false otherwise
            return File::isFile($location) ? $location : false;
        }
        // Return false if the file doesn't exist
        return false;
    }

    /**
     * Retrieve the list of supported color names.
     *
     * @return array An array of color names in lowercase.
     */
    public static function getSupportColorNames(): array {
        // Use array_change_key_case to normalize the color keys to lowercase
        // and then return only the keys (color names) as an array.
        return \array_keys(\array_change_key_case(Color::colors(), \CASE_LOWER));
    }


    //  PRIVATE METHODS

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

    /**
     * Retrieves an instance of the EThumbnail class.
     *
     * This method ensures the EThumbnail class is loaded before creating
     * and returning a new instance. If the class is not already declared,
     * it dynamically loads the required plugin using the `Utils::loadPlugin` method.
     *
     * @return \EThumbnail A new instance of the EThumbnail class.
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
     * @param \EThumbnail &$thumb A reference to the EThumbnail object.
     * @param string $image The original image path.
     * @param bool $replace Whether to replace the original file.
     * @return string The full path of the output file.
     */
    private static function getEThumbOutputPath(\EThumbnail &$thumb, string $image, bool $replace = false): string {
        // Check if the file exists
        if (File::isFile($image)) {
            // Resolve the real path of the image
            $image = realpath($image);
            $basename = basename($image);
            $dirname = Path::arrange_dir_separators(dirname($image), true);
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

    /**
     * Loads an image using the GD driver.
     *
     * This method attempts to load the specified image file using the GD image driver.
     * If the image cannot be loaded, the method returns false.
     *
     * @param string $image The path to the image file to be loaded.
     * @return SpatieImage|false The loaded SpatieImage instance, or false if the image could not be loaded.
     */
    private static function loadImage(string $image) {
        $loader = false;
        try {
            // Use the GD image driver and load the image file.
            $loader = SpatieImage::useImageDriver(SpatieImageDriver::Gd);
            $loader->loadFile($image);
        } catch (\Throwable $e) {
            // Catch any errors during the loading process and return false.
            self::setLastThrowable($e);
        }
        return $loader;
    }

    /**
     * Generates the output path for the modified image.
     *
     * This method determines the output path for the image, replacing the original if
     * specified, or appending a timestamp to the filename if not.
     *
     * @param string $image The original image file path.
     * @param bool $replace Indicates whether to replace the original image.
     * @return string The output file path for the modified image.
     */
    private static function getOutputPath(string $image, bool $replace = false): string {
        if (File::isFile($image)) {
            // Get the absolute path, filename, and directory of the image.
            $image = realpath($image);
            $basename = basename($image);
            $dirname = Path::arrange_dir_separators(dirname($image), true);
            // Determine the new file name based on whether replacement is enabled.
            $name = $replace ? $basename : File::removeExtension($basename) . "-" . time() . "." . File::getExtension($image);
            // Return the complete path with the new file name.
            return $dirname . $name;
        }
        // Return the original image path if the file is not valid.
        return $image;
    }

    /**
     * Resizes and fits the image to the specified dimensions.
     *
     * This method resizes the image using the provided fit option, width, and height,
     * and saves the result to the output path.
     *
     * @param ImageFit $fit The resizing fit option (e.g., crop, contain).
     * @param string $image The image file path to resize.
     * @param int $width The target width of the resized image.
     * @param int $height The target height of the resized image.
     * @param string $background The background color for resizing.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the image was resized and saved successfully; false otherwise.
     */
    private static function setResizeFit(ImageFit $fit, string $image, int $width, int $height, string $background = '#ffffff', $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage) {
            // Get the output path for the resized image.
            $output = self::getOutputPath($image, $replace);
            // Apply the resizing fit and save the image.
            $loader->fit($fit->value(), $width, $height, $background)->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
    }

    /**
     * Adds a border to the image with the specified width and color.
     *
     * This method applies a border to the image using the provided border type, width,
     * and color, and saves the result to the output path.
     *
     * @param ImageBorderType $type The type of border to apply (e.g., solid, dashed).
     * @param string $image The image file path to modify.
     * @param int $width The width of the border in pixels.
     * @param string $color The color of the border (default is white).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the border was applied and saved successfully; false otherwise.
     */
    private static function setBorder(ImageBorderType $type, string $image, int $width, string $color = '#ffffff', $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage) {
            // Get the output path for the image with a border.
            $output = self::getOutputPath($image, $replace);
            // Apply the border and save the image.
            $loader->border($width, $type->value(), $color)->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
    }

    /**
     * Rotates the image based on the provided orientation.
     *
     * This method rotates the image according to the specified orientation and saves
     * the result to the output path.
     *
     * @param ImageOrientation $orientation The orientation for rotating the image (e.g., left, right).
     * @param string $image The image file path to rotate.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the rotation was applied and saved successfully; false otherwise.
     */
    private static function setRotation(ImageOrientation $orientation, string $image, bool $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage) {
            // Get the output path for the rotated image.
            $output = self::getOutputPath($image, $replace);
            // Apply the rotation and save the image.
            $loader->orientation($orientation->value())->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
    }

    /**
     * Flips the image horizontally or vertically.
     *
     * This method flips the image based on the provided flip direction (horizontal or
     * vertical) and saves the result to the output path.
     *
     * @param ImageFlipDirection $direction The direction for flipping the image (horizontal or vertical).
     * @param string $image The image file path to flip.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the flip was applied and saved successfully; false otherwise.
     */
    private static function setFlip(ImageFlipDirection $direction, string $image, bool $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage) {
            // Get the output path for the flipped image.
            $output = self::getOutputPath($image, $replace);
            // Apply the flip and save the image.
            $loader->flip($direction->value())->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
    }

    /**
     * Adds a watermark to the image.
     *
     * This method applies a watermark image to the target image, using the provided
     * position, fit option, padding, and optional dimensions and transparency.
     *
     * @param ImageAlignPosition $position The alignment position for the watermark.
     * @param ImageFit $option The fit option for the watermark.
     * @param string $image The image file path to modify.
     * @param string $watermark The watermark image file path.
     * @param int $padding The padding around the watermark in pixels.
     * @param int $width The width of the watermark (optional).
     * @param int $height The height of the watermark (optional).
     * @param int $alpha The transparency level of the watermark (0 to 100).
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the watermark was applied and saved successfully; false otherwise.
     */
    private static function setWatermark(ImageAlignPosition $position, ImageFit $option, string $image, string $watermark, int $padding, int $width = 0, int $height = 0, int $alpha = 100, bool $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage && File::isFile($watermark)) {
            // Get the output path for the watermarked image.
            $output = self::getOutputPath($image, $replace);
            // Apply the watermark and save the image.
            $loader->watermark($watermark, $position->value(), $padding, $padding, SpatieUnit::Pixel, $width, SpatieUnit::Pixel, $height, SpatieUnit::Pixel, $option->value(), $alpha);
            $loader->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
    }

    /**
     * Adds text to an image.
     *
     * This method applies text to the target image at the specified position, angle,
     * and color. The text size and width can be adjusted, and the resulting image is
     * saved to the output path.
     *
     * @param string $image The image file path to modify.
     * @param string $text The text to be added to the image.
     * @param string $size The font size of the text.
     * @param string $color The color of the text in hex format.
     * @param int $x The X-coordinate of the text position.
     * @param int $y The Y-coordinate of the text position.
     * @param int $angle The angle of the text in degrees.
     * @param int $width The maximum width for the text box.
     * @param bool $replace Indicates whether to replace the original image.
     * @return bool Returns true if the text was applied and saved successfully; false otherwise.
     */
    private static function setText(string $image, string $text, string $size, string $color = "#FFFFFF", int $x = 0, int $y = 0, int $angle = 0, int $width = 0, bool $replace = false): bool {
        $loader = self::loadImage($image);
        if ($loader instanceof SpatieImage && !empty($text)) {
            // Get the output path for the image with text.
            $output = self::getOutputPath($image, $replace);
            // Define the font path (example: bookman.ttf from the specified directory).
            $font = PHPWEBFUSE['DIRECTORIES']['FONTS'] . "bookman.ttf";
            // Apply the text and save the image.
            $loader->text($text, $size, $color, $x, $y, $angle, $font, $width)->save($output);
            // Return true if the output file exists.
            return File::isFile($output);
        }
        return false;
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
