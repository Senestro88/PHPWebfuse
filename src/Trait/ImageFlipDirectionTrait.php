<?php

namespace PHPWebfuse\Trait;

use \Spatie\Image\Enums\FlipDirection as SpatieFlipDirection;

/**
 * @author Senestro
 */
trait ImageFlipDirectionTrait {
    public function value(): SpatieFlipDirection {
        switch ($this->value) {
            case 'vertical':
                return SpatieFlipDirection::Vertical;
                break;
            case 'horizontal':
                return SpatieFlipDirection::Horizontal;
                break;
            default:
                return SpatieFlipDirection::Both;
                break;
        }
    }
}
