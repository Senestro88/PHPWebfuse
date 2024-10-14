<?php

namespace PHPWebfuse\Enum;

use \PHPWebfuse\Trait\ImageOrientationTrait;

/**
 * @author Senestro
 */
enum ImageOrientation: string {
    use ImageOrientationTrait;
    case Rotate0 = "rotate0";
    case Rotate90 = "rotate90";
    case Rotate180 = "rotate180";
    case Rotate270 = "rotate270";
}
