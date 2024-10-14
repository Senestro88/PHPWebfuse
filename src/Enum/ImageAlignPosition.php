<?php

namespace PHPWebfuse\Enum;

use \PHPWebfuse\Trait\ImageAlignPositionTrait;

/**
 * @author Senestro
 */
enum ImageAlignPosition: string {
    use ImageAlignPositionTrait;
    case Bottom = "bottom";
    case BottomCenter = "bottomCenter";
    case BottomLeft = "bottomLeft";
    case BottomMiddle = "bottomMiddle";
    case BottomRight = "bottomRight";
    case Center = "center";
    case CenterBottom = "centerBottom";
    case CenterCenter = "centerCenter";
    case CenterLeft = "centerLeft";
    case CenterRight = "centerRight";
    case CenterTop = "centerTop";
    case Left = "left";
    case LeftBottom = "leftBottom";
    case LeftCenter = "leftCenter";
    case LeftMiddle = "leftMiddle";
    case LeftTop = "leftTop";
    case Middle = "middle";
    case MiddleBottom = "middleBottom";
    case MiddleLeft = "middleLeft";
    case MiddleMiddle = "middleMiddle";
    case MiddleRight = "middleRight";
    case MiddleTop = "middleTop";
    case Right = "right";
    case RightBottom = "rightBottom";
    case RightCenter = "rightCenter";
    case RightMiddle = "rightMiddle";
    case RightTop = "rightTop";
    case Top = "top";
    case TopCenter = "topCenter";
    case TopLeft = "topLeft";
    case TopMiddle = "topMiddle";
    case TopRight = "topRight";
}
