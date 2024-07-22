<?php
namespace PHPWebFuse;
/**
 *
 */
class Captcha extends \PHPWebFuse\Methods {
	// PRIVATE CONSTANTS

	/**
	 * Supported options
	 *
	 * @var const
	 */
	private const options = array(
		'bgColor' => "#fff",
		'textColor' => "#303832",
		'signColor' => "#4278F5",
		'lineColor' => "#47524a",
		'noiseColor' => "#47524a",
		'fontRatio' => 0.4,
		'textLength' => 6,
		'width' => 200,
		'height' => 80,
		'transparentPercentage' => 20,
		'numLines' => 10,
		'noiseLevel' => 4,
		'expires' => 900, // In seconds
		'randomBackground' => true,
		'randomSpaces' => true,
		'textAngles' => true,
		'randomBaseline' => true,
		'signature' => "",
	);

	/**
	 * Available formats for creating the captcha image
	 *
	 * @var const
	 */
	private const formats = array("png", "jpg", "jpeg", "gif");
	private const charset = 'abcdefghijkmnopqrstuvwxzyABCDEFGHJKLMNPQRSTUVWXZY0123456789';

	// PUBLIC METHODS

	public function __construct() {}

	/**
	 * Create a base64 image
	 *
	 * @param araay $options
	 * @param string $namespace
	 * @param string $format - Defaults to png , 
	 * @return string
	 */
	public function createBase64Image(array $options = array(), string $namespace = "default", string $format = "png"): string {
		$data = $this->createImageData($options, $namespace, $format);
		return isset($data['image']) ? $this->createBase64FromImage($data['image'], $data['directories'], $data['format']) : "";
	}

	/**
	 * Output image to browser
	 *
	 * @param araay $options
	 * @param string $namespace
	 * @param string $format - Defaults to png , 
	 * @return void
	 */
	public function createOuputImage(array $options = array(), string $namespace = "default", string $format = "png"): void {
		$data = $this->createImageData($options, $namespace, $format);
		if (isset($data['image'])) {$this->outputToBrowserFromImage($data['image'], $data['format']);}
	}

	/**
	 * Validate the captcha
	 *
	 * @param string $value
	 * @param string $namespace
	 * @param bool $caseInsensitive - Defaults to true , 
	 * @return bool
	 */
	public function validate(string $value, string $namespace = "default", bool $caseInsensitive = true): bool {
		return $this->validateValue($value, $namespace, $caseInsensitive);
	}

	// PRIAVTE METHODS
	
	private function createImageData(array $options = array(), string $namespace = "default", string $format = "png"): array {
		$result = array();
		$this->setInternalEncoding();
		$directories = $this->getDirectories();
		$options = $this->FilterOptions($options);
		$image = $this->createImage($options);
		if (parent::isNotFalse($image)) {
			$colors = $this->colorsAllocationForImage($image, $options);
			$this->setBackground($image, $options, $colors, $directories);
			$generatedText = $this->generateTextFile($options, $directories, $namespace);
			$this->noiseDrawOnImage($image, $options, $colors);
			$this->linesDrawOnImage($image, $options, $colors);
			$this->signatureDrawOnImage($image, $options, $colors, $directories);
			$this->captchaTextDrawOnImage($image, $options, $colors, $directories, $generatedText);
			$format = parent::inArray(strtolower($format), self::formats) ? $format : "png";
			$result['image'] = $image;
			$result['directories'] = $directories;
			$result['options'] = $options;
			$result['format'] = $format;
		}
		return $result;
	}

	private function setInternalEncoding(): void {
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding('UTF-8');
		}
	}

	private function getDirectories(): array {
		$captchaDirname = parent::INSERT_DIR_SEPARATOR(parent::ARRANGE_DIR_SEPARATOR(PHPWebFuse['directories']['data'] . DIRECTORY_SEPARATOR . "captcha"));
		parent::makeDir($captchaDirname);
		$directories = array("backgrounds" => $captchaDirname . "backgrounds" . DIRECTORY_SEPARATOR, "fonts" => $captchaDirname . "fonts" . DIRECTORY_SEPARATOR, "namespaces" => $captchaDirname . "namespaces" . DIRECTORY_SEPARATOR, "temp" => $captchaDirname . "temp" . DIRECTORY_SEPARATOR);
		foreach ($directories as $dirname) {parent::makeDir($dirname);}
		return $directories;
	}

	private function FilterOptions(array $options = array()): array {
		$filtered = self::options;
		foreach ($options as $key => $value) {
			if (isset(self::options[$key])) {
				if ($key == "textLength") {
					$filtered[$key] = $value < 4 OR $value > 8 ? 6 : $value;
				} else if ($key == "transparentPercentage") {
					$filtered[$key] = $value < 1 OR $value > 100 ? 20 : $value;
				} else if ($key == "noiseLevel") {
					$filtered[$key] = $value < 1 ? 6 : $value;
				} else { $filtered[$key] = $value;}
			}
		}
		return $filtered;
	}

	private function createImage(array $options = array()): \GdImage {
		$image = function_exists('imagecreatetruecolor') ? imagecreatetruecolor($options['width'], $options['height']) : imagecreate($options['width'], $options['height']);
		if (function_exists('imageantialias')) {imageantialias($image, true);}
		return $image;
	}

	private function colorsAllocationForImage(\GdImage $image, array $options = array()): array {
		$colors = array();
		if (parent::isNotFalse($image)) {
			$alpha = intval($options['transparentPercentage'] / 100 * 127);
			$bg = $this->hex2rgb($options['bgColor']);
			$text = $this->hex2rgb($options['textColor']);
			$sign = $this->hex2rgb($options['signColor']);
			$line = $this->hex2rgb($options['lineColor']);
			$noise = $this->hex2rgb($options['noiseColor']);
			$colors["background"] = imagecolorallocate($image, $bg['r'], $bg['g'], $bg['b']);
			$colors["text"] = imagecolorallocatealpha($image, $text['r'], $text['g'], $text['b'], $alpha);
			$colors["signature"] = imagecolorallocatealpha($image, $sign['r'], $sign['g'], $sign['b'], $alpha);
			$colors["lines"] = imagecolorallocatealpha($image, $line['r'], $line['g'], $line['b'], $alpha);
			$colors["noise"] = imagecolorallocatealpha($image, $noise['r'], $noise['g'], $noise['b'], $alpha);
		}
		return $colors;
	}

	private function setBackground(\GdImage $image, array $options = array(), array $colors = array(), array $directories = array()): void {
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($colors) && parent::isNotEmptyArray($directories)) {
			$backgroundImage = null;
			imagefilledrectangle($image, 0, 0, $options['width'], $options['height'], $colors['background']);
			if (parent::isTrue($options['randomBackground']) && parent::isDir($directories['backgrounds']) && parent::isReadable($directories['backgrounds'])) {
				$background = $this->getBackground($directories['backgrounds']);
				if (parent::isNonNull($background)) {$backgroundImage = $background;}
			}
			if (parent::isNonNull($backgroundImage)) {
				$backgroundSize = @getimagesize($backgroundImage);
				if (parent::isArray($backgroundSize)) {
					if (isset($backgroundSize[2])) {
						if ($backgroundSize[2] == 1) {
							$img = @imagecreatefromgif($backgroundImage);
						} else if ($backgroundSize[2] == 2) {
							$img = @imagecreatefromjpeg($backgroundImage);
						} else if ($backgroundSize[2] == 3) {$img = @imagecreatefrompng($backgroundImage);}
					}
					if (isset($img) && parent::isNotFalse($img)) {
						imagecopyresized($image, $img, 0, 0, 0, 0, $options['width'], $options['height'], imagesx($img), imagesy($img));
					}
				}
			}
		}
	}

	private function getBackground(string $dirname): ?string {
		if (parent::isDir($dirname) && parent::isReadable($dirname)) {
			$images = array();
			$extensions = array("jpg", "gif", "png", "jpeg");
			$scandir = parent::scanDir($dirname);
			foreach ($scandir as $filename) {
				if (parent::isFile($filename)) {
					$extension = parent::getExtension($filename);
					if (parent::inArray(strtolower($extension), $extensions)) {$images[] = $filename;}
				}
			}
			if (parent::isNotEmptyArray($images)) {return $images[mt_rand(0, count($images) - 1)];}
		}
		return null;
	}

	private function generateTextFile(array $options = array(), array $directories = array(), string $namespace = "default"): string {
		$code = "";
		if (parent::isNotEmptyArray($directories)) {
			for ($i = 0; $i < $options['textLength']; $i++) {$code .= substr(self::charset, rand(0, strlen(self::charset) - 1), 1);}
			$data = array('expires' => time() + (int) $options['expires'], 'code' => $code);
			$namespaceFilename = $directories['namespaces'] . $namespace . '.json';
			parent::saveContentToFile($namespaceFilename, parent::arrayToJson($data));
		}
		return $code;
	}

	private function noiseDrawOnImage(\GdImage $image, array $options = array(), array $colors = array()): void {
		// Check if the image is valid, colors array is not empty, and noise level is set and greater than 0
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($colors) && isset($options['noiseLevel']) && $options['noiseLevel'] > 0) {
			// Limit noise level to a maximum of 10 and adjust by logarithm base 2 of e
			$noiseLevel = min($options['noiseLevel'], 10) * M_LOG2E;
			// Extract width and height from options
			$width = $options['width'];
			$height = $options['height'];
			// Loop through the image in steps of 10 pixels
			for ($x = 1; $x < $width; $x += 10) {
				for ($y = 1; $y < $height; $y += 10) {
					// Generate noise within the current 10x10 grid
					for ($i = 0; $i < $noiseLevel; ++$i) {
						// Randomly generate x and y coordinates within the grid
						$x1 = round(mt_rand($x, $x + 10));
						$y1 = round(mt_rand($y, $y + 10));
						// Randomly generate size of the noise spot
						$size = mt_rand(1, 3);
						// Ensure the noise spot does not cover the top-left corner (0, 0)
						if ($x1 - $size <= 0 && $y1 - $size <= 0) {continue;}
						// Draw a filled arc at the generated coordinates
						imagefilledarc($image, $x1, $y1, $size, $size, 0, mt_rand(180, 360), $colors['noise'], IMG_ARC_PIE);
					}
				}
			}
		}
	}

	private function linesDrawOnImage(\GdImage $image, array $options = array(), array $colors = array()): void {
		// Check if the image is valid, colors array is not empty, and numLines is set and greater than 0
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($colors) && isset($options['numLines']) && $options['numLines'] > 0) {
			// Extract width, height, and number of lines from options
			$width = $options['width'];
			$height = $options['height'];
			$numLines = $options['numLines'];
			// Loop to draw the specified number of lines
			for ($line = 0; $line < $numLines; ++$line) {
				// Calculate x-coordinate of the line start point
				$x = ($width * (1 + $line)) / ($numLines + 1);
				$x += ((0.5 - $this->rand()) * ($width / $numLines));
				$x = round($x, 2);
				// Randomly generate y-coordinate within 10% to 90% of height
				$y = mt_rand(($height * 0.1), ($height * 0.9));
				// Randomly generate the angle of the line in radians
				$theta = round((($this->rand() - 0.5) * M_PI) * 0.33, 2);
				// Randomly generate the length of the line
				$len = mt_rand(($width * 0.4), ($width * 0.7));
				// Randomly decide the line width
				$lwid = !mt_rand(0, 2);
				// Calculate the frequency of the wave
				$k = round(($this->rand() * 0.6) + 0.2, 2);
				$k = round(($k * $k) * 0.5, 2);
				// Randomly generate the phase shift
				$phi = round($this->rand() * 6.28, 2);
				// Set the step size for drawing
				$step = 0.5;
				// Calculate the change in x and y per step
				$dx = round($step * cos($theta), 2);
				$dy = round($step * sin($theta), 2);
				// Calculate the number of steps
				$n = ($len / $step);
				// Calculate the amplitude of the wave
				$amp = round((1.5 * $this->rand()) / ($k + 5.0 / $len), 2);
				// Calculate the starting point of the line
				$x0 = round($x - 0.5 * $len * cos($theta), 2);
				$y0 = round($y - 0.5 * $len * sin($theta), 2);
				// Calculate the offset for the line width
				$ldx = round(-$dy * $lwid);
				$ldy = round($dx * $lwid);
				// Loop to draw the line step by step
				for ($i = 0; $i < $n; ++$i) {
					$x = round($x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi), 2);
					$y = round($y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi), 2);
					imagefilledrectangle($image, $x, $y, $x + $lwid, $y + $lwid, $colors['lines']);
				}
			}
		}
	}

	private function signatureDrawOnImage(\GdImage $image, array $options = array(), array $colors = array(), array $directories = array()): void {
		// Check if the image is valid, colors array is not empty, and directories array is not empty
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($colors) && parent::isNotEmptyArray($directories)) {
			// Define the path to the font file
			$font = $directories['fonts'] . "signature.ttf";
			// Check if the font file exists and is readable
			if (parent::isFile($font) && parent::isReadable($font)) {
				// Get bounding box details for the signature text
				$bboxDetails = $this->bboxDetails(15, 0, $font, $options['signature']);
				// Check if width is available in bounding box details
				if (isset($bboxDetails['width'])) {
					// Calculate x and y coordinates to position the signature at the bottom-right corner
					$x = (($options['width'] - $bboxDetails['width']) - 5);
					$y = ($options['height'] - 5);
					// Draw the signature text on the image
					imagettftext($image, 15, 0, $x, $y, $colors['signature'], $font, $options['signature']);
				}
			}
		}
	}

	private function captchaTextDrawOnImage(\GdImage $image, array $options = array(), array $colors = array(), array $directories = array(), string $generatedText = ""): void {
		// Check if the image is valid, colors array is not empty, directories array is not empty, and generated text is not empty
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($colors) && parent::isNotEmptyArray($directories) && parent::isNotEmptyString($generatedText)) {
			// Define the path to the font file
			$font = $directories['fonts'] . "captcha.ttf";
			// Set the font ratio, defaulting to 0.4 if not provided or out of bounds
			$ratio = isset($options['fontRatio']) ? $options['fontRatio'] : 0.4;
			if ((float) $ratio < 0.1 || (float) $ratio >= 1) {$ratio = 0.4;}
			// Check if the font file exists and is readable
			if (parent::isFile($font) && parent::isReadable($font)) {
				// Extract height and width from options
				$height = $options['height'];
				$width = $options['width'];
				$fontSize = $height * $ratio;
				$scale = 1;
				// Add random spaces to the generated text if the option is enabled
				if (parent::isTrue($options['randomSpaces']) && parent::isFalse($this->strpos($generatedText, ' '))) {
					if (mt_rand(1, 100) % 5 > 0) {
						$index = mt_rand(1, strlen($generatedText) - 1);
						$spaces = mt_rand(1, 3);
						$generatedText = $this->substr($generatedText, 0, $index) . str_repeat(' ', $spaces) . $this->substr($generatedText, $index);
					}
				}
				// Initialize arrays for fonts, angles, distances, and dimensions of characters
				$fonts = [];
				$angles = [];
				$distance = [];
				$dims = [];
				$txtWid = 0;
				// Set initial and final angles for the text
				$angle0 = mt_rand(10, 20);
				$angleN = round(mt_rand(-20, 10));
				// Adjust angles if the option is enabled
				if (parent::isNotFalse($angle0) && parent::isNotFalse($angleN)) {
					if (parent::isFalse($options['textAngles'])) {$angle0 = $angleN = $step = 0;}
					if (mt_rand(0, 99) % 2 == 0) {$angle0 = -$angle0;}
					if (mt_rand(0, 99) % 2 == 1) {$angleN = -$angleN;}
					// Calculate the step size for angle change
					$step = (abs($angle0 - $angleN) / ($this->strlen($generatedText) - 1));
					$step = ($angle0 > $angleN) ? -$step : $step;
					$angle = $angle0;
					// Loop through each character in the generated text
					for ($index = 0; $index < $this->strlen($generatedText); ++$index) {
						$fonts[] = $font;
						$angles[] = $angle;
						$dist = (round(mt_rand(-2, 0)) * $scale);
						$distance[] = $dist;
						$char = $this->substr($generatedText, $index, 1);
						$dim = $this->characterDimensions($char, $fontSize, $angle, $font);
						$dim[0] += $dist;
						$txtWid += $dim[0];
						$dims[] = $dim;
						$angle += $step;
						// Ensure angle stays within bounds
						if ($angle > 20) {
							$angle = 20;
							$step = (-1 * $step);
						} elseif ($angle < -20) {
							$angle = -20;
							$step = (-1 * $step);
						}
					}
					// Function to calculate the y-position for each character
					$nextYPos = function ($y, $i, $step) use ($height, $scale, $dims) {
						static $dir = 1;
						if ($y + $step + $dims[$i][2] + (10 * $scale) > $height) {
							$dir = 0;
						} elseif ($y - $step - $dims[$i][2] < $dims[$i][1] + $dims[$i][2] + (5 * $scale)) {$dir = 1;}
						if ($dir) {$y += $step;} else { $y -= $step;}
						return $y;
					};
					// Calculate the initial x-position for the text
					$cx = floor($width / 2 - ($txtWid / 2));
					$x = mt_rand(5 * $scale, max($cx * 2 - (5 * 1), 5 * $scale));
					// Calculate the initial y-position for the text
					if (parent::isTrue($options['randomBaseline'])) {
						$y = mt_rand($dims[0][1], $height - 10);
					} else { $y = ($height / 2 + $dims[0][1] / 2 - $dims[0][2]);}
					$randScale = ($scale * mt_rand(5, 10));
					// Loop through each character in the generated text to draw it on the image
					for ($i = 0; $i < $this->strlen($generatedText); ++$i) {
						$font = $fonts[$i];
						$char = $this->substr($generatedText, $i, 1);
						$angle = $angles[$i];
						$dim = $dims[$i];
						// Adjust y-position for each character if the option is enabled
						if (parent::isTrue($options['randomBaseline'])) {$y = $nextYPos($y, $i, $randScale);}
						// Draw the character on the image
						imagettftext($image, $fontSize, $angle, (int) $x, (int) $y, $colors['text'], $font, $char);
						// Adjust x-position for the next character
						if ($i == ' ') {
							$x += $dim[0];
						} else { $x += ($dim[0] + $distance[$i]);}
					}
				}
			} else {
				// Display an error message if the font file cannot be loaded
				imagestring($image, 4, 10, ($options['height'] / 2) - 5, 'Failed to load Font File', $colors['text']);
			}
		}
	}

	private function createBase64FromImage(\GdImage $image, array $directories = array(), string $format = "png") {
		$base64Image = "";
		if (parent::isNotFalse($image) && parent::isNotEmptyArray($directories)) {
			$format = parent::inArray(strtolower($format), self::formats) ? $format : "png";
			$filename = $directories['temp'] . "" . md5(time()) . "." . $format;
			if ($format == "jpg" OR $format == "jpeg") {
				imagejpeg($image, $filename, 100);
			} elseif ($format == "gif") {
				imagegif($image, $filename);
			} else {imagepng($image, $filename, 9);}
			imagedestroy($image);
			clearstatcache(false, $filename);
			$base64Image = parent::convertImageToBase64($filename);
			clearstatcache(false, $filename);
			parent::deleteFile($filename);
		}
		return $base64Image;
	}

	private function outputToBrowserFromImage(\GdImage $image, string $format = "png") {
		if (parent::isNotFalse($image) && !headers_sent()) {
			$format = parent::inArray(strtolower($format), self::formats) ? $format : "png";
			$contentType = ($format === "png" ? 'image/png' : ($format === "jpg" ? 'image/jpeg' : 'image/gif'));
			header("Expires: Mon, 7 Apr 1997 01:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Content-Type: " . $contentType);
			header("Pragma: no-cache");
			if ($format == "jpg" OR $format == "jpeg") {
				imagejpeg($image, null, 100);
			} elseif ($format == "gif") {
				imagegif($image, null);
			} else {imagepng($image, null, 9);}
			imagedestroy($image);
		}
	}

	private function getTextData(string $namespaceFilename): array {
		$data = array();
		if (parent::isFile($namespaceFilename) && parent::isReadable($namespaceFilename)) {
			$json = parent::getFileContent($namespaceFilename);
			$decoded = parent::jsonToArray($json);
			if (parent::isArray($decoded) && isset($decoded['expires']) && isset($decoded['code'])) {
				$data['expires'] = $decoded['expires'];
				$data['code'] = $decoded['code'];
			}
		}
		return $data;
	}

	private function validateValue(string $value, string $namespace = "default", bool $caseInsensitive = true): bool {
		$directories = $this->getDirectories();
		$namespaceFilename = $directories['namespaces'] . $namespace . '.json';
		$data = $this->getTextData($namespaceFilename);
		if (parent::isNotEmptyArray($data)) {
			$code = $data['code'] ?? "";
			$expires = $data['expires'] ?? 0;
			if (parent::isNotFalse($this->strpos($code, ' '))) {$code = preg_replace('/\s+/', ' ', $code);}
			if (parent::isNotFalse($this->strpos($value, ' '))) {$value = preg_replace('/\s+/', ' ', $value);}
			if (time() < $expires) {
				$comparism = $caseInsensitive ? strcasecmp($value, $code) : strcmp($value, $code);
				$validated = $comparism == 0;
				if (parent::isTrue($validated) && parent::isFile($namespaceFilename)) {parent::deleteFile($namespaceFilename);}
				return parent::isTrue($validated);
			}
		}
		return false;
	}

	private function bboxDetails(float $size = 15, float $angle = 0, string $font = null, string $string = "") {
		$bbox = @imagettfbbox($size, $angle, $font, $string);
		$data = array();
		if (parent::isNotFalse($bbox)) {
			$xCorr = 0 - $bbox[6]; // northwest X
			$yCorr = 0 - $bbox[7]; // northwest Y
			$data['left'] = $bbox[6] + $xCorr;
			$data['top'] = $bbox[7] + $yCorr;
			$data['width'] = $bbox[2] + $xCorr;
			$data['height'] = $bbox[3] + $yCorr;
		}
		return $data;
	}

	private function hex2rgb(string $hex = ""): array {
		$r = $g = $b = 0;
		$hex = str_replace("#", "", $hex);
		if ($this->strlen($hex) == 3 || $this->strlen($hex) == 6) {list($r, $g, $b) = array_map(function ($c) {return hexdec(str_pad($c, 2, $c));}, str_split(ltrim($hex, '#'), $this->strlen($hex) > 4 ? 2 : 1));}
		return array("r" => $r, "g" => $g, "b" => $b);
	}

	private function strlen(string $string): int {
		$strlen = 'strlen';
		if (function_exists('mb_strlen')) {$strlen = 'mb_strlen';}
		return $strlen($string);
	}

	private function substr(string $string, int $start, ?int $length = null) {
		$substr = 'substr';
		if (function_exists('mb_substr')) {$substr = 'mb_substr';}
		if ($length === null) {return $substr($string, $start);}
		return $substr($string, $start, $length);
	}

	private function strpos(string $haystack, string $needle, int $offset = 0) {
		$strpos = 'strpos';
		if (function_exists('mb_strpos')) {$strpos = 'mb_strpos';}
		return $strpos($haystack, $needle, $offset);
	}

	private function characterDimensions($char, $size, $angle, $font) {
		$box = imagettfbbox($size, $angle, $font, $char);
		return parent::isArray($box) ? array($box[2] - $box[0], max($box[1] - $box[7], $box[5] - $box[3]), $box[1]) : false;
	}

	private function rand() {return (0.0001 * mt_rand(0, 9999));}	
}