<?php

namespace PHPWebfuse;

/**
 * @author Senestro
 */
class Color {
    // PRIVATE VARIABLE

    // PUBLIC VARIABLES

    // PUBLIC METHODS

    public function __construct() {
    }

    /**
     * Returns an array of predefined colors and their hex values.
     *
     * @return array An associative array of colors where the key is the color name and the value is an array containing hex values.
     */
    public static function colors(): array {
        $colors =
            array(
                'Gainsboro' => array(
                    'hex' => '#DCDCDC',
                    'rgb' => array(0 => 220, 1 => 220, 2 => 220),
                ),
                'LightGray' => array(
                    'hex' => '#D3D3D3',
                    'rgb' => array(0 => 211, 1 => 211, 2 => 211),
                ),
                'Silver' => array(
                    'hex' => '#C0C0C0',
                    'rgb' => array(0 => 192, 1 => 192, 2 => 192),
                ),
                'DarkGray' => array(
                    'hex' => '#A9A9A9',
                    'rgb' => array(0 => 169, 1 => 169, 2 => 169),
                ),
                'Gray' => array(
                    'hex' => '#808080',
                    'rgb' => array(0 => 128, 1 => 128, 2 => 128),
                ),
                'DimGray' => array(
                    'hex' => '#696969',
                    'rgb' => array(0 => 105, 1 => 105, 2 => 105),
                ),
                'LightSlateGray' => array(
                    'hex' => '#778899',
                    'rgb' => array(0 => 119, 1 => 136, 2 => 153),
                ),
                'SlateGray' => array(
                    'hex' => '#708090',
                    'rgb' => array(0 => 112, 1 => 128, 2 => 144),
                ),
                'DarkSlateGray' => array(
                    'hex' => '#2F4F4F',
                    'rgb' => array(0 => 47, 1 => 79, 2 => 79),
                ),
                'Black' => array(
                    'hex' => '#000000',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 0),
                ),
                'White' => array(
                    'hex' => '#FFFFFF',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 255),
                ),
                'Red' => array(
                    'hex' => '#FF0000',
                    'rgb' => array(0 => 255, 1 => 0, 2 => 0),
                ),
                'Green' => array(
                    'hex' => '#00FF00',
                    'rgb' => array(0 => 0, 1 => 255, 2 => 0),
                ),
                'Blue' => array(
                    'hex' => '#0000FF',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 255),
                ),
                'Yellow' => array(
                    'hex' => '#FFFF00',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 0),
                ),
                'Cyan' => array(
                    'hex' => '#00FFFF',
                    'rgb' => array(0 => 0, 1 => 255, 2 => 255),
                ),
                'Magenta' => array(
                    'hex' => '#FF00FF',
                    'rgb' => array(0 => 255, 1 => 0, 2 => 255),
                ),
                'Orange' => array(
                    'hex' => '#FFA500',
                    'rgb' => array(0 => 255, 1 => 165, 2 => 0),
                ),
                'Pink' => array(
                    'hex' => '#FFC0CB',
                    'rgb' => array(0 => 255, 1 => 192, 2 => 203),
                ),
                'Purple' => array(
                    'hex' => '#800080',
                    'rgb' => array(0 => 128, 1 => 0, 2 => 128),
                ),
                'Brown' => array(
                    'hex' => '#A52A2A',
                    'rgb' => array(0 => 165, 1 => 42, 2 => 42),
                ),
                'Lime' => array(
                    'hex' => '#00FF00',
                    'rgb' => array(0 => 0, 1 => 255, 2 => 0),
                ),
                'Navy' => array(
                    'hex' => '#000080',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 128),
                ),
                'Olive' => array(
                    'hex' => '#808000',
                    'rgb' => array(0 => 128, 1 => 128, 2 => 0),
                ),
                'Teal' => array(
                    'hex' => '#008080',
                    'rgb' => array(0 => 0, 1 => 128, 2 => 128),
                ),
                'Maroon' => array(
                    'hex' => '#800000',
                    'rgb' => array(0 => 128, 1 => 0, 2 => 0),
                ),
                'Aquamarine' => array(
                    'hex' => '#7FFFD4',
                    'rgb' => array(0 => 127, 1 => 255, 2 => 212),
                ),
                'Coral' => array(
                    'hex' => '#FF7F50',
                    'rgb' => array(0 => 255, 1 => 127, 2 => 80),
                ),
                'Salmon' => array(
                    'hex' => '#FA8072',
                    'rgb' => array(0 => 250, 1 => 128, 2 => 114),
                ),
                'AliceBlue' => array(
                    'hex' => '#F0F8FF',
                    'rgb' => array(0 => 240, 1 => 248, 2 => 255),
                ),
                'AntiqueWhite' => array(
                    'hex' => '#FAEBD7',
                    'rgb' => array(0 => 250, 1 => 235, 2 => 215),
                ),
                'Azure' => array(
                    'hex' => '#F0FFFF',
                    'rgb' => array(0 => 240, 1 => 255, 2 => 255),
                ),
                'Beige' => array(
                    'hex' => '#F5F5DC',
                    'rgb' => array(0 => 245, 1 => 245, 2 => 220),
                ),
                'Bisque' => array(
                    'hex' => '#FFE4C4',
                    'rgb' => array(0 => 255, 1 => 228, 2 => 196),
                ),
                'BlanchedAlmond' => array(
                    'hex' => '#FFEBCD',
                    'rgb' => array(0 => 255, 1 => 235, 2 => 205),
                ),
                'BlueViolet' => array(
                    'hex' => '#8A2BE2',
                    'rgb' => array(0 => 138, 1 => 43, 2 => 226),
                ),
                'BurlyWood' => array(
                    'hex' => '#DEB887',
                    'rgb' => array(0 => 222, 1 => 184, 2 => 135),
                ),
                'CadetBlue' => array(
                    'hex' => '#5F9EA0',
                    'rgb' => array(0 => 95, 1 => 158, 2 => 160),
                ),
                'Chartreuse' => array(
                    'hex' => '#7FFF00',
                    'rgb' => array(0 => 127, 1 => 255, 2 => 0),
                ),
                'Chocolate' => array(
                    'hex' => '#D2691E',
                    'rgb' => array(0 => 210, 1 => 105, 2 => 30),
                ),
                'CornflowerBlue' => array(
                    'hex' => '#6495ED',
                    'rgb' => array(0 => 100, 1 => 149, 2 => 237),
                ),
                'Cornsilk' => array(
                    'hex' => '#FFF8DC',
                    'rgb' => array(0 => 255, 1 => 248, 2 => 220),
                ),
                'Crimson' => array(
                    'hex' => '#DC143C',
                    'rgb' => array(0 => 220, 1 => 20, 2 => 60),
                ),
                'DarkBlue' => array(
                    'hex' => '#00008B',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 139),
                ),
                'DarkCyan' => array(
                    'hex' => '#008B8B',
                    'rgb' => array(0 => 0, 1 => 139, 2 => 139),
                ),
                'DarkGoldenRod' => array(
                    'hex' => '#B8860B',
                    'rgb' => array(0 => 184, 1 => 134, 2 => 11),
                ),
                'DarkGreen' => array(
                    'hex' => '#006400',
                    'rgb' => array(0 => 0, 1 => 100, 2 => 0),
                ),
                'DarkKhaki' => array(
                    'hex' => '#BDB76B',
                    'rgb' => array(0 => 189, 1 => 183, 2 => 107),
                ),
                'DarkOliveGreen' => array(
                    'hex' => '#556B2F',
                    'rgb' => array(0 => 85, 1 => 107, 2 => 47),
                ),
                'DarkOrange' => array(
                    'hex' => '#FF8C00',
                    'rgb' => array(0 => 255, 1 => 140, 2 => 0),
                ),
                'DarkOrchid' => array(
                    'hex' => '#9932CC',
                    'rgb' => array(0 => 153, 1 => 50, 2 => 204),
                ),
                'DarkRed' => array(
                    'hex' => '#8B0000',
                    'rgb' => array(0 => 139, 1 => 0, 2 => 0),
                ),
                'DarkSalmon' => array(
                    'hex' => '#E9967A',
                    'rgb' => array(0 => 233, 1 => 150, 2 => 122),
                ),
                'DarkSeaGreen' => array(
                    'hex' => '#8FBC8F',
                    'rgb' => array(0 => 143, 1 => 188, 2 => 143),
                ),
                'DarkSlateBlue' => array(
                    'hex' => '#483D8B',
                    'rgb' => array(0 => 72, 1 => 61, 2 => 139),
                ),
                'DarkSlateGray' => array(
                    'hex' => '#2F4F4F',
                    'rgb' => array(0 => 47, 1 => 79, 2 => 79),
                ),
                'DarkTurquoise' => array(
                    'hex' => '#00CED1',
                    'rgb' => array(0 => 0, 1 => 206, 2 => 209),
                ),
                'DarkViolet' => array(
                    'hex' => '#9400D3',
                    'rgb' => array(0 => 148, 1 => 0, 2 => 211),
                ),
                'DeepPink' => array(
                    'hex' => '#FF1493',
                    'rgb' => array(0 => 255, 1 => 20, 2 => 147),
                ),
                'DeepSkyBlue' => array(
                    'hex' => '#00BFFF',
                    'rgb' => array(0 => 0, 1 => 191, 2 => 255),
                ),
                'DimGray' => array(
                    'hex' => '#696969',
                    'rgb' => array(0 => 105, 1 => 105, 2 => 105),
                ),
                'DodgerBlue' => array(
                    'hex' => '#1E90FF',
                    'rgb' => array(0 => 30, 1 => 144, 2 => 255),
                ),
                'FireBrick' => array(
                    'hex' => '#B22222',
                    'rgb' => array(0 => 178, 1 => 34, 2 => 34),
                ),
                'FloralWhite' => array(
                    'hex' => '#FFFAF0',
                    'rgb' => array(0 => 255, 1 => 250, 2 => 240),
                ),
                'ForestGreen' => array(
                    'hex' => '#228B22',
                    'rgb' => array(0 => 34, 1 => 139, 2 => 34),
                ),
                'Fuchsia' => array(
                    'hex' => '#FF00FF',
                    'rgb' => array(0 => 255, 1 => 0, 2 => 255),
                ),
                'Gainsboro' => array(
                    'hex' => '#DCDCDC',
                    'rgb' => array(0 => 220, 1 => 220, 2 => 220),
                ),
                'GhostWhite' => array(
                    'hex' => '#F8F8FF',
                    'rgb' => array(0 => 248, 1 => 248, 2 => 255),
                ),
                'Gold' => array(
                    'hex' => '#FFD700',
                    'rgb' => array(0 => 255, 1 => 215, 2 => 0),
                ),
                'GoldenRod' => array(
                    'hex' => '#DAA520',
                    'rgb' => array(0 => 218, 1 => 165, 2 => 32),
                ),
                'GreenYellow' => array(
                    'hex' => '#ADFF2F',
                    'rgb' => array(0 => 173, 1 => 255, 2 => 47),
                ),
                'HoneyDew' => array(
                    'hex' => '#F0FFF0',
                    'rgb' => array(0 => 240, 1 => 255, 2 => 240),
                ),
                'HotPink' => array(
                    'hex' => '#FF69B4',
                    'rgb' => array(0 => 255, 1 => 105, 2 => 180),
                ),
                'IndianRed' => array(
                    'hex' => '#CD5C5C',
                    'rgb' => array(0 => 205, 1 => 92, 2 => 92),
                ),
                'Indigo' => array(
                    'hex' => '#4B0082',
                    'rgb' => array(0 => 75, 1 => 0, 2 => 130),
                ),
                'Ivory' => array(
                    'hex' => '#FFFFF0',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 240),
                ),
                'Khaki' => array(
                    'hex' => '#F0E68C',
                    'rgb' => array(0 => 240, 1 => 230, 2 => 140),
                ),
                'Lavender' => array(
                    'hex' => '#E6E6FA',
                    'rgb' => array(0 => 230, 1 => 230, 2 => 250),
                ),
                'LavenderBlush' => array(
                    'hex' => '#FFF0F5',
                    'rgb' => array(0 => 255, 1 => 240, 2 => 245),
                ),
                'LawnGreen' => array(
                    'hex' => '#7CFC00',
                    'rgb' => array(0 => 124, 1 => 252, 2 => 0),
                ),
                'LemonChiffon' => array(
                    'hex' => '#FFFACD',
                    'rgb' => array(0 => 255, 1 => 250, 2 => 205),
                ),
                'LightBlue' => array(
                    'hex' => '#ADD8E6',
                    'rgb' => array(0 => 173, 1 => 216, 2 => 230),
                ),
                'LightCoral' => array(
                    'hex' => '#F08080',
                    'rgb' => array(0 => 240, 1 => 128, 2 => 128),
                ),
                'LightCyan' => array(
                    'hex' => '#E0FFFF',
                    'rgb' => array(0 => 224, 1 => 255, 2 => 255),
                ),
                'LightGoldenRodYellow' => array(
                    'hex' => '#FAFAD2',
                    'rgb' => array(0 => 250, 1 => 250, 2 => 210),
                ),
                'LightGreen' => array(
                    'hex' => '#90EE90',
                    'rgb' => array(0 => 144, 1 => 238, 2 => 144),
                ),
                'LightGray' => array(
                    'hex' => '#D3D3D3',
                    'rgb' => array(0 => 211, 1 => 211, 2 => 211),
                ),
                'LightPink' => array(
                    'hex' => '#FFB6C1',
                    'rgb' => array(0 => 255, 1 => 182, 2 => 193),
                ),
                'LightSalmon' => array(
                    'hex' => '#FFA07A',
                    'rgb' => array(0 => 255, 1 => 160, 2 => 122),
                ),
                'LightSeaGreen' => array(
                    'hex' => '#20B2AA',
                    'rgb' => array(0 => 32, 1 => 178, 2 => 170),
                ),
                'LightSkyBlue' => array(
                    'hex' => '#87CEFA',
                    'rgb' => array(0 => 135, 1 => 206, 2 => 250),
                ),
                'LightSlateGray' => array(
                    'hex' => '#778899',
                    'rgb' => array(0 => 119, 1 => 136, 2 => 153),
                ),
                'LightSteelBlue' => array(
                    'hex' => '#B0C4DE',
                    'rgb' => array(0 => 176, 1 => 196, 2 => 222),
                ),
                'LightYellow' => array(
                    'hex' => '#FFFFE0',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 224),
                ),
                'Lime' => array(
                    'hex' => '#00FF00',
                    'rgb' => array(0 => 0, 1 => 255, 2 => 0),
                ),
                'LimeGreen' => array(
                    'hex' => '#32CD32',
                    'rgb' => array(0 => 50, 1 => 205, 2 => 50),
                ),
                'Linen' => array(
                    'hex' => '#FAF0E6',
                    'rgb' => array(0 => 250, 1 => 240, 2 => 230),
                ),
                'Magenta' => array(
                    'hex' => '#FF00FF',
                    'rgb' => array(0 => 255, 1 => 0, 2 => 255),
                ),
                'Maroon' => array(
                    'hex' => '#800000',
                    'rgb' => array(0 => 128, 1 => 0, 2 => 0),
                ),
                'MediumAquaMarine' => array(
                    'hex' => '#66CDAA',
                    'rgb' => array(0 => 102, 1 => 205, 2 => 170),
                ),
                'MediumBlue' => array(
                    'hex' => '#0000CD',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 205),
                ),
                'MediumOrchid' => array(
                    'hex' => '#BA55D3',
                    'rgb' => array(0 => 186, 1 => 85, 2 => 211),
                ),
                'MediumPurple' => array(
                    'hex' => '#9370DB',
                    'rgb' => array(0 => 147, 1 => 112, 2 => 219),
                ),
                'MediumSeaGreen' => array(
                    'hex' => '#3CB371',
                    'rgb' => array(0 => 60, 1 => 179, 2 => 113),
                ),
                'MediumSlateBlue' => array(
                    'hex' => '#7B68EE',
                    'rgb' => array(0 => 123, 1 => 104, 2 => 238),
                ),
                'MediumSpringGreen' => array(
                    'hex' => '#00FA9A',
                    'rgb' => array(0 => 0, 1 => 250, 2 => 154),
                ),
                'MediumTurquoise' => array(
                    'hex' => '#48D1CC',
                    'rgb' => array(0 => 72, 1 => 209, 2 => 204),
                ),
                'MediumVioletRed' => array(
                    'hex' => '#C71585',
                    'rgb' => array(0 => 199, 1 => 21, 2 => 133),
                ),
                'MidnightBlue' => array(
                    'hex' => '#191970',
                    'rgb' => array(0 => 25, 1 => 25, 2 => 112),
                ),
                'MintCream' => array(
                    'hex' => '#F5FFFA',
                    'rgb' => array(0 => 245, 1 => 255, 2 => 250),
                ),
                'MistyRose' => array(
                    'hex' => '#FFE4E1',
                    'rgb' => array(0 => 255, 1 => 228, 2 => 225),
                ),
                'Moccasin' => array(
                    'hex' => '#FFE4B5',
                    'rgb' => array(0 => 255, 1 => 228, 2 => 181),
                ),
                'NavajoWhite' => array(
                    'hex' => '#FFDEAD',
                    'rgb' => array(0 => 255, 1 => 222, 2 => 173),
                ),
                'Navy' => array(
                    'hex' => '#000080',
                    'rgb' => array(0 => 0, 1 => 0, 2 => 128),
                ),
                'OldLace' => array(
                    'hex' => '#FDF5E6',
                    'rgb' => array(0 => 253, 1 => 245, 2 => 230),
                ),
                'Olive' => array(
                    'hex' => '#808000',
                    'rgb' => array(0 => 128, 1 => 128, 2 => 0),
                ),
                'OliveDrab' => array(
                    'hex' => '#6B8E23',
                    'rgb' => array(0 => 107, 1 => 142, 2 => 35),
                ),
                'Orange' => array(
                    'hex' => '#FFA500',
                    'rgb' => array(0 => 255, 1 => 165, 2 => 0),
                ),
                'OrangeRed' => array(
                    'hex' => '#FF4500',
                    'rgb' => array(0 => 255, 1 => 69, 2 => 0),
                ),
                'Orchid' => array(
                    'hex' => '#DA70D6',
                    'rgb' => array(0 => 218, 1 => 112, 2 => 214),
                ),
                'PaleGoldenRod' => array(
                    'hex' => '#EEE8AA',
                    'rgb' => array(0 => 238, 1 => 232, 2 => 170),
                ),
                'PaleGreen' => array(
                    'hex' => '#98FB98',
                    'rgb' => array(0 => 152, 1 => 251, 2 => 152),
                ),
                'PaleTurquoise' => array(
                    'hex' => '#AFEEEE',
                    'rgb' => array(0 => 175, 1 => 238, 2 => 238),
                ),
                'PaleVioletRed' => array(
                    'hex' => '#DB7093',
                    'rgb' => array(0 => 219, 1 => 112, 2 => 147),
                ),
                'PapayaWhip' => array(
                    'hex' => '#FFEFD5',
                    'rgb' => array(0 => 255, 1 => 239, 2 => 213),
                ),
                'PeachPuff' => array(
                    'hex' => '#FFDAB9',
                    'rgb' => array(0 => 255, 1 => 218, 2 => 185),
                ),
                'Peru' => array(
                    'hex' => '#CD853F',
                    'rgb' => array(0 => 205, 1 => 133, 2 => 63),
                ),
                'Pink' => array(
                    'hex' => '#FFC0CB',
                    'rgb' => array(0 => 255, 1 => 192, 2 => 203),
                ),
                'Plum' => array(
                    'hex' => '#DDA0DD',
                    'rgb' => array(0 => 221, 1 => 160, 2 => 221),
                ),
                'PowderBlue' => array(
                    'hex' => '#B0E0E6',
                    'rgb' => array(0 => 176, 1 => 224, 2 => 230),
                ),
                'Purple' => array(
                    'hex' => '#800080',
                    'rgb' => array(0 => 128, 1 => 0, 2 => 128),
                ),
                'Red' => array(
                    'hex' => '#FF0000',
                    'rgb' => array(0 => 255, 1 => 0, 2 => 0),
                ),
                'RosyBrown' => array(
                    'hex' => '#BC8F8F',
                    'rgb' => array(0 => 188, 1 => 143, 2 => 143),
                ),
                'RoyalBlue' => array(
                    'hex' => '#4169E1',
                    'rgb' => array(0 => 65, 1 => 105, 2 => 225),
                ),
                'SaddleBrown' => array(
                    'hex' => '#8B4513',
                    'rgb' => array(0 => 139, 1 => 69, 2 => 19),
                ),
                'Salmon' => array(
                    'hex' => '#FA8072',
                    'rgb' => array(0 => 250, 1 => 128, 2 => 114),
                ),
                'SandyBrown' => array(
                    'hex' => '#F4A460',
                    'rgb' => array(0 => 244, 1 => 164, 2 => 96),
                ),
                'SeaGreen' => array(
                    'hex' => '#2E8B57',
                    'rgb' => array(0 => 46, 1 => 139, 2 => 87),
                ),
                'SeaShell' => array(
                    'hex' => '#FFF5EE',
                    'rgb' => array(0 => 255, 1 => 245, 2 => 238),
                ),
                'Sienna' => array(
                    'hex' => '#A0522D',
                    'rgb' => array(0 => 160, 1 => 82, 2 => 45),
                ),
                'Silver' => array(
                    'hex' => '#C0C0C0',
                    'rgb' => array(0 => 192, 1 => 192, 2 => 192),
                ),
                'SkyBlue' => array(
                    'hex' => '#87CEEB',
                    'rgb' => array(0 => 135, 1 => 206, 2 => 235),
                ),
                'SlateBlue' => array(
                    'hex' => '#6A5ACD',
                    'rgb' => array(0 => 106, 1 => 90, 2 => 205),
                ),
                'SlateGray' => array(
                    'hex' => '#708090',
                    'rgb' => array(0 => 112, 1 => 128, 2 => 144),
                ),
                'Snow' => array(
                    'hex' => '#FFFAFA',
                    'rgb' => array(0 => 255, 1 => 250, 2 => 250),
                ),
                'SpringGreen' => array(
                    'hex' => '#00FF7F',
                    'rgb' => array(0 => 0, 1 => 255, 2 => 127),
                ),
                'SteelBlue' => array(
                    'hex' => '#4682B4',
                    'rgb' => array(0 => 70, 1 => 130, 2 => 180),
                ),
                'Tan' => array(
                    'hex' => '#D2B48C',
                    'rgb' => array(0 => 210, 1 => 180, 2 => 140),
                ),
                'Teal' => array(
                    'hex' => '#008080',
                    'rgb' => array(0 => 0, 1 => 128, 2 => 128),
                ),
                'Thistle' => array(
                    'hex' => '#D8BFD8',
                    'rgb' => array(0 => 216, 1 => 191, 2 => 216),
                ),
                'Tomato' => array(
                    'hex' => '#FF6347',
                    'rgb' => array(0 => 255, 1 => 99, 2 => 71),
                ),
                'Turquoise' => array(
                    'hex' => '#40E0D0',
                    'rgb' => array(0 => 64, 1 => 224, 2 => 208),
                ),
                'Violet' => array(
                    'hex' => '#EE82EE',
                    'rgb' => array(0 => 238, 1 => 130, 2 => 238),
                ),
                'Wheat' => array(
                    'hex' => '#F5DEB3',
                    'rgb' => array(0 => 245, 1 => 222, 2 => 179),
                ),
                'White' => array(
                    'hex' => '#FFFFFF',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 255),
                ),
                'WhiteSmoke' => array(
                    'hex' => '#F5F5F5',
                    'rgb' => array(0 => 245, 1 => 245, 2 => 245),
                ),
                'Yellow' => array(
                    'hex' => '#FFFF00',
                    'rgb' => array(0 => 255, 1 => 255, 2 => 0),
                ),
                'YellowGreen' => array(
                    'hex' => '#9ACD32',
                    'rgb' => array(0 => 154, 1 => 205, 2 => 50),
                ),
                'ColumbiaBlue' => array(
                    'hex' => '#D0DEEE',
                    'rgb' => array(0 => 208, 1 => 222, 2 => 238),
                )
            );
        return $colors;
    }
    /**
     * Retrieve the hex value of a color by its name.
     *
     * @param string $colorName The name of the color.
     * @return string The hex value of the color if found; otherwise, an empty string.
     */
    public static function getColorHexByName(string $colorName): string {
        // Fetch color data using the color name
        $colorData = self::getColor($colorName);

        // Return the hex code if the color data is found, otherwise return an empty string
        return !empty($colorData) ? $colorData['hex'] : "";
    }

    /**
     * Retrieve the name of a color by its hex value.
     *
     * @param string $colorHex The hex value of the color (e.g., #FFFFFF).
     * @return string The name of the color if found; otherwise, an empty string.
     */
    public static function getColorNameByHex(string $colorHex): string {
        // Fetch color data using the hex code
        $colorData = self::getColor($colorHex);

        // Return the color name if the data is found, otherwise return an empty string
        return !empty($colorData) ? $colorData['name'] : "";
    }

    /**
     * Check if a color is present in the predefined list of colors.
     * Handles both color hex codes (starting with '#') and color names.
     *
     * @param string $color The name of the color or color hex to check.
     * @return bool True if the color is present; otherwise, false.
     */
    public static function isColorPresent(string $color): bool {
        // Return true if the color exists, false otherwise
        return !empty(self::getColor($color));
    }

    /**
     * Retrieve the color details (name, hex code, and RGB values) if the provided color exists.
     * Handles both color hex codes (starting with '#') and color names.
     *
     * @param string $color The name of the color or color hex to retrieve.
     * @return array The color name and details (hex and rgb) if found; otherwise, an empty array.
     */
    public static function getColor(string $color): array {
        // Normalize the colors list for case-insensitive comparison
        $colors = array_change_key_case(self::colors(), CASE_LOWER);
        // Normalize the input color (trim whitespace and convert to lowercase)
        $normalizedColor = trim(strtolower($color));

        // Iterate over the list of predefined colors
        foreach ($colors as $colorName => $colorData) {
            // If input is a hex code (starts with '#'), compare it with the predefined hex codes
            if (self::startsWithHash($normalizedColor)) {
                if ($normalizedColor === strtolower(trim($colorData['hex']))) {
                    // Return matching color details if hex codes match
                    return ["name" => $colorName, "hex" => $colorData['hex'], "rgb" => $colorData['rgb']];
                }
            } else {
                // If input is a color name, compare it with predefined names
                if ($normalizedColor === strtolower(trim($colorName))) {
                    // Return matching color details if names match
                    return ["name" => $colorName, "hex" => $colorData['hex'], "rgb" => $colorData['rgb']];
                }
            }
        }
        // Return an empty array if no match is found
        return [];
    }

    // PRIVATE METHODS

    /**
     * Check if the given string starts with the character '#', indicating it's a hex color code.
     *
     * @param string $color The string to check.
     * @return bool True if the string starts with '#'; otherwise, false.
     */
    private static function startsWithHash(string $color): bool {
        // Check if the first character of the color string is '#'
        return substr($color, 0, 1) === '#';
    }
}
