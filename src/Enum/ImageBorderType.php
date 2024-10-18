<?php

namespace PHPWebfuse\Enum;

use \PHPWebfuse\Trait\ImageBorderTypeTrait;

/**
 * @author Senestro
 */
enum ImageBorderType: string {
    use ImageBorderTypeTrait;
    case Expand = "expand";
    case Overlay = "overlay";
    case Shrink = "shrink";
}
