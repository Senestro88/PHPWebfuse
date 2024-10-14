<?php

namespace PHPWebfuse\Trait;

use \Spatie\Image\Enums\AlignPosition as SpatieAlignPosition;

/**
 * @author Senestro
 */
trait ImageAlignPositionTrait {
    public function value(): SpatieAlignPosition {
        switch ($this->value) {
            case 'bottom':
                return SpatieAlignPosition::Bottom;
                break;
            case 'bottomCenter':
                return SpatieAlignPosition::BottomCenter;
                break;
            case 'bottomLeft':
                return SpatieAlignPosition::BottomLeft;
                break;
            case 'bottomMiddle':
                return SpatieAlignPosition::BottomMiddle;
                break;
            case 'bottomRight':
                return SpatieAlignPosition::BottomRight;
                break;
            case 'center':
                return SpatieAlignPosition::Center;
                break;
            case 'centerBottom':
                return SpatieAlignPosition::CenterBottom;
                break;
            case 'centerCenter':
                return SpatieAlignPosition::CenterCenter;
                break;
            case 'centerLeft':
                return SpatieAlignPosition::CenterLeft;
                break;
            case 'centerRight':
                return SpatieAlignPosition::CenterRight;
                break;
            case 'centerTop':
                return SpatieAlignPosition::CenterTop;
                break;
            case 'left':
                return SpatieAlignPosition::Left;
                break;
            case 'leftBottom':
                return SpatieAlignPosition::LeftBottom;
                break;
            case 'leftCenter':
                return SpatieAlignPosition::LeftCenter;
                break;
            case 'leftMiddle':
                return SpatieAlignPosition::LeftMiddle;
                break;
            case 'leftTop':
                return SpatieAlignPosition::LeftTop;
                break;
            case 'middle':
                return SpatieAlignPosition::Middle;
                break;
            case 'middleBottom':
                return SpatieAlignPosition::MiddleBottom;
                break;
            case 'middleLeft':
                return SpatieAlignPosition::MiddleLeft;
                break;
            case 'middleMiddle':
                return SpatieAlignPosition::MiddleMiddle;
                break;
            case 'middleRight':
                return SpatieAlignPosition::MiddleRight;
                break;
            case 'middleTop':
                return SpatieAlignPosition::MiddleTop;
                break;
            case 'right':
                return SpatieAlignPosition::Right;
                break;
            case 'rightBottom':
                return SpatieAlignPosition::RightBottom;
                break;
            case 'rightCenter':
                return SpatieAlignPosition::RightCenter;
                break;
            case 'rightMiddle':
                return SpatieAlignPosition::RightMiddle;
                break;
            case 'rightTop':
                return SpatieAlignPosition::RightTop;
                break;
            case 'top':
                return SpatieAlignPosition::Top;
                break;
            case 'topCenter':
                return SpatieAlignPosition::TopCenter;
                break;
            case 'topLeft':
                return SpatieAlignPosition::TopLeft;
                break;
            case 'topMiddle':
                return SpatieAlignPosition::TopMiddle;
                break;
            default:
                return SpatieAlignPosition::TopRight;
                break;
        }
    }
}
