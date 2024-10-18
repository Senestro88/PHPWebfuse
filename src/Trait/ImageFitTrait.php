<?php

namespace PHPWebfuse\Trait;

use \Spatie\Image\Enums\Fit as SpatieFit;

/**
 * @author Senestro
 */
trait ImageFitTrait {
    public function value(): SpatieFit {
        switch ($this->value) {
            case 'contain':
                return SpatieFit::Contain;
                break;
            case 'crop':
                return SpatieFit::Crop;
                break;
            case 'fill':
                return SpatieFit::Fill;
                break;
            case 'fill-max':
                return SpatieFit::FillMax;
                break;
            case 'max':
                return SpatieFit::Max;
                break;
            default:
                return SpatieFit::Stretch;
                break;
        }
    }
}
