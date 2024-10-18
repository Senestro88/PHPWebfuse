<?php

namespace PHPWebfuse\Trait;

use \Spatie\Image\Enums\BorderType as SpatieBorderType;

/**
 * @author Senestro
 */
trait ImageBorderTypeTrait {
    public function value(): SpatieBorderType {
        switch ($this->value) {
            case 'expand':
                return SpatieBorderType::Expand;
                break;
            case 'overlay':
                return SpatieBorderType::Overlay;
                break;
            default:
                return SpatieBorderType::Shrink;
                break;
        }
    }
}
