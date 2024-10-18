<?php

namespace PHPWebfuse\Trait;

use \Spatie\Image\Enums\Orientation as SpatieOrientation;

/**
 * @author Senestro
 */
trait ImageOrientationTrait {
    public function value(): SpatieOrientation {
        switch ($this->value) {
            case 'rotate0':
                return SpatieOrientation::Rotate0;
                break;
            case 'rotate90':
                return SpatieOrientation::Rotate90;
                break;
            case 'rotate180':
                return SpatieOrientation::Rotate180;
                break;
            default:
                return SpatieOrientation::Rotate270;
                break;
        }
    }
}
