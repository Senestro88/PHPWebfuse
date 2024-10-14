<?php

namespace PHPWebfuse\Enum;

use \PHPWebfuse\Trait\ImageFlipDirectionTrait;

/**
 * @author Senestro
 */
enum ImageFlipDirection: string {
    use ImageFlipDirectionTrait;
    case Vertical = "vertical";
    case Horizontal = "horizontal";
    case Both = "both";
}
